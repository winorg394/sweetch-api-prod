<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TontineMemberOrder extends Model
{
    protected $table = 'tontine_members_order';
    protected $fillable = [
        'tontine_id',
        'member_id',
        'position',
        'colleted',
    ];

    public function tontine(): BelongsTo
    {
        return $this->belongsTo(Tontine::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(TontineMember::class, 'member_id');
    }
}
