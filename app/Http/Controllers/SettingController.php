<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $recurringAlertDays = Setting::recurringAlertDays();

        return view('settings.index', compact('recurringAlertDays'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'recurring_alert_days' => 'required|integer|min:1|max:30',
        ]);

        Setting::set('recurring_alert_days', $validated['recurring_alert_days']);

        return redirect()->route('settings.index')->with('success', 'Đã lưu cài đặt.');
    }
}
