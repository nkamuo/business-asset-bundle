<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Domain\Service;

use Nkamuo\AssetBundle\Domain\Entity\Asset;
use Nkamuo\AssetBundle\Domain\Entity\AssetAttribute;
use Nkamuo\AssetBundle\Domain\Entity\AssetAttributeDefinition;
use Nkamuo\AssetBundle\Domain\Repository\AssetAttributeDefinitionRepositoryInterface;
use Nkamuo\AssetBundle\Domain\Repository\AssetAttributeRepositoryInterface;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetCategory;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetType;

/**
 * Domain service for asset attribute management and validation.
 */
class AssetAttributeService
{
    public function __construct(
        private AssetAttributeDefinitionRepositoryInterface $definitionRepository,
        private AssetAttributeRepositoryInterface $attributeRepository
    ) {}

    /**
     * Get all applicable attribute definitions for an asset.
     */
    public function getApplicableDefinitions(Asset $asset): array
    {
        return $this->definitionRepository->findApplicableToAsset(
            $asset->getType(),
            $asset->getCategory()
        );
    }

    /**
     * Get required attribute definitions for an asset.
     */
    public function getRequiredDefinitions(Asset $asset): array
    {
        return $this->definitionRepository->findRequired(
            $asset->getType(),
            $asset->getCategory()
        );
    }

    /**
     * Validate that an asset has all required attributes.
     */
    public function validateRequiredAttributes(Asset $asset): array
    {
        $errors = [];
        $requiredDefinitions = $this->getRequiredDefinitions($asset);

        foreach ($requiredDefinitions as $definition) {
            $attribute = $asset->getCustomAttribute($definition->getAttributeKey());
            
            if (!$attribute || !$attribute->hasValue()) {
                $errors[] = "Required attribute '{$definition->getDisplayName()}' is missing";
            }
        }

        return $errors;
    }

    /**
     * Get attribute templates for specific asset type/category.
     */
    public function getAttributeTemplate(AssetType $assetType, ?AssetCategory $assetCategory = null): array
    {
        $definitions = $this->definitionRepository->findApplicableToAsset($assetType, $assetCategory);
        
        $template = [];
        foreach ($definitions as $definition) {
            $template[] = [
                'key' => $definition->getAttributeKey(),
                'displayName' => $definition->getDisplayName(),
                'description' => $definition->getDescription(),
                'type' => $definition->getAttributeType()->value,
                'required' => $definition->isRequired(),
                'allowMultiple' => $definition->allowsMultipleValues(),
                'defaultValue' => $definition->getDefaultValue(),
                'unit' => $definition->getUnit(),
                'enumOptions' => $definition->getEnumOptions(),
                'validationRules' => $definition->getValidationRules(),
                'sortOrder' => $definition->getSortOrder(),
            ];
        }

        // Sort by sort order
        usort($template, fn ($a, $b) => $a['sortOrder'] <=> $b['sortOrder']);

        return $template;
    }

    /**
     * Bulk set attributes for an asset.
     */
    public function setAttributes(Asset $asset, array $attributeValues): array
    {
        $results = [];
        $errors = [];

        foreach ($attributeValues as $key => $value) {
            try {
                $definition = $this->definitionRepository->findByKey($key);
                if (!$definition) {
                    $errors[$key] = "Attribute definition not found";
                    continue;
                }

                if (!$definition->appliesTo($asset)) {
                    $errors[$key] = "Attribute does not apply to this asset type";
                    continue;
                }

                $asset->setCustomAttribute($definition, $value);
                $results[$key] = $value;
            } catch (\Exception $e) {
                $errors[$key] = $e->getMessage();
            }
        }

        return [
            'success' => $results,
            'errors' => $errors,
        ];
    }

    /**
     * Search assets by attribute values.
     */
    public function searchAssetsByAttributes(array $criteria): array
    {
        $assetIds = [];

        foreach ($criteria as $key => $value) {
            $definition = $this->definitionRepository->findByKey($key);
            if (!$definition) {
                continue;
            }

            $attributes = $this->attributeRepository->findByValue($value, $definition);
            $currentAssetIds = array_map(
                fn ($attr) => $attr->getAsset()->getId()->toString(),
                $attributes
            );

            if (empty($assetIds)) {
                $assetIds = $currentAssetIds;
            } else {
                // Intersection - assets must match all criteria
                $assetIds = array_intersect($assetIds, $currentAssetIds);
            }
        }

        return array_unique($assetIds);
    }

    /**
     * Get attribute usage statistics.
     */
    public function getAttributeUsageStatistics(): array
    {
        $definitions = $this->definitionRepository->findActive();
        $statistics = [];

        foreach ($definitions as $definition) {
            $count = $this->attributeRepository->countByDefinition($definition);
            $distinctValues = $this->attributeRepository->getDistinctValues($definition, 10);

            $statistics[] = [
                'key' => $definition->getAttributeKey(),
                'displayName' => $definition->getDisplayName(),
                'type' => $definition->getAttributeType()->value,
                'assetType' => $definition->getAssetType()?->value,
                'assetCategory' => $definition->getAssetCategory()?->value,
                'usageCount' => $count,
                'distinctValues' => count($distinctValues),
                'sampleValues' => array_slice($distinctValues, 0, 5),
                'isSearchable' => $definition->isSearchable(),
                'isRequired' => $definition->isRequired(),
            ];
        }

        return $statistics;
    }

    /**
     * Migrate attribute definition (e.g., change type, validation rules).
     */
    public function migrateAttributeDefinition(
        AssetAttributeDefinition $definition,
        array $changes,
        bool $validateExistingValues = true
    ): array {
        $migrationResults = [
            'success' => 0,
            'errors' => 0,
            'details' => [],
        ];

        if ($validateExistingValues) {
            $existingAttributes = $this->attributeRepository->findByDefinition($definition);
            
            foreach ($existingAttributes as $attribute) {
                try {
                    // Apply the changes temporarily to test validation
                    $testDefinition = clone $definition;
                    $this->applyChangesToDefinition($testDefinition, $changes);
                    
                    // Validate existing value against new rules
                    $errors = $testDefinition->validateValue($attribute->getValue());
                    
                    if (!empty($errors)) {
                        $migrationResults['errors']++;
                        $migrationResults['details'][] = [
                            'assetId' => $attribute->getAsset()->getId()->toString(),
                            'errors' => $errors,
                        ];
                    } else {
                        $migrationResults['success']++;
                    }
                } catch (\Exception $e) {
                    $migrationResults['errors']++;
                    $migrationResults['details'][] = [
                        'assetId' => $attribute->getAsset()->getId()->toString(),
                        'errors' => [$e->getMessage()],
                    ];
                }
            }
        }

        // Apply changes if validation passed or validation was skipped
        if ($migrationResults['errors'] === 0 || !$validateExistingValues) {
            $this->applyChangesToDefinition($definition, $changes);
            $this->definitionRepository->save($definition);
        }

        return $migrationResults;
    }

    private function applyChangesToDefinition(AssetAttributeDefinition $definition, array $changes): void
    {
        foreach ($changes as $property => $value) {
            match ($property) {
                'displayName' => $definition->setDisplayName($value),
                'description' => $definition->setDescription($value),
                'required' => $definition->setRequired($value),
                'searchable' => $definition->setSearchable($value),
                'allowMultipleValues' => $definition->setAllowMultipleValues($value),
                'validationRules' => $definition->setValidationRules($value),
                'enumOptions' => $definition->setEnumOptions($value),
                'defaultValue' => $definition->setDefaultValue($value),
                'unit' => $definition->setUnit($value),
                'sortOrder' => $definition->setSortOrder($value),
                'active' => $definition->setActive($value),
                default => throw new \InvalidArgumentException("Unknown property: {$property}"),
            };
        }
    }

    /**
     * Bulk update attributes for multiple assets
     *
     * @param Asset[] $assets
     * @param array{attributeKey: string, value: mixed} $updateData
     * @return AssetAttribute[]
     */
    public function bulkUpdateAttributesForAssets(array $assets, array $updateData): array
    {
        $definition = $this->definitionRepository->findByKey($updateData['attributeKey']);
        if (!$definition) {
            throw new \InvalidArgumentException("Attribute definition not found with key: {$updateData['attributeKey']}");
        }

        $results = [];
        foreach ($assets as $asset) {
            if (!$definition->appliesTo($asset)) {
                continue; // Skip assets that don't match the attribute definition
            }

            try {
                $asset->setCustomAttribute($definition, $updateData['value']);
                $attribute = $asset->getCustomAttribute($definition->getAttributeKey());
                if ($attribute) {
                    $results[] = $attribute;
                }
            } catch (\Exception $e) {
                // Skip assets that can't be updated
                continue;
            }
        }

        return $results;
    }

    /**
     * Get statistics for an attribute definition
     *
     * @return array{total_count: int, unique_values: int, value_distribution: array<string, int>}
     */
    public function getAttributeStatistics(AssetAttributeDefinition $definition): array
    {
        $attributes = $this->attributeRepository->findByDefinition($definition);
        
        $totalCount = count($attributes);
        $valueDistribution = [];
        
        foreach ($attributes as $attribute) {
            $value = (string) $attribute->getValue();
            $valueDistribution[$value] = ($valueDistribution[$value] ?? 0) + 1;
        }
        
        $uniqueValues = count($valueDistribution);
        
        return [
            'total_count' => $totalCount,
            'unique_values' => $uniqueValues,
            'value_distribution' => $valueDistribution,
        ];
    }
}
