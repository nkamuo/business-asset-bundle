<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nkamuo\AssetBundle\Domain\ValueObject\RateType;
use Symfony\Component\Uid\Ulid;
use Money\Money;

/**
 * Asset Rate Card entity defining billing rates for asset provisions
 * 
 * Provides flexible rate structures supporting various billing models
 * including fixed rates, usage-based rates, and complex tiered pricing.
 */
#[ORM\Entity]
#[ORM\Table(name: 'nkamuo_asset_rate_card')]
#[ORM\Index(columns: ['provision_id'], name: 'idx_rate_card_provision')]
#[ORM\Index(columns: ['rate_type'], name: 'idx_rate_card_type')]
#[ORM\Index(columns: ['effective_date'], name: 'idx_rate_card_effective')]
class AssetRateCard
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid')]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: AssetProvision::class, inversedBy: 'rateCards')]
    #[ORM\JoinColumn(nullable: false)]
    private AssetProvision $provision;

    #[ORM\Column(type: 'string', enumType: RateType::class)]
    private RateType $rateType;

    #[ORM\Column(type: 'integer')]
    private int $rateAmount; // Money amount in cents

    #[ORM\Column(type: 'string', length: 3)]
    private string $rateCurrency; // Currency code

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $unitOfMeasure = null; // 'mile', 'hour', 'day', 'trip', etc.

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $minimumChargeAmount = null; // Minimum charge in cents

    #[ORM\Column(type: 'string', length: 3, nullable: true)]
    private ?string $minimumChargeCurrency = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $maximumChargeAmount = null; // Maximum charge in cents

    #[ORM\Column(type: 'string', length: 3, nullable: true)]
    private ?string $maximumChargeCurrency = null;

    #[ORM\Column(type: 'json')]
    private array $conditions = []; // When this rate applies (JSON)

    #[ORM\Column(type: 'json')]
    private array $tierStructure = []; // For tiered rates (JSON)

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $effectiveDate;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $expiryDate = null;

    #[ORM\Column(type: 'json')]
    private array $metadata = []; // Additional flexible data

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        AssetProvision $provision,
        RateType $rateType,
        Money $rate,
        \DateTimeImmutable $effectiveDate,
        ?string $unitOfMeasure = null,
        ?Money $minimumCharge = null,
        ?Money $maximumCharge = null,
        ?\DateTimeImmutable $expiryDate = null,
        array $conditions = [],
        array $tierStructure = [],
        array $metadata = []
    ) {
        $this->id = new Ulid();
        $this->provision = $provision;
        $this->rateType = $rateType;
        $this->rateAmount = $rate->getAmount();
        $this->rateCurrency = $rate->getCurrency()->getCode();
        $this->unitOfMeasure = $unitOfMeasure;
        $this->effectiveDate = $effectiveDate;
        $this->expiryDate = $expiryDate;
        $this->conditions = $conditions;
        $this->tierStructure = $tierStructure;
        $this->metadata = $metadata;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();

        if ($minimumCharge) {
            $this->minimumChargeAmount = $minimumCharge->getAmount();
            $this->minimumChargeCurrency = $minimumCharge->getCurrency()->getCode();
        }

        if ($maximumCharge) {
            $this->maximumChargeAmount = $maximumCharge->getAmount();
            $this->maximumChargeCurrency = $maximumCharge->getCurrency()->getCode();
        }

        $provision->addRateCard($this);
    }

    // Getters
    public function getId(): Ulid
    {
        return $this->id;
    }

    public function getProvision(): AssetProvision
    {
        return $this->provision;
    }

    public function getRateType(): RateType
    {
        return $this->rateType;
    }

    public function getRate(): Money
    {
        return new Money($this->rateAmount, new \Money\Currency($this->rateCurrency));
    }

    public function getUnitOfMeasure(): ?string
    {
        return $this->unitOfMeasure;
    }

    public function getMinimumCharge(): ?Money
    {
        if ($this->minimumChargeAmount === null || $this->minimumChargeCurrency === null) {
            return null;
        }

        return new Money($this->minimumChargeAmount, new \Money\Currency($this->minimumChargeCurrency));
    }

    public function getMaximumCharge(): ?Money
    {
        if ($this->maximumChargeAmount === null || $this->maximumChargeCurrency === null) {
            return null;
        }

        return new Money($this->maximumChargeAmount, new \Money\Currency($this->maximumChargeCurrency));
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function getTierStructure(): array
    {
        return $this->tierStructure;
    }

    public function getEffectiveDate(): \DateTimeImmutable
    {
        return $this->effectiveDate;
    }

    public function getExpiryDate(): ?\DateTimeImmutable
    {
        return $this->expiryDate;
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
    public function updateRate(Money $rate): void
    {
        $this->rateAmount = $rate->getAmount();
        $this->rateCurrency = $rate->getCurrency()->getCode();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateMinimumCharge(?Money $minimumCharge): void
    {
        if ($minimumCharge) {
            $this->minimumChargeAmount = $minimumCharge->getAmount();
            $this->minimumChargeCurrency = $minimumCharge->getCurrency()->getCode();
        } else {
            $this->minimumChargeAmount = null;
            $this->minimumChargeCurrency = null;
        }
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateMaximumCharge(?Money $maximumCharge): void
    {
        if ($maximumCharge) {
            $this->maximumChargeAmount = $maximumCharge->getAmount();
            $this->maximumChargeCurrency = $maximumCharge->getCurrency()->getCode();
        } else {
            $this->maximumChargeAmount = null;
            $this->maximumChargeCurrency = null;
        }
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateConditions(array $conditions): void
    {
        $this->conditions = $conditions;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function addCondition(string $key, mixed $value): void
    {
        $this->conditions[$key] = $value;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateTierStructure(array $tierStructure): void
    {
        $this->tierStructure = $tierStructure;
        $this->updatedAt = new \DateTimeImmutable();
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

    public function extendExpiryDate(\DateTimeImmutable $newExpiryDate): void
    {
        if ($this->expiryDate && $newExpiryDate <= $this->expiryDate) {
            throw new \InvalidArgumentException('New expiry date must be after current expiry date');
        }

        $this->expiryDate = $newExpiryDate;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function removeExpiryDate(): void
    {
        $this->expiryDate = null;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function isActiveAt(\DateTimeImmutable $date): bool
    {
        if ($date < $this->effectiveDate) {
            return false;
        }

        if ($this->expiryDate && $date > $this->expiryDate) {
            return false;
        }

        return true;
    }

    public function isActive(): bool
    {
        return $this->isActiveAt(new \DateTimeImmutable());
    }

    public function isExpired(): bool
    {
        return $this->expiryDate && new \DateTimeImmutable() > $this->expiryDate;
    }

    public function getCondition(string $key): mixed
    {
        return $this->conditions[$key] ?? null;
    }

    public function hasCondition(string $key): bool
    {
        return array_key_exists($key, $this->conditions);
    }

    public function meetsConditions(array $context): bool
    {
        foreach ($this->conditions as $key => $expectedValue) {
            $actualValue = $context[$key] ?? null;
            
            if ($actualValue !== $expectedValue) {
                return false;
            }
        }

        return true;
    }

    public function calculateAmount(float $quantity, array $context = []): Money
    {
        if (!$this->meetsConditions($context)) {
            return new Money(0, new \Money\Currency($this->rateCurrency));
        }

        $amount = match($this->rateType) {
            RateType::FIXED_DAILY, RateType::FIXED_MONTHLY => $this->getRate(),
            RateType::PER_MILE, RateType::PER_HOUR, RateType::PER_TRIP => 
                $this->getRate()->multiply($quantity),
            RateType::PERCENTAGE_REVENUE => $this->calculatePercentageAmount($quantity),
            RateType::TIERED => $this->calculateTieredAmount($quantity),
            default => new Money(0, new \Money\Currency($this->rateCurrency))
        };

        return $this->applyLimits($amount);
    }

    private function calculatePercentageAmount(float $revenueAmount): Money
    {
        $percentage = $this->getRate()->getAmount() / 10000; // Rate stored as basis points
        $calculatedAmount = (int) round($revenueAmount * $percentage);
        
        return new Money($calculatedAmount, new \Money\Currency($this->rateCurrency));
    }

    private function calculateTieredAmount(float $quantity): Money
    {
        $totalAmount = 0;
        $remainingQuantity = $quantity;

        foreach ($this->tierStructure as $tier) {
            $tierLimit = $tier['limit'] ?? PHP_FLOAT_MAX;
            $tierRate = $tier['rate'] ?? 0;
            
            $tierQuantity = min($remainingQuantity, $tierLimit);
            $totalAmount += $tierQuantity * $tierRate;
            
            $remainingQuantity -= $tierQuantity;
            
            if ($remainingQuantity <= 0) {
                break;
            }
        }

        return new Money((int) round($totalAmount), new \Money\Currency($this->rateCurrency));
    }

    private function applyLimits(Money $amount): Money
    {
        $minimumCharge = $this->getMinimumCharge();
        $maximumCharge = $this->getMaximumCharge();

        if ($minimumCharge && $amount->lessThan($minimumCharge)) {
            return $minimumCharge;
        }

        if ($maximumCharge && $amount->greaterThan($maximumCharge)) {
            return $maximumCharge;
        }

        return $amount;
    }

    public function getDuration(): ?\DateInterval
    {
        if (!$this->expiryDate) {
            return null;
        }

        return $this->effectiveDate->diff($this->expiryDate);
    }

    public function getRemainingDuration(\DateTimeImmutable $at = null): ?\DateInterval
    {
        $at = $at ?? new \DateTimeImmutable();

        if (!$this->expiryDate || $at > $this->expiryDate) {
            return null;
        }

        if ($at < $this->effectiveDate) {
            return $this->effectiveDate->diff($this->expiryDate);
        }

        return $at->diff($this->expiryDate);
    }
}
