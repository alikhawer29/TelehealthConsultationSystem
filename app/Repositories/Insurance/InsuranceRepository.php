<?php

namespace App\Repositories\Insurance;

use App\Models\User;
use App\Models\Media;
use App\Models\Insurance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Core\Abstracts\Repository\BaseRepository;
use App\Repositories\Feedback\FeedbackRepositoryContract;


class InsuranceRepository extends BaseRepository implements InsuranceRepositoryContract
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

    public function status($id, $status)
    {
        try {
            // Fetch insurance with file relation and update status
            $insurance = Insurance::with('file')->findOrFail($id);
            $insurance->status = $status;
            $insurance->save();

            // Delete associated file if status is Rejected (2)
            if ($status == 2 && $insurance->file) {
                Media::find($insurance->file->id)?->delete();
            }

            // Determine notification content based on status
            $notifyStatus = $status == 2 ? 'Rejected' : 'Approved';
            $notifyBody = $status == 2
                ? 'Your insurance request has been rejected.'
                : 'Your insurance request has been approved.';

            // Fetch user
            $user = User::find($insurance->user_id);

            // Send notification
            $this->notification()->send(
                $user,
                title: 'Insurance Status Update',
                body: $notifyBody,
                sound: 'customSound',
                id: $appointment->id,
                data: [
                    'id' => $insurance->id,
                    'type' => 'insurance',
                    'status' => $notifyStatus,
                    'sound'  => 'customSound',
                ]
            );

            return true;
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }
}
