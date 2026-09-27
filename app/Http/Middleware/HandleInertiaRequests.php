<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Wallets\DebtGoal\Application\Query\GetDebtGoalProgress;
use Wallets\Shared\Application\QueryBus;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user()
                    ? [
                        'id' => $request->user()->id,
                        'name' => $request->user()->name,
                        'email' => $request->user()->email,
                    ]
                    : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'import_errors' => fn () => $request->session()->get('import_errors'),
            ],
            'debtGoal' => function () use ($request) {
                if (! $request->user()) {
                    return null;
                }

                return app(QueryBus::class)->ask(new GetDebtGoalProgress(
                    userId: (int) $request->user()->id,
                ));
            },
        ];
    }
}
