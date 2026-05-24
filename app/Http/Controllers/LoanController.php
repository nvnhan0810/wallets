<?php

namespace App\Http\Controllers;

use App\Http\Concerns\ConvertsVietnameseDates;
use App\Models\Holiday;
use App\Models\Loan;
use App\Models\LoanCustomSchedule;
use App\Models\Payment;
use App\Models\Wallet;
use App\Services\LoanPaymentScheduleService;
use App\Services\LoanWalletService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoanController extends Controller
{
    use ConvertsVietnameseDates;

    public function __construct(
        private LoanWalletService $loanWallet,
        private LoanPaymentScheduleService $paymentSchedule,
    ) {}

    public function index()
    {
        $loans = Loan::with(['payments', 'wallet', 'recurringItem'])->where('is_settled', false)->get();

        $loans->transform(function ($loan) {
            $totalPaid = $loan->payments->sum('amount');

            if ($loan->type === 'bank') {
                $schedule = $this->calculateAmortization(
                    $loan->id,
                    $loan->principal_amount,
                    $loan->interest_rate,
                    $loan->term_months,
                    $loan->started_at,
                    $loan->monthly_payment,
                    $loan->interest_calculation_method ?? 'monthly'
                );

                if ($schedule->isEmpty()) {
                    $loan->remaining_months = $loan->term_months;
                    $loan->remaining_principal = $loan->principal_amount;
                    $loan->remaining_interest = 0;
                } else {
                    $monthsPassed = $this->paymentSchedule->effectiveMonthsPaid($loan, $schedule, $loan->payments);
                    $maxIndex = $schedule->max('month_index');

                    $loan->remaining_months = max(0, ($loan->term_months ?? $maxIndex) - $monthsPassed);
                    $loan->remaining_principal = $this->paymentSchedule->remainingPrincipalAt($loan, $schedule, $monthsPassed);
                    $loan->remaining_interest = $schedule->where('month_index', '>', $monthsPassed)->sum('interest');
                }
            } else {
                $loan->remaining_amount = $loan->principal_amount - $totalPaid;
            }

            $loan->payoff_remaining = $loan->type === 'bank'
                ? ($loan->remaining_principal ?? 0)
                : ($loan->remaining_amount ?? 0);

            return $loan;
        });

        $totalRemaining = $loans->reduce(function ($carry, $loan) {
            if ($loan->type === 'bank') {
                return $carry + ($loan->remaining_principal ?? 0);
            }
            if ($loan->type === 'borrow') {
                return $carry + ($loan->remaining_amount ?? 0);
            }

            return $carry;
        }, 0);

        $totalLendRemaining = $loans->reduce(function ($carry, $loan) {
            if ($loan->type === 'lend') {
                $paid = $loan->payments->sum('amount');

                return $carry + max(($loan->principal_amount - $paid), 0);
            }

            return $carry;
        }, 0);

        $wallets = Wallet::query()->where('is_active', true)->orderBy('name')->get();

        return view('loans.index', compact('loans', 'totalRemaining', 'totalLendRemaining', 'wallets'));
    }

    public function show(Loan $loan)
    {
        $loan->load(['payments.transaction', 'wallet', 'recurringItem']);
        $schedule = collect([]);
        $monthsPassed = 0;
        $timeline = collect([]);

        if ($loan->type === 'bank') {
            $schedule = $this->calculateAmortization(
                $loan->id,
                $loan->principal_amount,
                $loan->interest_rate,
                $loan->term_months,
                $loan->started_at,
                $loan->monthly_payment,
                $loan->interest_calculation_method ?? 'monthly'
            );

            if ($schedule->isNotEmpty()) {
                $monthsPassed = $this->paymentSchedule->effectiveMonthsPaid($loan, $schedule, $loan->payments);
                $maxIndex = $schedule->max('month_index');

                $loan->remaining_months = max(0, ($loan->term_months ?? $maxIndex) - $monthsPassed);
                $loan->remaining_principal = $this->paymentSchedule->remainingPrincipalAt($loan, $schedule, $monthsPassed);
                $loan->remaining_interest = $schedule->where('month_index', '>', $monthsPassed)->sum('interest');
                $timeline = $this->paymentSchedule->buildTimeline($loan, $schedule, $loan->payments, $monthsPassed);
            } else {
                $loan->remaining_months = $loan->term_months;
                $loan->remaining_principal = $loan->principal_amount;
                $loan->remaining_interest = 0;
            }
        }

        $paymentDay = $loan->isBankLoan() ? $this->paymentSchedule->paymentDay($loan) : null;

        return view('loans.show', compact('loan', 'schedule', 'monthsPassed', 'timeline', 'paymentDay'));
    }

    public function create()
    {
        $wallets = Wallet::query()->where('is_active', true)->orderBy('name')->get();

        return view('loans.create', compact('wallets'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:bank,borrow,lend',
            'name' => 'required|string',
            'principal_amount' => 'required|numeric',
            'started_at' => 'required|string',
            'wallet_id' => 'nullable|exists:wallets,id',
            'record_cash_flow' => 'sometimes|boolean',
            'interest_rate' => 'nullable|numeric',
            'interest_calculation_method' => 'nullable|in:monthly,daily,custom',
            'term_months' => 'nullable|integer',
            'months_paid' => 'nullable|integer',
            'monthly_payment' => 'nullable|numeric',
            'payment_day' => 'nullable|integer|min:1|max:31',
            'link_recurring' => 'sometimes|boolean',
            'custom_schedule' => 'sometimes|array',
        ]);

        if ($request->boolean('record_cash_flow')) {
            $request->validate(['wallet_id' => 'required|exists:wallets,id']);
        }

        $validated['started_at'] = $this->convertDateFormat($validated['started_at']);
        $validated['months_paid'] = $validated['months_paid'] ?? 0;
        $validated['interest_calculation_method'] = $validated['interest_calculation_method'] ?? 'monthly';

        if (($validated['interest_calculation_method'] ?? 'monthly') === 'custom') {
            $request->validate([
                'monthly_payment' => 'required|numeric|min:1',
                'term_months' => 'required|integer|min:1',
                'custom_schedule' => 'required|array|min:1',
            ]);
        }

        $loan = DB::transaction(function () use ($request, $validated) {
            $loan = Loan::create(collect($validated)->only([
                'type', 'name', 'principal_amount', 'started_at', 'wallet_id',
                'interest_rate', 'interest_calculation_method', 'term_months',
                'months_paid', 'monthly_payment', 'payment_day',
            ])->filter(fn ($v) => $v !== null)->all());

            if ($request->boolean('record_cash_flow') && $validated['wallet_id']) {
                $wallet = Wallet::query()->findOrFail($validated['wallet_id']);
                $this->loanWallet->recordCreation($loan, $wallet);
            }

            if ($loan->type === 'bank' && $request->boolean('link_recurring', true)) {
                $this->paymentSchedule->syncRecurringItem($loan->fresh());
            }

            return $loan;
        });

        if (($validated['interest_calculation_method'] ?? 'monthly') === 'custom') {
            $customRows = $request->input('custom_schedule', []);
            $monthlyPayment = (float) $validated['monthly_payment'];
            $errors = [];
            foreach ($customRows as $idx => $row) {
                if (! isset($row['payment'], $row['principal'], $row['interest'])) {
                    $errors[] = 'Dòng '.($idx + 1).' thiếu dữ liệu.';
                    continue;
                }
                $payment = (float) $row['payment'];
                $principal = (float) $row['principal'];
                $interest = (float) $row['interest'];
                $fee = (float) ($row['fee'] ?? 0);

                if (round($principal + $interest + $fee, 0) !== round($payment, 0)) {
                    $errors[] = 'Dòng '.($idx + 1).': Gốc + Lãi + Phí phải bằng Tổng trả.';
                    continue;
                }
                if (round($payment, 0) > round($monthlyPayment, 0)) {
                    $errors[] = 'Dòng '.($idx + 1).': Tổng trả vượt số tiền hàng tháng.';
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
            if (! empty($errors)) {
                return back()->withErrors($errors)->withInput();
            }
        }

        return redirect()->route('loans.index')->with('success', 'Đã tạo khoản vay và ghi nhận dòng tiền vào ví.');
    }

    public function storePayment(Request $request)
    {
        $validated = $request->validate([
            'loan_id' => 'required|exists:loans,id',
            'wallet_id' => 'required|exists:wallets,id',
            'amount' => 'required|numeric|min:0.01',
            'paid_at' => 'required|string',
            'note' => 'nullable|string|max:255',
        ]);

        $validated['paid_at'] = $this->convertDateFormat($validated['paid_at']);
        $wasEarly = false;

        DB::transaction(function () use ($validated, &$wasEarly) {
            $loan = Loan::query()->findOrFail($validated['loan_id']);
            $wallet = Wallet::query()->findOrFail($validated['wallet_id']);
            $paidAt = Carbon::parse($validated['paid_at']);

            $periodMeta = ['kind' => Payment::KIND_PERIOD, 'reduces_principal' => true];
            if ($loan->type === 'bank') {
                $schedule = $this->calculateAmortization(
                    $loan->id,
                    $loan->principal_amount,
                    $loan->interest_rate,
                    $loan->term_months,
                    $loan->started_at,
                    $loan->monthly_payment,
                    $loan->interest_calculation_method ?? 'monthly'
                );
                $resolved = $this->paymentSchedule->resolvePeriodForPayment($loan, $paidAt, $schedule);
                $periodMeta = [
                    'kind' => $resolved['kind'],
                    'period_due_date' => $resolved['period_due_date']->toDateString(),
                    'schedule_month_index' => $resolved['schedule_month_index'],
                    'reduces_principal' => $resolved['reduces_principal'],
                ];
            }

            $payment = Payment::create([
                'loan_id' => $validated['loan_id'],
                'amount' => $validated['amount'],
                'paid_at' => $validated['paid_at'],
                'note' => $validated['note'] ?? null,
                ...$periodMeta,
            ]);

            $this->loanWallet->recordPayment($loan, $payment, $wallet);

            if ($loan->type === 'bank' && $payment->reduces_principal && $payment->schedule_month_index) {
                $loan->update([
                    'months_paid' => max((int) $loan->months_paid, (int) $payment->schedule_month_index),
                ]);
            }

            if (! $loan->wallet_id) {
                $loan->update(['wallet_id' => $wallet->id]);
            }

            if ($loan->type === 'bank') {
                $this->paymentSchedule->syncRecurringItem($loan->fresh());
            }

            $wasEarly = $payment->isEarly();
        });

        $message = $wasEarly
            ? 'Đã ghi thanh toán trước (chưa trừ gốc). Số dư gốc cập nhật khi thanh toán đúng kỳ từ 06/2026.'
            : 'Đã ghi thanh toán và cập nhật ví.';

        return redirect()->route('loans.show', $validated['loan_id'])->with('success', $message);
    }

    public function settle(Request $request, Loan $loan)
    {
        $loan->load('payments');

        $validated = $request->validate([
            'wallet_id' => 'required|exists:wallets,id',
            'amount' => 'nullable|numeric|min:0',
            'paid_at' => 'nullable|string',
            'note' => 'nullable|string|max:255',
        ]);

        $remainingPrincipal = $request->input('remaining_principal');

        DB::transaction(function () use ($loan, $validated, $remainingPrincipal) {
            $amount = $validated['amount'] ?? $this->loanWallet->remainingPayoff(
                $loan,
                $remainingPrincipal !== null ? (float) $remainingPrincipal : null
            );

            if ($amount > 0) {
                $paidAt = isset($validated['paid_at'])
                    ? $this->convertDateFormat($validated['paid_at'])
                    : now()->format('Y-m-d');

                $wallet = Wallet::query()->findOrFail($validated['wallet_id']);

                $payment = Payment::create([
                    'loan_id' => $loan->id,
                    'kind' => Payment::KIND_SETTLEMENT,
                    'amount' => $amount,
                    'paid_at' => $paidAt,
                    'note' => $validated['note'] ?? 'Tất toán',
                    'reduces_principal' => true,
                ]);

                $this->loanWallet->recordPayment($loan, $payment, $wallet);

                if (! $loan->wallet_id) {
                    $loan->update(['wallet_id' => $wallet->id]);
                }
            }

            $loan->update(['is_settled' => true]);
        });

        return redirect()->route('loans.index')->with('success', 'Đã tất toán và cập nhật ví.');
    }

    private function calculateAmortization($loanId, $principal, $annualRate, $months, $startDate, $fixedMonthlyPayment = null, $method = 'monthly')
    {
        $balance = $principal;
        $schedule = collect([]);
        $prevDate = Carbon::parse($startDate);

        if ($method === 'custom') {
            $rows = LoanCustomSchedule::where('loan_id', $loanId)->orderBy('month_index')->get();
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

        if (! $fixedMonthlyPayment) {
            $monthlyRate = ($annualRate / 100) / 12;
            $fixedMonthlyPayment = ($principal * $monthlyRate) / (1 - pow(1 + $monthlyRate, -$months));
        }

        for ($i = 1; $i <= $months; $i++) {
            $theoreticalDate = Carbon::parse($startDate)->addMonths($i);
            $actualDate = $method === 'daily'
                ? $this->adjustForNonWorkingDays($theoreticalDate)
                : $theoreticalDate->copy();

            if ($method === 'daily') {
                $days = $prevDate->diffInDays($actualDate);
                $interest = round($balance * ($annualRate / 100) * $days / 365, 0);
            } else {
                $monthlyRate = ($annualRate / 100) / 12;
                $interest = round($balance * $monthlyRate, 0);
                $days = $prevDate->diffInDays($actualDate);
            }

            $payment = round($fixedMonthlyPayment, 0);

            if ($i == $months && $balance + $interest < $payment + 100000) {
                $payment = $balance + $interest;
            }

            $principalPayment = round($payment - $interest, 0);

            if ($principalPayment > $balance) {
                $principalPayment = $balance;
                $payment = $interest + $principalPayment;
            }

            $balance = round($balance - $principalPayment, 0);
            if ($balance < 0) {
                $balance = 0;
            }

            $schedule->push([
                'month_index' => $i,
                'date' => $actualDate->copy(),
                'theoretical_date' => $theoreticalDate->copy(),
                'is_adjusted' => ! $theoreticalDate->isSameDay($actualDate),
                'days' => $days,
                'payment' => $payment,
                'interest' => $interest,
                'principal' => $principalPayment,
                'remaining_principal' => $balance,
            ]);

            $prevDate = $actualDate;
        }

        return $schedule;
    }

    private function adjustForNonWorkingDays($date)
    {
        static $holidays = null;
        if ($holidays === null) {
            $holidays = Holiday::pluck('date')->map(fn ($d) => $d->format('Y-m-d'))->toArray();
        }

        $adjustedDate = $date->copy();
        $iterations = 0;

        while ($iterations < 10) {
            $dayOfWeek = $adjustedDate->dayOfWeek;
            $dateString = $adjustedDate->format('Y-m-d');

            if ($dayOfWeek == Carbon::SATURDAY || $dayOfWeek == Carbon::SUNDAY || in_array($dateString, $holidays)) {
                $adjustedDate->addDay();
                $iterations++;

                continue;
            }

            break;
        }

        return $adjustedDate;
    }
}
