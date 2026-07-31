<?php

namespace App\Http\Controllers;

use App\Support\InertiaData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Wallets\Lending\Application\Query\ListActiveLoans;
use Wallets\Reporting\Application\Query\GetDashboardOverview;
use Wallets\Shared\Application\QueryBus;

class DashboardController extends Controller
{
    public function __construct(private readonly QueryBus $queries) {}

    public function index(Request $request)
    {
        $userId = auth()->id();

        $data = $this->queries->ask(new GetDashboardOverview(
            userId: $userId,
            chartPeriod: $request->get('period', 'month'),
        ));

        $activeLoansData = $this->queries->ask(new ListActiveLoans($userId));
        $data['totalDebt'] += $activeLoansData['totalRemaining'] ?? 0;

        $data['wallets'] = InertiaData::wallets(collect($data['wallets'] ?? []));
        $data['recentTransactions'] = collect($data['recentTransactions'] ?? [])
            ->map(fn ($tx) => InertiaData::transaction($tx))
            ->values()
            ->all();

        $data['upcomingReminders'] = collect($data['upcomingReminders'] ?? [])->map(function ($r) {
            return [
                'kind' => $r['kind'] ?? null,
                'name' => $r['name'] ?? '',
                'amount' => (float) ($r['amount'] ?? 0),
                'due_date' => isset($r['due_date']) ? (string) $r['due_date'] : null,
                'days_until' => $r['days_until'] ?? null,
                'type_label' => $r['type_label'] ?? '',
                'type_badge_class' => $r['type_badge_class'] ?? '',
                'insufficient_funds' => (bool) ($r['insufficient_funds'] ?? false),
                'pay_url' => $r['pay_url'] ?? null,
                'wallet_name' => isset($r['wallet']) ? ($r['wallet']->name ?? null) : null,
            ];
        })->values()->all();

        return Inertia::render('Dashboard/Index', $data);
    }
}
