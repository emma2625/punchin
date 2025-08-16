<?php

namespace App\Filament\Pages\Dashboards;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Enums\UserRole;
use BackedEnum;

class Dashboard extends BaseDashboard
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-squares-2x2';

    public function getWidgets(): array
    {
        $widgets = [];

        return $widgets;
    }
}
