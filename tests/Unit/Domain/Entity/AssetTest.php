<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Tests\Unit\Domain\Entity;

use Money\Currency;
use Money\Money;
use Nkamuo\AssetBundle\Domain\Entity\Asset;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetCategory;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetStatus;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

/**
 * Unit tests for Asset entity
 */
class AssetTest extends TestCase
{
    public function testAssetCreation(): void
    {
        $assetNumber = 'TRUCK-001';
        $name = 'Ford F-150 Pickup';
        $type = AssetType::VEHICLE;
        $category = AssetCategory::TRUCK_TRACTOR;
        $ownerId = new Ulid();
        $acquisitionCost = new Money(2500000, new Currency('USD')); // $25,000.00

        $asset = new Asset(
            assetNumber: $assetNumber,
            name: $name,
            type: $type,
            category: $category,
            ownerId: $ownerId,
            acquisitionCost: $acquisitionCost
        );

        $this->assertEquals($assetNumber, $asset->getAssetNumber());
        $this->assertEquals($name, $asset->getName());
        $this->assertEquals($type, $asset->getType());
        $this->assertEquals($category, $asset->getCategory());
        $this->assertEquals($ownerId, $asset->getOwnerId());
        $this->assertEquals($acquisitionCost, $asset->getAcquisitionCost());
        $this->assertEquals(AssetStatus::AVAILABLE, $asset->getStatus());
        $this->assertTrue($asset->isAvailable());
        $this->assertFalse($asset->isInService());
    }

    public function testAssetStatusTransition(): void
    {
        $asset = $this->createTestAsset();

        $this->assertTrue($asset->isAvailable());

        $asset->updateStatus(AssetStatus::IN_SERVICE);
        $this->assertTrue($asset->isInService());
        $this->assertFalse($asset->isAvailable());

        $asset->updateStatus(AssetStatus::MAINTENANCE);
        $this->assertTrue($asset->isInMaintenance());
        $this->assertFalse($asset->isInService());
    }

    public function testOperatorAssignment(): void
    {
        $asset = $this->createTestAsset();
        $operatorId = new Ulid();

        $this->assertNull($asset->getOperatorId());

        $asset->assignOperator($operatorId);
        $this->assertEquals($operatorId, $asset->getOperatorId());

        $asset->removeOperator();
        $this->assertNull($asset->getOperatorId());
    }

    public function testSpecificationsManagement(): void
    {
        $asset = $this->createTestAsset();

        $specifications = [
            'engine' => 'V8',
            'capacity' => '2000kg',
            'fuel_type' => 'diesel'
        ];

        $asset->updateSpecifications($specifications);
        $this->assertEquals($specifications, $asset->getSpecifications());

        $asset->addSpecification('transmission', 'automatic');
        $this->assertEquals('automatic', $asset->getSpecifications()['transmission']);
    }

    public function testIdentifiersManagement(): void
    {
        $asset = $this->createTestAsset();

        $identifiers = [
            'vin' => '1HGBH41JXMN109186',
            'license_plate' => 'ABC-1234'
        ];

        $asset->updateIdentifiers($identifiers);
        $this->assertEquals($identifiers, $asset->getIdentifiers());

        $asset->addIdentifier('registration', 'REG-789');
        $this->assertEquals('REG-789', $asset->getIdentifiers()['registration']);
    }

    public function testMetadataManagement(): void
    {
        $asset = $this->createTestAsset();

        $metadata = [
            'location' => 'Warehouse A',
            'last_service' => '2024-01-15',
            'notes' => 'Excellent condition'
        ];

        $asset->updateMetadata($metadata);
        $this->assertEquals($metadata, $asset->getMetadata());

        $asset->addMetadata('inspection_due', '2024-06-15');
        $this->assertEquals('2024-06-15', $asset->getMetadata()['inspection_due']);
    }

    private function createTestAsset(): Asset
    {
        return new Asset(
            assetNumber: 'TEST-001',
            name: 'Test Asset',
            type: AssetType::VEHICLE,
            category: AssetCategory::TRUCK_TRACTOR,
            ownerId: new Ulid(),
            acquisitionCost: new Money(1000000, new Currency('USD'))
        );
    }
}
