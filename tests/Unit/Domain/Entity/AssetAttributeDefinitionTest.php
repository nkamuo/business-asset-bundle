<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Tests\Unit\Domain\Entity;

use Nkamuo\AssetBundle\Domain\Entity\Asset;
use Nkamuo\AssetBundle\Domain\Entity\AssetAttributeDefinition;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetCategory;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetStatus;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetType;
use Nkamuo\AssetBundle\Domain\ValueObject\AttributeType;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

class AssetAttributeDefinitionTest extends TestCase
{
    public function testCreateBasicDefinition(): void
    {
        $definition = new AssetAttributeDefinition(
            'vehicle_make',
            'Vehicle Make',
            AttributeType::STRING,
            AssetType::VEHICLE,
            AssetCategory::TRUCK_TRACTOR
        );

        $this->assertSame('vehicle_make', $definition->getAttributeKey());
        $this->assertSame('Vehicle Make', $definition->getDisplayName());
        $this->assertSame(AttributeType::STRING, $definition->getAttributeType());
        $this->assertSame(AssetType::VEHICLE, $definition->getAssetType());
        $this->assertSame(AssetCategory::TRUCK_TRACTOR, $definition->getAssetCategory());
        $this->assertFalse($definition->isRequired());
        $this->assertTrue($definition->isSearchable());
        $this->assertTrue($definition->isActive());
    }

    public function testGlobalDefinition(): void
    {
        $definition = new AssetAttributeDefinition(
            'serial_number',
            'Serial Number',
            AttributeType::STRING
        );

        $this->assertNull($definition->getAssetType());
        $this->assertNull($definition->getAssetCategory());
    }

    public function testEnumDefinition(): void
    {
        $definition = new AssetAttributeDefinition(
            'condition',
            'Condition',
            AttributeType::ENUM
        );

        $options = ['Excellent', 'Good', 'Fair', 'Poor'];
        $definition->setEnumOptions($options);

        $this->assertSame($options, $definition->getEnumOptions());
    }

    public function testEnumOptionsOnNonEnumTypeThrowsException(): void
    {
        $definition = new AssetAttributeDefinition(
            'test',
            'Test',
            AttributeType::STRING
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Enum options can only be set for ENUM attribute types');

        $definition->setEnumOptions(['Option1', 'Option2']);
    }

    public function testValidationRules(): void
    {
        $definition = new AssetAttributeDefinition(
            'weight',
            'Weight (kg)',
            AttributeType::FLOAT
        );

        $rules = ['min' => 0, 'max' => 50000];
        $definition->setValidationRules($rules);

        $this->assertSame($rules, $definition->getValidationRules());
    }

    public function testAppliesTo(): void
    {
        // Global definition (applies to all)
        $globalDefinition = new AssetAttributeDefinition(
            'notes',
            'Notes',
            AttributeType::STRING
        );

        // Type-specific definition
        $vehicleDefinition = new AssetAttributeDefinition(
            'engine_type',
            'Engine Type',
            AttributeType::STRING,
            AssetType::VEHICLE
        );

        // Category-specific definition
        $truckDefinition = new AssetAttributeDefinition(
            'cdl_required',
            'CDL Required',
            AttributeType::BOOLEAN,
            AssetType::VEHICLE,
            AssetCategory::TRUCK_TRACTOR
        );

        $truckAsset = new Asset(
            'TRUCK-001',
            'Test Truck',
            AssetType::VEHICLE,
            AssetCategory::TRUCK_TRACTOR,
            new Ulid(),
            new Money(5000000, new Currency('USD'))
        );

        $equipmentAsset = new Asset(
            'EQUIP-001',
            'Test Equipment',
            AssetType::EQUIPMENT,
            AssetCategory::FORKLIFT,
            new Ulid(),
            new Money(2000000, new Currency('USD'))
        );

        // Global definition applies to all
        $this->assertTrue($globalDefinition->appliesTo($truckAsset));
        $this->assertTrue($globalDefinition->appliesTo($equipmentAsset));

        // Vehicle definition applies to trucks but not equipment
        $this->assertTrue($vehicleDefinition->appliesTo($truckAsset));
        $this->assertFalse($vehicleDefinition->appliesTo($equipmentAsset));

        // Truck-specific definition applies only to truck category
        $this->assertTrue($truckDefinition->appliesTo($truckAsset));
        $this->assertFalse($truckDefinition->appliesTo($equipmentAsset));
    }

    public function testValueValidation(): void
    {
        // String validation
        $stringDef = new AssetAttributeDefinition(
            'name',
            'Name',
            AttributeType::STRING
        );
        $stringDef->setRequired(true);

        $this->assertEmpty($stringDef->validateValue('Valid String'));
        $errors = $stringDef->validateValue('');
        $this->assertContains("Attribute 'Name' is required", $errors);

        // Integer validation
        $intDef = new AssetAttributeDefinition(
            'count',
            'Count',
            AttributeType::INTEGER
        );

        $this->assertEmpty($intDef->validateValue(42));
        $this->assertEmpty($intDef->validateValue('42'));
        $errors = $intDef->validateValue('not a number');
        $this->assertContains('Value must be a whole number', $errors);

        // Email validation
        $emailDef = new AssetAttributeDefinition(
            'email',
            'Email',
            AttributeType::EMAIL
        );

        $this->assertEmpty($emailDef->validateValue('test@example.com'));
        $errors = $emailDef->validateValue('invalid-email');
        $this->assertContains('Value must be a valid email address', $errors);
    }

    public function testEnumValidation(): void
    {
        $enumDef = new AssetAttributeDefinition(
            'status',
            'Status',
            AttributeType::ENUM
        );
        $enumDef->setEnumOptions(['Active', 'Inactive', 'Pending']);

        $this->assertEmpty($enumDef->validateValue('Active'));
        $errors = $enumDef->validateValue('InvalidStatus');
        $this->assertContains('Value must be one of: Active, Inactive, Pending', $errors);
    }

    public function testCustomValidationRules(): void
    {
        $numberDef = new AssetAttributeDefinition(
            'score',
            'Score',
            AttributeType::INTEGER
        );
        $numberDef->setValidationRules(['min' => 0, 'max' => 100]);

        $this->assertEmpty($numberDef->validateValue(50));
        $this->assertEmpty($numberDef->validateValue(0));
        $this->assertEmpty($numberDef->validateValue(100));

        $errors = $numberDef->validateValue(-1);
        $this->assertContains('Value must be at least 0', $errors);

        $errors = $numberDef->validateValue(101);
        $this->assertContains('Value must be at most 100', $errors);
    }
}
