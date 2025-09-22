# Usage Examples

## Basic Asset Management

### Creating an Asset

```php
use Nkamuo\AssetBundle\Application\Command\CreateAssetCommand;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetType;
use Nkamuo\AssetBundle\Domain\ValueObject\AssetCategory;
use Money\Money;
use Money\Currency;
use Symfony\Component\Uid\Ulid;

$command = new CreateAssetCommand(
    assetNumber: 'TRUCK-001',
    name: 'Ford F-150 Pickup Truck',
    type: AssetType::VEHICLE,
    category: AssetCategory::TRUCK_TRACTOR,
    ownerId: new Ulid(), // Partner/Company ID
    acquisitionCost: new Money(2500000, new Currency('USD')) // $25,000.00
);

$commandBus->dispatch($command);
```

### Creating Asset Provision

```php
use Nkamuo\AssetBundle\Application\Command\CreateAssetProvisionCommand;
use Nkamuo\AssetBundle\Domain\ValueObject\ProvisionType;

$command = new CreateAssetProvisionCommand(
    assetId: $assetId,
    providerId: new Ulid(),
    recipientId: new Ulid(),
    type: ProvisionType::LEASED,
    startDate: new \DateTimeImmutable('2024-01-01'),
    endDate: new \DateTimeImmutable('2024-12-31')
);

$commandBus->dispatch($command);
```

### Recording Asset Usage

```php
use Nkamuo\AssetBundle\Application\Command\RecordAssetUsageCommand;
use Nkamuo\AssetBundle\Domain\ValueObject\UsageType;

$command = new RecordAssetUsageCommand(
    assetId: $assetId,
    usageType: UsageType::MILEAGE,
    quantity: 150, // 150 miles
    startTime: new \DateTimeImmutable('2024-01-15 08:00:00'),
    endTime: new \DateTimeImmutable('2024-01-15 17:00:00')
);

$commandBus->dispatch($command);
```

## Rate Cards and Billing

### Creating a Rate Card

```php
use Nkamuo\AssetBundle\Application\Command\CreateAssetRateCardCommand;
use Nkamuo\AssetBundle\Domain\ValueObject\RateType;
use Money\Money;
use Money\Currency;

$command = new CreateAssetRateCardCommand(
    provisionId: $provisionId,
    rateType: RateType::PER_MILE,
    rate: new Money(15000, new Currency('USD')), // $1.50 per mile
    effectiveDate: new \DateTimeImmutable('2024-01-01'),
    expiryDate: new \DateTimeImmutable('2024-12-31')
);

$commandBus->dispatch($command);
```

## Query Examples

### Finding Assets

```php
use Nkamuo\AssetBundle\Domain\Repository\AssetRepositoryInterface;

// Inject the repository
public function __construct(
    private AssetRepositoryInterface $assetRepository
) {}

// Find by asset number
$asset = $this->assetRepository->findByAssetNumber('TRUCK-001');

// Find by type
$vehicles = $this->assetRepository->findByType(AssetType::VEHICLE);

// Find by owner
$ownedAssets = $this->assetRepository->findByOwner($ownerId);
```

### Finding Provisions

```php
use Nkamuo\AssetBundle\Domain\Repository\AssetProvisionRepositoryInterface;

// Find active provisions
$activeProvisions = $this->provisionRepository->findActive();

// Find by asset
$assetProvisions = $this->provisionRepository->findByAsset($assetId);

// Find expiring soon
$expiringProvisions = $this->provisionRepository->findExpiringSoon(
    new \DateTimeImmutable('+30 days')
);
```
