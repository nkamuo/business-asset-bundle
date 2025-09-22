<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Domain\Repository;

use Nkamuo\AssetBundle\Domain\Entity\AssetProvision;
use Nkamuo\AssetBundle\Domain\Entity\AssetRateCard;
use Nkamuo\AssetBundle\Domain\ValueObject\RateType;
use Symfony\Component\Uid\Ulid;

/**
 * Repository interface for AssetRateCard entities.
 */
interface AssetRateCardRepositoryInterface
{
    /**
     * Save an asset rate card.
     */
    public function save(AssetRateCard $rateCard): void;

    /**
     * Remove an asset rate card.
     */
    public function remove(AssetRateCard $rateCard): void;

    /**
     * Find rate card by ID.
     */
    public function findById(Ulid $id): ?AssetRateCard;

    /**
     * Find rate cards by provision.
     */
    public function findByProvision(AssetProvision $provision): array;

    /**
     * Find active rate cards for a provision at a specific date.
     */
    public function findActiveByProvision(AssetProvision $provision, \DateTimeImmutable $at = null): array;

    /**
     * Find rate cards by rate type.
     */
    public function findByRateType(RateType $rateType): array;

    /**
     * Find rate cards effective between dates.
     */
    public function findEffectiveBetween(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array;

    /**
     * Find rate cards expiring soon.
     */
    public function findExpiringSoon(\DateTimeImmutable $before): array;

    /**
     * Get all rate cards.
     */
    public function findAll(): array;
}
