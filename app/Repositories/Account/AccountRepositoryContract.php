<?php

namespace App\Repositories\Account;

use Illuminate\Database\Eloquent\Model;

interface AccountRepositoryContract
{
    public function getProfile();

    public function setModel(Model $model);
    
    public function updateProfile(Array $params);
    
    public function deleteAccount();
}
