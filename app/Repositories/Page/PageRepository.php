<?php

namespace App\Repositories\Page;

use App\Models\Media;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Core\Abstracts\Repository\BaseRepository;

class PageRepository extends BaseRepository implements PageRepositoryContract
{
    protected $model;

    public function setModel($model)
    {
        $this->model = $model;
    }

    public function create(array $params)
    {
        DB::beginTransaction();

        try {
            $page = $this->model->create([
                'name' => $params['name'],
                'slug' => $params['slug'],
                'title' => $params['title'],
                'description' => $params['description'],
                'status' => $params['status'] ?? 1,
            ]);

            DB::commit();
            return $page;
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function updatePage($id, array $params)
    {
        DB::beginTransaction();

        try {
            $page = $this->model->findOrFail($id);

            $page->update([
                'name' => $params['name'],
                'slug' => $params['slug'],
                'title' => $params['title'],
                'description' => $params['description'],
                'status' => $params['status'] ?? $page->status,
                'banner_image' => $params['banner_image'] ?? null,

            ]);

            DB::commit();
            return $page;
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function status($id)
    {
        try {
            $page = $this->model->findOrFail($id);
            $page->update([
                'status' => $page->status == 1 ? 0 : 1
            ]);

            return $page;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
