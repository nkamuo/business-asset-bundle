<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Tests\Unit\Domain\Entity;

use Money\Currency;
use Money\Money;
use Nkamuo\AssetBundle\Domain\Entity\Asset;
use Nkamuo\AssetBundle\Domain\Entity\AssetProvision;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetCategory;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetType;
use Nkamuo\AssetBundle\Domain\ValueObject\ProvisionStatus;
use Nkamuo\AssetBundle\Domain\ValueObject\ProvisionType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

/**
 * Unit tests for AssetProvision entity.
 */
class AssetProvisionTest extends TestCase
{
    public function testAssetProvisionCreation(): void
    {
        $asset = $this->createTestAsset();
        $providerId = new Ulid();
        $recipientId = new Ulid();
        $type = ProvisionType::LEASED;
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-12-31');
        $terms = ['payment_terms' => '30 days'];
        $metadata = ['contract_ref' => 'LEASE-001'];

        $provision = new AssetProvision(
            asset: $asset,
            providerId: $providerId,
            recipientId: $recipientId,
            type: $type,
            startDate: $startDate,
            endDate: $endDate,
            terms: $terms,
            metadata: $metadata
        );

        $this->assertEquals($asset, $provision->getAsset());
        $this->assertEquals($providerId, $provision->getProviderId());
        $this->assertEquals($recipientId, $provision->getRecipientId());
        $this->assertEquals($type, $provision->getType());
        $this->assertEquals($startDate, $provision->getStartDate());
        $this->assertEquals($endDate, $provision->getEndDate());
        $this->assertEquals($terms, $provision->getTerms());
        $this->assertEquals($metadata, $provision->getMetadata());
        $this->assertEquals(ProvisionStatus::DRAFT, $provision->getStatus());
        $this->assertFalse($provision->isActive());
    }

    public function testProvisionStatusTransition(): void
    {
        // Create a provision that's active for the current time period
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2025-12-31'); // Ensure it covers current date

        $provision = new AssetProvision(
            asset: $this->createTestAsset(),
            providerId: new Ulid(),
            recipientId: new Ulid(),
            type: ProvisionType::LEASED,
            startDate: $startDate,
            endDate: $endDate
        );

        // Start in DRAFT status
        $this->assertEquals(ProvisionStatus::DRAFT, $provision->getStatus());

        $provision->activate();
        $this->assertEquals(ProvisionStatus::ACTIVE, $provision->getStatus());
        $this->assertTrue($provision->isActive());

        $provision->suspend();
        $this->assertEquals(ProvisionStatus::SUSPENDED, $provision->getStatus());
        $this->assertFalse($provision->isActive());

        $provision->terminate();
        $this->assertEquals(ProvisionStatus::TERMINATED, $provision->getStatus());
        $this->assertFalse($provision->isActive());
    }

    public function testProvisionTermsManagement(): void
    {
        $provision = $this->createTestProvision();

        $terms = [
            'payment_terms' => '30 days',
            'maintenance_responsibility' => 'lessor',
            'insurance_responsibility' => 'lessee',
        ];

        $provision->updateTerms($terms);
        $this->assertEquals($terms, $provision->getTerms());

        $provision->addTerm('renewal_option', 'automatic');
        $this->assertEquals('automatic', $provision->getTerms()['renewal_option']);
    }

    public function testProvisionMetadataManagement(): void
    {
        $provision = $this->createTestProvision();

        $metadata = [
            'sales_rep' => 'John Doe',
            'discount_applied' => '10%',
            'approval_code' => 'APP-123',
        ];

        $provision->updateMetadata($metadata);
        $this->assertEquals($metadata, $provision->getMetadata());

        $provision->addMetadata('notes', 'Priority customer');
        $this->assertEquals('Priority customer', $provision->getMetadata()['notes']);
    }

    public function testGetDuration(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-12-31');

        $provision = new AssetProvision(
            asset: $this->createTestAsset(),
            providerId: new Ulid(),
            recipientId: new Ulid(),
            type: ProvisionType::LEASED,
            startDate: $startDate,
            endDate: $endDate
        );

        $duration = $provision->getDuration();
        $this->assertNotNull($duration);
        // Duration should be approximately 365 days
        $this->assertEquals(365, $duration->days);
    }

    public function testGetRemainingDuration(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-12-31');

        $provision = new AssetProvision(
            asset: $this->createTestAsset(),
            providerId: new Ulid(),
            recipientId: new Ulid(),
            type: ProvisionType::LEASED,
            startDate: $startDate,
            endDate: $endDate
        );

        $checkDate = new \DateTimeImmutable('2024-06-01');
        $remaining = $provision->getRemainingDuration($checkDate);
        $this->assertNotNull($remaining);
        // Should have approximately 214 days remaining (June 1 to Dec 31)
        $this->assertGreaterThan(200, $remaining->days);
        $this->assertLessThan(220, $remaining->days);
    }

    public function testIsActiveAt(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-12-31');

        $provision = new AssetProvision(
            asset: $this->createTestAsset(),
            providerId: new Ulid(),
            recipientId: new Ulid(),
            type: ProvisionType::LEASED,
            startDate: $startDate,
            endDate: $endDate
        );

        $provision->activate();

        $this->assertTrue($provision->isActiveAt(new \DateTimeImmutable('2024-06-15')));
        $this->assertTrue($provision->isActiveAt(new \DateTimeImmutable('2024-01-01')));
        $this->assertTrue($provision->isActiveAt(new \DateTimeImmutable('2024-12-31')));
        $this->assertFalse($provision->isActiveAt(new \DateTimeImmutable('2023-12-31')));
        $this->assertFalse($provision->isActiveAt(new \DateTimeImmutable('2025-01-01')));
    }

    public function testGetTermAndMetadataValues(): void
    {
        $terms = ['payment_terms' => '30 days', 'renewal' => 'automatic'];
        $metadata = ['contract_ref' => 'LEASE-001', 'notes' => 'Test provision'];

        $provision = new AssetProvision(
            asset: $this->createTestAsset(),
            providerId: new Ulid(),
            recipientId: new Ulid(),
            type: ProvisionType::LEASED,
            startDate: new \DateTimeImmutable('2024-01-01'),
            terms: $terms,
            metadata: $metadata
        );

        $this->assertEquals('30 days', $provision->getTerm('payment_terms'));
        $this->assertEquals('automatic', $provision->getTerm('renewal'));
        $this->assertNull($provision->getTerm('non_existent'));

        $this->assertEquals('LEASE-001', $provision->getMetadataValue('contract_ref'));
        $this->assertEquals('Test provision', $provision->getMetadataValue('notes'));
        $this->assertNull($provision->getMetadataValue('non_existent'));
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
            endDate: new \DateTimeImmutable('2024-12-31')
        );
    }
}
