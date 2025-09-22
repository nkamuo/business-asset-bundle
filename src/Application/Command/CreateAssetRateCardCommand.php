<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Application\Command;

use Money\Money;
use Nkamuo\AssetBundle\Domain\ValueObject\RateType;
use Symfony\Component\Uid\Ulid;

/**
 * Command to create a new asset rate card.
 */
final readonly class CreateAssetRateCardCommand
{
    public function __construct(
        public Ulid $provisionId,
        public RateType $rateType,
        public Money $rate,
        public \DateTimeImmutable $effectiveDate,
        public ?string $unitOfMeasure = null,
        public ?Money $minimumCharge = null,
        public ?Money $maximumCharge = null,
        public ?\DateTimeImmutable $expiryDate = null,
        public array $conditions = [],
        public array $tierStructure = [],
        public array $metadata = []
    ) {
    }
}
