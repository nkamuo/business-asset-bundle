<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

/**
 * Asset Attribute entity storing custom attribute values for assets.
 *
 * Provides flexible storage for dynamic asset attributes with type safety
 * and validation through attribute definitions.
 */
#[ORM\Entity]
#[ORM\Table(name: 'asset_attributes')]
#[ORM\Index(columns: ['asset_id', 'definition_id'])]
#[ORM\UniqueConstraint(columns: ['asset_id', 'definition_id'], name: 'unique_asset_attribute')]
class AssetAttribute
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid', unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Asset::class, inversedBy: 'customAttributes')]
    #[ORM\JoinColumn(nullable: false)]
    private Asset $asset;

    #[ORM\ManyToOne(targetEntity: AssetAttributeDefinition::class, inversedBy: 'attributes')]
    #[ORM\JoinColumn(nullable: false)]
    private AssetAttributeDefinition $definition;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $stringValue = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $integerValue = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $floatValue = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $booleanValue = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateValue = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $datetimeValue = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $jsonValue = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        Asset $asset,
        AssetAttributeDefinition $definition,
        mixed $value = null
    ) {
        $this->id = new Ulid();
        $this->asset = $asset;
        $this->definition = $definition;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();

        if ($value !== null) {
            $this->setValue($value);
        }
    }

    public function getId(): Ulid
    {
        return $this->id;
    }

    public function getAsset(): Asset
    {
        return $this->asset;
    }

    public function getDefinition(): AssetAttributeDefinition
    {
        return $this->definition;
    }

    public function getValue(): mixed
    {
        return match($this->definition->getAttributeType()) {
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::STRING,
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::EMAIL,
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::URL,
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::PHONE,
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::ENUM => $this->stringValue,
            
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::INTEGER => $this->integerValue,
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::FLOAT,
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::CURRENCY,
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::PERCENTAGE => $this->floatValue,
            
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::BOOLEAN => $this->booleanValue,
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::DATE => $this->dateValue,
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::DATETIME => $this->datetimeValue,
            
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::JSON,
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::COORDINATE,
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::FILE => $this->jsonValue,
        };
    }

    public function setValue(mixed $value): self
    {
        // Validate the value against the definition
        $errors = $this->definition->validateValue($value);
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Validation failed: ' . implode(', ', $errors));
        }

        // Clear all value fields first
        $this->clearAllValues();

        // Set the appropriate field based on attribute type
        match($this->definition->getAttributeType()) {
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::STRING,
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::EMAIL,
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::URL,
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::PHONE,
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::ENUM => $this->stringValue = (string)$value,
            
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::INTEGER => $this->integerValue = (int)$value,
            
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::FLOAT,
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::CURRENCY,
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::PERCENTAGE => $this->floatValue = (float)$value,
            
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::BOOLEAN => $this->booleanValue = $this->convertToBoolean($value),
            
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::DATE => $this->dateValue = $this->convertToDate($value),
            
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::DATETIME => $this->datetimeValue = $this->convertToDateTime($value),
            
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::JSON,
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::COORDINATE,
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::FILE => $this->jsonValue = is_array($value) ? $value : json_decode((string)$value, true),
        };

        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getDisplayValue(): string
    {
        $value = $this->getValue();

        if ($value === null) {
            return '';
        }

        return match($this->definition->getAttributeType()) {
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::BOOLEAN => $value ? 'Yes' : 'No',
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::DATE => $value->format('Y-m-d'),
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::DATETIME => $value->format('Y-m-d H:i:s'),
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::CURRENCY => $this->formatCurrency($value),
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::PERCENTAGE => $value . '%',
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::JSON,
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::COORDINATE,
            \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::FILE => json_encode($value),
            default => (string)$value,
        };
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Get the attribute key for easy access.
     */
    public function getAttributeKey(): string
    {
        return $this->definition->getAttributeKey();
    }

    /**
     * Get the display name for this attribute.
     */
    public function getDisplayName(): string
    {
        return $this->definition->getDisplayName();
    }

    /**
     * Check if this attribute has a non-null value.
     */
    public function hasValue(): bool
    {
        return $this->getValue() !== null;
    }

    private function clearAllValues(): void
    {
        $this->stringValue = null;
        $this->integerValue = null;
        $this->floatValue = null;
        $this->booleanValue = null;
        $this->dateValue = null;
        $this->datetimeValue = null;
        $this->jsonValue = null;
    }

    private function convertToBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['true', '1', 'yes', 'on'], true);
        }

        return (bool)$value;
    }

    private function convertToDate(mixed $value): \DateTimeImmutable
    {
        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof \DateTime) {
            return \DateTimeImmutable::createFromMutable($value);
        }

        if (is_string($value)) {
            return new \DateTimeImmutable($value);
        }

        throw new \InvalidArgumentException('Cannot convert value to date');
    }

    private function convertToDateTime(mixed $value): \DateTimeImmutable
    {
        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof \DateTime) {
            return \DateTimeImmutable::createFromMutable($value);
        }

        if (is_string($value)) {
            return new \DateTimeImmutable($value);
        }

        throw new \InvalidArgumentException('Cannot convert value to datetime');
    }

    private function formatCurrency(float $value): string
    {
        // This is a simple implementation - in a real system you'd want
        // to use the Money library or proper locale formatting
        return '$' . number_format($value, 2);
    }
}
