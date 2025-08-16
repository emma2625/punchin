<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUlids as ConcernsHasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasUlids
{
    use ConcernsHasUlids;

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public static function fromUlid(string|int|Model $model): ?static
    {
        if ($model instanceof Model) {
            return $model;
        }

        if (Str::isUlid($model)) {
            return static::whereUlid($model)->first();
        }

        return static::find($model);
    }
}
