<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Tests\Unit\Application\CommandHandler;

use Nkamuo\AssetBundle\Application\Command\CreateAssetAttributeDefinitionCommand;
use Nkamuo\AssetBundle\Application\CommandHandler\CreateAssetAttributeDefinitionCommandHandler;
use Nkamuo\AssetBundle\Domain\Repository\AssetAttributeDefinitionRepositoryInterface;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetCategory;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetType;
use Nkamuo\AssetBundle\Domain\ValueObject\AttributeType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateAssetAttributeDefinitionCommandHandlerTest extends TestCase
{
    private CreateAssetAttributeDefinitionCommandHandler $handler;
    private AssetAttributeDefinitionRepositoryInterface&MockObject $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(AssetAttributeDefinitionRepositoryInterface::class);
        $this->handler = new CreateAssetAttributeDefinitionCommandHandler($this->repository);
    }

    public function testCreateBasicDefinition(): void
    {
        $command = new CreateAssetAttributeDefinitionCommand(
            'vehicle_make',
            'Vehicle Make',
            AttributeType::STRING,
            'Vehicle manufacturer',
            AssetType::VEHICLE,
            AssetCategory::TRUCK_TRACTOR,
            false,
            true,
            false,
            null,
            null,
            null,
            null,
            10
        );

        $this->repository
            ->expects($this->once())
            ->method('isKeyUnique')
            ->with('vehicle_make')
            ->willReturn(true);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($definition) {
                return $definition->getAttributeKey() === 'vehicle_make' &&
                       $definition->getDisplayName() === 'Vehicle Make' &&
                       $definition->getAttributeType() === AttributeType::STRING &&
                       $definition->getAssetType() === AssetType::VEHICLE &&
                       $definition->getAssetCategory() === AssetCategory::TRUCK_TRACTOR &&
                       $definition->getDescription() === 'Vehicle manufacturer' &&
                       !$definition->isRequired() &&
                       $definition->isSearchable() &&
                       !$definition->allowsMultipleValues() &&
                       $definition->getSortOrder() === 10;
            }));

        $result = ($this->handler)($command);

        $this->assertSame('vehicle_make', $result->getAttributeKey());
        $this->assertSame('Vehicle Make', $result->getDisplayName());
        $this->assertSame(AttributeType::STRING, $result->getAttributeType());
    }

    public function testCreateEnumDefinition(): void
    {
        $enumOptions = ['Diesel', 'Gasoline', 'Electric', 'Hybrid'];
        $command = new CreateAssetAttributeDefinitionCommand(
            'fuel_type',
            'Fuel Type',
            AttributeType::ENUM,
            'Type of fuel used',
            AssetType::VEHICLE,
            null,
            true,
            true,
            false,
            null,
            $enumOptions
        );

        $this->repository
            ->expects($this->once())
            ->method('isKeyUnique')
            ->with('fuel_type')
            ->willReturn(true);

        $this->repository
            ->expects($this->once())
            ->method('save');

        $result = ($this->handler)($command);

        $this->assertSame($enumOptions, $result->getEnumOptions());
        $this->assertTrue($result->isRequired());
    }

    public function testDuplicateKeyThrowsException(): void
    {
        $command = new CreateAssetAttributeDefinitionCommand(
            'existing_key',
            'Display Name',
            AttributeType::STRING
        );

        $this->repository
            ->expects($this->once())
            ->method('isKeyUnique')
            ->with('existing_key')
            ->willReturn(false);

        $this->repository
            ->expects($this->never())
            ->method('save');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Attribute key 'existing_key' already exists");

        ($this->handler)($command);
    }

    public function testEnumWithoutOptionsThrowsException(): void
    {
        $command = new CreateAssetAttributeDefinitionCommand(
            'status',
            'Status',
            AttributeType::ENUM
        );

        $this->repository
            ->expects($this->once())
            ->method('isKeyUnique')
            ->willReturn(true);

        $this->repository
            ->expects($this->never())
            ->method('save');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Enum options are required for ENUM attribute type');

        ($this->handler)($command);
    }

    public function testCreateWithValidationRules(): void
    {
        $validationRules = ['min' => 0, 'max' => 100];
        $command = new CreateAssetAttributeDefinitionCommand(
            'score',
            'Score',
            AttributeType::INTEGER,
            'Performance score',
            null,
            null,
            false,
            true,
            false,
            $validationRules
        );

        $this->repository
            ->expects($this->once())
            ->method('isKeyUnique')
            ->willReturn(true);

        $this->repository
            ->expects($this->once())
            ->method('save');

        $result = ($this->handler)($command);

        $this->assertSame($validationRules, $result->getValidationRules());
    }

    public function testCreateWithDefaultValue(): void
    {
        $command = new CreateAssetAttributeDefinitionCommand(
            'warranty_years',
            'Warranty Years',
            AttributeType::INTEGER,
            'Warranty period in years',
            null,
            null,
            false,
            true,
            false,
            null,
            null,
            '3',
            'years'
        );

        $this->repository
            ->expects($this->once())
            ->method('isKeyUnique')
            ->willReturn(true);

        $this->repository
            ->expects($this->once())
            ->method('save');

        $result = ($this->handler)($command);

        $this->assertSame('3', $result->getDefaultValue());
        $this->assertSame('years', $result->getUnit());
    }
}
