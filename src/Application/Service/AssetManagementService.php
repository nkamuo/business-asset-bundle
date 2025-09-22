<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Application\Service;

use Nkamuo\AssetBundle\Application\Command\CreateAssetCommand;
use Nkamuo\AssetBundle\Application\Command\CreateAssetProvisionCommand;
use Nkamuo\AssetBundle\Application\Command\CreateAssetRateCardCommand;
use Nkamuo\AssetBundle\Application\Command\RecordAssetUsageCommand;
use Nkamuo\AssetBundle\Domain\Entity\Asset;
use Nkamuo\AssetBundle\Domain\Entity\AssetBillingEvent;
use Nkamuo\AssetBundle\Domain\Entity\AssetProvision;
use Nkamuo\AssetBundle\Domain\Entity\AssetRateCard;
use Nkamuo\AssetBundle\Domain\Entity\AssetUsageEvent;
use Nkamuo\AssetBundle\Domain\Repository\AssetRepositoryInterface;
use Nkamuo\AssetBundle\Domain\Repository\AssetProvisionRepositoryInterface;
use Nkamuo\AssetBundle\Domain\Repository\AssetBillingEventRepositoryInterface;
use Nkamuo\AssetBundle\Domain\Service\AssetBillingService;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetStatus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Ulid;

/**
 * Application service for asset management operations
 * 
 * Orchestrates asset management workflows including creation,
 * provisioning, usage tracking, and billing calculations.
 */
final readonly class AssetManagementService
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private AssetRepositoryInterface $assetRepository,
        private AssetProvisionRepositoryInterface $provisionRepository,
        private AssetBillingEventRepositoryInterface $billingEventRepository,
        private AssetBillingService $billingService
    ) {
    }

    /**
     * Create a new asset
     */
    public function createAsset(CreateAssetCommand $command): Asset
    {
        $this->commandBus->dispatch($command);
        
        // Return the created asset
        return $this->assetRepository->findByAssetNumber($command->assetNumber);
    }

    /**
     * Create a new asset provision
     */
    public function createAssetProvision(CreateAssetProvisionCommand $command): AssetProvision
    {
        $this->commandBus->dispatch($command);
        
        // Find and return the created provision
        $provisions = $this->provisionRepository->findByAsset($command->assetId);
        return end($provisions); // Return the most recently created provision
    }

    /**
     * Create a new asset rate card
     */
    public function createAssetRateCard(CreateAssetRateCardCommand $command): AssetRateCard
    {
        $provision = $this->provisionRepository->findById($command->provisionId);
        
        if (!$provision) {
            throw new \InvalidArgumentException('Provision not found');
        }

        $rateCard = new AssetRateCard(
            provision: $provision,
            rateType: $command->rateType,
            rate: $command->rate,
            effectiveDate: $command->effectiveDate,
            unitOfMeasure: $command->unitOfMeasure,
            minimumCharge: $command->minimumCharge,
            maximumCharge: $command->maximumCharge,
            expiryDate: $command->expiryDate,
            conditions: $command->conditions,
            tierStructure: $command->tierStructure,
            metadata: $command->metadata
        );

        // Note: This would typically use a repository to save
        // For this example, we're showing the creation logic
        
        return $rateCard;
    }

    /**
     * Record asset usage event
     */
    public function recordAssetUsage(RecordAssetUsageCommand $command): AssetUsageEvent
    {
        $this->commandBus->dispatch($command);
        
        $asset = $this->assetRepository->findById($command->assetId);
        if (!$asset) {
            throw new \InvalidArgumentException('Asset not found');
        }

        // Update asset status if needed
        if ($asset->getStatus() === AssetStatus::AVAILABLE) {
            $asset->updateStatus(AssetStatus::IN_SERVICE);
            $this->assetRepository->save($asset);
        }

        // Return the usage event (in real implementation, this would be retrieved from repository)
        return new AssetUsageEvent(
            asset: $asset,
            usageType: $command->usageType,
            quantity: $command->quantity,
            unitOfMeasure: $command->unitOfMeasure,
            startTime: $command->startTime,
            endTime: $command->endTime,
            sourceEntityType: $command->sourceEntityType,
            sourceEntityId: $command->sourceEntityId,
            location: $command->location,
            metadata: $command->metadata
        );
    }

    /**
     * Calculate billing for an asset in a specific period
     * 
     * @return AssetBillingEvent[]
     */
    public function calculateAssetBilling(
        Ulid $assetId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array {
        $asset = $this->assetRepository->findById($assetId);
        
        if (!$asset) {
            throw new \InvalidArgumentException('Asset not found');
        }

        return $this->billingService->calculateAssetCharges($asset, $startDate, $endDate);
    }

    /**
     * Calculate billing for all assets for a specific period
     * 
     * @return AssetBillingEvent[]
     */
    public function calculateBillingForAllAssets(
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array {
        $allAssets = $this->assetRepository->findAll();
        $allBillingEvents = [];

        foreach ($allAssets as $asset) {
            $billingEvents = $this->billingService->calculateAssetCharges($asset, $startDate, $endDate);
            $allBillingEvents = array_merge($allBillingEvents, $billingEvents);
        }

        return $allBillingEvents;
    }

    /**
     * Approve billing events
     */
    public function approveBillingEvents(array $billingEventIds): void
    {
        foreach ($billingEventIds as $id) {
            $billingEvent = $this->billingEventRepository->findById(Ulid::fromString($id));
            
            if ($billingEvent) {
                $billingEvent->approve();
                $this->billingEventRepository->save($billingEvent);
            }
        }
    }

    /**
     * Process billing events for settlement
     */
    public function processBillingForSettlement(
        array $billingEventIds,
        string $settlementId
    ): void {
        foreach ($billingEventIds as $id) {
            $billingEvent = $this->billingEventRepository->findById(Ulid::fromString($id));
            
            if ($billingEvent && $billingEvent->getStatus()->canBeSettled()) {
                $billingEvent->bill($settlementId);
                $this->billingEventRepository->save($billingEvent);
            }
        }
    }

    /**
     * Get asset utilization statistics
     */
    public function getAssetUtilizationStats(
        Ulid $assetId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array {
        $asset = $this->assetRepository->findById($assetId);
        
        if (!$asset) {
            throw new \InvalidArgumentException('Asset not found');
        }

        // Calculate total period hours
        $totalPeriodHours = $startDate->diff($endDate)->days * 24;
        
        // Get usage events for the period
        $usageEvents = []; // In real implementation, get from usage event repository
        
        $productiveHours = 0;
        $idleHours = 0;
        $maintenanceHours = 0;
        $totalUsageHours = 0;

        foreach ($usageEvents as $event) {
            $eventHours = $event->getDurationInHours() ?? 0;
            $totalUsageHours += $eventHours;
            
            if ($event->getUsageType()->isProductive()) {
                $productiveHours += $eventHours;
            } elseif ($event->getUsageType()->isDowntime()) {
                if ($event->getUsageType()->value === 'maintenance') {
                    $maintenanceHours += $eventHours;
                } else {
                    $idleHours += $eventHours;
                }
            }
        }

        $utilizationRate = $totalPeriodHours > 0 ? ($totalUsageHours / $totalPeriodHours) * 100 : 0;
        $productivityRate = $totalUsageHours > 0 ? ($productiveHours / $totalUsageHours) * 100 : 0;

        return [
            'asset_id' => (string) $assetId,
            'period_start' => $startDate->format('c'),
            'period_end' => $endDate->format('c'),
            'total_period_hours' => $totalPeriodHours,
            'total_usage_hours' => $totalUsageHours,
            'productive_hours' => $productiveHours,
            'idle_hours' => $idleHours,
            'maintenance_hours' => $maintenanceHours,
            'utilization_rate' => round($utilizationRate, 2),
            'productivity_rate' => round($productivityRate, 2),
            'usage_events_count' => count($usageEvents),
        ];
    }

    /**
     * Get billing summary for an asset
     */
    public function getAssetBillingSummary(
        Ulid $assetId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array {
        $billingEvents = $this->billingEventRepository->findWithFilters(
            assetId: $assetId,
            billingPeriodStart: $startDate,
            billingPeriodEnd: $endDate
        );

        $totalAmount = 0;
        $approvedAmount = 0;
        $billedAmount = 0;
        $paidAmount = 0;
        $statusCounts = [];

        foreach ($billingEvents as $event) {
            $amount = $event->getCalculatedAmount()->getAmount();
            $totalAmount += $amount;
            
            $status = $event->getStatus()->value;
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
            
            switch ($event->getStatus()) {
                case \Nkamuo\AssetBundle\Domain\ValueObject\BillingEventStatus::APPROVED:
                    $approvedAmount += $amount;
                    break;
                case \Nkamuo\AssetBundle\Domain\ValueObject\BillingEventStatus::BILLED:
                    $billedAmount += $amount;
                    break;
                case \Nkamuo\AssetBundle\Domain\ValueObject\BillingEventStatus::PAID:
                    $paidAmount += $amount;
                    break;
            }
        }

        return [
            'asset_id' => (string) $assetId,
            'period_start' => $startDate->format('c'),
            'period_end' => $endDate->format('c'),
            'billing_events_count' => count($billingEvents),
            'total_amount_cents' => $totalAmount,
            'approved_amount_cents' => $approvedAmount,
            'billed_amount_cents' => $billedAmount,
            'paid_amount_cents' => $paidAmount,
            'status_counts' => $statusCounts,
        ];
    }

    /**
     * Validate asset configuration
     */
    public function validateAssetConfiguration(Ulid $assetId): array
    {
        $asset = $this->assetRepository->findById($assetId);
        
        if (!$asset) {
            throw new \InvalidArgumentException('Asset not found');
        }

        $issues = [];
        
        // Check if asset has active provision
        $activeProvision = $asset->getActiveProvision();
        if (!$activeProvision) {
            $issues[] = 'Asset has no active provision';
        } else {
            // Check if provision has rate cards
            $rateCards = $activeProvision->getActiveRateCards();
            if (empty($rateCards)) {
                $issues[] = 'Active provision has no rate cards';
            }
        }
        
        // Check asset status
        if (!$asset->getStatus()->isOperational()) {
            $issues[] = sprintf('Asset status "%s" is not operational', $asset->getStatus()->value);
        }
        
        // Check required identifiers based on asset type
        $requiredIdentifiers = $this->getRequiredIdentifiersForAssetType($asset->getType());
        foreach ($requiredIdentifiers as $identifier) {
            if (!array_key_exists($identifier, $asset->getIdentifiers())) {
                $issues[] = sprintf('Missing required identifier: %s', $identifier);
            }
        }

        return [
            'asset_id' => (string) $assetId,
            'is_valid' => empty($issues),
            'issues' => $issues,
            'validation_timestamp' => (new \DateTimeImmutable())->format('c'),
        ];
    }

    private function getRequiredIdentifiersForAssetType($assetType): array
    {
        return match($assetType) {
            \Nkamuo\AssetBundle\Domain\ValueObject\AssetType::VEHICLE => ['vin', 'license_plate'],
            \Nkamuo\AssetBundle\Domain\ValueObject\AssetType::DRIVER => ['license_number'],
            \Nkamuo\AssetBundle\Domain\ValueObject\AssetType::EQUIPMENT => ['serial_number'],
            \Nkamuo\AssetBundle\Domain\ValueObject\AssetType::CONTAINER => ['container_number'],
            \Nkamuo\AssetBundle\Domain\ValueObject\AssetType::TRAILER => ['trailer_number'],
            default => [],
        };
    }
}
