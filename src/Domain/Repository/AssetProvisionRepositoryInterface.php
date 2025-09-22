<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Domain\Repository;

use Nkamuo\AssetBundle\Domain\Entity\AssetProvision;
use Nkamuo\AssetBundle\Domain\ValueObject\ProvisionStatus;
use Nkamuo\AssetBundle\Domain\ValueObject\ProvisionType;
use Symfony\Component\Uid\Ulid;

/**
 * Repository interface for AssetProvision entities.
 */
interface AssetProvisionRepositoryInterface
{
    public function save(AssetProvision $provision): void;

    public function delete(AssetProvision $provision): void;

    public function findById(Ulid $id): ?AssetProvision;

    /**
     * @return AssetProvision[]
     */
    public function findByAsset(Ulid $assetId): array;

    /**
     * @return AssetProvision[]
     */
    public function findByProvider(Ulid $providerId): array;

    /**
     * @return AssetProvision[]
     */
    public function findByRecipient(Ulid $recipientId): array;

    /**
     * @return AssetProvision[]
     */
    public function findByStatus(ProvisionStatus $status): array;

    /**
     * @return AssetProvision[]
     */
    public function findByType(ProvisionType $type): array;

    /**
     * @return AssetProvision[]
     */
    public function findActiveProvisions(\DateTimeImmutable $at = null): array;

    /**
     * @return AssetProvision[]
     */
    public function findExpiringProvisions(\DateTimeImmutable $before): array;

    public function findActiveProvisionForAsset(Ulid $assetId, \DateTimeImmutable $at = null): ?AssetProvision;

    /**
     * @return AssetProvision[]
     */
    public function findAll(): array;

    /**
     * Find provisions with filters.
     *
     * @return AssetProvision[]
     */
    public function findWithFilters(
        ?Ulid $assetId = null,
        ?Ulid $providerId = null,
        ?Ulid $recipientId = null,
        ?ProvisionType $type = null,
        ?ProvisionStatus $status = null,
        ?\DateTimeImmutable $activeBefore = null,
        ?\DateTimeImmutable $activeAfter = null,
        int $limit = 100,
        int $offset = 0
    ): array;

    /**
     * Count provisions with filters.
     */
    public function countWithFilters(
        ?Ulid $assetId = null,
        ?Ulid $providerId = null,
        ?Ulid $recipientId = null,
        ?ProvisionType $type = null,
        ?ProvisionStatus $status = null,
        ?\DateTimeImmutable $activeBefore = null,
        ?\DateTimeImmutable $activeAfter = null
    ): int;
}
