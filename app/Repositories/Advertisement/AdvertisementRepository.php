<?php

namespace App\Repositories\Advertisement;

use App\Core\Traits\SplitPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Core\Abstracts\Repository\BaseRepository;

class AdvertisementRepository extends BaseRepository implements AdvertisementRepositoryContract
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

    public function create(array $params)
    {
        DB::beginTransaction();

        try {

            $user = request()->user();

            $data = array_merge($params, [
                'status' => 'pending',
                'advertisement_status' => 'inactive',
                'payment_status' => 'unpaid',
                'expiry_date' => '2025-03-31',
                'user_id' => $user->id,
            ]);

            $result = $this->model->create($data);

            DB::commit();
            return $result;
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
}
