<?php

namespace App\Repositories\Commissions;

use Illuminate\Database\Eloquent\Model;
use App\Core\Abstracts\Repository\BaseRepository;
use App\Repositories\Commissions\CommissionRepositoryContract;

class CommissionRepository extends BaseRepository implements CommissionRepositoryContract
{
    protected $model;


    public function setModel(Model $model)
    {
        $this->model = $model;
    }


    public function updateCommission(array $params)
    {

        \DB::beginTransaction();
        try {

            foreach ($params['selected_suppliers'] as $supplier) {
                $this->model->updateOrCreate(
                    [
                        'supplier_id' => $supplier, // Matching condition (supplier_id)
                    ],
                    [
                        'rate' => $params['rate'], // Values to be updated or inserted
                        'effective_date' => $params['effective_date'],
                        'expiration_date' => $params['expiration_date'],
                        'status' => 1,
                    ]
                );
            }




            \DB::commit();
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }

    public function createPackage(array $params)
    {

        \DB::beginTransaction();
        try {

            $user = strtolower(class_basename(get_class(auth()->user())));

            $this->model->create(
                [
                    'rate' => $params['rate'],
                    'effective_date' => now(),
                    'user_type' => $params['user_type'],
                    'user_type' => $params['user_type'],
                    // 'vendor_id' => $user == 'admin' ? 0 : auth()->user()->id,
                    'status' => 1,
                    // 'rate_type' => $params['rate_type']
                ]
            );

            \DB::commit();
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }

    public function createCommission($data)
    {

        \DB::beginTransaction();
        try {

            $defaultCommission = $this->model->where('supplier_id', 0)->first();

            $defaultCommission->update([
                'rate' => $data['rate'], // Use the new rate from $data
                'effective_date' => now(),
                'status' => 1,
            ]);

            \DB::commit();
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }


    public function getFirst()
    {
        return $this->model->latest()->first();
    }
    public function getFirstCommission()
    {
        return $this->model->where('supplier_id', 0)->latest()->first();
    }
}
