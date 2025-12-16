<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HolidayController extends Controller
{
    public function index()
    {
        $holidays = Holiday::orderBy('date', 'desc')->paginate(50);
        return view('holidays.index', compact('holidays'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|string',
            'name' => 'required|string|max:255',
            'type' => 'required|in:public,bank,custom',
        ]);

        // Convert date format from dd/mm/yyyy to yyyy-mm-dd
        $validated['date'] = $this->convertDateFormat($validated['date']);

        Holiday::create($validated);

        return redirect()->route('holidays.index')->with('success', 'Đã thêm ngày lễ');
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();
        return redirect()->route('holidays.index')->with('success', 'Đã xóa ngày lễ');
    }

    /**
     * Convert date format from dd/mm/yyyy to yyyy-mm-dd
     */
    private function convertDateFormat($date)
    {
        // If already in yyyy-mm-dd format, return as is
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        // Convert from dd/mm/yyyy to yyyy-mm-dd
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $date, $matches)) {
            return $matches[3] . '-' . $matches[2] . '-' . $matches[1];
        }

        // If format is unrecognized, try to parse with Carbon
        try {
            return Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d');
        } catch (\Exception $e) {
            return $date;
        }
    }
}

