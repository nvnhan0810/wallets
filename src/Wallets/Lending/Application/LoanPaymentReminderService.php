<?php

namespace Wallets\Lending\Application;

use App\Models\Loan;
use App\Models\RecurringItem;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LoanPaymentReminderService
{
    public function __construct(private \Wallets\Lending\Application\LoanPaymentScheduleService $scheduleService) {}

    public function upcomingLoanPayments(int $userId, ?int $withinDays = null): Collection
    {
        $withinDays = $withinDays ?? Setting::recurringAlertDays($userId);
        $from = Carbon::today()->startOfDay();
        $until = $from->copy()->addDays($withinDays);

        return Loan::query()
            ->forUser($userId)
            ->where('is_settled', false)
            ->where('type', 'bank')
            ->with(['payments', 'recurringItem', 'wallet'])
            ->get()
            ->map(function (Loan $loan) use ($from) {
                $due = $this->nextPaymentDueDate($loan, $from);
                if (! $due) {
                    return null;
                }

                $loan->payment_due_date = $due;
                $loan->days_until_payment = (int) $from->diffInDays($due, false);

                return $loan;
            })
            ->filter()
            ->filter(function (Loan $loan) use ($from, $until) {
                if ($loan->payment_due_date->gt($until)) {
                    return false;
                }

                return ! $this->scheduleService->hasEarlyPaymentForDueDate($loan, $loan->payment_due_date);
            })
            ->sortBy('payment_due_date')
            ->values();
    }

    public function filterRecurringWithEarlyCoverage(Collection $recurringItems): Collection
    {
        return $recurringItems->filter(function (RecurringItem $item) {
            if (! $item->loan_id || ! $item->loan) {
                return true;
            }

            $due = $item->due_date ?? $item->nextDueDate();

            return ! $this->scheduleService->hasEarlyPaymentForDueDate($item->loan, $due);
        })->values();
    }

    private function nextPaymentDueDate(Loan $loan, Carbon $from): ?Carbon
    {
        if ($loan->recurringItem) {
            return $loan->recurringItem->nextDueDate($from);
        }

        $day = $this->scheduleService->paymentDay($loan);
        $due = $from->copy()->day(min($day, $from->daysInMonth));
        if ($due->lt($from)) {
            $next = $from->copy()->addMonthNoOverflow()->startOfMonth();
            $due = $next->copy()->day(min($day, $next->daysInMonth));
        }

        return $due;
    }
}
