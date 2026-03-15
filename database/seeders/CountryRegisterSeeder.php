<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CountryRegisterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run($user_id, $branch_id)
    {

        $countries = Country::all()->map(function ($country) use ($user_id, $branch_id) {
            return [
                'branch_id' => $branch_id,
                'country' => $country->name,
                'code' => $country->iso3,
                'created_by' => $user_id,
                'parent_id' => $user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        DB::table('country_register')->insert($countries);
    }
}
