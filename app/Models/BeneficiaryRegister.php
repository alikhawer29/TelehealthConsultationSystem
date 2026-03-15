<?php

namespace App\Models;

use App\Core\Traits\Fileable;
use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeneficiaryRegister extends Model
{
    use HasFactory, SoftDeletes, Filterable, Fileable;

    protected $table = 'beneficiary_register';

    protected $fillable = [
        'account',
        'type',
        'name',
        'company',
        'address',
        'nationality',
        'contact_no',
        'country_code',
        'bank_name',
        'bank_account_number',
        'swift_bic_code',
        'routing_number',
        'iban',
        'bank_address',
        'city',
        'country',
        'corresponding_bank',
        'corresponding_bank_account_number',
        'corresponding_swift_bic_code',
        'corresponding_routing_number',
        'corresponding_iban',
        'purpose',
        'branch',
        'ifsc_code',
        'created_by',
        'edited_by',
        'parent_id',
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
        'contact_number',
    ];

    public function contactNumber(): Attribute
    {
        return Attribute::make(
            get: fn() => "{$this->country_code}{$this->contact_no}"
        );
    }

    public function creator(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }
    public function editor(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'edited_by');
    }
    public function nationality(): HasOne
    {
        return $this->hasOne(Country::class, 'id', 'nationality');
    }
    public function nationalities(): HasOne
    {
        return $this->hasOne(Country::class, 'id', 'nationality');
    }
    public function city(): HasOne
    {
        return $this->hasOne(City::class, 'id', 'city');
    }
    public function country(): HasOne
    {
        return $this->hasOne(CountryRegister::class, 'id', 'country');
    }
    public function countries(): HasOne
    {
        return $this->hasOne(CountryRegister::class, 'id', 'country');
    }
    public function purpose(): HasOne
    {
        return $this->hasOne(ClassificationMaster::class, 'id', 'purpose');
    }
    public function purposes(): HasOne
    {
        return $this->hasOne(ClassificationMaster::class, 'id', 'purpose');
    }

    public function partyAccount(): HasOne
    {
        return $this->hasOne(PartyLedger::class, 'id', 'account');
    }

    public function walkinAccount(): HasOne
    {
        return $this->hasOne(WalkinCustomer::class, 'id', 'account');
    }
}
