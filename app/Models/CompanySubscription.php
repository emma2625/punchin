<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use App\Models\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class CompanySubscription extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'company_id',
        'subscription_id',
        'start_date',
        'end_date',
        'status', // e.g., active, expired, cancelled
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'status' => SubscriptionStatus::class,
    ];


    protected $with = ['subscription'];
    /**
     * The company that owns this subscription.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * The subscription plan assigned to the company.
     */
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Check if subscription is currently active.
     */
    public function isActive(): bool
    {
        $now = Carbon::now();
        return $this->status === 'active' && $this->start_date <= $now && $this->end_date >= $now;
    }
}
