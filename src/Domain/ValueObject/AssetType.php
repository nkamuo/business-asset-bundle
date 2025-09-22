<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Domain\ValueObject;

/**
 * Asset Type enumeration
 * 
 * Defines the broad categories of assets that can be managed
 * within the system, covering physical assets, human resources,
 * and technology components.
 */
enum AssetType: string
{
    case VEHICLE = 'vehicle';
    case DRIVER = 'driver';
    case EQUIPMENT = 'equipment';
    case INFRASTRUCTURE = 'infrastructure';
    case TECHNOLOGY = 'technology';
    case CONTAINER = 'container';
    case TRAILER = 'trailer';

    public function getDisplayName(): string
    {
        return match($this) {
            self::VEHICLE => 'Vehicle',
            self::DRIVER => 'Driver',
            self::EQUIPMENT => 'Equipment',
            self::INFRASTRUCTURE => 'Infrastructure',
            self::TECHNOLOGY => 'Technology',
            self::CONTAINER => 'Container',
            self::TRAILER => 'Trailer',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::VEHICLE => 'Motor vehicles including trucks, cars, and specialized vehicles',
            self::DRIVER => 'Human resources including drivers, operators, and technicians',
            self::EQUIPMENT => 'Operational equipment like forklifts, cranes, and loading equipment',
            self::INFRASTRUCTURE => 'Physical infrastructure like warehouses, yards, and facilities',
            self::TECHNOLOGY => 'Technology assets including software, devices, and communication equipment',
            self::CONTAINER => 'Shipping containers and cargo containers',
            self::TRAILER => 'Trailers and semi-trailers for cargo transport',
        };
    }

    /**
     * Get asset types that are typically mobile/movable
     */
    public static function getMobileTypes(): array
    {
        return [
            self::VEHICLE,
            self::DRIVER,
            self::EQUIPMENT,
            self::CONTAINER,
            self::TRAILER,
        ];
    }

    /**
     * Get asset types that are typically stationary
     */
    public static function getStationaryTypes(): array
    {
        return [
            self::INFRASTRUCTURE,
            self::TECHNOLOGY,
        ];
    }

    /**
     * Check if this asset type is typically mobile
     */
    public function isMobile(): bool
    {
        return in_array($this, self::getMobileTypes(), true);
    }

    /**
     * Check if this asset type is typically stationary
     */
    public function isStationary(): bool
    {
        return in_array($this, self::getStationaryTypes(), true);
    }
}
