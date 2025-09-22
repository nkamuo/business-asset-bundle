<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Domain\ValueObject;

/**
 * Provision Type enumeration.
 *
 * Defines the different types of asset provisioning arrangements
 * between partners, each with different financial and operational implications.
 */
enum ProvisionType: string
{
    case OWNED = 'owned';           // Internal asset owned by the company
    case LEASED = 'leased';         // Long-term lease arrangement
    case RENTED = 'rented';         // Short-term rental arrangement
    case SUBCONTRACTED = 'subcontracted'; // Partner provides asset and services
    case SHARED = 'shared';         // Joint usage or ownership agreement
    case BORROWED = 'borrowed';     // Temporary use without payment
    case CONSIGNMENT = 'consignment'; // Asset provided for sale/use with payment on use

    public function getDisplayName(): string
    {
        return match($this) {
            self::OWNED => 'Owned',
            self::LEASED => 'Leased',
            self::RENTED => 'Rented',
            self::SUBCONTRACTED => 'Subcontracted',
            self::SHARED => 'Shared',
            self::BORROWED => 'Borrowed',
            self::CONSIGNMENT => 'Consignment',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::OWNED => 'Asset is owned by the organization and operated internally',
            self::LEASED => 'Asset is under long-term lease agreement with fixed terms',
            self::RENTED => 'Asset is rented for short-term use with flexible terms',
            self::SUBCONTRACTED => 'Partner provides asset along with operational services',
            self::SHARED => 'Asset is jointly owned or used under sharing agreement',
            self::BORROWED => 'Asset is temporarily borrowed without direct payment',
            self::CONSIGNMENT => 'Asset is provided for use with payment based on actual usage',
        };
    }

    /**
     * Check if this provision type typically involves recurring payments.
     */
    public function hasRecurringPayments(): bool
    {
        return match($this) {
            self::LEASED, self::RENTED, self::SUBCONTRACTED, self::SHARED, self::CONSIGNMENT => true,
            self::OWNED, self::BORROWED => false,
        };
    }

    /**
     * Check if this provision type allows for usage-based billing.
     */
    public function allowsUsageBasedBilling(): bool
    {
        return match($this) {
            self::RENTED, self::SUBCONTRACTED, self::SHARED, self::CONSIGNMENT => true,
            self::OWNED, self::LEASED, self::BORROWED => false,
        };
    }

    /**
     * Check if this provision type requires external provider.
     */
    public function requiresExternalProvider(): bool
    {
        return match($this) {
            self::LEASED, self::RENTED, self::SUBCONTRACTED, self::SHARED,
            self::BORROWED, self::CONSIGNMENT => true,
            self::OWNED => false,
        };
    }

    /**
     * Check if this provision type implies operational responsibility transfer.
     */
    public function transfersOperationalResponsibility(): bool
    {
        return match($this) {
            self::SUBCONTRACTED => true,
            self::OWNED, self::LEASED, self::RENTED, self::SHARED,
            self::BORROWED, self::CONSIGNMENT => false,
        };
    }

    /**
     * Get typical contract duration for this provision type.
     */
    public function getTypicalDuration(): string
    {
        return match($this) {
            self::OWNED => 'Indefinite',
            self::LEASED => 'Long-term (1+ years)',
            self::RENTED => 'Short-term (days to months)',
            self::SUBCONTRACTED => 'Variable (project-based)',
            self::SHARED => 'Variable (agreement-based)',
            self::BORROWED => 'Very short-term (days to weeks)',
            self::CONSIGNMENT => 'Variable (usage-based)',
        };
    }

    /**
     * Get provision types that are typically internal (no external party).
     */
    public static function getInternalTypes(): array
    {
        return [self::OWNED];
    }

    /**
     * Get provision types that involve external parties.
     */
    public static function getExternalTypes(): array
    {
        return [
            self::LEASED,
            self::RENTED,
            self::SUBCONTRACTED,
            self::SHARED,
            self::BORROWED,
            self::CONSIGNMENT,
        ];
    }

    /**
     * Get provision types that typically involve fixed rates.
     */
    public static function getFixedRateTypes(): array
    {
        return [
            self::OWNED,
            self::LEASED,
        ];
    }

    /**
     * Get provision types that typically involve variable rates.
     */
    public static function getVariableRateTypes(): array
    {
        return [
            self::RENTED,
            self::SUBCONTRACTED,
            self::SHARED,
            self::CONSIGNMENT,
        ];
    }

    /**
     * Check if this is an internal provision type.
     */
    public function isInternal(): bool
    {
        return in_array($this, self::getInternalTypes(), true);
    }

    /**
     * Check if this is an external provision type.
     */
    public function isExternal(): bool
    {
        return in_array($this, self::getExternalTypes(), true);
    }

    /**
     * Get recommended billing frequency for this provision type.
     */
    public function getRecommendedBillingFrequency(): string
    {
        return match($this) {
            self::OWNED => 'None',
            self::LEASED => 'Monthly',
            self::RENTED => 'Weekly',
            self::SUBCONTRACTED => 'Per project/trip',
            self::SHARED => 'Monthly',
            self::BORROWED => 'None',
            self::CONSIGNMENT => 'Per usage',
        };
    }
}
