<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Domain\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Money\Money;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetCategory;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetStatus;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetType;
use Symfony\Component\Uid\Ulid;

/**
 * Core Asset entity representing any manageable resource.
 *
 * Supports various asset types: vehicles, human resources, equipment,
 * infrastructure, and technology assets with flexible specifications
 * and comprehensive tracking capabilities.
 */
#[ORM\Entity]
#[ORM\Table(name: 'nkamuo_asset')]
#[ORM\Index(columns: ['asset_number'], name: 'idx_asset_number')]
#[ORM\Index(columns: ['type'], name: 'idx_asset_type')]
#[ORM\Index(columns: ['status'], name: 'idx_asset_status')]
class Asset
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid')]
    private Ulid $id;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private string $assetNumber;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', enumType: AssetType::class)]
    private AssetType $type;

    #[ORM\Column(type: 'string', enumType: AssetCategory::class)]
    private AssetCategory $category;

    #[ORM\Column(type: 'ulid')]
    private Ulid $ownerId; // Partner who owns this asset

    #[ORM\Column(type: 'ulid', nullable: true)]
    private ?Ulid $operatorId = null; // Partner who operates/manages it

    #[ORM\Column(type: 'string', enumType: AssetStatus::class)]
    private AssetStatus $status;

    #[ORM\Column(type: 'json')]
    private array $specifications = []; // Technical specs as JSON

    #[ORM\Column(type: 'json')]
    private array $identifiers = []; // VIN, license, serial numbers as JSON

    #[ORM\Column(type: 'string')]
    private string $acquisitionCostAmount; // Money amount in cents

    #[ORM\Column(type: 'string', length: 3)]
    private string $acquisitionCostCurrency; // Currency code

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $acquisitionDate = null;

    #[ORM\Column(type: 'json')]
    private array $metadata = []; // Flexible additional data

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(mappedBy: 'asset', targetEntity: AssetProvision::class)]
    private Collection $provisions;

    #[ORM\OneToMany(mappedBy: 'asset', targetEntity: AssetBillingEvent::class)]
    private Collection $billingEvents;

    #[ORM\OneToMany(mappedBy: 'asset', targetEntity: AssetUsageEvent::class)]
    private Collection $usageEvents;

    /**
     * @var Collection<int, AssetAttribute>
     */
    #[ORM\OneToMany(mappedBy: 'asset', targetEntity: AssetAttribute::class, cascade: ['persist', 'remove'])]
    private Collection $customAttributes;

    public function __construct(
        string $assetNumber,
        string $name,
        AssetType $type,
        AssetCategory $category,
        Ulid $ownerId,
        Money $acquisitionCost,
        AssetStatus $status = AssetStatus::AVAILABLE,
        ?\DateTimeImmutable $acquisitionDate = null,
        array $specifications = [],
        array $identifiers = [],
        array $metadata = []
    ) {
        $this->id = new Ulid();
        $this->assetNumber = $assetNumber;
        $this->name = $name;
        $this->type = $type;
        $this->category = $category;
        $this->ownerId = $ownerId;
        $this->status = $status;
        $this->specifications = $specifications;
        $this->identifiers = $identifiers;
        $this->acquisitionCostAmount = $acquisitionCost->getAmount();
        $this->acquisitionCostCurrency = $acquisitionCost->getCurrency()->getCode();
        $this->acquisitionDate = $acquisitionDate;
        $this->metadata = $metadata;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->provisions = new ArrayCollection();
        $this->billingEvents = new ArrayCollection();
        $this->usageEvents = new ArrayCollection();
        $this->customAttributes = new ArrayCollection();
    }

    // Getters
    public function getId(): Ulid
    {
        return $this->id;
    }

    public function getAssetNumber(): string
    {
        return $this->assetNumber;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): AssetType
    {
        return $this->type;
    }

    public function getCategory(): AssetCategory
    {
        return $this->category;
    }

    public function getOwnerId(): Ulid
    {
        return $this->ownerId;
    }

    public function getOperatorId(): ?Ulid
    {
        return $this->operatorId;
    }

    public function getStatus(): AssetStatus
    {
        return $this->status;
    }

    public function getSpecifications(): array
    {
        return $this->specifications;
    }

    public function getIdentifiers(): array
    {
        return $this->identifiers;
    }

    public function getAcquisitionCost(): Money
    {
        return new Money($this->acquisitionCostAmount, new \Money\Currency($this->acquisitionCostCurrency));
    }

    public function getAcquisitionDate(): ?\DateTimeImmutable
    {
        return $this->acquisitionDate;
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

    public function getProvisions(): Collection
    {
        return $this->provisions;
    }

    public function getBillingEvents(): Collection
    {
        return $this->billingEvents;
    }

    public function getUsageEvents(): Collection
    {
        return $this->usageEvents;
    }

    // Business methods
    public function updateStatus(AssetStatus $status): void
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function assignOperator(Ulid $operatorId): void
    {
        $this->operatorId = $operatorId;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function removeOperator(): void
    {
        $this->operatorId = null;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateSpecifications(array $specifications): void
    {
        $this->specifications = $specifications;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function addSpecification(string $key, mixed $value): void
    {
        $this->specifications[$key] = $value;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateIdentifiers(array $identifiers): void
    {
        $this->identifiers = $identifiers;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function addIdentifier(string $type, string $value): void
    {
        $this->identifiers[$type] = $value;
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

    public function isAvailable(): bool
    {
        return $this->status === AssetStatus::AVAILABLE;
    }

    public function isInService(): bool
    {
        return $this->status === AssetStatus::IN_SERVICE;
    }

    public function isInMaintenance(): bool
    {
        return $this->status === AssetStatus::MAINTENANCE;
    }

    public function getActiveProvision(\DateTimeImmutable $at = null): ?AssetProvision
    {
        $at = $at ?? new \DateTimeImmutable();

        foreach ($this->provisions as $provision) {
            if ($provision->isActiveAt($at)) {
                return $provision;
            }
        }

        return null;
    }

    /**
     * @return Collection<int, AssetAttribute>
     */
    public function getCustomAttributes(): Collection
    {
        return $this->customAttributes;
    }

    /**
     * Get custom attribute by key.
     */
    public function getCustomAttribute(string $key): ?AssetAttribute
    {
        foreach ($this->customAttributes as $attribute) {
            if ($attribute->getAttributeKey() === $key) {
                return $attribute;
            }
        }

        return null;
    }

    /**
     * Get custom attribute value by key.
     */
    public function getCustomAttributeValue(string $key): mixed
    {
        $attribute = $this->getCustomAttribute($key);
        return $attribute?->getValue();
    }

    /**
     * Set custom attribute value.
     */
    public function setCustomAttribute(AssetAttributeDefinition $definition, mixed $value): self
    {
        // Check if attribute already exists
        $existingAttribute = $this->getCustomAttribute($definition->getAttributeKey());
        
        if ($existingAttribute) {
            $existingAttribute->setValue($value);
        } else {
            $newAttribute = new AssetAttribute($this, $definition, $value);
            $this->customAttributes->add($newAttribute);
        }

        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Remove custom attribute by key.
     */
    public function removeCustomAttribute(string $key): self
    {
        $attribute = $this->getCustomAttribute($key);
        if ($attribute) {
            $this->customAttributes->removeElement($attribute);
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    /**
     * Get all custom attributes as key-value array.
     */
    public function getCustomAttributesArray(): array
    {
        $attributes = [];
        foreach ($this->customAttributes as $attribute) {
            $attributes[$attribute->getAttributeKey()] = $attribute->getValue();
        }

        return $attributes;
    }

    /**
     * Check if asset has a specific custom attribute.
     */
    public function hasCustomAttribute(string $key): bool
    {
        return $this->getCustomAttribute($key) !== null;
    }
}
