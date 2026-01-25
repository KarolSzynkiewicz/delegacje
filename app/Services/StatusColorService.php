<?php

namespace App\Services;

use App\Enums\AssignmentStatus;
use App\Enums\ProjectStatus;
use App\Enums\VehiclePosition;

class StatusColorService
{
    /**
     * Mapowanie statusów przypisań na kolory Bootstrap
     */
    public static function getAssignmentStatusColor($status): string
    {
        if ($status instanceof AssignmentStatus) {
            $statusValue = $status->value;
        } else {
            $statusValue = $status;
        }

        return match($statusValue) {
            'active' => 'success',
            'completed' => 'primary',
            'cancelled' => 'danger',
            'in_transit' => 'warning',
            'at_base' => 'secondary',
            default => 'secondary'
        };
    }

    /**
     * Mapowanie statusów projektów na kolory Bootstrap
     */
    public static function getProjectStatusColor($status): string
    {
        if ($status instanceof ProjectStatus) {
            $statusValue = $status->value;
        } else {
            $statusValue = $status;
        }

        return match($statusValue) {
            'active' => 'success',
            'completed' => 'primary',
            'cancelled' => 'danger',
            'pending' => 'warning',
            'on_hold' => 'warning',
            default => 'secondary'
        };
    }

    /**
     * Mapowanie stanu technicznego pojazdu na kolory Bootstrap
     */
    public static function getVehicleConditionColor(string $condition): string
    {
        return match($condition) {
            'excellent' => 'success',
            'good' => 'primary',
            'fair' => 'warning',
            'poor' => 'warning',
            'workshop' => 'danger',
            default => 'secondary'
        };
    }

    /**
     * Mapowanie pozycji w pojeździe na kolory Bootstrap
     */
    public static function getVehiclePositionColor($position): string
    {
        if ($position instanceof VehiclePosition) {
            $positionValue = $position->value;
        } else {
            $positionValue = $position;
        }

        return match($positionValue) {
            'driver' => 'success',
            'passenger' => 'secondary',
            default => 'secondary'
        };
    }

    /**
     * Mapowanie statusu rotacji na kolory Bootstrap
     */
    public static function getRotationStatusColor(string $status): string
    {
        return match($status) {
            'active' => 'success',
            'completed' => 'primary',
            'cancelled' => 'danger',
            'upcoming' => 'info',
            default => 'secondary'
        };
    }

    /**
     * Mapowanie statusu dokumentu na kolory Bootstrap
     */
    public static function getDocumentStatusColor(string $status): string
    {
        return match($status) {
            'active', 'valid' => 'success',
            'expired', 'invalid' => 'danger',
            'expiring_soon' => 'warning',
            default => 'secondary'
        };
    }
}
