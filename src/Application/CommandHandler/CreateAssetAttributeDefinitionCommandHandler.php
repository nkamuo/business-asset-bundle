<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Application\CommandHandler;

use Nkamuo\AssetBundle\Application\Command\CreateAssetAttributeDefinitionCommand;
use Nkamuo\AssetBundle\Domain\Entity\AssetAttributeDefinition;
use Nkamuo\AssetBundle\Domain\Repository\AssetAttributeDefinitionRepositoryInterface;

/**
 * Command handler for creating asset attribute definitions.
 */
class CreateAssetAttributeDefinitionCommandHandler
{
    public function __construct(
        private AssetAttributeDefinitionRepositoryInterface $attributeDefinitionRepository
    ) {}

    public function __invoke(CreateAssetAttributeDefinitionCommand $command): AssetAttributeDefinition
    {
        // Check if attribute key is unique
        if (!$this->attributeDefinitionRepository->isKeyUnique($command->attributeKey)) {
            throw new \InvalidArgumentException("Attribute key '{$command->attributeKey}' already exists");
        }

        // Validate enum options for ENUM type
        if ($command->attributeType === \Nkamuo\AssetBundle\Domain\ValueObject\AttributeType::ENUM) {
            if (empty($command->enumOptions)) {
                throw new \InvalidArgumentException('Enum options are required for ENUM attribute type');
            }
        }

        // Create the attribute definition
        $definition = new AssetAttributeDefinition(
            $command->attributeKey,
            $command->displayName,
            $command->attributeType,
            $command->assetType,
            $command->assetCategory
        );

        // Set optional properties
        if ($command->description !== null) {
            $definition->setDescription($command->description);
        }

        $definition->setRequired($command->isRequired);
        $definition->setSearchable($command->isSearchable);

        if ($command->allowMultipleValues !== null) {
            $definition->setAllowMultipleValues($command->allowMultipleValues);
        }

        if ($command->validationRules !== null) {
            $definition->setValidationRules($command->validationRules);
        }

        if ($command->enumOptions !== null) {
            $definition->setEnumOptions($command->enumOptions);
        }

        if ($command->defaultValue !== null) {
            $definition->setDefaultValue($command->defaultValue);
        }

        if ($command->unit !== null) {
            $definition->setUnit($command->unit);
        }

        $definition->setSortOrder($command->sortOrder);

        // Save the definition
        $this->attributeDefinitionRepository->save($definition);

        return $definition;
    }
}
