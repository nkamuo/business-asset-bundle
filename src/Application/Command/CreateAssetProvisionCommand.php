<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Application\Command;

use Nkamuo\AssetBundle\Domain\ValueObject\ProvisionStatus;
use Nkamuo\AssetBundle\Domain\ValueObject\ProvisionType;
use Symfony\Component\Uid\Ulid;

/**
 * Command to create a new asset provision.
 */
final readonly class CreateAssetProvisionCommand
{
    public function __construct(
        public Ulid $assetId,
        public Ulid $providerId,
        public Ulid $recipientId,
        public ProvisionType $type,
        public \DateTimeImmutable $startDate,
        public ?\DateTimeImmutable $endDate = null,
        public array $terms = [],
        public array $metadata = [],
        public ProvisionStatus $status = ProvisionStatus::DRAFT
    ) {
    }
}
