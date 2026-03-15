<?php

namespace App\Repositories\Review;

use App\Models\Quotation;
use App\Models\Appointment;
use App\Core\Abstracts\Repository\BaseRepository;
use App\Repositories\Review\ReviewRepositoryContract;

class ReviewRepository extends BaseRepository implements ReviewRepositoryContract
{

    public function create(array $params)
    {
        extract($params);
        try {
            $review = $this->model->where('appointment_id', $params['appointment_id'])
                ->where('user_id', $params['user_id'])
                ->first();

            if ($review) {
                throw new \Exception('you\'ve already posted rating for this appointment');
            } else {
                $get_owner = Appointment::find($params['appointment_id']);

                $this->model->create(
                    [
                        'user_id' => $params['user_id'],
                        'appointment_id' => $params['appointment_id'],
                        'reviewable_type' => $get_owner->bookable_type,
                        'reviewable_id' => $get_owner->bookable_id,
                        'rating' => $params['rating'],
                        'review' => $params['review'],
                        'status' => 1,
                    ]
                );

                return true;
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
