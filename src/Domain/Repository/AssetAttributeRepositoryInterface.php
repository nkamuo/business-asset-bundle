<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Domain\Repository;

use Nkamuo\AssetBundle\Domain\Entity\Asset;
use Nkamuo\AssetBundle\Domain\Entity\AssetAttribute;
use Nkamuo\AssetBundle\Domain\Entity\AssetAttributeDefinition;
use Symfony\Component\Uid\Ulid;

/**
 * Repository interface for AssetAttribute entities.
 */
interface AssetAttributeRepositoryInterface
{
    /**
     * Save an asset attribute.
     */
    public function save(AssetAttribute $attribute): void;

    /**
     * Remove an asset attribute.
     */
    public function remove(AssetAttribute $attribute): void;

    /**
     * Find attribute by ID.
     */
    public function findById(Ulid $id): ?AssetAttribute;

    /**
     * Find attributes by asset.
     */
    public function findByAsset(Asset $asset): array;

    /**
     * Find attributes by asset ID.
     */
    public function findByAssetId(Ulid $assetId): array;

    /**
     * Find attributes by definition.
     */
    public function findByDefinition(AssetAttributeDefinition $definition): array;

    /**
     * Find attribute by asset and definition.
     */
    public function findByAssetAndDefinition(Asset $asset, AssetAttributeDefinition $definition): ?AssetAttribute;

    /**
     * Find attributes by asset and attribute key.
     */
    public function findByAssetAndKey(Asset $asset, string $attributeKey): ?AssetAttribute;

    /**
     * Find attributes with specific value.
     */
    public function findByValue(mixed $value, ?AssetAttributeDefinition $definition = null): array;

    /**
     * Search attributes by text value.
     */
    public function searchByText(string $searchTerm): array;

    /**
     * Find attributes within date range.
     */
    public function findByDateRange(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array;

    /**
     * Find attributes within numeric range.
     */
    public function findByNumericRange(float $min, float $max, ?AssetAttributeDefinition $definition = null): array;

    /**
     * Get distinct values for an attribute definition.
     */
    public function getDistinctValues(AssetAttributeDefinition $definition, int $limit = 100): array;

    /**
     * Count attributes by definition.
     */
    public function countByDefinition(AssetAttributeDefinition $definition): int;

    /**
     * Find attributes updated after specific date.
     */
    public function findUpdatedAfter(\DateTimeImmutable $date): array;

    /**
     * Get attribute statistics for a definition.
     */
    public function getStatistics(AssetAttributeDefinition $definition): array;

    /**
     * Bulk delete attributes by asset.
     */
    public function deleteByAsset(Asset $asset): int;

    /**
     * Bulk delete attributes by definition.
     */
    public function deleteByDefinition(AssetAttributeDefinition $definition): int;
}
