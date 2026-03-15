<?php

namespace App\Repositories\Subscription;

use Illuminate\Database\Eloquent\Model;
use App\Core\Abstracts\Repository\BaseRepository;
use App\Models\Subscription;
use App\Repositories\Subscription\SubscriptionRepositoryContract;

class SubscriptionRepository extends BaseRepository implements SubscriptionRepositoryContract
{

    protected $subscription;
    protected $model;

    public function setModel(Model $model)
    {
        $this->model = $model;
        $this->subscription = new Subscription();
    }

    public function getSubscriptions()
    {
        try {
            $user = request()->user();
            $packages = Subscription::with('package')->where('subscribable_id', $user->id)->where('subscribable_type', get_class($user))->get();
            return $packages;
        } catch (\Throwable $th) {
            \DB::rollback();
            throw $th;
        }
    }
}
