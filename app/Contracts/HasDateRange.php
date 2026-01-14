<?php

namespace App\Contracts;

use Carbon\Carbon;

interface HasDateRange
{
    /**
     * Get the start date of this assignment.
     */
    public function getStartDate(): Carbon;

    /**
     * Get the end date of this assignment (nullable).
     */
    public function getEndDate(): ?Carbon;
}
