<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Tests\Unit\Application\CommandHandler;

use Nkamuo\AssetBundle\Application\Command\SetAssetAttributeCommand;
use Nkamuo\AssetBundle\Application\CommandHandler\SetAssetAttributeCommandHandler;
use Nkamuo\AssetBundle\Domain\Entity\Asset;
use Nkamuo\AssetBundle\Domain\Entity\AssetAttribute;
use Nkamuo\AssetBundle\Domain\Entity\AssetAttributeDefinition;
use Nkamuo\AssetBundle\Domain\Repository\AssetAttributeDefinitionRepositoryInterface;
use Nkamuo\AssetBundle\Domain\Repository\AssetAttributeRepositoryInterface;
use Nkamuo\AssetBundle\Domain\Repository\AssetRepositoryInterface;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetCategory;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetType;
use Nkamuo\AssetBundle\Domain\ValueObject\AttributeType;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

class SetAssetAttributeCommandHandlerTest extends TestCase
{
    private SetAssetAttributeCommandHandler $handler;
    private AssetRepositoryInterface&MockObject $assetRepository;
    private AssetAttributeDefinitionRepositoryInterface&MockObject $definitionRepository;
    private AssetAttributeRepositoryInterface&MockObject $attributeRepository;
    private Asset $asset;
    private AssetAttributeDefinition $definition;

    protected function setUp(): void
    {
        $this->assetRepository = $this->createMock(AssetRepositoryInterface::class);
        $this->definitionRepository = $this->createMock(AssetAttributeDefinitionRepositoryInterface::class);
        $this->attributeRepository = $this->createMock(AssetAttributeRepositoryInterface::class);
        
        $this->handler = new SetAssetAttributeCommandHandler(
            $this->assetRepository,
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

        $this->definition = new AssetAttributeDefinition(
            'make',
            'Vehicle Make',
            AttributeType::STRING,
            AssetType::VEHICLE
        );
    }

    public function testSetNewAttribute(): void
    {
        $assetId = $this->asset->getId();
        $command = new SetAssetAttributeCommand($assetId, 'make', 'Ford');

        $this->assetRepository
            ->expects($this->once())
            ->method('findById')
            ->with($assetId)
            ->willReturn($this->asset);

        $this->definitionRepository
            ->expects($this->once())
            ->method('findByKey')
            ->with('make')
            ->willReturn($this->definition);

        $this->attributeRepository
            ->expects($this->once())
            ->method('findByAssetAndDefinition')
            ->with($this->asset, $this->definition)
            ->willReturn(null);

        $this->attributeRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($attribute) {
                return $attribute instanceof AssetAttribute &&
                       $attribute->getAsset() === $this->asset &&
                       $attribute->getDefinition() === $this->definition &&
                       $attribute->getValue() === 'Ford';
            }));

        $result = ($this->handler)($command);

        $this->assertInstanceOf(AssetAttribute::class, $result);
        $this->assertSame('Ford', $result->getValue());
    }

    public function testUpdateExistingAttribute(): void
    {
        $assetId = $this->asset->getId();
        $command = new SetAssetAttributeCommand($assetId, 'make', 'Toyota');

        $existingAttribute = new AssetAttribute($this->asset, $this->definition, 'Ford');

        $this->assetRepository
            ->expects($this->once())
            ->method('findById')
            ->with($assetId)
            ->willReturn($this->asset);

        $this->definitionRepository
            ->expects($this->once())
            ->method('findByKey')
            ->with('make')
            ->willReturn($this->definition);

        $this->attributeRepository
            ->expects($this->once())
            ->method('findByAssetAndDefinition')
            ->with($this->asset, $this->definition)
            ->willReturn($existingAttribute);

        $this->attributeRepository
            ->expects($this->once())
            ->method('save')
            ->with($existingAttribute);

        $result = ($this->handler)($command);

        $this->assertSame($existingAttribute, $result);
        $this->assertSame('Toyota', $result->getValue());
    }

    public function testAssetNotFoundThrowsException(): void
    {
        $assetId = new Ulid();
        $command = new SetAssetAttributeCommand($assetId, 'make', 'Ford');

        $this->assetRepository
            ->expects($this->once())
            ->method('findById')
            ->with($assetId)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Asset not found with ID: {$assetId}");

        ($this->handler)($command);
    }

    public function testAttributeDefinitionNotFoundThrowsException(): void
    {
        $assetId = $this->asset->getId();
        $command = new SetAssetAttributeCommand($assetId, 'unknown_attribute', 'value');

        $this->assetRepository
            ->expects($this->once())
            ->method('findById')
            ->with($assetId)
            ->willReturn($this->asset);

        $this->definitionRepository
            ->expects($this->once())
            ->method('findByKey')
            ->with('unknown_attribute')
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Attribute definition not found with key: unknown_attribute");

        ($this->handler)($command);
    }

    public function testAttributeNotApplicableToAssetThrowsException(): void
    {
        $equipmentDefinition = new AssetAttributeDefinition(
            'lift_capacity',
            'Lift Capacity',
            AttributeType::INTEGER,
            AssetType::EQUIPMENT
        );

        $assetId = $this->asset->getId();
        $command = new SetAssetAttributeCommand($assetId, 'lift_capacity', 5000);

        $this->assetRepository
            ->expects($this->once())
            ->method('findById')
            ->with($assetId)
            ->willReturn($this->asset);

        $this->definitionRepository
            ->expects($this->once())
            ->method('findByKey')
            ->with('lift_capacity')
            ->willReturn($equipmentDefinition);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Attribute 'lift_capacity' does not apply to asset type 'vehicle' and category 'truck_tractor'");

        ($this->handler)($command);
    }
}
