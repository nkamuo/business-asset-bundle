<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Application\CommandHandler;

use Nkamuo\AssetBundle\Application\Command\RecordAssetUsageCommand;
use Nkamuo\AssetBundle\Domain\Entity\AssetUsageEvent;
use Nkamuo\AssetBundle\Domain\Repository\AssetRepositoryInterface;
use Nkamuo\AssetBundle\Domain\Repository\AssetUsageEventRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles the recording of asset usage events.
 */
#[AsMessageHandler]
final readonly class RecordAssetUsageCommandHandler
{
    public function __construct(
        private AssetRepositoryInterface $assetRepository,
        private AssetUsageEventRepositoryInterface $usageEventRepository,
    ) {
    }

    public function __invoke(RecordAssetUsageCommand $command): AssetUsageEvent
    {
        $asset = $this->assetRepository->findById($command->assetId);
        if (!$asset) {
            throw new \InvalidArgumentException(sprintf('Asset with ID %s not found', $command->assetId));
        }

        $usageEvent = new AssetUsageEvent(
            asset: $asset,
            usageType: $command->usageType,
            quantity: $command->quantity,
            unitOfMeasure: $command->unitOfMeasure,
            startTime: $command->startTime,
            endTime: $command->endTime,
            sourceEntityType: $command->sourceEntityType,
            sourceEntityId: $command->sourceEntityId,
            location: $command->location,
            metadata: $command->metadata
        );

        $this->usageEventRepository->save($usageEvent);

        return $usageEvent;
    }
}
