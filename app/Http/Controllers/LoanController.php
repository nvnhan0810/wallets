<?php

namespace App\Http\Controllers;

use App\Http\Concerns\ConvertsVietnameseDates;
use App\Models\Loan;
use App\Support\InertiaData;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Wallets\Lending\Application\Command\CreateLoan;
use Wallets\Lending\Application\Command\RecordLoanPayment;
use Wallets\Lending\Application\Command\SettleLoan;
use Wallets\Lending\Application\Query\GetLoanDetail;
use Wallets\Lending\Application\Query\ListActiveLoans;
use Wallets\Shared\Application\CommandBus;
use Wallets\Shared\Application\QueryBus;
use Wallets\WalletAccounting\Application\Query\ListWallets;

class LoanController extends Controller
{
    use ConvertsVietnameseDates;

    public function __construct(
        private readonly CommandBus $commands,
        private readonly QueryBus $queries,
    ) {}

    public function index()
    {
        $result = $this->queries->ask(new ListActiveLoans(userId: auth()->id()));
        $wallets = $this->queries->ask(new ListWallets(userId: auth()->id(), activeOnly: true));

        return Inertia::render('Loans/Index', [
            'loans' => collect($result['loans'])->map(function ($loan) {
                return [
                    'id' => $loan->id,
                    'name' => $loan->name,
                    'type' => $loan->type,
                    'principal_amount' => (float) $loan->principal_amount,
                    'monthly_payment' => (float) ($loan->monthly_payment ?? 0),
                    'wallet_id' => $loan->wallet_id,
                    'interest_rate' => (float) ($loan->interest_rate ?? 0),
                    'interest_calculation_method' => $loan->interest_calculation_method,
                    'term_months' => $loan->term_months,
                    'months_passed' => $loan->months_passed ?? null,
                    'remaining_months' => $loan->remaining_months ?? null,
                    'remaining_principal' => isset($loan->remaining_principal) ? (float) $loan->remaining_principal : null,
                    'remaining_interest' => isset($loan->remaining_interest) ? (float) $loan->remaining_interest : null,
                    'remaining_amount' => isset($loan->remaining_amount) ? (float) $loan->remaining_amount : null,
                    'payoff_remaining' => (float) ($loan->payoff_remaining ?? 0),
                    'started_at' => optional($loan->started_at)?->toDateString(),
                ];
            })->values()->all(),
            'totalRemaining' => $result['totalRemaining'],
            'totalLendRemaining' => $result['totalLendRemaining'],
            'wallets' => InertiaData::wallets($wallets),
        ]);
    }

    public function show(Loan $loan)
    {
        $data = $this->queries->ask(new GetLoanDetail(
            userId: auth()->id(),
            loanId: $loan->id,
        ));

        $loanModel = $data['loan'];
        $data['loan'] = [
            'id' => $loanModel->id,
            'name' => $loanModel->name,
            'type' => $loanModel->type,
            'principal_amount' => (float) $loanModel->principal_amount,
            'interest_rate' => (float) ($loanModel->interest_rate ?? 0),
            'interest_calculation_method' => $loanModel->interest_calculation_method,
            'term_months' => $loanModel->term_months,
            'monthly_payment' => (float) ($loanModel->monthly_payment ?? 0),
            'remaining_principal' => isset($loanModel->remaining_principal) ? (float) $loanModel->remaining_principal : null,
            'started_at' => optional($loanModel->started_at)?->format('d/m/Y'),
            'wallet' => $loanModel->wallet ? ['id' => $loanModel->wallet->id, 'name' => $loanModel->wallet->name] : null,
            'payments' => $loanModel->payments->map(fn ($p) => [
                'id' => $p->id,
                'amount' => (float) $p->amount,
                'paid_at' => optional($p->paid_at)?->format('d/m/Y'),
                'note' => $p->note,
                'kind_label' => method_exists($p, 'kindLabel') ? $p->kindLabel() : ($p->kind ?? ''),
                'is_early' => method_exists($p, 'isEarly') ? $p->isEarly() : false,
                'period_due_date' => $p->period_due_date ?? null,
            ])->values()->all(),
        ];

        return Inertia::render('Loans/Show', $data);
    }

    public function create()
    {
        $wallets = $this->queries->ask(new ListWallets(userId: auth()->id(), activeOnly: true));

        return Inertia::render('Loans/Create', [
            'wallets' => InertiaData::wallets($wallets),
        ]);
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
            'received_amount' => 'nullable|numeric|min:0',
            'interest_rate' => 'nullable|numeric',
            'interest_calculation_method' => 'nullable|in:monthly,daily,custom',
            'term_months' => 'nullable|integer',
            'months_paid' => 'nullable|integer',
            'monthly_payment' => 'nullable|numeric',
            'payment_day' => 'nullable|integer|min:1|max:31',
            'custom_schedule' => 'sometimes|array',
        ]);

        $validated['started_at'] = $this->convertDateFormat($validated['started_at']);

        // Ví và khoản vay độc lập: chỉ ghi tiền vào ví khi ngày bắt đầu là hôm nay.
        $startsToday = Carbon::parse($validated['started_at'])->isToday();
        $recordCashFlow = $startsToday && $request->boolean('record_cash_flow');

        if ($recordCashFlow) {
            $request->validate(['wallet_id' => 'required|exists:wallets,id']);
        }

        $validated['months_paid'] = $validated['months_paid'] ?? 0;
        $validated['interest_calculation_method'] = $validated['interest_calculation_method'] ?? 'monthly';

        if (($validated['interest_calculation_method'] ?? 'monthly') === 'custom') {
            $request->validate([
                'monthly_payment' => 'required|numeric|min:1',
                'term_months' => 'required|integer|min:1',
                'custom_schedule' => 'required|array|min:1',
            ]);
        }

        $customRows = $request->input('custom_schedule', []);
        foreach ($customRows as $idx => $row) {
            if (isset($row['paid_at'])) {
                $customRows[$idx]['paid_at'] = $this->convertDateFormat($row['paid_at']);
            }
        }

        $receivedAmount = $request->filled('received_amount') ? (float) $validated['received_amount'] : null;

        $result = $this->commands->dispatch(new CreateLoan(
            userId: auth()->id(),
            data: $validated,
            recordCashFlow: $recordCashFlow,
            customSchedule: $customRows,
            receivedAmount: $receivedAmount,
        ));

        if (! empty($result['errors'])) {
            return back()->withErrors($result['errors'])->withInput();
        }

        $message = $recordCashFlow
            ? 'Đã tạo khoản vay và ghi nhận tiền vào ví.'
            : 'Đã tạo khoản vay. Kỳ trả sẽ nhắc khi tới hạn.';

        return redirect()->route('loans.index')->with('success', $message);
    }

    public function storePayment(Request $request)
    {
        $validated = $request->validate([
            'loan_id' => 'required|exists:loans,id',
            'wallet_id' => 'required|exists:wallets,id',
            'amount' => 'required|numeric|min:0.01',
            'paid_at' => 'required|string',
            'note' => 'nullable|string|max:255',
            'period_id' => 'nullable|exists:loan_custom_schedules,id',
        ]);

        $validated['paid_at'] = $this->convertDateFormat($validated['paid_at']);

        $result = $this->commands->dispatch(new RecordLoanPayment(
            userId: auth()->id(),
            data: $validated,
        ));

        $message = ($result['was_early'] ?? false)
            ? 'Đã ghi thanh toán trước (chưa trừ gốc). Số dư gốc cập nhật khi thanh toán đúng kỳ từ 06/2026.'
            : 'Đã ghi thanh toán và cập nhật ví.';

        return redirect()->route('loans.show', $validated['loan_id'])->with('success', $message);
    }

    public function settle(Request $request, Loan $loan)
    {
        $validated = $request->validate([
            'wallet_id' => 'required|exists:wallets,id',
            'amount' => 'nullable|numeric|min:0',
            'paid_at' => 'nullable|string',
            'note' => 'nullable|string|max:255',
        ]);

        if (isset($validated['paid_at'])) {
            $validated['paid_at'] = $this->convertDateFormat($validated['paid_at']);
        }

        $remainingPrincipal = $request->input('remaining_principal');

        $this->commands->dispatch(new SettleLoan(
            userId: auth()->id(),
            loanId: $loan->id,
            data: $validated,
            remainingPrincipal: $remainingPrincipal !== null ? (float) $remainingPrincipal : null,
        ));

        return redirect()->route('loans.index')->with('success', 'Đã tất toán và cập nhật ví.');
    }
}
