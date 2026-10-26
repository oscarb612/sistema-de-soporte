<?php

namespace App\Services;

use Carbon\CarbonInterface;

class BusinessDeadlineService
{
    public function addBusinessHours(CarbonInterface $start, int $hours): CarbonInterface
    {
        $deadline = $start->copy();

        while ($hours > 0) {
            $deadline->addHour();
            if (! $deadline->isWeekend()) {
                $hours--;
            }
        }

        return $deadline;
    }
}
