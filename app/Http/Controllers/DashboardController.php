<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Setting;
use App\Models\Wallet;
use App\Services\RecurringItemService;

class DashboardController extends Controller
{
    public function __construct(private RecurringItemService $recurringService) {}

    public function index()
    {
        $alertDays = Setting::recurringAlertDays();
        $upcomingRecurring = $this->recurringService->upcoming($alertDays);

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

        return view('dashboard.index', compact(
            'alertDays',
            'upcomingRecurring',
            'wallets',
            'totalWalletBalance',
            'recentTransactions',
            'unsettledLoansCount',
        ));
    }
}
