<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Tests\Unit\Domain\Entity;

use Nkamuo\AssetBundle\Domain\Entity\Asset;
use Nkamuo\AssetBundle\Domain\Entity\AssetAttribute;
use Nkamuo\AssetBundle\Domain\Entity\AssetAttributeDefinition;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetCategory;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetStatus;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetType;
use Nkamuo\AssetBundle\Domain\ValueObject\AttributeType;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

class AssetAttributeTest extends TestCase
{
    private Asset $asset;
    private AssetAttributeDefinition $stringDefinition;
    private AssetAttributeDefinition $integerDefinition;
    private AssetAttributeDefinition $enumDefinition;

    protected function setUp(): void
    {
        $this->asset = new Asset(
            'TEST-001',
            'Test Asset',
            AssetType::VEHICLE,
            AssetCategory::TRUCK_TRACTOR,
            new Ulid(),
            new Money(5000000, new Currency('USD'))
        );

        $this->stringDefinition = new AssetAttributeDefinition(
            'make',
            'Vehicle Make',
            AttributeType::STRING,
            AssetType::VEHICLE
        );

        $this->integerDefinition = new AssetAttributeDefinition(
            'year',
            'Model Year',
            AttributeType::INTEGER,
            AssetType::VEHICLE
        );

        $this->enumDefinition = new AssetAttributeDefinition(
            'fuel_type',
            'Fuel Type',
            AttributeType::ENUM,
            AssetType::VEHICLE
        );
        $this->enumDefinition->setEnumOptions(['Diesel', 'Gasoline', 'Electric', 'Hybrid']);
    }

    public function testCreateStringAttribute(): void
    {
        $attribute = new AssetAttribute($this->asset, $this->stringDefinition, 'Ford');

        $this->assertSame($this->asset, $attribute->getAsset());
        $this->assertSame($this->stringDefinition, $attribute->getDefinition());
        $this->assertSame('Ford', $attribute->getValue());
        $this->assertSame('make', $attribute->getAttributeKey());
        $this->assertSame('Vehicle Make', $attribute->getDisplayName());
        $this->assertTrue($attribute->hasValue());
    }

    public function testCreateIntegerAttribute(): void
    {
        $attribute = new AssetAttribute($this->asset, $this->integerDefinition, 2023);

        $this->assertSame(2023, $attribute->getValue());
        $this->assertSame('2023', $attribute->getDisplayValue());
    }

    public function testCreateEnumAttribute(): void
    {
        $attribute = new AssetAttribute($this->asset, $this->enumDefinition, 'Diesel');

        $this->assertSame('Diesel', $attribute->getValue());
        $this->assertSame('Diesel', $attribute->getDisplayValue());
    }

    public function testInvalidEnumValueThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation failed');

        new AssetAttribute($this->asset, $this->enumDefinition, 'InvalidFuelType');
    }

    public function testSetValue(): void
    {
        $attribute = new AssetAttribute($this->asset, $this->stringDefinition);
        $this->assertNull($attribute->getValue());
        $this->assertFalse($attribute->hasValue());

        $attribute->setValue('Toyota');
        $this->assertSame('Toyota', $attribute->getValue());
        $this->assertTrue($attribute->hasValue());
    }

    public function testBooleanAttribute(): void
    {
        $boolDefinition = new AssetAttributeDefinition(
            'has_gps',
            'Has GPS',
            AttributeType::BOOLEAN
        );

        $attribute = new AssetAttribute($this->asset, $boolDefinition, true);
        $this->assertTrue($attribute->getValue());
        $this->assertSame('Yes', $attribute->getDisplayValue());

        $attribute->setValue(false);
        $this->assertFalse($attribute->getValue());
        $this->assertSame('No', $attribute->getDisplayValue());
    }

    public function testDateAttribute(): void
    {
        $dateDefinition = new AssetAttributeDefinition(
            'purchase_date',
            'Purchase Date',
            AttributeType::DATE
        );

        $date = new \DateTimeImmutable('2023-01-15');
        $attribute = new AssetAttribute($this->asset, $dateDefinition, $date);
        
        $this->assertEquals($date, $attribute->getValue());
        $this->assertSame('2023-01-15', $attribute->getDisplayValue());
    }

    public function testStringDateConversion(): void
    {
        $dateDefinition = new AssetAttributeDefinition(
            'purchase_date',
            'Purchase Date',
            AttributeType::DATE
        );

        $attribute = new AssetAttribute($this->asset, $dateDefinition, '2023-01-15');
        
        $this->assertInstanceOf(\DateTimeImmutable::class, $attribute->getValue());
        $this->assertSame('2023-01-15', $attribute->getDisplayValue());
    }

    public function testJsonAttribute(): void
    {
        $jsonDefinition = new AssetAttributeDefinition(
            'specs',
            'Specifications',
            AttributeType::JSON
        );

        $data = ['engine' => 'V8', 'horsepower' => 400];
        $attribute = new AssetAttribute($this->asset, $jsonDefinition, $data);
        
        $this->assertSame($data, $attribute->getValue());
        $this->assertSame(json_encode($data), $attribute->getDisplayValue());
    }
}
