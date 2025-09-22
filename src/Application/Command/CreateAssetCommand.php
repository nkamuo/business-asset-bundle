<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Application\Command;

use Nkamuo\AssetBundle\Domain\ValueObject\AssetCategory;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetStatus;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetType;
use Money\Money;
use Symfony\Component\Uid\Ulid;

/**
 * Command to create a new asset
 */
final readonly class CreateAssetCommand
{
    public function __construct(
        public string $assetNumber,
        public string $name,
        public AssetType $type,
        public AssetCategory $category,
        public Ulid $ownerId,
        public Money $acquisitionCost,
        public AssetStatus $status = AssetStatus::AVAILABLE,
        public ?\DateTimeImmutable $acquisitionDate = null,
        public ?Ulid $operatorId = null,
        public array $specifications = [],
        public array $identifiers = [],
        public array $metadata = []
    ) {
    }
}
