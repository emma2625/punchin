<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use App\Models\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'latitude',
        'longitude',
        'admin_id',
    ];

    /**
     * Company belongs to an admin.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Users (staff) belonging to this company via pivot table.
     */
    public function staff()
    {
        return $this->belongsToMany(User::class, 'company_user', 'company_id', 'user_id')
            ->withTimestamps()
            ->where('role', 'staff');
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * Company has many subscriptions (historical and current).
     */
    public function subscriptions()
    {
        return $this->hasMany(CompanySubscription::class);
    }

    /**
     * Optionally, get the current active subscription.
     */
    public function activeSubscription()
    {
        return $this->hasOne(CompanySubscription::class)
            ->where('status', SubscriptionStatus::ACTIVE)
            ->latest('start_date');
    }


    /**
     * Company has many clock-ins through staff.
     */
    // public function clockIns(): HasMany
    // {
    //     return $this->hasManyThrough(
    //         StaffClockIn::class,
    //         User::class,
    //         'company_id', // Foreign key on users table
    //         'user_id',    // Foreign key on staff_clock_ins table
    //         'id',         // Local key on companies table
    //         'id'          // Local key on users table
    //     );
    // }
}
