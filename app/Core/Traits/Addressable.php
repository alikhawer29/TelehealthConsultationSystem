<?php 
namespace App\Core\Traits;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait Addressable {
 
    public function country(): BelongsTo {
        return $this->belongsTo(Country::class,'country_id','id');
    }

    public function state(): BelongsTo {
        return $this->belongsTo(State::class,'state_id','id');
    }

    public function city(): BelongsTo {
        return $this->belongsTo(City::class,'city_id','id');
    }
}