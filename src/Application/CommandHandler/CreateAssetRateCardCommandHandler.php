<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Application\CommandHandler;

use Nkamuo\AssetBundle\Application\Command\CreateAssetRateCardCommand;
use Nkamuo\AssetBundle\Domain\Entity\AssetRateCard;
use Nkamuo\AssetBundle\Domain\Repository\AssetProvisionRepositoryInterface;
use Nkamuo\AssetBundle\Domain\Repository\AssetRateCardRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles the creation of new asset rate cards.
 */
#[AsMessageHandler]
final readonly class CreateAssetRateCardCommandHandler
{
    public function __construct(
        private AssetProvisionRepositoryInterface $provisionRepository,
        private AssetRateCardRepositoryInterface $rateCardRepository,
    ) {
    }

    public function __invoke(CreateAssetRateCardCommand $command): AssetRateCard
    {
        $provision = $this->provisionRepository->findById($command->provisionId);
        if (!$provision) {
            throw new \InvalidArgumentException(sprintf('Asset provision with ID %s not found', $command->provisionId));
        }

        $rateCard = new AssetRateCard(
            provision: $provision,
            rateType: $command->rateType,
            rate: $command->rate,
            effectiveDate: $command->effectiveDate,
            unitOfMeasure: $command->unitOfMeasure,
            minimumCharge: $command->minimumCharge,
            maximumCharge: $command->maximumCharge,
            expiryDate: $command->expiryDate,
            conditions: $command->conditions,
            tierStructure: $command->tierStructure,
            metadata: $command->metadata
        );

        $this->rateCardRepository->save($rateCard);

        return $rateCard;
    }
}
