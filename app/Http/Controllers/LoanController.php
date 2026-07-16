<?php

namespace App\Http\Controllers;

use App\Http\Concerns\ConvertsVietnameseDates;
use App\Models\Loan;
use Illuminate\Http\Request;
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

        return view('loans.index', [
            'loans' => $result['loans'],
            'totalRemaining' => $result['totalRemaining'],
            'totalLendRemaining' => $result['totalLendRemaining'],
            'wallets' => $wallets,
        ]);
    }

    public function show(Loan $loan)
    {
        $data = $this->queries->ask(new GetLoanDetail(
            userId: auth()->id(),
            loanId: $loan->id,
        ));

        return view('loans.show', $data);
    }

    public function create()
    {
        $wallets = $this->queries->ask(new ListWallets(userId: auth()->id(), activeOnly: true));

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

        $customRows = $request->input('custom_schedule', []);
        foreach ($customRows as $idx => $row) {
            if (isset($row['paid_at'])) {
                $customRows[$idx]['paid_at'] = $this->convertDateFormat($row['paid_at']);
            }
        }

        $result = $this->commands->dispatch(new CreateLoan(
            userId: auth()->id(),
            data: $validated,
            recordCashFlow: $request->boolean('record_cash_flow'),
            linkRecurring: $request->boolean('link_recurring', true),
            customSchedule: $customRows,
        ));

        if (! empty($result['errors'])) {
            return back()->withErrors($result['errors'])->withInput();
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
