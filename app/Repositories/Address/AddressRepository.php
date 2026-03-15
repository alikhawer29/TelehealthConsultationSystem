<?php

namespace App\Repositories\Address;

use GuzzleHttp\Client;
use App\Models\Address;
use App\Core\Abstracts\Filters;
use App\Core\Traits\SplitPayment;
use Illuminate\Database\Eloquent\Model;
use App\Core\Abstracts\Repository\BaseRepository;
use App\Core\Traits\GeoLocation;

class AddressRepository extends BaseRepository implements AddressRepositoryContract
{

    protected $model;
    use SplitPayment;
    use GeoLocation;


    public function setModel(Model $model)
    {
        $this->model = $model;
    }

    public function get()
    {
        try {
            $user = request()->user();
            $data =   $this->model->where('user_id', $user->id)->latest()->first();
            return $data;
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }

    public function status($id)
    {
        try {
            $user = request()->user();
            $this->model->where('user_id', $user->id)
                ->update([
                    'status' => \DB::raw("CASE WHEN id = $id THEN 'active' ELSE 'inactive' END")
                ]);
            $data =   $this->model->where('user_id', $user->id)->latest()->get();


            return $data;
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }

    public function updateAddress($conditionalParams, $id)
    {
        \DB::beginTransaction();
        try {
            $user = request()->user();

            $geocodeData = $this->getGeolocationData($conditionalParams['lat'], $conditionalParams['lng']);

            if (isset($geocodeData['error'])) {
                throw new \Exception($geocodeData['error']);
            }

            $city = null;
            $country = null;


            foreach ($geocodeData['results'][0]['address_components'] as $component) {
                if (in_array('locality', $component['types'])) {
                    $city = $component['long_name'];
                } elseif (in_array('country', $component['types'])) {
                    $country = $component['long_name'];
                }

                // Break the loop if both city and country are found
                if ($city !== null && $country !== null) {
                    break;
                }
            }


            $address =  $this->model->where('id', $id)->update([
                'lat' =>  $conditionalParams['lat'],
                'lng' =>  $conditionalParams['lng'],
                'address' =>  $city . ',' . $country,
            ]);

            \DB::commit();
            return $address;
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }

    public function create(array $params)
    {
        \DB::beginTransaction();
        try {

            $user = request()->user();
            $address = $this->model->create([
                'user_id' => $user->id, // Make sure this corresponds to an existing user ID
                'lat' => $params['lat'],
                'lng' => $params['lng'],
                'address' => $params['address'],
                'building_name' => $params['building_name'],
                'flat_no' => $params['flat_no'],

            ]);

            $this->model->where('id', '!=', $address->id)->where('user_id', $user->id)->update(['status' => 'inactive']);
            \DB::commit();
            return $address;
        } catch (\Throwable $th) {
            \DB::rollback();
            throw $th;
        }
    }
}
