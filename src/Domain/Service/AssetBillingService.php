<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Domain\Service;

use Nkamuo\AssetBundle\Domain\Entity\Asset;
use Nkamuo\AssetBundle\Domain\Entity\AssetBillingEvent;
use Nkamuo\AssetBundle\Domain\Entity\AssetProvision;
use Nkamuo\AssetBundle\Domain\Entity\AssetRateCard;
use Nkamuo\AssetBundle\Domain\Entity\AssetUsageEvent;
use Nkamuo\AssetBundle\Domain\Repository\AssetUsageEventRepositoryInterface;
use Nkamuo\AssetBundle\Domain\ValueObject\RateType;
use Money\Money;

/**
 * Domain service for asset billing calculations
 * 
 * Handles complex billing logic including rate calculations,
 * usage aggregation, and billing event generation.
 */
final readonly class AssetBillingService
{
    public function __construct(
        private AssetUsageEventRepositoryInterface $usageEventRepository
    ) {
    }

    /**
     * Calculate asset charges for a billing period
     * 
     * @return AssetBillingEvent[]
     */
    public function calculateAssetCharges(
        Asset $asset,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array {
        $provision = $asset->getActiveProvision($startDate);
        
        if (!$provision) {
            return [];
        }

        $usageEvents = $this->usageEventRepository->findByAssetInPeriod(
            $asset->getId(),
            $startDate,
            $endDate
        );

        $rateCards = $provision->getActiveRateCards($startDate);
        $billingEvents = [];

        foreach ($rateCards as $rateCard) {
            $amount = $this->calculateRateAmount($rateCard, $usageEvents, $startDate, $endDate);
            
            if ($amount->getAmount() > 0) {
                $usageEventIds = array_map(
                    fn(AssetUsageEvent $event) => (string) $event->getId(),
                    $usageEvents
                );

                $billingEvents[] = new AssetBillingEvent(
                    asset: $asset,
                    provision: $provision,
                    rateCard: $rateCard,
                    calculatedAmount: $amount,
                    billingPeriodStart: $startDate,
                    billingPeriodEnd: $endDate,
                    calculation: $this->getCalculationDetails($rateCard, $usageEvents),
                    usageEventIds: $usageEventIds
                );
            }
        }

        return $billingEvents;
    }

    /**
     * Calculate amount for a specific rate card
     */
    private function calculateRateAmount(
        AssetRateCard $rateCard,
        array $usageEvents,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): Money {
        return match($rateCard->getRateType()) {
            RateType::FIXED_DAILY => $this->calculateDailyRate($rateCard, $startDate, $endDate),
            RateType::FIXED_MONTHLY => $this->calculateMonthlyRate($rateCard, $startDate, $endDate),
            RateType::PER_MILE => $this->calculateMileageRate($rateCard, $usageEvents),
            RateType::PER_HOUR => $this->calculateHourlyRate($rateCard, $usageEvents),
            RateType::PER_TRIP => $this->calculateTripRate($rateCard, $usageEvents),
            RateType::PERCENTAGE_REVENUE => $this->calculateRevenueShare($rateCard, $usageEvents),
            RateType::COST_PLUS => $this->calculateCostPlus($rateCard, $usageEvents),
            RateType::TIERED => $this->calculateTieredRate($rateCard, $usageEvents),
        };
    }

    private function calculateDailyRate(
        AssetRateCard $rateCard,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): Money {
        $days = $startDate->diff($endDate)->days + 1;
        return $rateCard->getRate()->multiply($days);
    }

    private function calculateMonthlyRate(
        AssetRateCard $rateCard,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): Money {
        $interval = $startDate->diff($endDate);
        $months = $interval->m + ($interval->y * 12);
        
        // Calculate partial month
        if ($interval->d > 0) {
            $months += $interval->d / 30; // Approximate
        }
        
        return $rateCard->getRate()->multiply($months);
    }

    private function calculateMileageRate(AssetRateCard $rateCard, array $usageEvents): Money
    {
        $totalMiles = 0;
        
        foreach ($usageEvents as $event) {
            if ($event->getUnitOfMeasure() === 'miles' || $event->getUnitOfMeasure() === 'mile') {
                $totalMiles += $event->getQuantity();
            }
        }
        
        return $rateCard->calculateAmount($totalMiles);
    }

    private function calculateHourlyRate(AssetRateCard $rateCard, array $usageEvents): Money
    {
        $totalHours = 0;
        
        foreach ($usageEvents as $event) {
            if ($event->getUnitOfMeasure() === 'hours' || $event->getUnitOfMeasure() === 'hour') {
                $totalHours += $event->getQuantity();
            } elseif ($event->getDurationInHours() !== null) {
                $totalHours += $event->getDurationInHours();
            }
        }
        
        return $rateCard->calculateAmount($totalHours);
    }

    private function calculateTripRate(AssetRateCard $rateCard, array $usageEvents): Money
    {
        $tripCount = 0;
        
        foreach ($usageEvents as $event) {
            if ($event->getUnitOfMeasure() === 'trips' || $event->getUnitOfMeasure() === 'trip') {
                $tripCount += $event->getQuantity();
            }
        }
        
        return $rateCard->calculateAmount($tripCount);
    }

    private function calculateRevenueShare(AssetRateCard $rateCard, array $usageEvents): Money
    {
        $totalRevenue = 0;
        
        foreach ($usageEvents as $event) {
            $revenue = $event->getMetadataValue('revenue');
            if ($revenue !== null) {
                $totalRevenue += (float) $revenue;
            }
        }
        
        return $rateCard->calculateAmount($totalRevenue);
    }

    private function calculateCostPlus(AssetRateCard $rateCard, array $usageEvents): Money
    {
        $totalCosts = 0;
        
        foreach ($usageEvents as $event) {
            $cost = $event->getMetadataValue('cost');
            if ($cost !== null) {
                $totalCosts += (float) $cost;
            }
        }
        
        // Apply markup percentage
        $markup = $rateCard->getRate()->getAmount() / 10000; // Rate as percentage
        $totalWithMarkup = $totalCosts * (1 + $markup);
        
        return new Money((int) round($totalWithMarkup * 100), $rateCard->getRate()->getCurrency());
    }

    private function calculateTieredRate(AssetRateCard $rateCard, array $usageEvents): Money
    {
        $totalQuantity = 0;
        
        foreach ($usageEvents as $event) {
            $totalQuantity += $event->getQuantity();
        }
        
        return $rateCard->calculateAmount($totalQuantity);
    }

    /**
     * Get detailed calculation information
     */
    private function getCalculationDetails(AssetRateCard $rateCard, array $usageEvents): array
    {
        $details = [
            'rate_type' => $rateCard->getRateType()->value,
            'rate_amount' => $rateCard->getRate()->getAmount(),
            'rate_currency' => $rateCard->getRate()->getCurrency()->getCode(),
            'usage_events_count' => count($usageEvents),
            'calculation_timestamp' => (new \DateTimeImmutable())->format('c'),
        ];

        switch ($rateCard->getRateType()) {
            case RateType::PER_MILE:
                $totalMiles = array_sum(array_map(
                    fn($event) => $event->getUnitOfMeasure() === 'miles' ? $event->getQuantity() : 0,
                    $usageEvents
                ));
                $details['total_miles'] = $totalMiles;
                $details['unit'] = 'miles';
                $details['quantity'] = $totalMiles;
                break;

            case RateType::PER_HOUR:
                $totalHours = array_sum(array_map(
                    fn($event) => $event->getDurationInHours() ?? 0,
                    $usageEvents
                ));
                $details['total_hours'] = $totalHours;
                $details['unit'] = 'hours';
                $details['quantity'] = $totalHours;
                break;

            case RateType::PER_TRIP:
                $tripCount = count(array_filter(
                    $usageEvents,
                    fn($event) => $event->getUnitOfMeasure() === 'trips'
                ));
                $details['trip_count'] = $tripCount;
                $details['unit'] = 'trips';
                $details['quantity'] = $tripCount;
                break;
        }

        return $details;
    }

    /**
     * Validate billing calculation for accuracy
     */
    public function validateBillingCalculation(AssetBillingEvent $billingEvent): array
    {
        $issues = [];
        
        // Check if provision was active during billing period
        if (!$billingEvent->getProvision()->isActiveAt($billingEvent->getBillingPeriodStart())) {
            $issues[] = 'Provision was not active at the start of billing period';
        }
        
        // Check if rate card was active during billing period
        if (!$billingEvent->getRateCard()->isActiveAt($billingEvent->getBillingPeriodStart())) {
            $issues[] = 'Rate card was not active during billing period';
        }
        
        // Check for minimum charge compliance
        $minimumCharge = $billingEvent->getRateCard()->getMinimumCharge();
        if ($minimumCharge && $billingEvent->getCalculatedAmount()->lessThan($minimumCharge)) {
            $issues[] = 'Calculated amount is below minimum charge requirement';
        }
        
        // Check for maximum charge compliance
        $maximumCharge = $billingEvent->getRateCard()->getMaximumCharge();
        if ($maximumCharge && $billingEvent->getCalculatedAmount()->greaterThan($maximumCharge)) {
            $issues[] = 'Calculated amount exceeds maximum charge limit';
        }
        
        return $issues;
    }
}
