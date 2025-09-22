<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Domain\Repository;

use Nkamuo\AssetBundle\Domain\Entity\Asset;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetStatus;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetType;
use Symfony\Component\Uid\Ulid;

/**
 * Repository interface for Asset entities.
 */
interface AssetRepositoryInterface
{
    public function save(Asset $asset): void;

    public function delete(Asset $asset): void;

    public function findById(Ulid $id): ?Asset;

    public function findByAssetNumber(string $assetNumber): ?Asset;

    /**
     * @return Asset[]
     */
    public function findByOwner(Ulid $ownerId): array;

    /**
     * @return Asset[]
     */
    public function findByOperator(Ulid $operatorId): array;

    /**
     * @return Asset[]
     */
    public function findByType(AssetType $type): array;

    /**
     * @return Asset[]
     */
    public function findByStatus(AssetStatus $status): array;

    /**
     * @return Asset[]
     */
    public function findAvailableAssets(): array;

    /**
     * @return Asset[]
     */
    public function findAssetsInService(): array;

    /**
     * @return Asset[]
     */
    public function findAll(): array;

    /**
     * Find assets with filters.
     *
     * @return Asset[]
     */
    public function findWithFilters(
        ?AssetType $type = null,
        ?AssetStatus $status = null,
        ?Ulid $ownerId = null,
        ?Ulid $operatorId = null,
        ?string $search = null,
        int $limit = 100,
        int $offset = 0
    ): array;

    /**
     * Count assets with filters.
     */
    public function countWithFilters(
        ?AssetType $type = null,
        ?AssetStatus $status = null,
        ?Ulid $ownerId = null,
        ?Ulid $operatorId = null,
        ?string $search = null
    ): int;
}
