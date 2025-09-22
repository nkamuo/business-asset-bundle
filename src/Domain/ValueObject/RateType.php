<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Domain\ValueObject;

/**
 * Rate Type enumeration
 * 
 * Defines different billing rate structures for asset provisioning,
 * supporting various business models from fixed rates to complex
 * usage-based and performance-based billing.
 */
enum RateType: string
{
    case FIXED_DAILY = 'fixed_daily';
    case FIXED_MONTHLY = 'fixed_monthly';
    case PER_MILE = 'per_mile';
    case PER_HOUR = 'per_hour';
    case PER_TRIP = 'per_trip';
    case PERCENTAGE_REVENUE = 'percentage_revenue';
    case COST_PLUS = 'cost_plus';
    case TIERED = 'tiered'; // Different rates for usage tiers

    public function getDisplayName(): string
    {
        return match($this) {
            self::FIXED_DAILY => 'Fixed Daily Rate',
            self::FIXED_MONTHLY => 'Fixed Monthly Rate',
            self::PER_MILE => 'Per Mile Rate',
            self::PER_HOUR => 'Per Hour Rate',
            self::PER_TRIP => 'Per Trip Rate',
            self::PERCENTAGE_REVENUE => 'Percentage of Revenue',
            self::COST_PLUS => 'Cost Plus Markup',
            self::TIERED => 'Tiered Rate Structure',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::FIXED_DAILY => 'Fixed amount charged per day regardless of usage',
            self::FIXED_MONTHLY => 'Fixed amount charged per month regardless of usage',
            self::PER_MILE => 'Amount charged per mile traveled or distance covered',
            self::PER_HOUR => 'Amount charged per hour of usage or operation',
            self::PER_TRIP => 'Fixed amount charged per trip or delivery completed',
            self::PERCENTAGE_REVENUE => 'Percentage of revenue generated using the asset',
            self::COST_PLUS => 'Actual costs incurred plus a fixed markup percentage',
            self::TIERED => 'Different rates based on usage volume tiers',
        };
    }

    public function getDefaultUnitOfMeasure(): ?string
    {
        return match($this) {
            self::FIXED_DAILY => 'day',
            self::FIXED_MONTHLY => 'month',
            self::PER_MILE => 'mile',
            self::PER_HOUR => 'hour',
            self::PER_TRIP => 'trip',
            self::PERCENTAGE_REVENUE => 'percent',
            self::COST_PLUS => 'percent',
            self::TIERED => null, // Depends on tier structure
        };
    }

    /**
     * Check if this rate type is usage-based
     */
    public function isUsageBased(): bool
    {
        return match($this) {
            self::PER_MILE, self::PER_HOUR, self::PER_TRIP, 
            self::PERCENTAGE_REVENUE, self::TIERED => true,
            self::FIXED_DAILY, self::FIXED_MONTHLY, self::COST_PLUS => false,
        };
    }

    /**
     * Check if this rate type is time-based
     */
    public function isTimeBased(): bool
    {
        return match($this) {
            self::FIXED_DAILY, self::FIXED_MONTHLY, self::PER_HOUR => true,
            self::PER_MILE, self::PER_TRIP, self::PERCENTAGE_REVENUE, 
            self::COST_PLUS, self::TIERED => false,
        };
    }

    /**
     * Check if this rate type is distance-based
     */
    public function isDistanceBased(): bool
    {
        return match($this) {
            self::PER_MILE => true,
            self::FIXED_DAILY, self::FIXED_MONTHLY, self::PER_HOUR, 
            self::PER_TRIP, self::PERCENTAGE_REVENUE, self::COST_PLUS, 
            self::TIERED => false,
        };
    }

    /**
     * Check if this rate type is performance-based
     */
    public function isPerformanceBased(): bool
    {
        return match($this) {
            self::PERCENTAGE_REVENUE, self::PER_TRIP => true,
            self::FIXED_DAILY, self::FIXED_MONTHLY, self::PER_MILE, 
            self::PER_HOUR, self::COST_PLUS, self::TIERED => false,
        };
    }

    /**
     * Check if this rate type requires minimum charge protection
     */
    public function requiresMinimumCharge(): bool
    {
        return match($this) {
            self::PER_MILE, self::PER_HOUR, self::PER_TRIP, 
            self::PERCENTAGE_REVENUE, self::TIERED => true,
            self::FIXED_DAILY, self::FIXED_MONTHLY, self::COST_PLUS => false,
        };
    }

    /**
     * Check if this rate type supports maximum charge limits
     */
    public function supportsMaximumCharge(): bool
    {
        return match($this) {
            self::PER_MILE, self::PER_HOUR, self::PERCENTAGE_REVENUE, 
            self::COST_PLUS, self::TIERED => true,
            self::FIXED_DAILY, self::FIXED_MONTHLY, self::PER_TRIP => false,
        };
    }

    /**
     * Get rate types suitable for specific asset types
     */
    public static function getSuitableForAssetType(AssetType $assetType): array
    {
        return match($assetType) {
            AssetType::VEHICLE => [
                self::FIXED_DAILY,
                self::FIXED_MONTHLY,
                self::PER_MILE,
                self::PER_HOUR,
                self::PER_TRIP,
                self::PERCENTAGE_REVENUE,
            ],
            AssetType::DRIVER => [
                self::FIXED_DAILY,
                self::FIXED_MONTHLY,
                self::PER_HOUR,
                self::PER_TRIP,
                self::PERCENTAGE_REVENUE,
            ],
            AssetType::EQUIPMENT => [
                self::FIXED_DAILY,
                self::FIXED_MONTHLY,
                self::PER_HOUR,
                self::COST_PLUS,
            ],
            AssetType::INFRASTRUCTURE => [
                self::FIXED_DAILY,
                self::FIXED_MONTHLY,
                self::PERCENTAGE_REVENUE,
            ],
            AssetType::TECHNOLOGY => [
                self::FIXED_MONTHLY,
                self::PER_HOUR,
                self::COST_PLUS,
            ],
            AssetType::CONTAINER, AssetType::TRAILER => [
                self::FIXED_DAILY,
                self::PER_MILE,
                self::PER_TRIP,
                self::PERCENTAGE_REVENUE,
            ],
        };
    }

    /**
     * Get billing frequency recommendation for this rate type
     */
    public function getRecommendedBillingFrequency(): string
    {
        return match($this) {
            self::FIXED_DAILY, self::PER_MILE, self::PER_HOUR, self::PER_TRIP => 'Weekly',
            self::FIXED_MONTHLY => 'Monthly',
            self::PERCENTAGE_REVENUE, self::COST_PLUS, self::TIERED => 'Weekly',
        };
    }

    /**
     * Check if this rate type requires usage tracking
     */
    public function requiresUsageTracking(): bool
    {
        return match($this) {
            self::PER_MILE, self::PER_HOUR, self::PER_TRIP, 
            self::PERCENTAGE_REVENUE, self::TIERED => true,
            self::FIXED_DAILY, self::FIXED_MONTHLY, self::COST_PLUS => false,
        };
    }

    /**
     * Get example rate structure for documentation
     */
    public function getExampleStructure(): array
    {
        return match($this) {
            self::FIXED_DAILY => [
                'rate' => '$150.00 per day',
                'example' => '5 days × $150 = $750.00'
            ],
            self::FIXED_MONTHLY => [
                'rate' => '$2,500.00 per month',
                'example' => '1 month × $2,500 = $2,500.00'
            ],
            self::PER_MILE => [
                'rate' => '$0.65 per mile',
                'example' => '1,000 miles × $0.65 = $650.00'
            ],
            self::PER_HOUR => [
                'rate' => '$35.00 per hour',
                'example' => '40 hours × $35 = $1,400.00'
            ],
            self::PER_TRIP => [
                'rate' => '$250.00 per trip',
                'example' => '8 trips × $250 = $2,000.00'
            ],
            self::PERCENTAGE_REVENUE => [
                'rate' => '15% of revenue',
                'example' => '$10,000 revenue × 15% = $1,500.00'
            ],
            self::COST_PLUS => [
                'rate' => 'Cost + 20% markup',
                'example' => '$800 cost + 20% = $960.00'
            ],
            self::TIERED => [
                'rate' => 'Tier 1: $0.70/mile (0-500), Tier 2: $0.60/mile (501+)',
                'example' => '800 miles: (500×$0.70) + (300×$0.60) = $530.00'
            ],
        };
    }
}
