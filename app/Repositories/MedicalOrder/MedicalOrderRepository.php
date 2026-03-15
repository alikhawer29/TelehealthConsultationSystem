<?php

namespace App\Repositories\MedicalOrder;

use GuzzleHttp\Client;
use App\Models\Address;
use App\Core\Abstracts\Filters;
use App\Core\Traits\SplitPayment;
use Illuminate\Database\Eloquent\Model;
use App\Core\Abstracts\Repository\BaseRepository;
use App\Core\Traits\GeoLocation;

class MedicalOrderRepository extends BaseRepository implements MedicalOrderRepositoryContract
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

    public function updateStatus(array $params, $id)
    {
        \DB::beginTransaction();
        try {
            $order = $this->model->findOrFail($id);

            $order->update([
                'status' => $params['status'],
                'reason' => $params['reason'] ?? null,
            ]);

            \DB::commit();
            return $order;
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
                'name' => $params['name'],
                'medicine_name' => $params['medicine_name'],
                'quantity' => $params['quantity'],
                'contact_details' => $params['contact_details'],
                'payment_method' => 'stripe',
                'status' => 'pending',

            ]);
            \DB::commit();
            return $address;
        } catch (\Throwable $th) {
            \DB::rollback();
            throw $th;
        }
    }
}
