<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TontineMember extends Pivot
{
    use HasFactory;

    public $table = "tontine_members";
    protected $appends = ['contribution_percentage'];

    protected $fillable = [
        'tontine_id',
        'user_id',
        'is_admin'
    ];

    protected $casts = [
        'is_admin' => 'boolean'
    ];

    public function tontine()
    {
        return $this->belongsTo(Tontine::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contributions()
    {
        return $this->hasMany(TontineContribution::class, 'member_id', 'id')->where('status', 'paid');
    }

    public function order()
    {
        return $this->hasOne(TontineMemberOrder::class, 'member_id');
    }


    public function getContributionPercentageAttribute()
    {
        if ($this->tontine && $this->tontine->amount > 0 && $this->contributions) {
            $totalContributed = $this->contributions->sum('amount');
            return ($totalContributed / $this->tontine->amount) * 100;
        }
        return 0;
    }
}
