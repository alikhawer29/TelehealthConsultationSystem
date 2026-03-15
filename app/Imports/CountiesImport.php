<?php

namespace App\Imports;

use App\Models\Counties;
use Maatwebsite\Excel\Concerns\ToModel;

class CountiesImport implements ToModel
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new Counties([
            'name' => $row[0],
            'state_id' => $row[1],
        ]);
    }
}
