<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Tests\Integration;

use Nkamuo\AssetBundle\Application\Command\CreateAssetAttributeDefinitionCommand;
use Nkamuo\AssetBundle\Application\Command\SetAssetAttributeCommand;
use Nkamuo\AssetBundle\Application\CommandHandler\CreateAssetAttributeDefinitionCommandHandler;
use Nkamuo\AssetBundle\Application\CommandHandler\SetAssetAttributeCommandHandler;
use Nkamuo\AssetBundle\Domain\Entity\Asset;
use Nkamuo\AssetBundle\Domain\Repository\AssetAttributeDefinitionRepositoryInterface;
use Nkamuo\AssetBundle\Domain\Repository\AssetAttributeRepositoryInterface;
use Nkamuo\AssetBundle\Domain\Repository\AssetRepositoryInterface;
use Nkamuo\AssetBundle\Domain\Service\AssetAttributeService;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetCategory;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetType;
use Nkamuo\AssetBundle\Domain\ValueObject\AttributeType;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

/**
 * Integration test demonstrating the complete dynamic attribute system working together.
 */
class DynamicAttributeSystemIntegrationTest extends TestCase
{
    private AssetRepositoryInterface&MockObject $assetRepository;
    private AssetAttributeDefinitionRepositoryInterface&MockObject $definitionRepository;
    private AssetAttributeRepositoryInterface&MockObject $attributeRepository;
    private CreateAssetAttributeDefinitionCommandHandler $createDefinitionHandler;
    private SetAssetAttributeCommandHandler $setAttributeHandler;
    private AssetAttributeService $attributeService;

    protected function setUp(): void
    {
        $this->assetRepository = $this->createMock(AssetRepositoryInterface::class);
        $this->definitionRepository = $this->createMock(AssetAttributeDefinitionRepositoryInterface::class);
        $this->attributeRepository = $this->createMock(AssetAttributeRepositoryInterface::class);

        $this->createDefinitionHandler = new CreateAssetAttributeDefinitionCommandHandler(
            $this->definitionRepository
        );

        $this->setAttributeHandler = new SetAssetAttributeCommandHandler(
            $this->assetRepository,
            $this->definitionRepository,
            $this->attributeRepository
        );

        $this->attributeService = new AssetAttributeService(
            $this->definitionRepository,
            $this->attributeRepository
        );
    }

    public function testCompleteAttributeWorkflow(): void
    {
        // Reset mock for this test method
        $this->definitionRepository = $this->createMock(AssetAttributeDefinitionRepositoryInterface::class);
        $this->assetRepository = $this->createMock(AssetRepositoryInterface::class);
        $this->attributeRepository = $this->createMock(AssetAttributeRepositoryInterface::class);
        
        $this->createDefinitionHandler = new CreateAssetAttributeDefinitionCommandHandler(
            $this->definitionRepository
        );

        $this->setAttributeHandler = new SetAssetAttributeCommandHandler(
            $this->assetRepository,
            $this->definitionRepository,
            $this->attributeRepository
        );

        $this->attributeService = new AssetAttributeService(
            $this->definitionRepository,
            $this->attributeRepository
        );

        // Step 1: Create attribute definitions for vehicles
        $makeDefinitionCommand = new CreateAssetAttributeDefinitionCommand(
            'vehicle_make',
            'Vehicle Make',
            AttributeType::STRING,
            'The manufacturer of the vehicle',
            AssetType::VEHICLE,
            null,
            true,  // required
            true,  // searchable
            false, // allow multiple values
            ['required' => true, 'max_length' => 50],
            null,
            null,
            null,
            10
        );

        $yearDefinitionCommand = new CreateAssetAttributeDefinitionCommand(
            'model_year',
            'Model Year',
            AttributeType::INTEGER,
            'The year the vehicle was manufactured',
            AssetType::VEHICLE,
            null,
            true,  // required
            true,  // searchable
            false, // allow multiple values
            ['required' => true, 'min' => 1900, 'max' => 2030],
            null,
            null,
            null,
            20
        );

        $fuelTypeCommand = new CreateAssetAttributeDefinitionCommand(
            'fuel_type',
            'Fuel Type',
            AttributeType::ENUM,
            'The type of fuel the vehicle uses',
            AssetType::VEHICLE,
            null,
            false, // not required
            true,  // searchable
            false, // allow multiple values
            null,
            ['diesel', 'gasoline', 'electric', 'hybrid'],
            'gasoline',
            null,
            30
        );

        // Mock repository responses for definition creation
        $this->definitionRepository
            ->method('isKeyUnique')
            ->willReturnMap([
                ['vehicle_make', true],
                ['model_year', true],
                ['fuel_type', true]
            ]);

        $this->definitionRepository
            ->expects($this->exactly(3))
            ->method('save')
            ->with($this->isInstanceOf(\Nkamuo\AssetBundle\Domain\Entity\AssetAttributeDefinition::class));

        // Create the definitions
        $makeDefinition = ($this->createDefinitionHandler)($makeDefinitionCommand);
        $yearDefinition = ($this->createDefinitionHandler)($yearDefinitionCommand);
        $fuelDefinition = ($this->createDefinitionHandler)($fuelTypeCommand);

        $this->assertSame('vehicle_make', $makeDefinition->getAttributeKey());
        $this->assertTrue($makeDefinition->isRequired());
        $this->assertSame(AttributeType::STRING, $makeDefinition->getAttributeType());

        // Step 2: Create a vehicle asset
        $vehicle = new Asset(
            'TRUCK-001',
            '2020 Ford F-350 Super Duty',
            AssetType::VEHICLE,
            AssetCategory::TRUCK_TRACTOR,
            new Ulid(),
            new Money(4500000, new Currency('USD')) // $45,000
        );

        // Step 3: Set attributes on the vehicle
        $vehicleId = $vehicle->getId();

        // Mock repository responses for setting attributes
        $this->assetRepository
            ->expects($this->exactly(3))
            ->method('findById')
            ->with($vehicleId)
            ->willReturn($vehicle);

        $this->definitionRepository
            ->method('findByKey')
            ->willReturnMap([
                ['vehicle_make', $makeDefinition],
                ['model_year', $yearDefinition],
                ['fuel_type', $fuelDefinition]
            ]);

        $this->attributeRepository
            ->expects($this->exactly(3))
            ->method('findByAssetAndDefinition')
            ->willReturn(null); // No existing attributes

        $this->attributeRepository
            ->expects($this->exactly(3))
            ->method('save');

        // Set make attribute
        $setMakeCommand = new SetAssetAttributeCommand($vehicleId, 'vehicle_make', 'Ford');
        $makeAttribute = ($this->setAttributeHandler)($setMakeCommand);

        // Set year attribute
        $setYearCommand = new SetAssetAttributeCommand($vehicleId, 'model_year', 2020);
        $yearAttribute = ($this->setAttributeHandler)($setYearCommand);

        // Set fuel type attribute
        $setFuelCommand = new SetAssetAttributeCommand($vehicleId, 'fuel_type', 'diesel');
        $fuelAttribute = ($this->setAttributeHandler)($setFuelCommand);

        $this->assertSame('Ford', $makeAttribute->getValue());
        $this->assertSame(2020, $yearAttribute->getValue());
        $this->assertSame('diesel', $fuelAttribute->getValue());

        // Step 4: Validate required attributes using the service
        $definitions = [$makeDefinition, $yearDefinition, $fuelDefinition];
        $requiredDefinitions = [$makeDefinition, $yearDefinition]; // Only make and year are required

        $this->definitionRepository
            ->expects($this->once())
            ->method('findRequired')
            ->with(AssetType::VEHICLE, AssetCategory::TRUCK_TRACTOR)
            ->willReturn($requiredDefinitions);

        // Add attributes to the asset for validation
        $vehicle->getCustomAttributes()->add($makeAttribute);
        $vehicle->getCustomAttributes()->add($yearAttribute);
        $vehicle->getCustomAttributes()->add($fuelAttribute);

        $validationErrors = $this->attributeService->validateRequiredAttributes($vehicle);
        $this->assertEmpty($validationErrors, 'All required attributes should be present');

        // Step 5: Test attribute template generation
        $this->definitionRepository
            ->expects($this->once())
            ->method('findApplicableToAsset')
            ->with(AssetType::VEHICLE, AssetCategory::TRUCK_TRACTOR)
            ->willReturn($definitions);

        $template = $this->attributeService->getAttributeTemplate(AssetType::VEHICLE, AssetCategory::TRUCK_TRACTOR);

        $this->assertCount(3, $template);
        
        // Check template structure for make attribute
        $makeTemplate = array_filter($template, fn($item) => $item['key'] === 'vehicle_make')[0];
        $this->assertSame('Vehicle Make', $makeTemplate['displayName']);
        $this->assertTrue($makeTemplate['required']);
        $this->assertSame('string', $makeTemplate['type']);

        // Check template structure for fuel type attribute
        $fuelTemplate = array_filter($template, fn($item) => $item['key'] === 'fuel_type')[0];
        $this->assertSame(['diesel', 'gasoline', 'electric', 'hybrid'], $fuelTemplate['enumOptions']);
        $this->assertSame('gasoline', $fuelTemplate['defaultValue']);

        // This integration test demonstrates:
        // 1. ✅ Creating attribute definitions with various types and validation
        // 2. ✅ Setting attributes on assets with proper validation
        // 3. ✅ Required attribute validation
        // 4. ✅ Template generation for UI forms
        // 5. ✅ Complete CQRS command/handler flow
        // 6. ✅ Domain service orchestration
    }

    public function testAttributeSearchAndStatistics(): void
    {
        // Reset mocks for this test method
        $this->definitionRepository = $this->createMock(AssetAttributeDefinitionRepositoryInterface::class);
        $this->attributeRepository = $this->createMock(AssetAttributeRepositoryInterface::class);
        $this->createDefinitionHandler = new CreateAssetAttributeDefinitionCommandHandler(
            $this->definitionRepository
        );
        $this->attributeService = new AssetAttributeService(
            $this->definitionRepository,
            $this->attributeRepository
        );

        $this->definitionRepository
            ->method('isKeyUnique')
            ->with('make')
            ->willReturn(true);

        // Mock search functionality  
        $searchCriteria = ['make' => 'Ford'];

        $this->definitionRepository
            ->method('findByKey')
            ->with('make')
            ->willReturn($makeDefinition);

        $this->attributeRepository
            ->method('findByValue')
            ->with('Ford', $makeDefinition)
            ->willReturn([]);

        $searchResults = $this->attributeService->searchAssetsByAttributes($searchCriteria);
        $this->assertIsArray($searchResults);

        // Mock statistics functionality
        $mockAttributes = []; // Would contain AssetAttribute instances in real scenario
        
        $this->attributeRepository
            ->method('findByDefinition')
            ->with($makeDefinition)
            ->willReturn($mockAttributes);

        $statistics = $this->attributeService->getAttributeStatistics($makeDefinition);

        $this->assertArrayHasKey('total_count', $statistics);
        $this->assertArrayHasKey('unique_values', $statistics);
        $this->assertArrayHasKey('value_distribution', $statistics);
    }
}
