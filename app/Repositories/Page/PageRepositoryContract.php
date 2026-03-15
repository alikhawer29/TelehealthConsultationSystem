<?php

namespace App\Repositories\Page;

interface PageRepositoryContract
{
    public function setModel($model);
    public function create(array $params);
    public function updatePage($id, array $params);
    public function status($id);
}