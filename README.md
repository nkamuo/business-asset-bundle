# NkamuoAssetBundle

[![Latest Stable Version](https://poser.pugx.org/nkamuo/business-asset-bundle/v/stable)](https://packagist.org/packages/nkamuo/business-asset-bundle)
[![Total Downloads](https://poser.pugx.org/nkamuo/business-asset-bundle/downloads)](https://packagist.org/packages/nkamuo/business-asset-bundle)
[![License](https://poser.pugx.org/nkamuo/business-asset-bundle/license)](https://packagist.org/packages/nkamuo/business-asset-bundle)
[![PHP Version Require](https://poser.pugx.org/nkamuo/business-asset-bundle/require/php)](https://packagist.org/packages/nkamuo/business-asset-bundle)

A comprehensive Symfony bundle for asset management in transportation and logistics industries, implementing Clean Architecture principles with CQRS pattern and Ecotone framework integration.

## Features

🚛 **Comprehensive Asset Management**
- Multi-category asset support (vehicles, equipment, infrastructure)
- Complete lifecycle tracking from acquisition to disposal
- Rich metadata and specification management

💰 **Advanced Billing & Provisioning**
- Flexible rate card system (fixed, usage-based, percentage, tiered)
- Complex provisioning arrangements (owned, leased, rented, subcontracted)
- Automated billing event generation

📊 **Usage Tracking**
- Real-time usage event recording
- Multi-dimensional usage metrics
- Performance analytics and reporting

🏗️ **Clean Architecture**
- Domain-driven design implementation
- CQRS pattern with command/query separation
- Repository pattern with Doctrine integration

⚡ **Modern Technology Stack**
- Symfony 7.3+ compatibility
- PHP 8.2+ requirement
- Ecotone framework for CQRS
- Symfony Messenger integration

## Installation

Install the bundle via Composer:

```bash
composer require nkamuo/business-asset-bundle
```

### Enable the Bundle

Add the bundle to your `config/bundles.php`:

```php
<?php

return [
    // ... other bundles
    Nkamuo\AssetBundle\NkamuoAssetBundle::class => ['all' => true],
];
```

### Configure Database

Create and run the database migrations:

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

## Quick Start

### 1. Create an Asset

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

### 2. Create Asset Provision

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

### 3. Record Asset Usage

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

## Configuration

### Basic Configuration

```yaml
# config/packages/nkamuo_asset.yaml
nkamuo_asset:
    billing:
        enabled: true
        currency: 'USD'
        billing_cycle: 'monthly'
        auto_generate_events: true
    
    assets:
        default_status: 'available'
        enable_depreciation: true
        depreciation_method: 'straight_line'
    
    provisions:
        auto_activate: false
        require_approval: true
        default_terms: []
    
    usage_tracking:
        enabled: true
        real_time: true
        batch_size: 100
```

## Testing

Run the test suite:

```bash
# Run all tests
composer test

# Run tests with coverage
composer test-coverage

# Run specific test suite
vendor/bin/phpunit tests/Unit/Domain/Entity/
```

## Code Quality

The bundle includes comprehensive code quality tools:

```bash
# Check coding standards
composer cs-check

# Fix coding standards
composer cs-fix

# Run static analysis
composer phpstan
composer psalm

# Run all quality checks
composer quality
```

## Requirements

- PHP 8.2 or higher
- Symfony 7.0 or higher
- Doctrine ORM 3.0 or higher

## License

This bundle is released under the MIT License. See [LICENSE](LICENSE) for details.

## Changelog

### v0.0.1 (2025-01-20)

**Initial Release**

- ✅ Complete asset management system
- ✅ CQRS implementation with command handlers
- ✅ Flexible billing and rate card system
- ✅ Asset provisioning and lifecycle management
- ✅ Usage tracking and event recording
- ✅ Clean Architecture with DDD principles
- ✅ Comprehensive test suite (25 tests, 101 assertions)
- ✅ Full Doctrine ORM integration
- ✅ Symfony 7.3 compatibility

---

Built with ❤️ for the transportation and logistics industry.
