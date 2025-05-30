<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use NYCorp\Finance\Traits\FinanceAccountTrait;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Pivot;
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable,HasApiTokens;
    use FinanceAccountTrait;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'phone',
        'password',
        'email_verified_at',
        'phone_verified_at'
    ];
    protected $appends = ['balance'];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function getCurrency()
    {
        // Implement your logic to get currency here the default value is set in the finance config file
        return \NYCorp\Finance\Http\Core\ConfigReader::getDefaultCurrency();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's profile.
     */
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function tontines()
    {
        return $this->belongsToMany(Tontine::class, 'tontine_members')
            ->using(TontineMember::class)
            ->withPivot('is_admin');
    }

    public function tontineMemberships()
    {
        return $this->hasMany(TontineMember::class);
    }

    public function tontineContributions()
    {
        return $this->hasManyThrough(TontineContribution::class, TontineMember::class);
    }

    public function tontineInvitations()
    {
        return $this->hasMany(TontineInvitation::class, 'invited_user_id');
    }
}
