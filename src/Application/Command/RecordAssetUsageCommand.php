<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Application\Command;

use Nkamuo\AssetBundle\Domain\ValueObject\UsageType;
use Symfony\Component\Uid\Ulid;

/**
 * Command to record asset usage event
 */
final readonly class RecordAssetUsageCommand
{
    public function __construct(
        public Ulid $assetId,
        public UsageType $usageType,
        public float $quantity,
        public string $unitOfMeasure,
        public \DateTimeImmutable $startTime,
        public ?\DateTimeImmutable $endTime = null,
        public ?string $sourceEntityType = null,
        public ?string $sourceEntityId = null,
        public ?string $location = null,
        public array $metadata = []
    ) {
    }
}
