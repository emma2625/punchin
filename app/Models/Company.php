<?php

namespace App\Models;

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
     * Company has many staff users.
     */
    public function staff(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'staff');
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
