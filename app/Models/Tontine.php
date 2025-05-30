<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TontineMemberOrder;
class Tontine extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'amount',
        'deadline',
        'start_date',
        'creator_id',
        'current_beneficiary_id',
        'status',
        'frequency'  // Add this line for yearly/daily/monthly/weekly tracking
    ];
    protected $appends = ['total_contribution_percentage'];
    protected $casts = [
        'deadline' => 'datetime',
        'start_date'=>'datetime',
        'amount' => 'float',
        'frequency' => 'string'  // Add this line to cast the frequency field
    ];
    public function getTotalContributionPercentageAttribute()
    {
        // return $this->members_count;
        if ($this->amount > 0 && $this->contributions && $this->members_count > 0) {
            $totalContributed = ($this->contributions->sum('amount'));
            if ($totalContributed >= ($this->amount*$this->members_count)) {
                return 100;
            }
            $perMemberTarget = $this->amount;
            return round(($totalContributed / ($perMemberTarget * $this->members_count)) * 100, 1);
        }
        return 0;
    }
    public function order()
    {
        return $this->hasMany(TontineMemberOrder::class, 'tontine_id');
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id')->with('profile');
    }

    public function currentBeneficiary()
    {
        return $this->belongsTo(User::class, 'current_beneficiary_id');
    }

    public function members()
    {
        return $this->hasMany(TontineMember::class);
    }

    public function contributions()
    {
        return $this->hasMany(TontineContribution::class)->where('status', 'paid');
    }

    public function invitations()
    {
        return $this->hasMany(TontineInvitation::class);
    }
}
