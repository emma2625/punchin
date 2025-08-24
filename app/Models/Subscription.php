<?php

namespace App\Models;

use App\Models\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Subscription extends BaseModel
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function ($subscription) {
            if (!$subscription->created_by) {
                $subscription->created_by = Auth::id();
            }
        });
    }
    protected $fillable = [
        'ulid',
        'name',
        'price',
        'duration_days',
        'description',
        'staff_limit',
        'features',
        'created_by',
    ];

    protected $casts = [
        'features' => 'array', // Laravel will auto-cast JSON
    ];

    /**
     * Subscription was created by a Superadmin.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
