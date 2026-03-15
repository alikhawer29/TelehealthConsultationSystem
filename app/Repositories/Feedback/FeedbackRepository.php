<?php

namespace App\Repositories\Feedback;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Core\Abstracts\Repository\BaseRepository;
use App\Repositories\Feedback\FeedbackRepositoryContract;


class FeedbackRepository extends BaseRepository implements FeedbackRepositoryContract
{

    protected $model;

    public function setModel(Model $model)
    {
        $this->model = $model;
    }

    public function create($params)
    {
        try {
            if (Auth::check()) {
                $params['user_id'] = Auth::id(); // or Auth::user()->id
            }

            return $this->model->create($params);
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public function updates($id)
    {
        try {

            return $this->model->where('id', $id)->update([
                'admin_comments' => request('admin_comments'),
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
