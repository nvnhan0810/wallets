<?php

namespace Wallets\RecurringPlanning\Application;

use App\Models\RecurringItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Wallets\Lending\Application\LoanPaymentReminderService;

class RecurringItemService
{
    public function __construct(private LoanPaymentReminderService $loanReminders) {}

    public function upcoming(int $userId, int $withinDays, ?Carbon $from = null): Collection
    {
        $from = ($from ?? Carbon::today())->copy()->startOfDay();
        $until = $from->copy()->addDays($withinDays);

        return RecurringItem::query()
            ->forUser($userId)
            ->active()
            ->with(['wallet', 'loan'])
            ->get()
            ->map(function (RecurringItem $item) use ($from) {
                $dueDate = $item->nextDueDate($from);
                $item->due_date = $dueDate;
                $item->days_until = (int) $from->diffInDays($dueDate, false);
                $item->insufficient_funds = $item->isInsufficientFunds();

                return $item;
            })
            ->filter(fn (RecurringItem $item) => $item->due_date->lte($until))
            ->pipe(fn ($items) => $this->loanReminders->filterRecurringWithEarlyCoverage($items))
            ->sortBy('due_date')
            ->values();
    }
}
