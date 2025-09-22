<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nkamuo\AssetBundle\Domain\ValueObject\UsageType;
use Symfony\Component\Uid\Ulid;

/**
 * Asset Usage Event entity tracking asset utilization
 * 
 * Records specific usage events for assets enabling
 * accurate billing calculation and performance analysis.
 */
#[ORM\Entity]
#[ORM\Table(name: 'nkamuo_asset_usage_event')]
#[ORM\Index(columns: ['asset_id'], name: 'idx_usage_asset')]
#[ORM\Index(columns: ['usage_type'], name: 'idx_usage_type')]
#[ORM\Index(columns: ['start_time'], name: 'idx_usage_start_time')]
class AssetUsageEvent
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid')]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Asset::class, inversedBy: 'usageEvents')]
    #[ORM\JoinColumn(nullable: false)]
    private Asset $asset;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $sourceEntityType = null; // 'shipment', 'route', 'task'

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $sourceEntityId = null;

    #[ORM\Column(type: 'string', enumType: UsageType::class)]
    private UsageType $usageType;

    #[ORM\Column(type: 'float')]
    private float $quantity;

    #[ORM\Column(type: 'string', length: 50)]
    private string $unitOfMeasure;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $startTime;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $endTime = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: 'json')]
    private array $metadata = []; // GPS coordinates, fuel consumption, etc.

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Asset $asset,
        UsageType $usageType,
        float $quantity,
        string $unitOfMeasure,
        \DateTimeImmutable $startTime,
        ?\DateTimeImmutable $endTime = null,
        ?string $sourceEntityType = null,
        ?string $sourceEntityId = null,
        ?string $location = null,
        array $metadata = []
    ) {
        $this->id = new Ulid();
        $this->asset = $asset;
        $this->usageType = $usageType;
        $this->quantity = $quantity;
        $this->unitOfMeasure = $unitOfMeasure;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->sourceEntityType = $sourceEntityType;
        $this->sourceEntityId = $sourceEntityId;
        $this->location = $location;
        $this->metadata = $metadata;
        $this->createdAt = new \DateTimeImmutable();
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

    public function getSourceEntityType(): ?string
    {
        return $this->sourceEntityType;
    }

    public function getSourceEntityId(): ?string
    {
        return $this->sourceEntityId;
    }

    public function getUsageType(): UsageType
    {
        return $this->usageType;
    }

    public function getQuantity(): float
    {
        return $this->quantity;
    }

    public function getUnitOfMeasure(): string
    {
        return $this->unitOfMeasure;
    }

    public function getStartTime(): \DateTimeImmutable
    {
        return $this->startTime;
    }

    public function getEndTime(): ?\DateTimeImmutable
    {
        return $this->endTime;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    // Business methods
    public function complete(\DateTimeImmutable $endTime): void
    {
        if ($endTime <= $this->startTime) {
            throw new \InvalidArgumentException('End time must be after start time');
        }

        $this->endTime = $endTime;
    }

    public function updateLocation(string $location): void
    {
        $this->location = $location;
    }

    public function updateMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function addMetadata(string $key, mixed $value): void
    {
        $this->metadata[$key] = $value;
    }

    public function getDuration(): ?\DateInterval
    {
        if (!$this->endTime) {
            return null;
        }

        return $this->startTime->diff($this->endTime);
    }

    public function getDurationInSeconds(): ?int
    {
        $duration = $this->getDuration();
        if (!$duration) {
            return null;
        }

        return ($duration->days * 24 * 60 * 60) + 
               ($duration->h * 60 * 60) + 
               ($duration->i * 60) + 
               $duration->s;
    }

    public function getDurationInHours(): ?float
    {
        $seconds = $this->getDurationInSeconds();
        return $seconds ? $seconds / 3600 : null;
    }

    public function isCompleted(): bool
    {
        return $this->endTime !== null;
    }

    public function isOngoing(): bool
    {
        return $this->endTime === null && $this->startTime <= new \DateTimeImmutable();
    }

    public function hasSource(): bool
    {
        return $this->sourceEntityType !== null && $this->sourceEntityId !== null;
    }

    public function getMetadataValue(string $key): mixed
    {
        return $this->metadata[$key] ?? null;
    }

    public function hasMetadata(string $key): bool
    {
        return array_key_exists($key, $this->metadata);
    }

    public function isAtLocation(): bool
    {
        return $this->location !== null;
    }

    public function calculateRate(float $ratePerUnit): float
    {
        return $this->quantity * $ratePerUnit;
    }
}
