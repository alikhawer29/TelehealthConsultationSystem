<?php

namespace App\Core\Generators\Graph;

use Illuminate\Support\Carbon;

class Appointment
{
    private $data = [];

    public function setData($data = [])
    {
        $this->data = $data;
        return $this;
    }

    public function get()
    {

        // For monthly
        if (request('type') === 'monthly' && count($this->data) > 0 && !empty($this->data) && is_object($this->data[0]) && isset($this->data[0]->day)) {
            $currentYear = date('Y');
            $currentMonth = date('m');
            // Get the number of days in the current month
            $daysInMonth = date('t', mktime(0, 0, 0, $currentMonth, 1, $currentYear));
            // Initialize the array with keys for the dates of the current month
            $months = [];
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
                $months[$date] = 0;
            }
        }
        // For yearly
        elseif (request('type') === 'yearly' && count($this->data) > 0 && !empty($this->data) && is_object($this->data[0]) && isset($this->data[0]->month)) {
            $months = [
                'January' => 0,
                'February' => 0,
                'March' => 0,
                'April' => 0,
                'May' => 0,
                'June' => 0,
                'July' => 0,
                'August' => 0,
                'September' => 0,
                'October' => 0,
                'November' => 0,
                'December' => 0,
            ];
        }
        // For past 6 months
        elseif (request('type') === 'past6months' && count($this->data) > 0 && !empty($this->data) && is_object($this->data[0]) && isset($this->data[0]->month)) {

            // Get the current month
            $currentMonth = Carbon::now();

            // Create an array with the last 6 months, each initialized to 0
            $months = [];
            for ($i = 0; $i < 7; $i++) {
                $monthLabel = $currentMonth->format('F'); // Get the current month label
                $months[$monthLabel] = 0; // Initialize with 0
                $currentMonth->subMonth(); // Move to the previous month
            }
            $months = array_reverse($months);
        }
        // Default case for unrecognized types
        else {
            $months = [
                'January' => 0,
                'February' => 0,
                'March' => 0,
                'April' => 0,
                'May' => 0,
                'June' => 0,
                'July' => 0,
                'August' => 0,
                'September' => 0,
                'October' => 0,
                'November' => 0,
                'December' => 0,
            ];
        }
        $logs = [];
        $i = 0;
        $currentDate = Carbon::now();
        foreach ($this->data as $key => $month) {
            if ($month->month) {
                $months[$month->month] = $month->total;
            }
            if ($month->day) {
                $newDate = $currentDate->format('Y-m-') . sprintf('%02d', $month->day);
                if (array_key_exists($newDate, $months)) {
                    $months[$newDate] = $month->total; // Update the total for the respective date
                }
            }

            if ($month->date) {
                $yearString = strval($month->date);
                $months[$yearString] = $month->total;
            }
        }

        collect($months)->each(function ($value, $key) use (&$i, &$logs, $months) {
            $logs[] = [strval($key), $value];
            $i++;
        });

        return $logs;
    }
}
