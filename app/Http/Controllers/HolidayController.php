<?php

namespace App\Http\Controllers;

use App\Http\Concerns\ConvertsVietnameseDates;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Wallets\Calendar\Application\Command\CreateHoliday;
use Wallets\Calendar\Application\Command\DeleteHoliday;
use Wallets\Calendar\Application\Query\ListHolidays;
use Wallets\Shared\Application\CommandBus;
use Wallets\Shared\Application\QueryBus;

class HolidayController extends Controller
{
    use ConvertsVietnameseDates;

    public function __construct(
        private readonly CommandBus $commands,
        private readonly QueryBus $queries,
    ) {}

    public function index()
    {
        $holidays = $this->queries->ask(new ListHolidays);

        return view('holidays.index', compact('holidays'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|string',
            'name' => 'required|string|max:255',
            'type' => 'required|in:public,bank,custom',
        ]);

        $validated['date'] = $this->convertDateFormat($validated['date']);

        $this->commands->dispatch(new CreateHoliday(data: $validated));

        return redirect()->route('holidays.index')->with('success', 'Đã thêm ngày lễ');
    }

    public function destroy(Holiday $holiday)
    {
        $this->commands->dispatch(new DeleteHoliday(holidayId: $holiday->id));

        return redirect()->route('holidays.index')->with('success', 'Đã xóa ngày lễ');
    }
}
