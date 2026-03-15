<?php

namespace App\Core\Generators\Graph;

class Coach
{
    private $data = [];

    public function setData($data = [])
    {
        $this->data = $data;
        return $this;
    }

    public function get()
    {
        if (count($this->data) > 0 && !empty($this->data) && is_object($this->data[0]) && isset($this->data[0]->day)) {
            $currentYear = date('Y');
            $currentMonth = date('n');
            // Get the number of days in the current month
            $daysInMonth = date('t', mktime(0, 0, 0, $currentMonth, 1, $currentYear));
            $months = [];
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $months[$day] = 0;
            }
        } else {
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

        foreach ($this->data as $key => $month) {
            if ($month->month) {
                $months[$month->month] += $month->total;
            }
            if ($month->day) {
                $yearString = strval($month->day);
                $months[$yearString] = $month->total;
            }
        }
        collect($months)->each(function ($value, $key) use (&$i, &$logs, $months) {
            $color = 'blue';
            $logs[] =  [strval($key), $value, $color];
            $i++;
        });
        return $logs;
    }
}
