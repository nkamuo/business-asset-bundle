<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Money\Money;
use Nkamuo\AssetBundle\Domain\ValueObject\BillingEventStatus;
use Symfony\Component\Uid\Ulid;

/**
 * Asset Billing Event entity representing calculated charges.
 *
 * Contains the calculated billing amounts for asset usage
 * based on rate cards and usage events, ready for settlement.
 */
#[ORM\Entity]
#[ORM\Table(name: 'nkamuo_asset_billing_event')]
#[ORM\Index(columns: ['asset_id'], name: 'idx_billing_asset')]
#[ORM\Index(columns: ['provision_id'], name: 'idx_billing_provision')]
#[ORM\Index(columns: ['status'], name: 'idx_billing_status')]
#[ORM\Index(columns: ['billing_period_start'], name: 'idx_billing_period_start')]
class AssetBillingEvent
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid')]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Asset::class, inversedBy: 'billingEvents')]
    #[ORM\JoinColumn(nullable: false)]
    private Asset $asset;

    #[ORM\ManyToOne(targetEntity: AssetProvision::class, inversedBy: 'billingEvents')]
    #[ORM\JoinColumn(nullable: false)]
    private AssetProvision $provision;

    #[ORM\ManyToOne(targetEntity: AssetRateCard::class)]
    #[ORM\JoinColumn(nullable: false)]
    private AssetRateCard $rateCard;

    #[ORM\Column(type: 'integer')]
    private int $calculatedAmountValue; // Money amount in cents

    #[ORM\Column(type: 'string', length: 3)]
    private string $calculatedAmountCurrency; // Currency code

    #[ORM\Column(type: 'string', enumType: BillingEventStatus::class)]
    private BillingEventStatus $status;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $settlementId = null; // Reference to settlement when billed

    #[ORM\Column(type: 'json')]
    private array $calculation = []; // How amount was calculated (JSON)

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $billingPeriodStart;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $billingPeriodEnd;

    #[ORM\Column(type: 'json')]
    private array $usageEventIds = []; // Related usage event IDs

    #[ORM\Column(type: 'json')]
    private array $metadata = []; // Additional data

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        Asset $asset,
        AssetProvision $provision,
        AssetRateCard $rateCard,
        Money $calculatedAmount,
        \DateTimeImmutable $billingPeriodStart,
        \DateTimeImmutable $billingPeriodEnd,
        array $calculation = [],
        array $usageEventIds = [],
        array $metadata = [],
        BillingEventStatus $status = BillingEventStatus::CALCULATED
    ) {
        $this->id = new Ulid();
        $this->asset = $asset;
        $this->provision = $provision;
        $this->rateCard = $rateCard;
        $this->calculatedAmountValue = $calculatedAmount->getAmount();
        $this->calculatedAmountCurrency = $calculatedAmount->getCurrency()->getCode();
        $this->billingPeriodStart = $billingPeriodStart;
        $this->billingPeriodEnd = $billingPeriodEnd;
        $this->calculation = $calculation;
        $this->usageEventIds = $usageEventIds;
        $this->metadata = $metadata;
        $this->status = $status;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters
    public function getId(): Ulid
    {
        return $this->id;
    }

    public function getAsset(): Asset
    {
        return $this->asset;
    }

    public function getProvision(): AssetProvision
    {
        return $this->provision;
    }

    public function getRateCard(): AssetRateCard
    {
        return $this->rateCard;
    }

    public function getCalculatedAmount(): Money
    {
        return new Money($this->calculatedAmountValue, new \Money\Currency($this->calculatedAmountCurrency));
    }

    public function getStatus(): BillingEventStatus
    {
        return $this->status;
    }

    public function getSettlementId(): ?string
    {
        return $this->settlementId;
    }

    public function getCalculation(): array
    {
        return $this->calculation;
    }

    public function getBillingPeriodStart(): \DateTimeImmutable
    {
        return $this->billingPeriodStart;
    }

    public function getBillingPeriodEnd(): \DateTimeImmutable
    {
        return $this->billingPeriodEnd;
    }

    public function getUsageEventIds(): array
    {
        return $this->usageEventIds;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // Business methods
    public function approve(): void
    {
        if (!$this->status->canTransitionTo(BillingEventStatus::APPROVED)) {
            throw new \InvalidArgumentException(
                sprintf('Cannot approve billing event from status %s', $this->status->value)
            );
        }

        $this->status = BillingEventStatus::APPROVED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function bill(string $settlementId): void
    {
        if (!$this->status->canTransitionTo(BillingEventStatus::BILLED)) {
            throw new \InvalidArgumentException(
                sprintf('Cannot bill event from status %s', $this->status->value)
            );
        }

        $this->status = BillingEventStatus::BILLED;
        $this->settlementId = $settlementId;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function dispute(string $reason): void
    {
        if (!$this->status->canTransitionTo(BillingEventStatus::DISPUTED)) {
            throw new \InvalidArgumentException(
                sprintf('Cannot dispute billing event from status %s', $this->status->value)
            );
        }

        $this->status = BillingEventStatus::DISPUTED;
        $this->addMetadata('dispute_reason', $reason);
        $this->addMetadata('disputed_at', new \DateTimeImmutable());
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function markPaid(): void
    {
        if (!$this->status->canTransitionTo(BillingEventStatus::PAID)) {
            throw new \InvalidArgumentException(
                sprintf('Cannot mark billing event as paid from status %s', $this->status->value)
            );
        }

        $this->status = BillingEventStatus::PAID;
        $this->addMetadata('paid_at', new \DateTimeImmutable());
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function recalculate(Money $newAmount, array $newCalculation): void
    {
        if ($this->status === BillingEventStatus::PAID) {
            throw new \InvalidArgumentException('Cannot recalculate a paid billing event');
        }

        $this->calculatedAmountValue = $newAmount->getAmount();
        $this->calculatedAmountCurrency = $newAmount->getCurrency()->getCode();
        $this->calculation = $newCalculation;
        $this->status = BillingEventStatus::CALCULATED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function addUsageEventId(string $usageEventId): void
    {
        if (!in_array($usageEventId, $this->usageEventIds, true)) {
            $this->usageEventIds[] = $usageEventId;
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function removeUsageEventId(string $usageEventId): void
    {
        $key = array_search($usageEventId, $this->usageEventIds, true);
        if ($key !== false) {
            array_splice($this->usageEventIds, $key, 1);
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function updateMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function addMetadata(string $key, mixed $value): void
    {
        $this->metadata[$key] = $value;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getBillingPeriod(): \DateInterval
    {
        return $this->billingPeriodStart->diff($this->billingPeriodEnd);
    }

    public function getBillingPeriodDays(): int
    {
        return (int) $this->getBillingPeriod()->format('%a');
    }

    public function isInPeriod(\DateTimeImmutable $date): bool
    {
        return $date >= $this->billingPeriodStart && $date <= $this->billingPeriodEnd;
    }

    public function hasUsageEvents(): bool
    {
        return count($this->usageEventIds) > 0;
    }

    public function getUsageEventCount(): int
    {
        return count($this->usageEventIds);
    }

    public function getCalculationValue(string $key): mixed
    {
        return $this->calculation[$key] ?? null;
    }

    public function hasCalculationValue(string $key): bool
    {
        return array_key_exists($key, $this->calculation);
    }

    public function getMetadataValue(string $key): mixed
    {
        return $this->metadata[$key] ?? null;
    }

    public function hasMetadata(string $key): bool
    {
        return array_key_exists($key, $this->metadata);
    }

    public function isBilled(): bool
    {
        return $this->settlementId !== null;
    }

    public function canBeModified(): bool
    {
        return in_array($this->status, [
            BillingEventStatus::CALCULATED,
            BillingEventStatus::DISPUTED,
        ], true);
    }

    public function getCalculationSummary(): string
    {
        $rateType = $this->rateCard->getRateType();
        $amount = $this->getCalculatedAmount();

        $summary = sprintf(
            '%s: %s',
            $rateType->getDisplayName(),
            $amount->getAmount() / 100 . ' ' . $amount->getCurrency()->getCode()
        );

        if ($this->hasCalculationValue('quantity')) {
            $quantity = $this->getCalculationValue('quantity');
            $unit = $this->getCalculationValue('unit') ?? 'units';
            $summary .= sprintf(' (%s %s)', $quantity, $unit);
        }

        return $summary;
    }
}
