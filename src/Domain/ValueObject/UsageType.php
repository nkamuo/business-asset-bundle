<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Domain\ValueObject;

/**
 * Usage Type enumeration
 * 
 * Defines different types of asset usage activities
 * for accurate tracking and billing purposes.
 */
enum UsageType: string
{
    case DRIVING = 'driving';
    case LOADING = 'loading';
    case WAITING = 'waiting';
    case MAINTENANCE = 'maintenance';
    case IDLE = 'idle';
    case OPERATING = 'operating'; // For equipment
    case SETUP = 'setup';
    case BREAKDOWN = 'breakdown';
    case TRAINING = 'training';
    case INSPECTION = 'inspection';

    public function getDisplayName(): string
    {
        return match($this) {
            self::DRIVING => 'Driving',
            self::LOADING => 'Loading/Unloading',
            self::WAITING => 'Waiting',
            self::MAINTENANCE => 'Maintenance',
            self::IDLE => 'Idle',
            self::OPERATING => 'Operating',
            self::SETUP => 'Setup',
            self::BREAKDOWN => 'Breakdown',
            self::TRAINING => 'Training',
            self::INSPECTION => 'Inspection',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::DRIVING => 'Vehicle is in motion, traveling between locations',
            self::LOADING => 'Asset is engaged in loading or unloading activities',
            self::WAITING => 'Asset is waiting for assignment or next task',
            self::MAINTENANCE => 'Asset is undergoing scheduled or unscheduled maintenance',
            self::IDLE => 'Asset is idle with no active engagement',
            self::OPERATING => 'Equipment is actively operating or performing its function',
            self::SETUP => 'Asset is being set up or prepared for operation',
            self::BREAKDOWN => 'Asset is experiencing breakdown or failure',
            self::TRAINING => 'Asset is being used for training purposes',
            self::INSPECTION => 'Asset is undergoing inspection or evaluation',
        };
    }

    /**
     * Check if this usage type is billable
     */
    public function isBillable(): bool
    {
        return match($this) {
            self::DRIVING, self::LOADING, self::OPERATING, self::SETUP => true,
            self::WAITING, self::MAINTENANCE, self::IDLE, self::BREAKDOWN, 
            self::TRAINING, self::INSPECTION => false,
        };
    }

    /**
     * Check if this usage type is productive
     */
    public function isProductive(): bool
    {
        return match($this) {
            self::DRIVING, self::LOADING, self::OPERATING => true,
            self::WAITING, self::MAINTENANCE, self::IDLE, self::BREAKDOWN, 
            self::SETUP, self::TRAINING, self::INSPECTION => false,
        };
    }

    /**
     * Check if this usage type indicates downtime
     */
    public function isDowntime(): bool
    {
        return match($this) {
            self::MAINTENANCE, self::BREAKDOWN, self::IDLE => true,
            self::DRIVING, self::LOADING, self::WAITING, self::OPERATING, 
            self::SETUP, self::TRAINING, self::INSPECTION => false,
        };
    }

    /**
     * Check if this usage type requires location tracking
     */
    public function requiresLocationTracking(): bool
    {
        return match($this) {
            self::DRIVING, self::LOADING, self::OPERATING => true,
            self::WAITING, self::MAINTENANCE, self::IDLE, self::BREAKDOWN, 
            self::SETUP, self::TRAINING, self::INSPECTION => false,
        };
    }

    /**
     * Get default unit of measure for this usage type
     */
    public function getDefaultUnitOfMeasure(): string
    {
        return match($this) {
            self::DRIVING => 'miles',
            self::LOADING, self::WAITING, self::MAINTENANCE, self::IDLE, 
            self::OPERATING, self::SETUP, self::BREAKDOWN, self::TRAINING, 
            self::INSPECTION => 'hours',
        };
    }

    /**
     * Get color code for visual representation
     */
    public function getColor(): string
    {
        return match($this) {
            self::DRIVING => 'blue',
            self::LOADING => 'green',
            self::OPERATING => 'green',
            self::WAITING => 'orange',
            self::SETUP => 'purple',
            self::TRAINING => 'cyan',
            self::INSPECTION => 'yellow',
            self::MAINTENANCE => 'orange',
            self::IDLE => 'gray',
            self::BREAKDOWN => 'red',
        };
    }

    /**
     * Get usage types suitable for specific asset types
     */
    public static function getSuitableForAssetType(AssetType $assetType): array
    {
        return match($assetType) {
            AssetType::VEHICLE => [
                self::DRIVING,
                self::LOADING,
                self::WAITING,
                self::MAINTENANCE,
                self::IDLE,
                self::BREAKDOWN,
                self::INSPECTION,
            ],
            AssetType::DRIVER => [
                self::DRIVING,
                self::LOADING,
                self::WAITING,
                self::IDLE,
                self::TRAINING,
                self::INSPECTION,
            ],
            AssetType::EQUIPMENT => [
                self::OPERATING,
                self::LOADING,
                self::SETUP,
                self::MAINTENANCE,
                self::IDLE,
                self::BREAKDOWN,
                self::TRAINING,
                self::INSPECTION,
            ],
            AssetType::INFRASTRUCTURE => [
                self::OPERATING,
                self::MAINTENANCE,
                self::IDLE,
                self::BREAKDOWN,
                self::INSPECTION,
            ],
            AssetType::TECHNOLOGY => [
                self::OPERATING,
                self::MAINTENANCE,
                self::IDLE,
                self::BREAKDOWN,
                self::INSPECTION,
            ],
            AssetType::CONTAINER, AssetType::TRAILER => [
                self::LOADING,
                self::WAITING,
                self::MAINTENANCE,
                self::IDLE,
                self::INSPECTION,
            ],
        };
    }

    /**
     * Get billable usage types
     */
    public static function getBillableTypes(): array
    {
        return array_filter(
            self::cases(),
            fn(self $type) => $type->isBillable()
        );
    }

    /**
     * Get productive usage types
     */
    public static function getProductiveTypes(): array
    {
        return array_filter(
            self::cases(),
            fn(self $type) => $type->isProductive()
        );
    }

    /**
     * Get downtime usage types
     */
    public static function getDowntimeTypes(): array
    {
        return array_filter(
            self::cases(),
            fn(self $type) => $type->isDowntime()
        );
    }

    /**
     * Check if this usage type affects asset availability
     */
    public function affectsAvailability(): bool
    {
        return match($this) {
            self::MAINTENANCE, self::BREAKDOWN => true,
            self::DRIVING, self::LOADING, self::WAITING, self::IDLE, 
            self::OPERATING, self::SETUP, self::TRAINING, self::INSPECTION => false,
        };
    }

    /**
     * Get priority level for this usage type (1 = highest, 10 = lowest)
     */
    public function getPriority(): int
    {
        return match($this) {
            self::BREAKDOWN => 1,
            self::MAINTENANCE => 2,
            self::DRIVING => 3,
            self::OPERATING => 3,
            self::LOADING => 4,
            self::SETUP => 5,
            self::INSPECTION => 6,
            self::TRAINING => 7,
            self::WAITING => 8,
            self::IDLE => 10,
        };
    }

    /**
     * Check if usage type can be scheduled in advance
     */
    public function canBeScheduled(): bool
    {
        return match($this) {
            self::MAINTENANCE, self::TRAINING, self::INSPECTION => true,
            self::DRIVING, self::LOADING, self::WAITING, self::IDLE, 
            self::OPERATING, self::SETUP, self::BREAKDOWN => false,
        };
    }
}
