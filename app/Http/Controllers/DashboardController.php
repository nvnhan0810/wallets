<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

        return view('dashboard.index', $data);
    }
}
