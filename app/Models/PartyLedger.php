<?php

namespace App\Models;

use App\Core\Traits\Fileable;
use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PartyLedger extends Model
{
    use HasFactory, SoftDeletes, Filterable, Fileable;

    protected $table = 'party_ledgers';

    protected $fillable = [
        'account_code',
        'account_title',
        'rtl_title',
        'classification',
        'central_bank_group',
        'debit_posting_account',
        'credit_posting_account',
        'status',
        'offline_iwt_entry',
        'money_service_agent',
        'office',
        'debit_limit',
        'credit_limit',
        'company_name',
        'address',
        'telephone_number',
        'country_code',
        'fax_code',
        'fax',
        'email',
        'contact_person',
        'mobile_number',
        'mobile_country_code',
        'nationality',
        'entity',
        'id_type',
        'id_number',
        'issue_date',
        'valid_upto',
        'issue_place',
        'vat_trn',
        'vat_country',
        'vat_state',
        'vat_exempted',
        'outward_tt_commission',
        'created_by',
        'edited_by',
        'parent_id',
        'branch_id'
    ];

    protected $casts = [
        'office' => 'integer',
        'credit_limit' => 'integer',
        'debit_limit' => 'integer'
    ];

    protected $hidden = [
        // 'created_at',
        // 'updated_at',
        'deleted_at',
    ];

    protected $appends = [
        'telephone_no',
        'mobile_no',
        'fax_no'
    ];

    protected $images = [
        'image' => 'multiple',
    ];

    protected $imageable = ['image'];

    public function telephoneNo(): Attribute
    {
        return Attribute::make(
            get: fn() => "{$this->country_code}{$this->telephone_number}"
        );
    }

    public function mobileNo(): Attribute
    {
        return Attribute::make(
            get: fn() => "{$this->mobile_country_code}{$this->mobile_number}"
        );
    }

    public function faxNo(): Attribute
    {
        return Attribute::make(
            get: fn() => "{$this->mobile_country_code}{$this->fax}"
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

    public function central_bank_group(): HasOne
    {
        return $this->hasOne(CBClassificationMaster::class, 'id', 'central_bank_group');
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
    public function vat_country(): HasOne
    {
        return $this->hasOne(Country::class, 'id', 'vat_country');
    }

    public function vat_state(): HasOne
    {
        return $this->hasOne(State::class, 'id', 'vat_state');
    }

    public function id_type(): HasOne
    {
        return $this->hasOne(ClassificationMaster::class, 'id', 'id_type');
    }

    public function officeLocation(): HasOne
    {
        return $this->hasOne(OfficeLocationMaster::class, 'id', 'office');
    }

    public function classifications(): HasOne
    {
        return $this->hasOne(PartyLedgerClassification::class, 'id', 'classification');
    }
}
