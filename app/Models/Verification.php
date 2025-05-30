<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

enum VerificationType: string
{
    case EMAIL = 'email';
    case PHONE = 'phone';
    case PASSWORD_RESET = 'password_reset';
}

class Verification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'otp_code',
        'expires_at',
    ];

    protected $casts = [
        'type' => VerificationType::class,
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
