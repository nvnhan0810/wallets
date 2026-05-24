<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Setting;
use App\Models\Wallet;
use App\Services\LoanPaymentReminderService;
use App\Services\RecurringItemService;
use App\Services\TransactionAnalyticsService;

class DashboardController extends Controller
{
    public function __construct(
        private RecurringItemService $recurringService,
        private LoanPaymentReminderService $loanReminders,
        private TransactionAnalyticsService $analytics,
    ) {}

    public function index()
    {
        $alertDays = Setting::recurringAlertDays();
        $upcomingLoanPayments = $this->loanReminders->upcomingLoanPayments($alertDays);
        $upcomingRecurring = $this->recurringService->upcoming($alertDays);
        $upcomingReminders = $this->buildUpcomingReminders($upcomingLoanPayments, $upcomingRecurring);

        $wallets = Wallet::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $totalWalletBalance = $wallets->sum('balance');

        $recentTransactions = \App\Models\Transaction::query()
            ->with('wallet')
            ->orderByDesc('transacted_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $unsettledLoansCount = Loan::query()->where('is_settled', false)->count();

        $chartPeriod = request('period', 'month');
        if (! array_key_exists($chartPeriod, TransactionAnalyticsService::PERIODS)) {
            $chartPeriod = 'month';
        }
        $cashFlowStats = $this->analytics->summary($chartPeriod);

        return view('dashboard.index', compact(
            'alertDays',
            'upcomingReminders',
            'wallets',
            'totalWalletBalance',
            'recentTransactions',
            'unsettledLoansCount',
            'cashFlowStats',
            'chartPeriod',
        ));
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
