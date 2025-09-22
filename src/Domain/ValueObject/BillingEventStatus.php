<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Domain\ValueObject;

/**
 * Billing Event Status enumeration.
 *
 * Represents the current status of a billing event
 * in the billing and settlement workflow.
 */
enum BillingEventStatus: string
{
    case CALCULATED = 'calculated';
    case APPROVED = 'approved';
    case BILLED = 'billed';
    case DISPUTED = 'disputed';
    case PAID = 'paid';

    public function getDisplayName(): string
    {
        return match($this) {
            self::CALCULATED => 'Calculated',
            self::APPROVED => 'Approved',
            self::BILLED => 'Billed',
            self::DISPUTED => 'Disputed',
            self::PAID => 'Paid',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::CALCULATED => 'Billing amount has been calculated but not yet approved',
            self::APPROVED => 'Billing amount has been approved and ready for billing',
            self::BILLED => 'Billing has been sent to settlement system',
            self::DISPUTED => 'Billing amount is being disputed and under review',
            self::PAID => 'Billing has been paid and settled',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::CALCULATED => 'blue',
            self::APPROVED => 'green',
            self::BILLED => 'purple',
            self::DISPUTED => 'orange',
            self::PAID => 'gray',
        };
    }

    /**
     * Get valid status transitions from current status.
     */
    public function getValidTransitions(): array
    {
        return match($this) {
            self::CALCULATED => [self::APPROVED, self::DISPUTED],
            self::APPROVED => [self::BILLED, self::DISPUTED],
            self::BILLED => [self::PAID, self::DISPUTED],
            self::DISPUTED => [self::CALCULATED, self::APPROVED],
            self::PAID => [], // No transitions from paid status
        };
    }

    /**
     * Check if transition to another status is valid.
     */
    public function canTransitionTo(BillingEventStatus $newStatus): bool
    {
        return in_array($newStatus, $this->getValidTransitions(), true);
    }

    /**
     * Check if billing event can be modified in this status.
     */
    public function allowsModification(): bool
    {
        return match($this) {
            self::CALCULATED, self::DISPUTED => true,
            self::APPROVED, self::BILLED, self::PAID => false,
        };
    }

    /**
     * Check if billing event contributes to revenue in this status.
     */
    public function contributesToRevenue(): bool
    {
        return match($this) {
            self::BILLED, self::PAID => true,
            self::CALCULATED, self::APPROVED, self::DISPUTED => false,
        };
    }

    /**
     * Check if billing event requires approval in this status.
     */
    public function requiresApproval(): bool
    {
        return match($this) {
            self::CALCULATED => true,
            self::APPROVED, self::BILLED, self::DISPUTED, self::PAID => false,
        };
    }

    /**
     * Check if billing event can be included in settlement.
     */
    public function canBeSettled(): bool
    {
        return match($this) {
            self::APPROVED => true,
            self::CALCULATED, self::BILLED, self::DISPUTED, self::PAID => false,
        };
    }

    /**
     * Get statuses that are considered final.
     */
    public static function getFinalStatuses(): array
    {
        return [self::PAID];
    }

    /**
     * Get statuses that are considered active (not final).
     */
    public static function getActiveStatuses(): array
    {
        return [
            self::CALCULATED,
            self::APPROVED,
            self::BILLED,
            self::DISPUTED,
        ];
    }

    /**
     * Get statuses that indicate pending settlement.
     */
    public static function getPendingSettlementStatuses(): array
    {
        return [
            self::CALCULATED,
            self::APPROVED,
        ];
    }

    /**
     * Get statuses that indicate completed billing.
     */
    public static function getCompletedStatuses(): array
    {
        return [
            self::BILLED,
            self::PAID,
        ];
    }

    /**
     * Check if this is a final status.
     */
    public function isFinal(): bool
    {
        return in_array($this, self::getFinalStatuses(), true);
    }

    /**
     * Check if this is an active status.
     */
    public function isActive(): bool
    {
        return in_array($this, self::getActiveStatuses(), true);
    }

    /**
     * Check if this status indicates pending settlement.
     */
    public function isPendingSettlement(): bool
    {
        return in_array($this, self::getPendingSettlementStatuses(), true);
    }

    /**
     * Check if this status indicates completed billing.
     */
    public function isCompleted(): bool
    {
        return in_array($this, self::getCompletedStatuses(), true);
    }

    /**
     * Get the next recommended status.
     */
    public function getNextRecommendedStatus(): ?BillingEventStatus
    {
        return match($this) {
            self::CALCULATED => self::APPROVED,
            self::APPROVED => self::BILLED,
            self::BILLED => self::PAID,
            self::DISPUTED => self::CALCULATED,
            self::PAID => null,
        };
    }

    /**
     * Get action label for transitioning to next status.
     */
    public function getNextActionLabel(): ?string
    {
        return match($this) {
            self::CALCULATED => 'Approve',
            self::APPROVED => 'Bill',
            self::BILLED => 'Mark as Paid',
            self::DISPUTED => 'Recalculate',
            self::PAID => null,
        };
    }

    /**
     * Check if status requires manual intervention.
     */
    public function requiresManualIntervention(): bool
    {
        return match($this) {
            self::DISPUTED => true,
            self::CALCULATED, self::APPROVED, self::BILLED, self::PAID => false,
        };
    }

    /**
     * Get billing workflow priority (1 = highest, 10 = lowest).
     */
    public function getWorkflowPriority(): int
    {
        return match($this) {
            self::DISPUTED => 1,
            self::CALCULATED => 2,
            self::APPROVED => 3,
            self::BILLED => 4,
            self::PAID => 10,
        };
    }
}
