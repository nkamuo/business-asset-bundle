<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Infrastructure\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nkamuo\AssetBundle\Domain\Entity\Asset;
use Nkamuo\AssetBundle\Domain\Repository\AssetRepositoryInterface;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetStatus;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetType;
use Symfony\Component\Uid\Ulid;

/**
 * Doctrine repository implementation for Asset entities
 */
class DoctrineAssetRepository extends ServiceEntityRepository implements AssetRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Asset::class);
    }

    public function save(Asset $asset): void
    {
        $this->getEntityManager()->persist($asset);
        $this->getEntityManager()->flush();
    }

    public function delete(Asset $asset): void
    {
        $this->getEntityManager()->remove($asset);
        $this->getEntityManager()->flush();
    }

    public function findById(Ulid $id): ?Asset
    {
        return $this->find($id);
    }

    public function findByAssetNumber(string $assetNumber): ?Asset
    {
        return $this->findOneBy(['assetNumber' => $assetNumber]);
    }

    public function findByOwner(Ulid $ownerId): array
    {
        return $this->findBy(['ownerId' => $ownerId]);
    }

    public function findByOperator(Ulid $operatorId): array
    {
        return $this->findBy(['operatorId' => $operatorId]);
    }

    public function findByType(AssetType $type): array
    {
        return $this->findBy(['type' => $type]);
    }

    public function findByStatus(AssetStatus $status): array
    {
        return $this->findBy(['status' => $status]);
    }

    public function findAvailableAssets(): array
    {
        return $this->findByStatus(AssetStatus::AVAILABLE);
    }

    public function findAssetsInService(): array
    {
        return $this->findByStatus(AssetStatus::IN_SERVICE);
    }

    public function findAll(): array
    {
        return parent::findAll();
    }

    public function findWithFilters(
        ?AssetType $type = null,
        ?AssetStatus $status = null,
        ?Ulid $ownerId = null,
        ?Ulid $operatorId = null,
        ?string $search = null,
        int $limit = 100,
        int $offset = 0
    ): array {
        $qb = $this->createQueryBuilder('a');

        if ($type !== null) {
            $qb->andWhere('a.type = :type')
               ->setParameter('type', $type);
        }

        if ($status !== null) {
            $qb->andWhere('a.status = :status')
               ->setParameter('status', $status);
        }

        if ($ownerId !== null) {
            $qb->andWhere('a.ownerId = :ownerId')
               ->setParameter('ownerId', $ownerId);
        }

        if ($operatorId !== null) {
            $qb->andWhere('a.operatorId = :operatorId')
               ->setParameter('operatorId', $operatorId);
        }

        if ($search !== null) {
            $qb->andWhere('a.assetNumber LIKE :search OR a.name LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $qb->setMaxResults($limit)
           ->setFirstResult($offset)
           ->orderBy('a.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function countWithFilters(
        ?AssetType $type = null,
        ?AssetStatus $status = null,
        ?Ulid $ownerId = null,
        ?Ulid $operatorId = null,
        ?string $search = null
    ): int {
        $qb = $this->createQueryBuilder('a')
                   ->select('COUNT(a.id)');

        if ($type !== null) {
            $qb->andWhere('a.type = :type')
               ->setParameter('type', $type);
        }

        if ($status !== null) {
            $qb->andWhere('a.status = :status')
               ->setParameter('status', $status);
        }

        if ($ownerId !== null) {
            $qb->andWhere('a.ownerId = :ownerId')
               ->setParameter('ownerId', $ownerId);
        }

        if ($operatorId !== null) {
            $qb->andWhere('a.operatorId = :operatorId')
               ->setParameter('operatorId', $operatorId);
        }

        if ($search !== null) {
            $qb->andWhere('a.assetNumber LIKE :search OR a.name LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
