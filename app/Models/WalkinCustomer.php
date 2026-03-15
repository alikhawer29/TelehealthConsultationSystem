<?php

namespace App\Models;

use App\Core\Traits\Fileable;
use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;


class WalkinCustomer extends Model
{

    use HasFactory, SoftDeletes, Filterable, Fileable;

    protected $table = 'walk_in_customers';

    protected $fillable = [
        'customer_name',
        'company',
        'address',
        'city',
        'designation',
        'mobile_number',
        'mobile_country_code',
        'telephone_number',
        'telephone_country_code',
        'fax_number',
        'fax_country_code',
        'email',
        'id_type',
        'id_number',
        'issue_date',
        'expiry_date',
        'issue_place',
        'nationality',
        'status',
        'vat_trn',
        'vat_country',
        'vat_state',
        'vat_exempted',
        'created_by',
        'edited_by',
        'parent_id',
        'status',
        'branch_id'
    ];

    protected $images = [
        'image' => 'multiple',
    ];

    protected $imageable = ['image'];



    protected $hidden = [
        // 'created_at',
        // 'updated_at',
        'deleted_at',
    ];

    protected $appends = [
        'mobile_number_full',
        'telephone_number_full',
        'fax_number_full'
    ];

    public function mobileNumberFull(): Attribute
    {
        return Attribute::make(
            get: fn() => "{$this->mobile_country_code}{$this->mobile_number}"
        );
    }

    public function telephoneNumberFull(): Attribute
    {
        return Attribute::make(
            get: fn() => "{$this->telephone_country_code}{$this->telephone_number}"
        );
    }

    public function faxNumberFull(): Attribute
    {
        return Attribute::make(
            get: fn() => "{$this->fax_country_code}{$this->fax_number}"
        );
    }


    public function city(): HasOne
    {
        return $this->hasOne(City::class, 'id', 'city');
    }
    public function nationality(): HasOne
    {
        return $this->hasOne(Country::class, 'id', 'nationality');
    }
    public function nationalities(): HasOne
    {
        return $this->hasOne(Country::class, 'id', 'nationality');
    }
    public function vat_country(): HasOne
    {
        return $this->hasOne(Country::class, 'id', 'vat_country');
    }
    public function vat_state(): HasOne
    {
        return $this->hasOne(State::class, 'id', 'vat_state');
    }
    public function created_by(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }
    public function creator(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }
    public function editor(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'edited_by');
    }
    public function parent_id(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'parent_id');
    }

    public function id_type(): HasOne
    {
        return $this->hasOne(ClassificationMaster::class, 'id', 'id_type');
    }
    public function id_types(): HasOne
    {
        return $this->hasOne(ClassificationMaster::class, 'id', 'id_type');
    }
}
