<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Domain\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetCategory;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetType;
use Nkamuo\AssetBundle\Domain\ValueObject\AttributeType;
use Symfony\Component\Uid\Ulid;

/**
 * Asset Attribute Definition entity defining custom attribute schemas.
 *
 * Provides flexible attribute definitions that can be applied to assets
 * based on their type and category, with validation rules and inheritance.
 */
#[ORM\Entity]
#[ORM\Table(name: 'asset_attribute_definitions')]
#[ORM\Index(columns: ['asset_type', 'asset_category'])]
#[ORM\Index(columns: ['attribute_key'])]
class AssetAttributeDefinition
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid', unique: true)]
    private Ulid $id;

    #[ORM\Column(type: 'string', length: 100)]
    private string $attributeKey;

    #[ORM\Column(type: 'string', length: 255)]
    private string $displayName;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', enumType: AttributeType::class)]
    private AttributeType $attributeType;

    #[ORM\Column(type: 'string', enumType: AssetType::class, nullable: true)]
    private ?AssetType $assetType = null;

    #[ORM\Column(type: 'string', enumType: AssetCategory::class, nullable: true)]
    private ?AssetCategory $assetCategory = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isRequired = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isSearchable = true;

    #[ORM\Column(type: 'boolean')]
    private bool $allowMultipleValues = false;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $validationRules = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $enumOptions = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $defaultValue = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $unit = null;

    #[ORM\Column(type: 'integer')]
    private int $sortOrder = 0;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, AssetAttribute>
     */
    #[ORM\OneToMany(mappedBy: 'definition', targetEntity: AssetAttribute::class)]
    private Collection $attributes;

    public function __construct(
        string $attributeKey,
        string $displayName,
        AttributeType $attributeType,
        ?AssetType $assetType = null,
        ?AssetCategory $assetCategory = null
    ) {
        $this->id = new Ulid();
        $this->attributeKey = $attributeKey;
        $this->displayName = $displayName;
        $this->attributeType = $attributeType;
        $this->assetType = $assetType;
        $this->assetCategory = $assetCategory;
        $this->allowMultipleValues = $attributeType->supportsMultipleValues();
        $this->validationRules = $attributeType->getDefaultValidationRules();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->attributes = new ArrayCollection();
    }

    public function getId(): Ulid
    {
        return $this->id;
    }

    public function getAttributeKey(): string
    {
        return $this->attributeKey;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): self
    {
        $this->displayName = $displayName;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getAttributeType(): AttributeType
    {
        return $this->attributeType;
    }

    public function getAssetType(): ?AssetType
    {
        return $this->assetType;
    }

    public function getAssetCategory(): ?AssetCategory
    {
        return $this->assetCategory;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function setRequired(bool $isRequired): self
    {
        $this->isRequired = $isRequired;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isSearchable(): bool
    {
        return $this->isSearchable;
    }

    public function setSearchable(bool $isSearchable): self
    {
        $this->isSearchable = $isSearchable;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function allowsMultipleValues(): bool
    {
        return $this->allowMultipleValues;
    }

    public function setAllowMultipleValues(bool $allowMultipleValues): self
    {
        $this->allowMultipleValues = $allowMultipleValues;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getValidationRules(): ?array
    {
        return $this->validationRules;
    }

    public function setValidationRules(?array $validationRules): self
    {
        $this->validationRules = $validationRules;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getEnumOptions(): ?array
    {
        return $this->enumOptions;
    }

    public function setEnumOptions(?array $enumOptions): self
    {
        if ($this->attributeType !== AttributeType::ENUM && $enumOptions !== null) {
            throw new \InvalidArgumentException('Enum options can only be set for ENUM attribute types');
        }
        $this->enumOptions = $enumOptions;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    public function setDefaultValue(?string $defaultValue): self
    {
        $this->defaultValue = $defaultValue;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): self
    {
        $this->unit = $unit;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
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
     * @return Collection<int, AssetAttribute>
     */
    public function getAttributes(): Collection
    {
        return $this->attributes;
    }

    /**
     * Check if this definition applies to a specific asset.
     */
    public function appliesTo(Asset $asset): bool
    {
        // If no specific type/category is set, applies to all assets
        if ($this->assetType === null && $this->assetCategory === null) {
            return true;
        }

        // Check asset type match
        if ($this->assetType !== null && $asset->getType() !== $this->assetType) {
            return false;
        }

        // Check asset category match
        if ($this->assetCategory !== null && $asset->getCategory() !== $this->assetCategory) {
            return false;
        }

        return true;
    }

    /**
     * Validate a value against this definition's rules.
     */
    public function validateValue(mixed $value): array
    {
        $errors = [];

        // Check required
        if ($this->isRequired && ($value === null || $value === '')) {
            $errors[] = "Attribute '{$this->displayName}' is required";
        }

        if ($value === null || $value === '') {
            return $errors;
        }

        // Type-specific validation
        $errors = array_merge($errors, $this->validateByType($value));

        // Custom validation rules
        if ($this->validationRules) {
            $errors = array_merge($errors, $this->validateCustomRules($value));
        }

        return $errors;
    }

    private function validateByType(mixed $value): array
    {
        $errors = [];

        switch ($this->attributeType) {
            case AttributeType::INTEGER:
                if (!is_numeric($value) || (int)$value != $value) {
                    $errors[] = "Value must be a whole number";
                }
                break;

            case AttributeType::FLOAT:
                if (!is_numeric($value)) {
                    $errors[] = "Value must be a number";
                }
                break;

            case AttributeType::BOOLEAN:
                if (!is_bool($value) && !in_array($value, ['true', 'false', '1', '0', 1, 0], true)) {
                    $errors[] = "Value must be true or false";
                }
                break;

            case AttributeType::DATE:
                if (!$this->isValidDate($value)) {
                    $errors[] = "Value must be a valid date";
                }
                break;

            case AttributeType::DATETIME:
                if (!$this->isValidDateTime($value)) {
                    $errors[] = "Value must be a valid date and time";
                }
                break;

            case AttributeType::EMAIL:
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Value must be a valid email address";
                }
                break;

            case AttributeType::URL:
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    $errors[] = "Value must be a valid URL";
                }
                break;

            case AttributeType::ENUM:
                if ($this->enumOptions && !in_array($value, $this->enumOptions, true)) {
                    $errors[] = "Value must be one of: " . implode(', ', $this->enumOptions);
                }
                break;
        }

        return $errors;
    }

    private function validateCustomRules(mixed $value): array
    {
        $errors = [];

        foreach ($this->validationRules as $rule => $constraint) {
            switch ($rule) {
                case 'min':
                    if (is_numeric($value) && $value < $constraint) {
                        $errors[] = "Value must be at least {$constraint}";
                    }
                    break;

                case 'max':
                    if (is_numeric($value) && $value > $constraint) {
                        $errors[] = "Value must be at most {$constraint}";
                    }
                    break;

                case 'pattern':
                    if (is_string($value) && !preg_match($constraint, $value)) {
                        $errors[] = "Value does not match required format";
                    }
                    break;

                case 'length':
                    if (is_string($value)) {
                        $length = strlen($value);
                        if (isset($constraint['min']) && $length < $constraint['min']) {
                            $errors[] = "Value must be at least {$constraint['min']} characters";
                        }
                        if (isset($constraint['max']) && $length > $constraint['max']) {
                            $errors[] = "Value must be at most {$constraint['max']} characters";
                        }
                    }
                    break;
            }
        }

        return $errors;
    }

    private function isValidDate(mixed $value): bool
    {
        if ($value instanceof \DateTimeInterface) {
            return true;
        }

        if (is_string($value)) {
            return \DateTime::createFromFormat('Y-m-d', $value) !== false;
        }

        return false;
    }

    private function isValidDateTime(mixed $value): bool
    {
        if ($value instanceof \DateTimeInterface) {
            return true;
        }

        if (is_string($value)) {
            return \DateTime::createFromFormat('Y-m-d H:i:s', $value) !== false ||
                   \DateTime::createFromFormat(\DateTime::ISO8601, $value) !== false;
        }

        return false;
    }
}
