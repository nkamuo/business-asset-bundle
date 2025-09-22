<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Application\Command;

use Nkamuo\AssetBundle\Domain\ValueObject\AssetCategory;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetType;
use Nkamuo\AssetBundle\Domain\ValueObject\AttributeType;

/**
 * Command to create a new asset attribute definition.
 */
readonly class CreateAssetAttributeDefinitionCommand
{
    public function __construct(
        public string $attributeKey,
        public string $displayName,
        public AttributeType $attributeType,
        public ?string $description = null,
        public ?AssetType $assetType = null,
        public ?AssetCategory $assetCategory = null,
        public bool $isRequired = false,
        public bool $isSearchable = true,
        public bool $allowMultipleValues = false,
        public ?array $validationRules = null,
        public ?array $enumOptions = null,
        public ?string $defaultValue = null,
        public ?string $unit = null,
        public int $sortOrder = 0
    ) {}
}
