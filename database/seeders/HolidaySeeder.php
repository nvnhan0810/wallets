<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Holiday;
use Carbon\Carbon;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currentYear = Carbon::now()->year;
        $nextYear = $currentYear + 1;

        // Common fixed holidays for current and next year
        $holidays = [
            // Current Year
            ["date" => "$currentYear-01-01", "name" => "Tết Dương Lịch", "type" => "public"],
            ["date" => "$currentYear-04-30", "name" => "Ngày Giải phóng miền Nam", "type" => "public"],
            ["date" => "$currentYear-05-01", "name" => "Ngày Quốc tế Lao động", "type" => "public"],
            ["date" => "$currentYear-09-02", "name" => "Ngày Quốc khánh", "type" => "public"],

            // Next Year
            ["date" => "$nextYear-01-01", "name" => "Tết Dương Lịch", "type" => "public"],
            ["date" => "$nextYear-04-30", "name" => "Ngày Giải phóng miền Nam", "type" => "public"],
            ["date" => "$nextYear-05-01", "name" => "Ngày Quốc tế Lao động", "type" => "public"],
            ["date" => "$nextYear-09-02", "name" => "Ngày Quốc khánh", "type" => "public"],
        ];

        foreach ($holidays as $holiday) {
            Holiday::updateOrCreate(
                ['date' => $holiday['date']],
                $holiday
            );
        }
    }
}

