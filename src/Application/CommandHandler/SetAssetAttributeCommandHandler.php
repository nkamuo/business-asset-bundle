<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Application\CommandHandler;

use Nkamuo\AssetBundle\Application\Command\SetAssetAttributeCommand;
use Nkamuo\AssetBundle\Domain\Entity\AssetAttribute;
use Nkamuo\AssetBundle\Domain\Repository\AssetAttributeDefinitionRepositoryInterface;
use Nkamuo\AssetBundle\Domain\Repository\AssetAttributeRepositoryInterface;
use Nkamuo\AssetBundle\Domain\Repository\AssetRepositoryInterface;

/**
 * Command handler for setting asset attribute values.
 */
class SetAssetAttributeCommandHandler
{
    public function __construct(
        private AssetRepositoryInterface $assetRepository,
        private AssetAttributeDefinitionRepositoryInterface $attributeDefinitionRepository,
        private AssetAttributeRepositoryInterface $attributeRepository
    ) {}

    public function __invoke(SetAssetAttributeCommand $command): AssetAttribute
    {
        // Find the asset
        $asset = $this->assetRepository->findById($command->assetId);
        if (!$asset) {
            throw new \InvalidArgumentException("Asset not found with ID: {$command->assetId}");
        }

        // Find the attribute definition
        $definition = $this->attributeDefinitionRepository->findByKey($command->attributeKey);
        if (!$definition) {
            throw new \InvalidArgumentException("Attribute definition not found with key: {$command->attributeKey}");
        }

        // Check if definition applies to this asset
        if (!$definition->appliesTo($asset)) {
            throw new \InvalidArgumentException(
                "Attribute '{$command->attributeKey}' does not apply to asset type '{$asset->getType()->value}' and category '{$asset->getCategory()->value}'"
            );
        }

        // Check if attribute already exists for this asset
        $existingAttribute = $this->attributeRepository->findByAssetAndDefinition($asset, $definition);

        if ($existingAttribute) {
            // Update existing attribute
            $existingAttribute->setValue($command->value);
            $this->attributeRepository->save($existingAttribute);
            return $existingAttribute;
        } else {
            // Create new attribute
            $newAttribute = new AssetAttribute($asset, $definition, $command->value);
            $this->attributeRepository->save($newAttribute);
            return $newAttribute;
        }
    }
}
