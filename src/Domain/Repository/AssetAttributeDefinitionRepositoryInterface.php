<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Domain\Repository;

use Nkamuo\AssetBundle\Domain\Entity\AssetAttributeDefinition;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetCategory;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetType;
use Nkamuo\AssetBundle\Domain\ValueObject\AttributeType;
use Symfony\Component\Uid\Ulid;

/**
 * Repository interface for AssetAttributeDefinition entities.
 */
interface AssetAttributeDefinitionRepositoryInterface
{
    /**
     * Save an asset attribute definition.
     */
    public function save(AssetAttributeDefinition $definition): void;

    /**
     * Remove an asset attribute definition.
     */
    public function remove(AssetAttributeDefinition $definition): void;

    /**
     * Find definition by ID.
     */
    public function findById(Ulid $id): ?AssetAttributeDefinition;

    /**
     * Find definition by attribute key.
     */
    public function findByKey(string $key): ?AssetAttributeDefinition;

    /**
     * Find definitions applicable to an asset type.
     */
    public function findByAssetType(?AssetType $assetType = null): array;

    /**
     * Find definitions applicable to an asset category.
     */
    public function findByAssetCategory(?AssetCategory $assetCategory = null): array;

    /**
     * Find definitions by attribute type.
     */
    public function findByAttributeType(AttributeType $attributeType): array;

    /**
     * Find searchable definitions.
     */
    public function findSearchable(): array;

    /**
     * Find required definitions for a specific asset type/category.
     */
    public function findRequired(?AssetType $assetType = null, ?AssetCategory $assetCategory = null): array;

    /**
     * Find active definitions.
     */
    public function findActive(): array;

    /**
     * Find definitions applicable to a specific asset.
     */
    public function findApplicableToAsset(AssetType $assetType, AssetCategory $assetCategory): array;

    /**
     * Get all definitions ordered by sort order.
     */
    public function findAllOrdered(): array;

    /**
     * Check if attribute key is unique.
     */
    public function isKeyUnique(string $key, ?Ulid $excludeId = null): bool;

    /**
     * Find definitions with validation rules.
     */
    public function findWithValidationRules(): array;

    /**
     * Get attribute keys by type.
     */
    public function getKeysByType(AttributeType $attributeType): array;
}
