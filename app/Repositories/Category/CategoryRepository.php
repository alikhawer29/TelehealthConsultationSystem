<?php

namespace App\Repositories\Category;

use GuzzleHttp\Client;
use App\Core\Traits\SplitPayment;
use Illuminate\Database\Eloquent\Model;
use App\Core\Abstracts\Repository\BaseRepository;

class CategoryRepository extends BaseRepository implements CategoryRepositoryContract
{

    protected $model;
    use SplitPayment;


    public function setModel(Model $model)
    {
        $this->model = $model;
    }

    public function get()
    {
        try {
            $data =   $this->model->get();
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
            $this->model->where('id', $id)
                ->update([
                    'status' => \DB::raw("CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END")
                ]);

            return true;
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

            $client = new Client();
            $response = $client->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'query' => [
                    'latlng' => $conditionalParams['lat'] . ',' . $conditionalParams['lng'],
                    'key' => 'AIzaSyAHPUufTlBkF5NfBT3uhS9K4BbW2N-mkb4',
                ]
            ]);

            $geocodeData = json_decode($response->getBody(), true);

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

            $this->model->create(
                $params
            );
            \DB::commit();
            return true;
        } catch (\Throwable $th) {
            \DB::rollback();
            throw $th;
        }
    }
}
