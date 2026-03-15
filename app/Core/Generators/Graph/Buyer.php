<?php

namespace App\Core\Generators\Graph;

class Buyer
{
    private $data = [];

    public function setData($data = [])
    {

        $this->data = $data;
        return $this;
    }

    public function get()
    {

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
        // }
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
            // $logs[] =  [strval($key), $value, $color];
            $logs[] =  $value;
            $i++;
        });
        return $logs;
    }
}
