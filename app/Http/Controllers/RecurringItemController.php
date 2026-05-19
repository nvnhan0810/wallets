<?php

namespace App\Http\Controllers;

use App\Models\RecurringItem;
use App\Models\Wallet;
use Illuminate\Http\Request;

class RecurringItemController extends Controller
{
    public function index()
    {
        $items = RecurringItem::query()
            ->with('wallet')
            ->orderByDesc('is_active')
            ->orderBy('day_of_month')
            ->orderBy('name')
            ->get()
            ->map(function (RecurringItem $item) {
                $item->next_due = $item->nextDueDate();
                $item->days_until = $item->daysUntilDue();
                $item->insufficient_funds = $item->isInsufficientFunds();

                return $item;
            });

        $wallets = Wallet::query()->where('is_active', true)->orderBy('name')->get();

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

        RecurringItem::create($validated);

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

        $recurringItem->update($validated);

        return redirect()->route('recurring-items.index')->with('success', 'Đã cập nhật.');
    }

    public function destroy(RecurringItem $recurringItem)
    {
        $recurringItem->delete();

        return redirect()->route('recurring-items.index')->with('success', 'Đã xóa.');
    }
}
