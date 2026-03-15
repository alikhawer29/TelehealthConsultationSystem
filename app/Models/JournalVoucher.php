<?php

namespace App\Models;

use App\Core\Traits\Fileable;
use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalVoucher extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected $fillable = [
        'ledger',
        'account_id',
        'narration',
        'currency_id',
        'fc_amount',
        'rate',
        'lc_amount',
        'sign',
        'created_by',
        'branch_id',
        'business_id',
        'edited_by',
        'voucher_id',
        'modification_status'
    ];

    protected $appends = [
        'account_details',
        'attachments'
    ];

    protected $imageable = ['image'];
    protected $images = [
        'image' => 'multiple',
    ];

    public function getAccountDetailsAttribute()
    {
        return match ($this->ledger) {
            'party' => PartyLedger::select('id', 'account_title as title')->find($this->account_id),
            'walkin' => WalkinCustomer::select('id', 'customer_name as title')->find($this->account_id),
            'general' => ChartOfAccount::select('id', 'account_name as title')->find($this->account_id),
            default => null,
        };
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucher_id')->where('modification_status', 'active');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getAttachmentsAttribute()
    {
        $exists = Media::where('fileable_type', 'App\Models\Voucher')
            ->where('fileable_id', $this->voucher_id)
            ->exists(); // Efficient check

        return $exists ? 'yes' : 'no';
    }

    public function currency()
    {
        return $this->belongsTo(CurrencyRegister::class, 'currency_id');
    }
}
