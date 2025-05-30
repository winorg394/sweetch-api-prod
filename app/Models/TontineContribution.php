<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TontineContribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'tontine_id',
        'member_id',
        'amount',
        'status',
        'paid_at'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'amount' => 'float'
    ];

    public function tontine()
    {
        return $this->belongsTo(Tontine::class);
    }

    public function member()
    {
        return $this->belongsTo(TontineMember::class,'member_id','id');
    }
}
