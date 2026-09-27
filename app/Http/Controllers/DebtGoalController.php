<?php

namespace App\Http\Controllers;

use DomainException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Wallets\DebtGoal\Application\Command\UpdateDebtGoal;
use Wallets\DebtGoal\Application\Query\GetDebtGoalDetail;
use Wallets\DebtGoal\Application\Query\GetDebtGoalSettings;
use Wallets\DebtGoal\Domain\SettlementFeeType;
use Wallets\Shared\Application\CommandBus;
use Wallets\Shared\Application\QueryBus;

class DebtGoalController extends Controller
{
    public function __construct(
        private readonly CommandBus $commands,
        private readonly QueryBus $queries,
    ) {}

    public function index()
    {
        $data = $this->queries->ask(new GetDebtGoalSettings(userId: (int) auth()->id()));

        return Inertia::render('DebtGoals/Index', $data);
    }

    public function show()
    {
        $data = $this->queries->ask(new GetDebtGoalDetail(userId: (int) auth()->id()));

        if ($data === null) {
            return redirect()->route('debt-goals.index')
                ->with('error', 'Chưa gắn khoản vay để xem chi tiết tất toán.');
        }

        return Inertia::render('DebtGoals/Show', $data);
    }

    public function update(Request $request)
    {
        if ($request->boolean('clear')) {
            $this->commands->dispatch(new UpdateDebtGoal(
                userId: (int) auth()->id(),
                name: '',
                walletId: 0,
                clear: true,
            ));

            return redirect()->route('debt-goals.index')->with('success', 'Đã xóa mục tiêu trả nợ.');
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:120',
            'wallet_id' => 'required|integer|exists:wallets,id',
            'loan_id' => 'nullable|integer|exists:loans,id',
            'target_amount' => 'nullable|numeric|min:1',
            'settlement_fees' => 'nullable|array',
            'settlement_fees.*.year' => 'required_with:settlement_fees|integer|min:1|max:40',
            'settlement_fees.*.type' => 'required_with:settlement_fees|string|in:'.implode(',', SettlementFeeType::all()),
            'settlement_fees.*.value' => 'required_with:settlement_fees|numeric|min:0',
        ]);

        $loanId = isset($validated['loan_id']) && (int) $validated['loan_id'] > 0
            ? (int) $validated['loan_id']
            : null;

        if ($loanId === null && empty($validated['target_amount'])) {
            return redirect()->back()
                ->with('error', 'Nhập số tiền mục tiêu hoặc chọn khoản vay.')
                ->withInput();
        }

        $fees = [];
        foreach ($validated['settlement_fees'] ?? [] as $row) {
            $fees[] = [
                'year' => (int) $row['year'],
                'type' => (string) $row['type'],
                'value' => (float) $row['value'],
            ];
        }

        try {
            $this->commands->dispatch(new UpdateDebtGoal(
                userId: (int) auth()->id(),
                name: (string) ($validated['name'] ?? ''),
                walletId: (int) $validated['wallet_id'],
                targetAmount: isset($validated['target_amount']) ? (float) $validated['target_amount'] : null,
                loanId: $loanId,
                settlementFees: $fees,
            ));
        } catch (DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('debt-goals.index')->with('success', 'Đã lưu mục tiêu trả nợ.');
    }
}
