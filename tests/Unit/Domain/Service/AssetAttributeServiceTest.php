<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Tests\Unit\Domain\Service;

use Nkamuo\AssetBundle\Domain\Entity\Asset;
use Nkamuo\AssetBundle\Domain\Entity\AssetAttribute;
use Nkamuo\AssetBundle\Domain\Entity\AssetAttributeDefinition;
use Nkamuo\AssetBundle\Domain\Repository\AssetAttributeDefinitionRepositoryInterface;
use Nkamuo\AssetBundle\Domain\Repository\AssetAttributeRepositoryInterface;
use Nkamuo\AssetBundle\Domain\Service\AssetAttributeService;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetCategory;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetType;
use Nkamuo\AssetBundle\Domain\ValueObject\AttributeType;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

class AssetAttributeServiceTest extends TestCase
{
    private AssetAttributeService $service;
    private AssetAttributeDefinitionRepositoryInterface&MockObject $definitionRepository;
    private AssetAttributeRepositoryInterface&MockObject $attributeRepository;
    private Asset $asset;

    protected function setUp(): void
    {
        $this->definitionRepository = $this->createMock(AssetAttributeDefinitionRepositoryInterface::class);
        $this->attributeRepository = $this->createMock(AssetAttributeRepositoryInterface::class);
        
        $this->service = new AssetAttributeService(
            $this->definitionRepository,
            $this->attributeRepository
        );

        $this->asset = new Asset(
            'TEST-001',
            'Test Asset',
            AssetType::VEHICLE,
            AssetCategory::TRUCK_TRACTOR,
            new Ulid(),
            new Money(5000000, new Currency('USD'))
        );
    }

    public function testGetApplicableDefinitions(): void
    {
        $definitions = [
            new AssetAttributeDefinition('make', 'Vehicle Make', AttributeType::STRING, AssetType::VEHICLE),
            new AssetAttributeDefinition('year', 'Year', AttributeType::INTEGER, AssetType::VEHICLE),
            new AssetAttributeDefinition('odometer', 'Odometer', AttributeType::INTEGER, AssetType::VEHICLE, AssetCategory::TRUCK_TRACTOR),
        ];

        $this->definitionRepository
            ->expects($this->once())
            ->method('findApplicableToAsset')
            ->with(AssetType::VEHICLE, AssetCategory::TRUCK_TRACTOR)
            ->willReturn($definitions);

        $result = $this->service->getApplicableDefinitions($this->asset);

        $this->assertCount(3, $result);
        $this->assertSame($definitions, $result);
    }

    public function testValidateRequiredAttributesWithAllPresent(): void
    {
        $definition1 = new AssetAttributeDefinition('make', 'Vehicle Make', AttributeType::STRING, AssetType::VEHICLE);
        $definition1->setRequired(true);
        
        $definition2 = new AssetAttributeDefinition('year', 'Year', AttributeType::INTEGER, AssetType::VEHICLE);
        $definition2->setRequired(true);

        $requiredDefinitions = [$definition1, $definition2];

        // Mock the asset to have the required attributes
        $attribute1 = new AssetAttribute($this->asset, $definition1, 'Ford');
        $attribute2 = new AssetAttribute($this->asset, $definition2, 2020);
        
        $this->asset->getCustomAttributes()->add($attribute1);
        $this->asset->getCustomAttributes()->add($attribute2);

        $this->definitionRepository
            ->expects($this->once())
            ->method('findRequired')
            ->with(AssetType::VEHICLE, AssetCategory::TRUCK_TRACTOR)
            ->willReturn($requiredDefinitions);

        $result = $this->service->validateRequiredAttributes($this->asset);

        $this->assertEmpty($result); // No errors expected
    }

    public function testValidateRequiredAttributesWithMissing(): void
    {
        $definition1 = new AssetAttributeDefinition('make', 'Vehicle Make', AttributeType::STRING, AssetType::VEHICLE);
        $definition1->setRequired(true);
        
        $definition2 = new AssetAttributeDefinition('year', 'Year', AttributeType::INTEGER, AssetType::VEHICLE);
        $definition2->setRequired(true);

        $requiredDefinitions = [$definition1, $definition2];

        // Only add one attribute, leaving year missing
        $attribute1 = new AssetAttribute($this->asset, $definition1, 'Ford');
        $this->asset->getCustomAttributes()->add($attribute1);

        $this->definitionRepository
            ->expects($this->once())
            ->method('findRequired')
            ->with(AssetType::VEHICLE, AssetCategory::TRUCK_TRACTOR)
            ->willReturn($requiredDefinitions);

        $result = $this->service->validateRequiredAttributes($this->asset);

        $this->assertNotEmpty($result);
        $this->assertContains("Required attribute 'Year' is missing", $result);
    }

    public function testSearchAssetsByAttributes(): void
    {
        $searchCriteria = [
            'make' => 'Ford',
            'year' => 2020
        ];

        $definition1 = new AssetAttributeDefinition('make', 'Vehicle Make', AttributeType::STRING, AssetType::VEHICLE);
        $definition2 = new AssetAttributeDefinition('year', 'Year', AttributeType::INTEGER, AssetType::VEHICLE);

        $this->definitionRepository
            ->expects($this->exactly(2))
            ->method('findByKey')
            ->willReturnMap([
                ['make', $definition1],
                ['year', $definition2]
            ]);

        $makeAttributes = [
            new AssetAttribute($this->asset, $definition1, 'Ford'),
        ];
        
        $yearAttributes = [
            new AssetAttribute($this->asset, $definition2, 2020),
        ];

        $this->attributeRepository
            ->expects($this->exactly(2))
            ->method('findByValue')
            ->willReturnMap([
                ['Ford', $definition1, $makeAttributes],
                [2020, $definition2, $yearAttributes]
            ]);

        $result = $this->service->searchAssetsByAttributes($searchCriteria);

        $this->assertContains($this->asset->getId()->toString(), $result);
    }

    public function testBulkUpdateAttributesForAssets(): void
    {
        $asset1 = new Asset('TEST-001', 'Test Asset 1', AssetType::VEHICLE, AssetCategory::TRUCK_TRACTOR, new Ulid(), new Money(5000000, new Currency('USD')));
        $asset2 = new Asset('TEST-002', 'Test Asset 2', AssetType::VEHICLE, AssetCategory::TRUCK_TRACTOR, new Ulid(), new Money(6000000, new Currency('USD')));
        $assets = [$asset1, $asset2];

        $definition = new AssetAttributeDefinition('status', 'Status', AttributeType::STRING, AssetType::VEHICLE);

        $updateData = [
            'attributeKey' => 'status',
            'value' => 'active'
        ];

        $this->definitionRepository
            ->expects($this->once())
            ->method('findByKey')
            ->with('status')
            ->willReturn($definition);

        $result = $this->service->bulkUpdateAttributesForAssets($assets, $updateData);

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(AssetAttribute::class, $result);
    }

    public function testBulkUpdateAttributesWithInvalidDefinition(): void
    {
        $assets = [$this->asset];
        $updateData = [
            'attributeKey' => 'nonexistent',
            'value' => 'value'
        ];

        $this->definitionRepository
            ->expects($this->once())
            ->method('findByKey')
            ->with('nonexistent')
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Attribute definition not found with key: nonexistent");

        $this->service->bulkUpdateAttributesForAssets($assets, $updateData);
    }

    public function testGetAttributeStatistics(): void
    {
        $definition = new AssetAttributeDefinition('make', 'Vehicle Make', AttributeType::STRING, AssetType::VEHICLE);

        $attributes = [
            new AssetAttribute($this->asset, $definition, 'Ford'),
            new AssetAttribute($this->asset, $definition, 'Toyota'),
            new AssetAttribute($this->asset, $definition, 'Ford'),
        ];

        $this->attributeRepository
            ->expects($this->once())
            ->method('findByDefinition')
            ->with($definition)
            ->willReturn($attributes);

        $result = $this->service->getAttributeStatistics($definition);

        $this->assertArrayHasKey('total_count', $result);
        $this->assertArrayHasKey('unique_values', $result);
        $this->assertArrayHasKey('value_distribution', $result);
        $this->assertSame(3, $result['total_count']);
        $this->assertSame(2, $result['unique_values']);
        $this->assertSame(['Ford' => 2, 'Toyota' => 1], $result['value_distribution']);
    }
}
