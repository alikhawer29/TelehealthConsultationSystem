<?php

namespace App\Repositories\Speciality;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Branch;
use App\Models\Package;
use App\Models\UserBranch;
use App\Models\Subscription;
use App\Models\UserTimeSlot;
use App\Core\Abstracts\Filters;
use App\Models\AccessManagement;
use App\Models\AccountsPermission;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Core\Abstracts\Repository\BaseRepository;

class SpecialityRepository extends BaseRepository implements SpecialityRepositoryContract

{
    protected $model;

    public function setModel(Model $model)
    {
        $this->model = $model;
    }





    public function status($id)
    {
        try {
            $user = request()->user();
            $data = $this->model->where('id', $id)
                ->update([
                    'status' => \DB::raw("CASE WHEN status = 1 THEN 0 ELSE 1 END")
                ]);

            return $data;
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }


    public function totalUsers(Filters|null $filter = null)
    {
        try {

            $totalUsers = $this->model->filter($filter)->where('role', 'user')->count();
            return  $totalUsers;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function totalEarnings(Filters|null $filter = null)
    {
        try {
            // Apply filters if provided
            $query = Subscription::query();

            if ($filter) {
                $query = $filter->apply($query);
            }

            $totalEarnings = $query->with('package') // Eager load the related package
                ->get()
                ->sum(function ($subscription) {
                    // Calculate the price based on the type of subscription
                    if ($subscription->type === 'yearly') {
                        return $subscription?->package?->price_yearly;
                    } elseif ($subscription->type === 'monthly') {
                        return $subscription?->package?->price_monthly;
                    }
                    return 0; // Default to 0 if the type is unknown
                });

            return $totalEarnings;
        } catch (\Throwable $th) {
            throw $th;
        }
    }




    public function getLapsAccount(Filters|null $filter = null)
    {
        try {
            $oneWeekAgo = Carbon::now()->subWeek(); // One week ago from now
            $now = Carbon::now(); // Current time

            // Total users count
            $totalUsers = $this->model->filter($filter)->count();

            // Count users whose `updated_at` was not updated in the last week
            $inactiveUsers = $this->model
                ->filter($filter)
                ->where('updated_at', '<', $oneWeekAgo) // Last updated before one week ago
                ->count();

            return ['total' => $inactiveUsers, 'inactive' => $inactiveUsers];
        } catch (\Throwable $th) {
            throw $th;
        }
    }



    public function getTotalCount(Filters|null $filter = null)
    {
        try {
            $oneWeekAgo = Carbon::now()->subWeek();
            $now = Carbon::now();
            $totalUsers = $this->model->filter($filter)->count();
            $lastWeekUsers = $this->model->filter($filter)->whereBetween('created_at', [$oneWeekAgo, $now])->count();
            if ($totalUsers > 0) {
                $percentageLastWeek = ($lastWeekUsers / $totalUsers) * 100;
            } else {
                // Handle the case where there are no orders to avoid division by zero
                $percentageLastWeek = 0;
            }

            return  ['total' => $totalUsers, 'trend' => $percentageLastWeek];
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
