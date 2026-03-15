<?php

namespace App\Repositories\Payout;

use App\Core\Abstracts\Repository\BaseRepository;
use App\Repositories\Payout\PayoutRepositoryContract;

class PayoutRepository extends BaseRepository implements PayoutRepositoryContract
{

    public function create(array $params)
    {
        \DB::beginTransaction();
        try {

            $this->model->create(
                [
                    'time' => $params['days'],
                ]
            );

            \DB::commit();
            return true;
        } catch (\Throwable $th) {
            \DB::rollback();
            throw $th;
        }
    }

    public function get($params)
    {
        \DB::beginTransaction();
        try {
            $data = $this->model->orderBy('updated_at', 'desc')
                ->when(request()->filled('from') && request()->filled('to'), function ($q) {
                    $q->whereBetween('created_at', [request('from'), request('to')]);
                })
                ->paginate(request('per_page', 10));
            \DB::commit();
            return $data;
        } catch (\Throwable $th) {
            \DB::rollback();
            throw $th;
        }
    }

    public function first()
    {
        \DB::beginTransaction();
        try {
            $data = $this->model->orderBy('created_at', 'desc')->latest()->first();
            \DB::commit();
            return $data;
        } catch (\Throwable $th) {
            \DB::rollback();
            throw $th;
        }
    }
}
