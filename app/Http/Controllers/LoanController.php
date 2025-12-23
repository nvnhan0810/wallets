<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Payment;
use App\Models\Holiday;
use App\Models\LoanCustomSchedule;
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
                    $loan->id,
                    $loan->principal_amount,
                    $loan->interest_rate,
                    $loan->term_months,
                    $loan->started_at,
                    $loan->monthly_payment, // Pass the fixed monthly payment
                    $loan->interest_calculation_method ?? 'monthly'
                );

                if ($schedule->isEmpty()) {
                    $loan->remaining_months = $loan->term_months;
                    $loan->remaining_principal = $loan->principal_amount;
                    $loan->remaining_interest = 0;
                } else {
                    // Find current state based on months passed
                    $monthsPassed = $loan->months_paid > 0 ? $loan->months_paid : (int) $loan->started_at->diffInMonths(Carbon::now());
                    $maxIndex = $schedule->max('month_index');
                    $monthsPassed = min($monthsPassed, $maxIndex - 1);

                    $currentStatus = $schedule->first(function($item) use ($monthsPassed) {
                        return $item['month_index'] > $monthsPassed;
                    }) ?? $schedule->last();

                    $loan->remaining_months = max(0, ($loan->term_months ?? $maxIndex) - $monthsPassed);
                    $loan->remaining_principal = $currentStatus['remaining_principal'] ?? 0;
                    $loan->remaining_interest = $schedule->where('month_index', '>', $monthsPassed)->sum('interest');
                }

            } else {
                // Simple debt/lend
                $loan->remaining_amount = $loan->principal_amount - $totalPaid;
            }

            return $loan;
        });

        // Tổng gốc còn lại cho các khoản vay/nợ (bank + borrow). Cho mượn (lend) bỏ qua.
        $totalRemaining = $loans->reduce(function ($carry, $loan) {
            if ($loan->type === 'bank') {
                return $carry + ($loan->remaining_principal ?? 0);
            }
            if ($loan->type === 'borrow') {
                return $carry + ($loan->remaining_amount ?? 0);
            }
            return $carry;
        }, 0);

        // Tổng đang cho mượn (lend) sau khi trừ phần đã nhận
        $totalLendRemaining = $loans->reduce(function ($carry, $loan) {
            if ($loan->type === 'lend') {
                $paid = $loan->payments->sum('amount');
                return $carry + max(($loan->principal_amount - $paid), 0);
            }
            return $carry;
        }, 0);

        return view('loans.index', [
            'loans' => $loans,
            'totalRemaining' => $totalRemaining,
            'totalLendRemaining' => $totalLendRemaining,
        ]);
    }

    public function show(Loan $loan)
    {
        $schedule = collect([]);
        $monthsPassed = 0;

        if ($loan->type === 'bank') {
            $schedule = $this->calculateAmortization(
                $loan->id,
                $loan->principal_amount,
                $loan->interest_rate,
                $loan->term_months,
                $loan->started_at,
                $loan->monthly_payment, // Pass the fixed monthly payment
                $loan->interest_calculation_method ?? 'monthly'
            );

            if ($schedule->isNotEmpty()) {
                $monthsPassed = $loan->months_paid > 0 ? $loan->months_paid : (int) $loan->started_at->diffInMonths(Carbon::now());
                $maxIndex = $schedule->max('month_index');
                $monthsPassed = min($monthsPassed, $maxIndex - 1);

                $currentStatus = $schedule->first(function($item) use ($monthsPassed) {
                    return $item['month_index'] > $monthsPassed;
                }) ?? $schedule->last();

                $loan->remaining_months = max(0, ($loan->term_months ?? $maxIndex) - $monthsPassed);
                $loan->remaining_principal = $currentStatus['remaining_principal'] ?? 0;
                $loan->remaining_interest = $schedule->where('month_index', '>', $monthsPassed)->sum('interest');
            } else {
                $monthsPassed = 0;
                $loan->remaining_months = $loan->term_months;
                $loan->remaining_principal = $loan->principal_amount;
                $loan->remaining_interest = 0;
            }
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
            'interest_calculation_method' => 'nullable|in:monthly,daily,custom',
            'term_months' => 'nullable|integer',
            'months_paid' => 'nullable|integer',
            'monthly_payment' => 'nullable|numeric',
            'custom_schedule' => 'sometimes|array',
        ]);

        // Convert date format from dd/mm/yyyy to yyyy-mm-dd
        $validated['started_at'] = $this->convertDateFormat($validated['started_at']);

        $validated['months_paid'] = $validated['months_paid'] ?? 0;
        $validated['interest_calculation_method'] = $validated['interest_calculation_method'] ?? 'monthly';

        if (($validated['interest_calculation_method'] ?? 'monthly') === 'custom') {
            // monthly_payment required for custom
            $request->validate([
                'monthly_payment' => 'required|numeric|min:1',
                'term_months' => 'required|integer|min:1',
                'custom_schedule' => 'required|array|min:1',
            ]);
        }

        $loan = Loan::create($validated);

        // Save custom schedules if provided
        if (($validated['interest_calculation_method'] ?? 'monthly') === 'custom') {
            $customRows = $request->input('custom_schedule', []);
            $monthlyPayment = (float) $validated['monthly_payment'];
            $errors = [];
            foreach ($customRows as $idx => $row) {
                if (!isset($row['payment'], $row['principal'], $row['interest'])) {
                    $errors[] = "Dòng " . ($idx + 1) . " thiếu dữ liệu.";
                    continue;
                }
                $payment = (float) $row['payment'];
                $principal = (float) $row['principal'];
                $interest = (float) $row['interest'];
                $fee = (float) ($row['fee'] ?? 0);

                if (round($principal + $interest + $fee, 0) !== round($payment, 0)) {
                    $errors[] = "Dòng " . ($idx + 1) . ": Gốc + Lãi + Phí phải bằng Tổng trả.";
                    continue;
                }
                if (round($payment, 0) > round($monthlyPayment, 0)) {
                    $errors[] = "Dòng " . ($idx + 1) . ": Tổng trả vượt số tiền hàng tháng.";
                    continue;
                }

                LoanCustomSchedule::create([
                    'loan_id' => $loan->id,
                    'month_index' => $row['month_index'] ?? ($idx + 1),
                    'payment' => $payment,
                    'principal' => $principal,
                    'interest' => $interest,
                    'fee' => $fee,
                    'remaining_principal' => $row['remaining_principal'] ?? 0,
                    'paid_at' => isset($row['paid_at']) ? $this->convertDateFormat($row['paid_at']) : null,
                    'note' => $row['note'] ?? null,
                ]);
            }
            if (!empty($errors)) {
                return back()->withErrors($errors)->withInput();
            }
        }

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
    private function calculateAmortization($loanId, $principal, $annualRate, $months, $startDate, $fixedMonthlyPayment = null, $method = 'monthly')
    {
        // $annualRate is in percent (e.g. 15.8)
        $balance = $principal;

        $schedule = collect([]);
        $prevDate = Carbon::parse($startDate);

        // Custom method: return schedules from stored custom rows
        if ($method === 'custom') {
            $rows = LoanCustomSchedule::where('loan_id', $loanId)
                ->orderBy('month_index')
                ->get();
            if ($rows->isEmpty()) {
                return $schedule;
            }

            $balance = $principal;
            $result = [];
            foreach ($rows as $row) {
                $date = $row->paid_at ? Carbon::parse($row->paid_at) : Carbon::now();
                $principalPayment = (float) $row->principal;
                $interest = (float) $row->interest;
                $fee = (float) ($row->fee ?? 0);
                $payment = $row->payment ?? ($principalPayment + $interest + $fee);

                $balance = max($balance - $principalPayment, 0);

                $result[] = [
                    'month_index' => $row->month_index,
                    'date' => $date,
                    'theoretical_date' => $date,
                    'is_adjusted' => false,
                    'days' => null,
                    'payment' => $payment,
                    'interest' => $interest,
                    'principal' => $principalPayment,
                    'fee' => $fee,
                    'remaining_principal' => $balance,
                ];
            }

            return collect($result);
        }

        // Custom method: return schedules from stored custom rows
        if ($method === 'custom') {
            $rows = LoanCustomSchedule::where('loan_id', $loanId)
                ->orderBy('month_index')
                ->get();
            if ($rows->isEmpty()) {
                return $schedule;
            }
            return $rows->map(function ($row) {
                return [
                    'month_index' => $row->month_index,
                    'date' => $row->paid_at ?? Carbon::now(),
                    'theoretical_date' => $row->paid_at ?? Carbon::now(),
                    'is_adjusted' => false,
                    'days' => null,
                    'payment' => $row->payment,
                    'interest' => $row->interest,
                    'principal' => $row->principal,
                    'remaining_principal' => $row->remaining_principal,
                ];
            });
        }

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
                $interest = round($balance * ($annualRate / 100) * $days / 365, 0);
            } else {
                // Monthly: Fixed monthly rate
                $monthlyRate = ($annualRate / 100) / 12;
                $interest = round($balance * $monthlyRate, 0);
                $days = $prevDate->diffInDays($actualDate); // Just for display
            }

            $payment = round($fixedMonthlyPayment, 0);

            // Handle last month - pay off remaining balance
            if ($i == $months) {
                if ($balance + $interest < $payment + 100000) { // Tolerance
                     $payment = $balance + $interest;
                }
            }

            $principalPayment = round($payment - $interest, 0);

            // Prevent negative balance
            if ($principalPayment > $balance) {
                $principalPayment = $balance;
                $payment = $interest + $principalPayment;
            }

            $balance = round($balance - $principalPayment, 0);
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

