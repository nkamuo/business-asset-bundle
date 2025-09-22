<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Application\CommandHandler;

use Nkamuo\AssetBundle\Application\Command\CreateAssetCommand;
use Nkamuo\AssetBundle\Domain\Entity\Asset;
use Nkamuo\AssetBundle\Domain\Repository\AssetRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler for CreateAssetCommand
 */
#[AsMessageHandler]
final readonly class CreateAssetCommandHandler
{
    public function __construct(
        private AssetRepositoryInterface $assetRepository
    ) {
    }

    public function __invoke(CreateAssetCommand $command): Asset
    {
        // Check if asset number already exists
        if ($this->assetRepository->findByAssetNumber($command->assetNumber)) {
            throw new \InvalidArgumentException(
                sprintf('Asset with number "%s" already exists', $command->assetNumber)
            );
        }

        // Validate that category is compatible with type
        if ($command->category->getAssetType() !== $command->type) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Asset category "%s" is not compatible with type "%s"',
                    $command->category->value,
                    $command->type->value
                )
            );
        }

        $asset = new Asset(
            assetNumber: $command->assetNumber,
            name: $command->name,
            type: $command->type,
            category: $command->category,
            ownerId: $command->ownerId,
            acquisitionCost: $command->acquisitionCost,
            status: $command->status,
            acquisitionDate: $command->acquisitionDate,
            specifications: $command->specifications,
            identifiers: $command->identifiers,
            metadata: $command->metadata
        );

        if ($command->operatorId) {
            $asset->assignOperator($command->operatorId);
        }

        $this->assetRepository->save($asset);

        return $asset;
    }
}
