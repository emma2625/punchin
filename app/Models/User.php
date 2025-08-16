<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Models\Concerns\HasUlids;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, HasName
{
    use HasApiTokens, Notifiable, HasUlids, SoftDeletes;

    /**
     * Use ULID for route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    protected static function booted(): void
    {
        static::creating(function ($user) {
            if (!$user->full_name) {
                $user->full_name = (string) $user->first_name . ' ' . $user->last_name;
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'ulid',
        'first_name',
        'last_name',
        'full_name',
        'email',
        'password',
        'avatar_url',
        'role',
        'company_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => UserRole::class,
    ];

    /**
     * Determine if the user can access Filament admin panel.
     */

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() == 'admin') {
            return $this->role == UserRole::SUPERADMIN;
        }

        return false;
    }

    public function getFilamentName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * User has a company (nullable for Superadmins)
     */
    public function company(): HasOne
    {
        return $this->hasOne(Company::class, 'admin_id', 'id');
    }

    /**
     * User has many clock-in records (only for Staff)
     */
    // public function clockIns(): HasMany
    // {
    //     return $this->hasMany(StaffClockIn::class);
    // }


}
