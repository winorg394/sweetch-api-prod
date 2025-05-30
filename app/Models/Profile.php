<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

enum IdType: string
{
    case PASSPORT = 'passport';
    case CNI = 'cni';
}

class Profile extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'second_name',
        'whatsapp',
        'id_type',
        'id_verified_at',
        'niu',
        'niu_verified_at',
        'rejection_reason',
        'comment',
        'status',
        'id_number'
    ];

    protected $casts = [
        'id_type' => IdType::class,
        'id_verified_at' => 'datetime',
        'niu_verified_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
