<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Wallets\Reporting\Application\Query\GetDashboardOverview;
use Wallets\Shared\Application\QueryBus;

class DashboardController extends Controller
{
    public function __construct(private readonly QueryBus $queries) {}

    public function index(Request $request)
    {
        $data = $this->queries->ask(new GetDashboardOverview(
            userId: auth()->id(),
            chartPeriod: $request->get('period', 'month'),
        ));

        return view('dashboard.index', $data);
    }
}
