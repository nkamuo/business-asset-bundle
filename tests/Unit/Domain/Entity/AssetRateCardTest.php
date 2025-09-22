<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Tests\Unit\Domain\Entity;

use Money\Currency;
use Money\Money;
use Nkamuo\AssetBundle\Domain\Entity\Asset;
use Nkamuo\AssetBundle\Domain\Entity\AssetProvision;
use Nkamuo\AssetBundle\Domain\Entity\AssetRateCard;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetCategory;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetType;
use Nkamuo\AssetBundle\Domain\ValueObject\ProvisionType;
use Nkamuo\AssetBundle\Domain\ValueObject\RateType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

/**
 * Unit tests for AssetRateCard entity
 */
class AssetRateCardTest extends TestCase
{
    public function testAssetRateCardCreation(): void
    {
        $provision = $this->createTestProvision();
        $rateType = RateType::FIXED_MONTHLY;
        $rate = new Money(150000, new Currency('USD')); // $1,500.00
        $effectiveDate = new \DateTimeImmutable('2024-01-01');
        $expiryDate = new \DateTimeImmutable('2024-12-31');
        $unitOfMeasure = 'month';
        $metadata = ['contract_ref' => 'RATE-001'];

        $rateCard = new AssetRateCard(
            provision: $provision,
            rateType: $rateType,
            rate: $rate,
            effectiveDate: $effectiveDate,
            unitOfMeasure: $unitOfMeasure,
            expiryDate: $expiryDate,
            metadata: $metadata
        );

        $this->assertEquals($provision, $rateCard->getProvision());
        $this->assertEquals($rateType, $rateCard->getRateType());
        $this->assertEquals($rate, $rateCard->getRate());
        $this->assertEquals($effectiveDate, $rateCard->getEffectiveDate());
        $this->assertEquals($expiryDate, $rateCard->getExpiryDate());
        $this->assertEquals($unitOfMeasure, $rateCard->getUnitOfMeasure());
        $this->assertEquals($metadata, $rateCard->getMetadata());
        $this->assertTrue($rateCard->isActiveAt(new \DateTimeImmutable('2024-06-15')));
    }

    public function testFixedMonthlyRateCalculation(): void
    {
        $rateCard = $this->createFixedMonthlyRateCard();
        $quantity = 1;
        $usageData = [];

        $calculatedAmount = $rateCard->calculateRateAmount($quantity, $usageData);
        $expectedAmount = new Money(150000, new Currency('USD')); // Base rate
        
        $this->assertEquals($expectedAmount, $calculatedAmount);
    }

    public function testPerMileRateCalculation(): void
    {
        $rateCard = $this->createPerMileRateCard();
        $quantity = 500; // 500 miles
        $usageData = [];

        $calculatedAmount = $rateCard->calculateRateAmount($quantity, $usageData);
        $expectedAmount = new Money(125000, new Currency('USD')); // 500 * $2.50
        
        $this->assertEquals($expectedAmount, $calculatedAmount);
    }

    public function testPercentageRevenueCalculation(): void
    {
        $rateCard = $this->createPercentageRevenueRateCard();
        $quantity = 1;
        $usageData = ['total_revenue' => new Money(1000000, new Currency('USD'))]; // $10,000 revenue

        $calculatedAmount = $rateCard->calculateRateAmount($quantity, $usageData);
        $expectedAmount = new Money(150000, new Currency('USD')); // 15% of $10,000
        
        $this->assertEquals($expectedAmount, $calculatedAmount);
    }

    public function testTieredRateCalculation(): void
    {
        $rateCard = $this->createTieredRateCard();
        
        // Test first tier calculation
        $calculatedAmount1 = $rateCard->calculateRateAmount(50, []);
        $expectedAmount1 = new Money(5000, new Currency('USD')); // 50 * $1.00
        $this->assertEquals($expectedAmount1, $calculatedAmount1);

        // Test second tier calculation
        $calculatedAmount2 = $rateCard->calculateRateAmount(150, []);
        // First 100 at $1.00 + next 50 at $0.80 = $100 + $40 = $140
        $expectedAmount2 = new Money(14000, new Currency('USD'));
        $this->assertEquals($expectedAmount2, $calculatedAmount2);
    }

    public function testRateCardActiveStatus(): void
    {
        // Create rate card that's currently active
        $effectiveDate = new \DateTimeImmutable('2024-01-01');
        $expiryDate = new \DateTimeImmutable('2025-12-31');
        
        $rateCard = new AssetRateCard(
            provision: $this->createTestProvision(),
            rateType: RateType::FIXED_MONTHLY,
            rate: new Money(100000, new Currency('USD')),
            effectiveDate: $effectiveDate,
            expiryDate: $expiryDate
        );

        $this->assertTrue($rateCard->isActiveAt(new \DateTimeImmutable('2024-06-15')));
        $this->assertTrue($rateCard->isActiveAt(new \DateTimeImmutable('2024-01-01')));
        $this->assertTrue($rateCard->isActiveAt(new \DateTimeImmutable('2025-12-31')));
        $this->assertFalse($rateCard->isActiveAt(new \DateTimeImmutable('2023-12-31')));
        $this->assertFalse($rateCard->isActiveAt(new \DateTimeImmutable('2026-01-01')));
    }

    public function testRateCardMetadataManagement(): void
    {
        $rateCard = $this->createFixedMonthlyRateCard();

        $metadata = [
            'discount_code' => 'SUMMER2024',
            'customer_type' => 'premium',
            'contract_terms' => 'annual'
        ];

        $rateCard->updateMetadata($metadata);
        $this->assertEquals($metadata, $rateCard->getMetadata());

        $rateCard->addMetadata('approval_required', true);
        $this->assertTrue($rateCard->getMetadata()['approval_required']);
    }

    public function testGetMetadataValue(): void
    {
        $metadata = [
            'discount_code' => 'SUMMER2024',
            'customer_type' => 'premium'
        ];

        $rateCard = new AssetRateCard(
            provision: $this->createTestProvision(),
            rateType: RateType::FIXED_MONTHLY,
            rate: new Money(100000, new Currency('USD')),
            effectiveDate: new \DateTimeImmutable('2024-01-01'),
            metadata: $metadata
        );

        $this->assertEquals('SUMMER2024', $rateCard->getMetadataValue('discount_code'));
        $this->assertEquals('premium', $rateCard->getMetadataValue('customer_type'));
        $this->assertNull($rateCard->getMetadataValue('non_existent'));
    }

    public function testRateCardWithMinimumAndMaximumCharges(): void
    {
        $minimumCharge = new Money(50000, new Currency('USD')); // $500 minimum
        $maximumCharge = new Money(300000, new Currency('USD')); // $3,000 maximum

        $rateCard = new AssetRateCard(
            provision: $this->createTestProvision(),
            rateType: RateType::PER_MILE,
            rate: new Money(250, new Currency('USD')), // $2.50 per mile
            effectiveDate: new \DateTimeImmutable('2024-01-01'),
            minimumCharge: $minimumCharge,
            maximumCharge: $maximumCharge
        );

        $this->assertEquals($minimumCharge, $rateCard->getMinimumCharge());
        $this->assertEquals($maximumCharge, $rateCard->getMaximumCharge());
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

    private function createTestProvision(): AssetProvision
    {
        return new AssetProvision(
            asset: $this->createTestAsset(),
            providerId: new Ulid(),
            recipientId: new Ulid(),
            type: ProvisionType::LEASED,
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2025-12-31')
        );
    }

    private function createFixedMonthlyRateCard(): AssetRateCard
    {
        return new AssetRateCard(
            provision: $this->createTestProvision(),
            rateType: RateType::FIXED_MONTHLY,
            rate: new Money(150000, new Currency('USD')),
            effectiveDate: new \DateTimeImmutable('2024-01-01'),
            expiryDate: new \DateTimeImmutable('2025-12-31'),
            unitOfMeasure: 'month'
        );
    }

    private function createPerMileRateCard(): AssetRateCard
    {
        return new AssetRateCard(
            provision: $this->createTestProvision(),
            rateType: RateType::PER_MILE,
            rate: new Money(250, new Currency('USD')), // $2.50 per mile
            effectiveDate: new \DateTimeImmutable('2024-01-01'),
            expiryDate: new \DateTimeImmutable('2025-12-31'),
            unitOfMeasure: 'mile'
        );
    }

    private function createPercentageRevenueRateCard(): AssetRateCard
    {
        return new AssetRateCard(
            provision: $this->createTestProvision(),
            rateType: RateType::PERCENTAGE_REVENUE,
            rate: new Money(1500, new Currency('USD')), // 15% as basis points
            effectiveDate: new \DateTimeImmutable('2024-01-01'),
            expiryDate: new \DateTimeImmutable('2025-12-31'),
            unitOfMeasure: 'percentage'
        );
    }

    private function createTieredRateCard(): AssetRateCard
    {
        $tierStructure = [
            ['max_quantity' => 100, 'rate_amount' => '100'], // $1.00 for 0-100 units
            ['max_quantity' => 500, 'rate_amount' => '80'],  // $0.80 for 101-500 units
            ['max_quantity' => null, 'rate_amount' => '60']  // $0.60 for 501+ units
        ];

        return new AssetRateCard(
            provision: $this->createTestProvision(),
            rateType: RateType::TIERED,
            rate: new Money(100, new Currency('USD')), // Base tier rate
            effectiveDate: new \DateTimeImmutable('2024-01-01'),
            expiryDate: new \DateTimeImmutable('2025-12-31'),
            tierStructure: $tierStructure,
            unitOfMeasure: 'unit'
        );
    }
}
