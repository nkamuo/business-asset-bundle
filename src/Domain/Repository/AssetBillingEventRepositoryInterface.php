<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Domain\Repository;

use Nkamuo\AssetBundle\Domain\Entity\AssetBillingEvent;
use Nkamuo\AssetBundle\Domain\ValueObject\BillingEventStatus;
use Symfony\Component\Uid\Ulid;

/**
 * Repository interface for AssetBillingEvent entities
 */
interface AssetBillingEventRepositoryInterface
{
    public function save(AssetBillingEvent $billingEvent): void;

    public function delete(AssetBillingEvent $billingEvent): void;

    public function findById(Ulid $id): ?AssetBillingEvent;

    /**
     * @return AssetBillingEvent[]
     */
    public function findByAsset(Ulid $assetId): array;

    /**
     * @return AssetBillingEvent[]
     */
    public function findByProvision(Ulid $provisionId): array;

    /**
     * @return AssetBillingEvent[]
     */
    public function findByStatus(BillingEventStatus $status): array;

    /**
     * @return AssetBillingEvent[]
     */
    public function findBySettlement(string $settlementId): array;

    /**
     * @return AssetBillingEvent[]
     */
    public function findInBillingPeriod(
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array;

    /**
     * @return AssetBillingEvent[]
     */
    public function findPendingApproval(): array;

    /**
     * @return AssetBillingEvent[]
     */
    public function findReadyForBilling(): array;

    /**
     * @return AssetBillingEvent[]
     */
    public function findDisputed(): array;

    /**
     * @return AssetBillingEvent[]
     */
    public function findAll(): array;

    /**
     * Find billing events with filters
     * 
     * @return AssetBillingEvent[]
     */
    public function findWithFilters(
        ?Ulid $assetId = null,
        ?Ulid $provisionId = null,
        ?BillingEventStatus $status = null,
        ?string $settlementId = null,
        ?\DateTimeImmutable $billingPeriodStart = null,
        ?\DateTimeImmutable $billingPeriodEnd = null,
        int $limit = 100,
        int $offset = 0
    ): array;

    /**
     * Calculate total billing amount for a period
     */
    public function calculateTotalAmountForPeriod(
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        ?BillingEventStatus $status = null
    ): int; // Amount in cents

    /**
     * Get billing statistics for an asset
     */
    public function getBillingStatistics(
        Ulid $assetId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array;
}
