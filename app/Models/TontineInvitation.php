<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TontineInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'tontine_id',
        'invited_by_id',
        'invited_user_id',
        'status'
    ];

    public function tontine()
    {
        return $this->belongsTo(Tontine::class)->withCount('members');
    }

    public function invitedBy()
    {
        return $this->belongsTo(User::class, 'invited_by_id');
    }

    public function invitedUser()
    {
        return $this->belongsTo(User::class, 'invited_user_id');
    }
}
