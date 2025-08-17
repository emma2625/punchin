<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Branch extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'ulid',
        'company_id',
        'name',
        'location',
    ];

    /**
     * Branch belongs to a company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Branch has many staff (many-to-many)
     */
    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'branch_user', 'branch_id', 'user_id')
                    ->withTimestamps()
                    ->where('role', UserRole::STAFF);
    }
}
