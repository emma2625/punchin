<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasUlids;

abstract class BaseModel extends Model
{
    use HasUlids;

    /**
     * Use ULID for route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
