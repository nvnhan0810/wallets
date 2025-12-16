<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Payment;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LoanController extends Controller
{
    public function index()
    {
        $loans = Loan::with('payments')->where('is_settled', false)->get();

        $loans->transform(function ($loan) {
            $totalPaid = $loan->payments->sum('amount');

            if ($loan->type === 'bank') {
                // Calculate amortization status
                $schedule = $this->calculateAmortization(
                    $loan->principal_amount,
                    $loan->interest_rate,
                    $loan->term_months,
                    $loan->started_at,
                    $loan->monthly_payment, // Pass the fixed monthly payment
                    $loan->interest_calculation_method ?? 'monthly'
                );

                // Find current state based on months passed
                // We use integer casting to ensure no float issues.
                // diffInMonths returns the number of full months between start date and now.
                // Example: Start 05/07/2023 -> 05/08/2023 is 1 month.
                $monthsPassed = $loan->months_paid > 0 ? $loan->months_paid : (int) $loan->started_at->diffInMonths(Carbon::now());

                $currentStatus = $schedule->first(function($item) use ($monthsPassed) {
                    return $item['month_index'] > $monthsPassed;
                }) ?? $schedule->last();

                $loan->remaining_months = max(0, $loan->term_months - $monthsPassed);
                // If we are past the term, rely on what's left in the schedule or 0
                $loan->remaining_principal = $currentStatus['remaining_principal'] ?? 0;

                // For "Remaining Interest", it's sum of future interest in schedule
                $loan->remaining_interest = $schedule->where('month_index', '>', $monthsPassed)->sum('interest');

            } else {
                // Simple debt/lend
                $loan->remaining_amount = $loan->principal_amount - $totalPaid;
            }

            return $loan;
        });

        return view('loans.index', compact('loans'));
    }

    public function show(Loan $loan)
    {
        $schedule = collect([]);
        $monthsPassed = 0;

        if ($loan->type === 'bank') {
            $schedule = $this->calculateAmortization(
                $loan->principal_amount,
                $loan->interest_rate,
                $loan->term_months,
                $loan->started_at,
                $loan->monthly_payment, // Pass the fixed monthly payment
                $loan->interest_calculation_method ?? 'monthly'
            );

            // Calculate months passed for highlighting in view
            $monthsPassed = $loan->months_paid > 0 ? $loan->months_paid : (int) $loan->started_at->diffInMonths(Carbon::now());
        }

        return view('loans.show', compact('loan', 'schedule', 'monthsPassed'));
    }

    public function create()
    {
        return view('loans.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:bank,borrow,lend',
            'name' => 'required|string',
            'principal_amount' => 'required|numeric',
            'started_at' => 'required|string',
            // Optional fields depending on type
            'interest_rate' => 'nullable|numeric',
            'interest_calculation_method' => 'nullable|in:monthly,daily',
            'term_months' => 'nullable|integer',
            'months_paid' => 'nullable|integer',
            'monthly_payment' => 'nullable|numeric',
        ]);

        // Convert date format from dd/mm/yyyy to yyyy-mm-dd
        $validated['started_at'] = $this->convertDateFormat($validated['started_at']);

        $validated['months_paid'] = $validated['months_paid'] ?? 0;
        $validated['interest_calculation_method'] = $validated['interest_calculation_method'] ?? 'monthly';

        Loan::create($validated);

        return redirect()->route('dashboard');
    }

    public function storePayment(Request $request)
    {
        $validated = $request->validate([
            'loan_id' => 'required|exists:loans,id',
            'amount' => 'required|numeric',
            'paid_at' => 'required|string',
        ]);

        // Convert date format from dd/mm/yyyy to yyyy-mm-dd
        $validated['paid_at'] = $this->convertDateFormat($validated['paid_at']);

        Payment::create($validated);

        return redirect()->route('dashboard');
    }

    public function settle(Loan $loan)
    {
        $loan->update(['is_settled' => true]);
        return redirect()->route('dashboard');
    }

    /**
     * Helper to calculate amortization schedule
     */
    private function calculateAmortization($principal, $annualRate, $months, $startDate, $fixedMonthlyPayment = null, $method = 'monthly')
    {
        // $annualRate is in percent (e.g. 15.8)
        $balance = $principal;

        $schedule = collect([]);
        $prevDate = Carbon::parse($startDate);

        // If fixed monthly payment is not provided, calculate standard annuity
        if (!$fixedMonthlyPayment) {
            $monthlyRate = ($annualRate / 100) / 12;
            $fixedMonthlyPayment = ($principal * $monthlyRate) / (1 - pow(1 + $monthlyRate, -$months));
        }

        for ($i = 1; $i <= $months; $i++) {
            // Calculate theoretical payment date (same day each month)
            $theoreticalDate = Carbon::parse($startDate)->addMonths($i);

            // For daily method, adjust if it falls on weekend or holiday
            if ($method === 'daily') {
                $actualDate = $this->adjustForNonWorkingDays($theoreticalDate);
            } else {
                $actualDate = $theoreticalDate->copy();
            }

            // Calculate interest based on method
            if ($method === 'daily') {
                // Actual/365: Calculate based on actual days between payments
                $days = $prevDate->diffInDays($actualDate);
                $interest = $balance * ($annualRate / 100) * $days / 365;
            } else {
                // Monthly: Fixed monthly rate
                $monthlyRate = ($annualRate / 100) / 12;
                $interest = $balance * $monthlyRate;
                $days = $prevDate->diffInDays($actualDate); // Just for display
            }

            $payment = $fixedMonthlyPayment;

            // Handle last month - pay off remaining balance
            if ($i == $months) {
                if ($balance + $interest < $payment + 100000) { // Tolerance
                     $payment = $balance + $interest;
                }
            }

            $principalPayment = $payment - $interest;

            // Prevent negative balance
            if ($principalPayment > $balance) {
                $principalPayment = $balance;
                $payment = $interest + $principalPayment;
            }

            $balance -= $principalPayment;
            if ($balance < 0) $balance = 0;

            $schedule->push([
                'month_index' => $i,
                'date' => $actualDate->copy(),
                'theoretical_date' => $theoreticalDate->copy(),
                'is_adjusted' => !$theoreticalDate->isSameDay($actualDate),
                'days' => $days,
                'payment' => $payment,
                'interest' => $interest,
                'principal' => $principalPayment,
                'remaining_principal' => $balance
            ]);

            $prevDate = $actualDate;
        }

        return $schedule;
    }

    /**
     * Adjust date if it falls on weekend or holiday - move to next working day
     */
    private function adjustForNonWorkingDays($date)
    {
        // Cache holidays for better performance
        static $holidays = null;
        if ($holidays === null) {
            $holidays = Holiday::pluck('date')->map(function($d) {
                return $d->format('Y-m-d');
            })->toArray();
        }

        $adjustedDate = $date->copy();
        $maxIterations = 10; // Prevent infinite loop
        $iterations = 0;

        while ($iterations < $maxIterations) {
            $dayOfWeek = $adjustedDate->dayOfWeek;
            $dateString = $adjustedDate->format('Y-m-d');

            // Check if weekend
            if ($dayOfWeek == Carbon::SATURDAY || $dayOfWeek == Carbon::SUNDAY) {
                $adjustedDate->addDay();
                $iterations++;
                continue;
            }

            // Check if holiday
            if (in_array($dateString, $holidays)) {
                $adjustedDate->addDay();
                $iterations++;
                continue;
            }

            // It's a working day
            break;
        }

        return $adjustedDate;
    }

    /**
     * Convert date format from dd/mm/yyyy to yyyy-mm-dd
     */
    private function convertDateFormat($date)
    {
        // If already in yyyy-mm-dd format, return as is
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        // Convert from dd/mm/yyyy to yyyy-mm-dd
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $date, $matches)) {
            return $matches[3] . '-' . $matches[2] . '-' . $matches[1];
        }

        // If format is unrecognized, try to parse with Carbon
        try {
            return Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d');
        } catch (\Exception $e) {
            return $date;
        }
    }
}

