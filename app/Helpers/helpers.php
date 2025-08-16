<?php

use Carbon\CarbonInterval;

if (! function_exists('formatDaysToYearsMonthsDays')) {
    function formatDaysToYearsMonthsDays(int $days): string
    {
        $parts = [];

        // Calculate years and remaining days manually
        $years = intdiv($days, 365);
        $remainingDays = $days % 365;

        if ($years > 0) {
            $parts[] = $years . ' year' . ($years > 1 ? 's' : '');
        }

        // Calculate months and days manually
        $months = intdiv($remainingDays, 30);
        $daysLeft = $remainingDays % 30;

        if ($months > 0) {
            $parts[] = $months . ' month' . ($months > 1 ? 's' : '');
        }

        if ($daysLeft > 0) {
            $parts[] = $daysLeft . ' day' . ($daysLeft > 1 ? 's' : '');
        }

        return implode(', ', $parts) ?: '0 days';
    }
}
