<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Domain\ValueObject;

/**
 * Asset Status enumeration
 * 
 * Represents the current operational status of an asset,
 * determining its availability for assignment and billing.
 */
enum AssetStatus: string
{
    case AVAILABLE = 'available';
    case IN_SERVICE = 'in_service';
    case MAINTENANCE = 'maintenance';
    case OUT_OF_SERVICE = 'out_of_service';
    case RETIRED = 'retired';

    public function getDisplayName(): string
    {
        return match($this) {
            self::AVAILABLE => 'Available',
            self::IN_SERVICE => 'In Service',
            self::MAINTENANCE => 'Maintenance',
            self::OUT_OF_SERVICE => 'Out of Service',
            self::RETIRED => 'Retired',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::AVAILABLE => 'Asset is available for assignment and use',
            self::IN_SERVICE => 'Asset is currently assigned and in active use',
            self::MAINTENANCE => 'Asset is undergoing maintenance or repair',
            self::OUT_OF_SERVICE => 'Asset is temporarily unavailable for operational reasons',
            self::RETIRED => 'Asset has been permanently removed from service',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::AVAILABLE => 'green',
            self::IN_SERVICE => 'blue',
            self::MAINTENANCE => 'orange',
            self::OUT_OF_SERVICE => 'red',
            self::RETIRED => 'gray',
        };
    }

    /**
     * Check if asset can be assigned in this status
     */
    public function canBeAssigned(): bool
    {
        return match($this) {
            self::AVAILABLE => true,
            self::IN_SERVICE, self::MAINTENANCE, self::OUT_OF_SERVICE, self::RETIRED => false,
        };
    }

    /**
     * Check if asset generates billing in this status
     */
    public function generatesBilling(): bool
    {
        return match($this) {
            self::AVAILABLE, self::IN_SERVICE => true,
            self::MAINTENANCE, self::OUT_OF_SERVICE, self::RETIRED => false,
        };
    }

    /**
     * Check if asset requires maintenance tracking in this status
     */
    public function requiresMaintenanceTracking(): bool
    {
        return match($this) {
            self::MAINTENANCE => true,
            self::AVAILABLE, self::IN_SERVICE, self::OUT_OF_SERVICE, self::RETIRED => false,
        };
    }

    /**
     * Get valid status transitions from current status
     */
    public function getValidTransitions(): array
    {
        return match($this) {
            self::AVAILABLE => [self::IN_SERVICE, self::MAINTENANCE, self::OUT_OF_SERVICE, self::RETIRED],
            self::IN_SERVICE => [self::AVAILABLE, self::MAINTENANCE, self::OUT_OF_SERVICE, self::RETIRED],
            self::MAINTENANCE => [self::AVAILABLE, self::OUT_OF_SERVICE, self::RETIRED],
            self::OUT_OF_SERVICE => [self::AVAILABLE, self::MAINTENANCE, self::RETIRED],
            self::RETIRED => [], // No transitions from retired status
        };
    }

    /**
     * Check if transition to another status is valid
     */
    public function canTransitionTo(AssetStatus $newStatus): bool
    {
        return in_array($newStatus, $this->getValidTransitions(), true);
    }

    /**
     * Get statuses that are considered active (not retired)
     */
    public static function getActiveStatuses(): array
    {
        return [
            self::AVAILABLE,
            self::IN_SERVICE,
            self::MAINTENANCE,
            self::OUT_OF_SERVICE,
        ];
    }

    /**
     * Get statuses that indicate asset is operational
     */
    public static function getOperationalStatuses(): array
    {
        return [
            self::AVAILABLE,
            self::IN_SERVICE,
        ];
    }

    /**
     * Check if this is an active status
     */
    public function isActive(): bool
    {
        return in_array($this, self::getActiveStatuses(), true);
    }

    /**
     * Check if this is an operational status
     */
    public function isOperational(): bool
    {
        return in_array($this, self::getOperationalStatuses(), true);
    }
}
