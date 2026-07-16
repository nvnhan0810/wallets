<?php

namespace Wallets\Reporting\Application\Handler;

use App\Models\Loan;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\Wallet;
use Wallets\Lending\Application\LoanPaymentReminderService;
use Wallets\RecurringPlanning\Application\RecurringItemService;
use Wallets\Reporting\Application\Query\GetDashboardOverview;
use Wallets\Reporting\Application\TransactionAnalyticsService;
use Wallets\Shared\Application\Query;
use Wallets\Shared\Application\QueryHandler;

final class GetDashboardOverviewHandler implements QueryHandler
{
    public function __construct(
        private readonly RecurringItemService $recurringService,
        private readonly LoanPaymentReminderService $loanReminders,
        private readonly TransactionAnalyticsService $analytics,
    ) {}

    public function handle(Query $query): mixed
    {
        assert($query instanceof GetDashboardOverview);

        $alertDays = Setting::recurringAlertDays($query->userId);
        $upcomingLoanPayments = $this->loanReminders->upcomingLoanPayments($query->userId, $alertDays);
        $upcomingRecurring = $this->recurringService->upcoming($query->userId, $alertDays);
        $upcomingReminders = $this->buildUpcomingReminders($upcomingLoanPayments, $upcomingRecurring);

        $wallets = Wallet::query()
            ->forUser($query->userId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $totalWalletBalance = $wallets->sum('balance');

        $recentTransactions = Transaction::query()
            ->forUser($query->userId)
            ->with('wallet')
            ->orderByDesc('transacted_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $unsettledLoansCount = Loan::query()->forUser($query->userId)->where('is_settled', false)->count();

        $chartPeriod = $query->chartPeriod;
        if (! array_key_exists($chartPeriod, TransactionAnalyticsService::PERIODS)) {
            $chartPeriod = 'month';
        }
        $cashFlowStats = $this->analytics->summary($query->userId, $chartPeriod);

        return compact(
            'alertDays',
            'upcomingReminders',
            'wallets',
            'totalWalletBalance',
            'recentTransactions',
            'unsettledLoansCount',
            'cashFlowStats',
            'chartPeriod',
        );
    }

    private function buildUpcomingReminders($upcomingLoanPayments, $upcomingRecurring): \Illuminate\Support\Collection
    {
        $loanIds = $upcomingLoanPayments->pluck('id');
        $loanRecurringIds = $upcomingLoanPayments->pluck('recurring_item_id')->filter();

        $reminders = $upcomingLoanPayments->map(fn (Loan $loan) => (object) [
            'kind' => 'loan',
            'name' => $loan->name,
            'amount' => (float) ($loan->monthly_payment ?? 0),
            'due_date' => $loan->payment_due_date,
            'days_until' => $loan->days_until_payment,
            'type_label' => 'Trả khoản vay',
            'type_badge_class' => 'bg-indigo-100 text-indigo-800',
            'insufficient_funds' => false,
            'wallet' => $loan->wallet,
            'loan' => $loan,
            'recurring' => null,
        ]);

        foreach ($upcomingRecurring as $item) {
            if ($item->loan_id && ($loanIds->contains($item->loan_id) || $loanRecurringIds->contains($item->id))) {
                continue;
            }

            $reminders->push((object) [
                'kind' => 'recurring',
                'name' => $item->name,
                'amount' => (float) $item->amount,
                'due_date' => $item->due_date,
                'days_until' => $item->days_until,
                'type_label' => $item->typeLabel(),
                'type_badge_class' => $item->type === 'income' ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800',
                'insufficient_funds' => $item->insufficient_funds,
                'wallet' => $item->wallet,
                'loan' => null,
                'recurring' => $item,
            ]);
        }

        return $reminders->sortBy('due_date')->values();
    }
}
