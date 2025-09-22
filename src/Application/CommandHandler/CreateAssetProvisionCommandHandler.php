<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Application\CommandHandler;

use Nkamuo\AssetBundle\Application\Command\CreateAssetProvisionCommand;
use Nkamuo\AssetBundle\Domain\Entity\AssetProvision;
use Nkamuo\AssetBundle\Domain\Repository\AssetProvisionRepositoryInterface;
use Nkamuo\AssetBundle\Domain\Repository\AssetRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles the creation of new asset provision agreements.
 */
#[AsMessageHandler]
final readonly class CreateAssetProvisionCommandHandler
{
    public function __construct(
        private AssetRepositoryInterface $assetRepository,
        private AssetProvisionRepositoryInterface $provisionRepository,
    ) {
    }

    public function __invoke(CreateAssetProvisionCommand $command): AssetProvision
    {
        $asset = $this->assetRepository->findById($command->assetId);
        if (!$asset) {
            throw new \InvalidArgumentException(sprintf('Asset with ID %s not found', $command->assetId));
        }

        $provision = new AssetProvision(
            asset: $asset,
            providerId: $command->providerId,
            recipientId: $command->recipientId,
            type: $command->type,
            startDate: $command->startDate,
            endDate: $command->endDate,
            terms: $command->terms,
            metadata: $command->metadata,
            status: $command->status
        );

        $this->provisionRepository->save($provision);

        return $provision;
    }
}
