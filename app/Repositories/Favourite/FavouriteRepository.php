<?php

namespace App\Repositories\Favourite;

use Carbon\Carbon;
use App\Core\Abstracts\Filters;
use Illuminate\Database\Eloquent\Model;
use App\Core\Abstracts\Repository\BaseRepository;

class FavouriteRepository extends BaseRepository implements FavouriteRepositoryContract

{
    protected $model;
    protected $favourite;

    public function setModel(Model $model)
    {
        $this->model = $model;
        // $this->favourite = new FavouriteRestaurant();
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

    public function getTotalCount(Filters|null $filter = null)
    {
        try {

            $oneWeekAgo = Carbon::now()->subWeek();
            $now = Carbon::now();
            $totalRestaurants = $this->model->filter($filter)->count();
            $lastWeekRestaurants = $this->model->filter($filter)->whereBetween('created_at', [$oneWeekAgo, $now])->count();
            if ($totalRestaurants > 0) {
                $percentageLastWeek = ($lastWeekRestaurants / $totalRestaurants) * 100;
            } else {
                // Handle the case where there are no orders to avoid division by zero
                $percentageLastWeek = 0;
            }

            // $currentMonth = Carbon::now()->month;

            // $totalPlayers = $this->model->filter($filter)->count();
            // $currentMonthPlayers = $this->model->whereMonth('created_at', $currentMonth)->count();
            // if ($totalPlayers > 0) {
            //     $percentageCurrentMonth = ($currentMonthPlayers / $totalPlayers) * 100;
            // } else {
            //     // Handle the case where there are no players to avoid division by zero
            //     $percentageCurrentMonth = 0;
            // }

            return  ['total' => $lastWeekRestaurants, 'trend' => $percentageLastWeek];
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function addFavourite($data)
    {
        try {
            $user = request()->user();

            $message = '';

            // Check if the user already has a favorite for the given branch
            $existingFavorite = $this->model
                ->where('user_id', $user->id)
                ->where('id', $data->id)
                ->first();

            if ($existingFavorite) {
                // If already exists, delete the existing favorite
                $existingFavorite->delete();
                $message = 'successfully removed to favourite';
                // return false; // Indicate that a favorite was removed
            } else {
                $message = 'No Record Found';
            }
            return $message;
            // else {
            //     // If doesn't exist, create a new favorite
            //     $this->model->create([
            //         'user_id' => $user->id,
            //         'supplier_id' => $data->supplier_id,
            //     ]);
            //     return true; // Indicate that a new favorite was added
            // }
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public function getFavourite()
    {
        try {
            $user = request()->user();
            $data = $this->favourite->where('user_id', $user->id)->with('branch.file')->get();
            return $data;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
