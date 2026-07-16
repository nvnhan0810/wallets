<?php

namespace App\Http\Controllers;

use App\Models\RecurringItem;
use Illuminate\Http\Request;
use Wallets\RecurringPlanning\Application\Command\CreateRecurringItem;
use Wallets\RecurringPlanning\Application\Command\DeleteRecurringItem;
use Wallets\RecurringPlanning\Application\Command\UpdateRecurringItem;
use Wallets\RecurringPlanning\Application\Query\ListRecurringItems;
use Wallets\Shared\Application\CommandBus;
use Wallets\Shared\Application\QueryBus;
use Wallets\WalletAccounting\Application\Query\ListWallets;

class RecurringItemController extends Controller
{
    public function __construct(
        private readonly CommandBus $commands,
        private readonly QueryBus $queries,
    ) {}

    public function index()
    {
        $items = $this->queries->ask(new ListRecurringItems(userId: auth()->id()));
        $wallets = $this->queries->ask(new ListWallets(userId: auth()->id(), activeOnly: true));

        return view('recurring-items.index', compact('items', 'wallets'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0.01',
            'wallet_id' => 'required|exists:wallets,id',
            'day_of_month' => 'required|integer|min:1|max:31',
            'note' => 'nullable|string',
        ]);

        $this->commands->dispatch(new CreateRecurringItem(
            userId: auth()->id(),
            data: $validated,
        ));

        return redirect()->route('recurring-items.index')->with('success', 'Đã thêm khoản thu/chi cố định.');
    }

    public function update(Request $request, RecurringItem $recurringItem)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0.01',
            'wallet_id' => 'required|exists:wallets,id',
            'day_of_month' => 'required|integer|min:1|max:31',
            'note' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $this->commands->dispatch(new UpdateRecurringItem(
            userId: auth()->id(),
            recurringItemId: $recurringItem->id,
            data: $validated,
        ));

        return redirect()->route('recurring-items.index')->with('success', 'Đã cập nhật.');
    }

    public function destroy(RecurringItem $recurringItem)
    {
        $this->commands->dispatch(new DeleteRecurringItem(
            userId: auth()->id(),
            recurringItemId: $recurringItem->id,
        ));

        return redirect()->route('recurring-items.index')->with('success', 'Đã xóa.');
    }
}
