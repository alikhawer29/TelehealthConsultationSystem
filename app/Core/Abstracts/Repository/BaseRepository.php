<?php

namespace App\Core\Abstracts\Repository;

use App\Core\Abstracts\Filters;
use App\Models\Notification;
use App\Repositories\Notification\NotificationRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

abstract class BaseRepository
{
    protected $model;
    protected $countRelations = [];

    public function setModel(Model $model)
    {

        $this->model = $model;
        return $this;
    }
    public function withCount($relations = [])
    {
        $this->countRelations = $relations;
        return $this;
    }

    public function findAll(Filters|null $filter = null, array $relations = [])
    {
        try {
            return $this->model->withCount($this->countRelations)->with($relations)->filter($filter)->get();
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function multiWhere(array $param, array $paramTwo)
    {
        try {
            return $this->model->where($param[0], $param[1])->where($paramTwo[0], $paramTwo[1])->first();
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function where(array $param)
    {
        try {
            return $this->model->where($param[0], $param[1])->first();
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public function findById(int $id, array $relations = [], Filters|null $filter = null)
    {
        try {
            return $this->model->withCount($this->countRelations)->with($relations)->filter($filter)->findOrFail($id);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function findByAnotherId(string $param, int $id, array $relations = [], Filters|null $filter = null)
    {
        try {
            return $this->model->withCount($this->countRelations)->with($relations)->where($param, $id)->get();
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function findOne(array $relations = [], Filters|null $filter = null)
    {
        try {
            return $this->model->withCount($this->countRelations)->with($relations)->filter($filter)->latest()->first();
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    public function findLatest(array $relations = [], Filters|null $filter = null)
    {
        try {
            return $this->model->latest()->first();
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function paginate(int $perPage = 10, array $relations = [], Filters|null $filter = null)
    {
        try {
            return $this->model->withCount($this->countRelations)->with($relations)->filter($filter)->paginate($perPage);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function create(array $params)
    {

        try {
            return $this->model->create($params);
        } catch (\Throwable $th) {
            throw $th;
        }
    }



    public function update(int $id, array $params, Filters|null $filter = null)
    {
        try {
            $model = $this->findById($id, filter: $filter);
            $model->update($params);
            return $model;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function delete(int $id, Filters|null $filter = null)
    {
        try {
            $model = $this->findById($id, filter: $filter);
            $model->delete();
            return true;
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public function getTotal(Filters|null $filter = null)
    {
        try {
            return $this->model->filter($filter)->count();
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function notification()
    {
        return (new NotificationRepository())->setModel(new Notification());
    }
}
