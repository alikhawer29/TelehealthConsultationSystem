<?php

namespace App\Core\Traits;


trait Filterable{

    public function scopeFilter($query, $filters)
    {   
        if($filters){

            return $filters->apply($query);
        }
        return $query;
    }

}
