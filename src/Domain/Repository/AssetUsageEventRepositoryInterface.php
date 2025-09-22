<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Domain\Repository;

use Nkamuo\AssetBundle\Domain\Entity\AssetUsageEvent;
use Nkamuo\AssetBundle\Domain\ValueObject\UsageType;
use Symfony\Component\Uid\Ulid;

/**
 * Repository interface for AssetUsageEvent entities
 */
interface AssetUsageEventRepositoryInterface
{
    public function save(AssetUsageEvent $usageEvent): void;

    public function delete(AssetUsageEvent $usageEvent): void;

    public function findById(Ulid $id): ?AssetUsageEvent;

    /**
     * @return AssetUsageEvent[]
     */
    public function findByAsset(Ulid $assetId): array;

    /**
     * @return AssetUsageEvent[]
     */
    public function findByAssetInPeriod(
        Ulid $assetId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array;

    /**
     * @return AssetUsageEvent[]
     */
    public function findByUsageType(UsageType $usageType): array;

    /**
     * @return AssetUsageEvent[]
     */
    public function findBySourceEntity(string $sourceEntityType, string $sourceEntityId): array;

    /**
     * @return AssetUsageEvent[]
     */
    public function findOngoingUsage(Ulid $assetId): array;

    /**
     * @return AssetUsageEvent[]
     */
    public function findCompletedUsage(Ulid $assetId): array;

    /**
     * @return AssetUsageEvent[]
     */
    public function findAll(): array;

    /**
     * Find usage events with filters
     * 
     * @return AssetUsageEvent[]
     */
    public function findWithFilters(
        ?Ulid $assetId = null,
        ?UsageType $usageType = null,
        ?\DateTimeImmutable $startAfter = null,
        ?\DateTimeImmutable $startBefore = null,
        ?\DateTimeImmutable $endAfter = null,
        ?\DateTimeImmutable $endBefore = null,
        ?string $sourceEntityType = null,
        ?string $sourceEntityId = null,
        ?bool $isCompleted = null,
        int $limit = 100,
        int $offset = 0
    ): array;

    /**
     * Get usage statistics for an asset in a period
     */
    public function getUsageStatistics(
        Ulid $assetId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array;

    /**
     * Calculate total usage by type for an asset in a period
     */
    public function calculateUsageByType(
        Ulid $assetId,
        UsageType $usageType,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): float;
}
