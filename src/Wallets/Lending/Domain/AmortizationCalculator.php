<?php

namespace Wallets\Lending\Domain;

use App\Models\Holiday;
use App\Models\LoanCustomSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class AmortizationCalculator
{
    public function calculate(
        $loanId,
        $principal,
        $annualRate,
        $months,
        $startDate,
        $fixedMonthlyPayment = null,
        $method = 'monthly',
    ): Collection {
        $balance = $principal;
        $schedule = collect([]);
        $prevDate = Carbon::parse($startDate);

        if ($method === 'custom') {
            $rows = LoanCustomSchedule::where('loan_id', $loanId)->orderBy('month_index')->get();
            if ($rows->isEmpty()) {
                return $schedule;
            }

            $balance = $principal;
            $result = [];
            foreach ($rows as $row) {
                $date = $row->paid_at ? Carbon::parse($row->paid_at) : Carbon::now();
                $principalPayment = (float) $row->principal;
                $interest = (float) $row->interest;
                $fee = (float) ($row->fee ?? 0);
                $payment = $row->payment ?? ($principalPayment + $interest + $fee);
                $balance = max($balance - $principalPayment, 0);

                $result[] = [
                    'month_index' => $row->month_index,
                    'date' => $date,
                    'theoretical_date' => $date,
                    'is_adjusted' => false,
                    'days' => null,
                    'payment' => $payment,
                    'interest' => $interest,
                    'principal' => $principalPayment,
                    'fee' => $fee,
                    'remaining_principal' => $balance,
                ];
            }

            return collect($result);
        }

        if (! $fixedMonthlyPayment) {
            $monthlyRate = ($annualRate / 100) / 12;
            $fixedMonthlyPayment = ($principal * $monthlyRate) / (1 - pow(1 + $monthlyRate, -$months));
        }

        for ($i = 1; $i <= $months; $i++) {
            $theoreticalDate = Carbon::parse($startDate)->addMonths($i);
            $actualDate = $method === 'daily'
                ? $this->adjustForNonWorkingDays($theoreticalDate)
                : $theoreticalDate->copy();

            if ($method === 'daily') {
                $days = $prevDate->diffInDays($actualDate);
                $interest = round($balance * ($annualRate / 100) * $days / 365, 0);
            } else {
                $monthlyRate = ($annualRate / 100) / 12;
                $interest = round($balance * $monthlyRate, 0);
                $days = $prevDate->diffInDays($actualDate);
            }

            $payment = round($fixedMonthlyPayment, 0);

            if ($i == $months && $balance + $interest < $payment + 100000) {
                $payment = $balance + $interest;
            }

            $principalPayment = round($payment - $interest, 0);

            if ($principalPayment > $balance) {
                $principalPayment = $balance;
                $payment = $interest + $principalPayment;
            }

            $balance = round($balance - $principalPayment, 0);
            if ($balance < 0) {
                $balance = 0;
            }

            $schedule->push([
                'month_index' => $i,
                'date' => $actualDate->copy(),
                'theoretical_date' => $theoreticalDate->copy(),
                'is_adjusted' => ! $theoreticalDate->isSameDay($actualDate),
                'days' => $days,
                'payment' => $payment,
                'interest' => $interest,
                'principal' => $principalPayment,
                'remaining_principal' => $balance,
            ]);

            $prevDate = $actualDate;
        }

        return $schedule;
    }

    public function adjustForNonWorkingDays($date)
    {
        static $holidays = null;
        if ($holidays === null) {
            $holidays = Holiday::pluck('date')->map(fn ($d) => $d->format('Y-m-d'))->toArray();
        }

        $adjustedDate = $date->copy();
        $iterations = 0;

        while ($iterations < 10) {
            $dayOfWeek = $adjustedDate->dayOfWeek;
            $dateString = $adjustedDate->format('Y-m-d');

            if ($dayOfWeek == Carbon::SATURDAY || $dayOfWeek == Carbon::SUNDAY || in_array($dateString, $holidays)) {
                $adjustedDate->addDay();
                $iterations++;

                continue;
            }

            break;
        }

        return $adjustedDate;
    }
}
