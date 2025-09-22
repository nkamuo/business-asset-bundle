<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Tests\Unit\Application\CommandHandler;

use Money\Currency;
use Money\Money;
use Nkamuo\AssetBundle\Application\Command\CreateAssetProvisionCommand;
use Nkamuo\AssetBundle\Application\CommandHandler\CreateAssetProvisionCommandHandler;
use Nkamuo\AssetBundle\Domain\Entity\Asset;
use Nkamuo\AssetBundle\Domain\Repository\AssetProvisionRepositoryInterface;
use Nkamuo\AssetBundle\Domain\Repository\AssetRepositoryInterface;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetCategory;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetType;
use Nkamuo\AssetBundle\Domain\ValueObject\ProvisionType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

/**
 * Unit tests for CreateAssetProvisionCommandHandler.
 */
class CreateAssetProvisionCommandHandlerTest extends TestCase
{
    private $assetRepository;
    private $provisionRepository;
    private CreateAssetProvisionCommandHandler $handler;

    protected function setUp(): void
    {
        $this->assetRepository = $this->createMock(AssetRepositoryInterface::class);
        $this->provisionRepository = $this->createMock(AssetProvisionRepositoryInterface::class);
        $this->handler = new CreateAssetProvisionCommandHandler(
            $this->assetRepository,
            $this->provisionRepository
        );
    }

    public function testCreateAssetProvisionSuccess(): void
    {
        $assetId = new Ulid();
        $providerId = new Ulid();
        $recipientId = new Ulid();
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-12-31');
        $terms = ['payment_terms' => '30 days'];
        $metadata = ['contract_ref' => 'PROV-001'];

        $asset = new Asset(
            assetNumber: 'TEST-001',
            name: 'Test Asset',
            type: AssetType::VEHICLE,
            category: AssetCategory::TRUCK_TRACTOR,
            ownerId: new Ulid(),
            acquisitionCost: new Money(1000000, new Currency('USD'))
        );

        $command = new CreateAssetProvisionCommand(
            assetId: $assetId,
            providerId: $providerId,
            recipientId: $recipientId,
            type: ProvisionType::LEASED,
            startDate: $startDate,
            endDate: $endDate,
            terms: $terms,
            metadata: $metadata
        );

        $this->assetRepository
            ->expects($this->once())
            ->method('findById')
            ->with($assetId)
            ->willReturn($asset);

        $this->provisionRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($provision) use ($asset, $providerId, $recipientId, $startDate, $endDate, $terms, $metadata) {
                return $provision->getAsset() === $asset
                    && $provision->getProviderId() === $providerId
                    && $provision->getRecipientId() === $recipientId
                    && $provision->getType() === ProvisionType::LEASED
                    && $provision->getStartDate() === $startDate
                    && $provision->getEndDate() === $endDate
                    && $provision->getTerms() === $terms
                    && $provision->getMetadata() === $metadata;
            }));

        $result = $this->handler->__invoke($command);

        $this->assertEquals($asset, $result->getAsset());
        $this->assertEquals($providerId, $result->getProviderId());
        $this->assertEquals($recipientId, $result->getRecipientId());
        $this->assertEquals(ProvisionType::LEASED, $result->getType());
        $this->assertEquals($startDate, $result->getStartDate());
        $this->assertEquals($endDate, $result->getEndDate());
        $this->assertEquals($terms, $result->getTerms());
        $this->assertEquals($metadata, $result->getMetadata());
    }

    public function testCreateAssetProvisionWithNonExistentAsset(): void
    {
        $assetId = new Ulid();
        $command = new CreateAssetProvisionCommand(
            assetId: $assetId,
            providerId: new Ulid(),
            recipientId: new Ulid(),
            type: ProvisionType::LEASED,
            startDate: new \DateTimeImmutable('2024-01-01')
        );

        $this->assetRepository
            ->expects($this->once())
            ->method('findById')
            ->with($assetId)
            ->willReturn(null);

        $this->provisionRepository
            ->expects($this->never())
            ->method('save');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Asset with ID %s not found', $assetId));

        $this->handler->__invoke($command);
    }
}
