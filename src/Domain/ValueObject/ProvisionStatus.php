<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Domain\ValueObject;

/**
 * Provision Status enumeration.
 *
 * Represents the current status of an asset provision agreement,
 * controlling billing activation and asset availability.
 */
enum ProvisionStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case EXPIRED = 'expired';
    case TERMINATED = 'terminated';

    public function getDisplayName(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::ACTIVE => 'Active',
            self::SUSPENDED => 'Suspended',
            self::EXPIRED => 'Expired',
            self::TERMINATED => 'Terminated',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::DRAFT => 'Provision is being prepared and not yet active',
            self::ACTIVE => 'Provision is currently active and in effect',
            self::SUSPENDED => 'Provision is temporarily suspended but can be reactivated',
            self::EXPIRED => 'Provision has reached its end date and expired naturally',
            self::TERMINATED => 'Provision has been terminated before its end date',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::ACTIVE => 'green',
            self::SUSPENDED => 'orange',
            self::EXPIRED => 'red',
            self::TERMINATED => 'red',
        };
    }

    /**
     * Check if billing should be generated for this status.
     */
    public function generatesBilling(): bool
    {
        return match($this) {
            self::ACTIVE => true,
            self::DRAFT, self::SUSPENDED, self::EXPIRED, self::TERMINATED => false,
        };
    }

    /**
     * Check if asset is available for use in this status.
     */
    public function allowsAssetUsage(): bool
    {
        return match($this) {
            self::ACTIVE => true,
            self::DRAFT, self::SUSPENDED, self::EXPIRED, self::TERMINATED => false,
        };
    }

    /**
     * Check if provision can be modified in this status.
     */
    public function allowsModification(): bool
    {
        return match($this) {
            self::DRAFT, self::ACTIVE, self::SUSPENDED => true,
            self::EXPIRED, self::TERMINATED => false,
        };
    }

    /**
     * Get valid status transitions from current status.
     */
    public function getValidTransitions(): array
    {
        return match($this) {
            self::DRAFT => [self::ACTIVE, self::TERMINATED],
            self::ACTIVE => [self::SUSPENDED, self::EXPIRED, self::TERMINATED],
            self::SUSPENDED => [self::ACTIVE, self::TERMINATED],
            self::EXPIRED => [], // No transitions from expired
            self::TERMINATED => [], // No transitions from terminated
        };
    }

    /**
     * Check if transition to another status is valid.
     */
    public function canTransitionTo(ProvisionStatus $newStatus): bool
    {
        return in_array($newStatus, $this->getValidTransitions(), true);
    }

    /**
     * Get statuses that are considered active (not final).
     */
    public static function getActiveStatuses(): array
    {
        return [
            self::DRAFT,
            self::ACTIVE,
            self::SUSPENDED,
        ];
    }

    /**
     * Get statuses that are considered final (cannot be changed).
     */
    public static function getFinalStatuses(): array
    {
        return [
            self::EXPIRED,
            self::TERMINATED,
        ];
    }

    /**
     * Get statuses that indicate the provision is operational.
     */
    public static function getOperationalStatuses(): array
    {
        return [
            self::ACTIVE,
        ];
    }

    /**
     * Check if this is an active status.
     */
    public function isActive(): bool
    {
        return in_array($this, self::getActiveStatuses(), true);
    }

    /**
     * Check if this is a final status.
     */
    public function isFinal(): bool
    {
        return in_array($this, self::getFinalStatuses(), true);
    }

    /**
     * Check if this is an operational status.
     */
    public function isOperational(): bool
    {
        return in_array($this, self::getOperationalStatuses(), true);
    }

    /**
     * Get the reason code for non-active statuses.
     */
    public function getReasonCode(): ?string
    {
        return match($this) {
            self::DRAFT => 'NOT_ACTIVATED',
            self::SUSPENDED => 'TEMPORARILY_SUSPENDED',
            self::EXPIRED => 'NATURAL_EXPIRATION',
            self::TERMINATED => 'EARLY_TERMINATION',
            self::ACTIVE => null,
        };
    }

    /**
     * Check if billing reconciliation is needed when transitioning to this status.
     */
    public function requiresBillingReconciliation(): bool
    {
        return match($this) {
            self::SUSPENDED, self::EXPIRED, self::TERMINATED => true,
            self::DRAFT, self::ACTIVE => false,
        };
    }

    /**
     * Get recommended next actions for this status.
     */
    public function getRecommendedActions(): array
    {
        return match($this) {
            self::DRAFT => ['Activate provision', 'Complete setup', 'Terminate if not needed'],
            self::ACTIVE => ['Monitor usage', 'Review billing', 'Suspend if needed'],
            self::SUSPENDED => ['Reactivate', 'Terminate permanently', 'Review suspension reason'],
            self::EXPIRED => ['Review for renewal', 'Archive provision'],
            self::TERMINATED => ['Archive provision', 'Review termination reason'],
        };
    }
}
