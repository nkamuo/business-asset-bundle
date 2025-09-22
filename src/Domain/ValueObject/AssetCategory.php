<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Domain\ValueObject;

/**
 * Asset Category enumeration.
 *
 * Provides detailed categorization within each asset type,
 * allowing for specific asset classification and appropriate
 * handling based on asset characteristics.
 */
enum AssetCategory: string
{
    // Vehicle categories
    case TRUCK_TRACTOR = 'truck_tractor';
    case STRAIGHT_TRUCK = 'straight_truck';
    case VAN = 'van';
    case PICKUP_TRUCK = 'pickup_truck';
    case MOTORCYCLE = 'motorcycle';

    // Trailer categories
    case TRAILER_DRY = 'trailer_dry';
    case TRAILER_REEFER = 'trailer_reefer';
    case TRAILER_FLATBED = 'trailer_flatbed';
    case TRAILER_TANKER = 'trailer_tanker';
    case TRAILER_LOWBOY = 'trailer_lowboy';

    // Container categories
    case CONTAINER_20FT = 'container_20ft';
    case CONTAINER_40FT = 'container_40ft';
    case CONTAINER_40FT_HC = 'container_40ft_hc';
    case CONTAINER_45FT = 'container_45ft';
    case CONTAINER_REEFER = 'container_reefer';

    // Human resources categories
    case DRIVER_CDL_A = 'driver_cdl_a';
    case DRIVER_CDL_B = 'driver_cdl_b';
    case DRIVER_CDL_C = 'driver_cdl_c';
    case OPERATOR_FORKLIFT = 'operator_forklift';
    case OPERATOR_CRANE = 'operator_crane';
    case TECHNICIAN_MECHANICAL = 'technician_mechanical';
    case TECHNICIAN_ELECTRICAL = 'technician_electrical';

    // Equipment categories
    case FORKLIFT = 'forklift';
    case REACH_TRUCK = 'reach_truck';
    case CRANE_MOBILE = 'crane_mobile';
    case CRANE_OVERHEAD = 'crane_overhead';
    case LOADING_DOCK = 'loading_dock';
    case PALLET_JACK = 'pallet_jack';
    case CONVEYOR = 'conveyor';

    // Infrastructure categories
    case WAREHOUSE = 'warehouse';
    case YARD = 'yard';
    case DOCK = 'dock';
    case FUEL_STATION = 'fuel_station';
    case MAINTENANCE_BAY = 'maintenance_bay';
    case OFFICE_SPACE = 'office_space';

    // Technology categories
    case GPS_DEVICE = 'gps_device';
    case COMMUNICATION_RADIO = 'communication_radio';
    case COMPUTER_SYSTEM = 'computer_system';
    case SOFTWARE_LICENSE = 'software_license';
    case TRACKING_SENSOR = 'tracking_sensor';
    case SECURITY_CAMERA = 'security_camera';

    public function getDisplayName(): string
    {
        return match($this) {
            // Vehicles
            self::TRUCK_TRACTOR => 'Truck Tractor',
            self::STRAIGHT_TRUCK => 'Straight Truck',
            self::VAN => 'Van',
            self::PICKUP_TRUCK => 'Pickup Truck',
            self::MOTORCYCLE => 'Motorcycle',

            // Trailers
            self::TRAILER_DRY => 'Dry Van Trailer',
            self::TRAILER_REEFER => 'Refrigerated Trailer',
            self::TRAILER_FLATBED => 'Flatbed Trailer',
            self::TRAILER_TANKER => 'Tanker Trailer',
            self::TRAILER_LOWBOY => 'Lowboy Trailer',

            // Containers
            self::CONTAINER_20FT => '20ft Container',
            self::CONTAINER_40FT => '40ft Container',
            self::CONTAINER_40FT_HC => '40ft High Cube Container',
            self::CONTAINER_45FT => '45ft Container',
            self::CONTAINER_REEFER => 'Refrigerated Container',

            // Human Resources
            self::DRIVER_CDL_A => 'CDL Class A Driver',
            self::DRIVER_CDL_B => 'CDL Class B Driver',
            self::DRIVER_CDL_C => 'CDL Class C Driver',
            self::OPERATOR_FORKLIFT => 'Forklift Operator',
            self::OPERATOR_CRANE => 'Crane Operator',
            self::TECHNICIAN_MECHANICAL => 'Mechanical Technician',
            self::TECHNICIAN_ELECTRICAL => 'Electrical Technician',

            // Equipment
            self::FORKLIFT => 'Forklift',
            self::REACH_TRUCK => 'Reach Truck',
            self::CRANE_MOBILE => 'Mobile Crane',
            self::CRANE_OVERHEAD => 'Overhead Crane',
            self::LOADING_DOCK => 'Loading Dock',
            self::PALLET_JACK => 'Pallet Jack',
            self::CONVEYOR => 'Conveyor System',

            // Infrastructure
            self::WAREHOUSE => 'Warehouse',
            self::YARD => 'Yard Space',
            self::DOCK => 'Dock',
            self::FUEL_STATION => 'Fuel Station',
            self::MAINTENANCE_BAY => 'Maintenance Bay',
            self::OFFICE_SPACE => 'Office Space',

            // Technology
            self::GPS_DEVICE => 'GPS Device',
            self::COMMUNICATION_RADIO => 'Communication Radio',
            self::COMPUTER_SYSTEM => 'Computer System',
            self::SOFTWARE_LICENSE => 'Software License',
            self::TRACKING_SENSOR => 'Tracking Sensor',
            self::SECURITY_CAMERA => 'Security Camera',
        };
    }

    public function getAssetType(): AssetType
    {
        return match($this) {
            // Vehicle categories
            self::TRUCK_TRACTOR, self::STRAIGHT_TRUCK, self::VAN,
            self::PICKUP_TRUCK, self::MOTORCYCLE => AssetType::VEHICLE,

            // Trailer categories
            self::TRAILER_DRY, self::TRAILER_REEFER, self::TRAILER_FLATBED,
            self::TRAILER_TANKER, self::TRAILER_LOWBOY => AssetType::TRAILER,

            // Container categories
            self::CONTAINER_20FT, self::CONTAINER_40FT, self::CONTAINER_40FT_HC,
            self::CONTAINER_45FT, self::CONTAINER_REEFER => AssetType::CONTAINER,

            // Human resource categories
            self::DRIVER_CDL_A, self::DRIVER_CDL_B, self::DRIVER_CDL_C,
            self::OPERATOR_FORKLIFT, self::OPERATOR_CRANE, self::TECHNICIAN_MECHANICAL,
            self::TECHNICIAN_ELECTRICAL => AssetType::DRIVER,

            // Equipment categories
            self::FORKLIFT, self::REACH_TRUCK, self::CRANE_MOBILE, self::CRANE_OVERHEAD,
            self::LOADING_DOCK, self::PALLET_JACK, self::CONVEYOR => AssetType::EQUIPMENT,

            // Infrastructure categories
            self::WAREHOUSE, self::YARD, self::DOCK, self::FUEL_STATION,
            self::MAINTENANCE_BAY, self::OFFICE_SPACE => AssetType::INFRASTRUCTURE,

            // Technology categories
            self::GPS_DEVICE, self::COMMUNICATION_RADIO, self::COMPUTER_SYSTEM,
            self::SOFTWARE_LICENSE, self::TRACKING_SENSOR, self::SECURITY_CAMERA => AssetType::TECHNOLOGY,
        };
    }

    /**
     * Get categories for a specific asset type.
     */
    public static function getCategoriesForType(AssetType $type): array
    {
        return array_filter(
            self::cases(),
            fn (self $category) => $category->getAssetType() === $type
        );
    }

    /**
     * Check if this category requires special licensing.
     */
    public function requiresLicense(): bool
    {
        return match($this) {
            self::DRIVER_CDL_A, self::DRIVER_CDL_B, self::DRIVER_CDL_C,
            self::OPERATOR_FORKLIFT, self::OPERATOR_CRANE => true,
            default => false,
        };
    }

    /**
     * Check if this category is temperature controlled.
     */
    public function isTemperatureControlled(): bool
    {
        return match($this) {
            self::TRAILER_REEFER, self::CONTAINER_REEFER => true,
            default => false,
        };
    }

    /**
     * Get typical capacity unit for this category.
     */
    public function getCapacityUnit(): ?string
    {
        return match($this) {
            // Weight-based capacity
            self::TRUCK_TRACTOR, self::STRAIGHT_TRUCK, self::VAN, self::PICKUP_TRUCK,
            self::TRAILER_DRY, self::TRAILER_REEFER, self::TRAILER_FLATBED,
            self::TRAILER_TANKER, self::FORKLIFT, self::REACH_TRUCK => 'kg',

            // Volume-based capacity
            self::CONTAINER_20FT, self::CONTAINER_40FT, self::CONTAINER_40FT_HC,
            self::CONTAINER_45FT, self::CONTAINER_REEFER, self::WAREHOUSE => 'm³',

            // Area-based capacity
            self::YARD, self::DOCK, self::OFFICE_SPACE => 'm²',

            // Power-based capacity
            self::CRANE_MOBILE, self::CRANE_OVERHEAD => 'tons',

            default => null,
        };
    }
}
