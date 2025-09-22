# NkamuoAssetBundle

A comprehensive Symfony 7.3 bundle for asset management following Clean Architecture principles with CQRS pattern implementation using Ecotone framework and Symfony Messenger.

## Features

- **Clean Architecture**: Domain-driven design with clear separation of concerns
- **CQRS Pattern**: Command Query Responsibility Segregation using Ecotone framework
- **Asset Management**: Complete lifecycle management for various asset types
- **Flexible Billing**: Multi-rate billing system with usage tracking
- **Partnership Support**: Asset sharing and provisioning between partners
- **Real-time Events**: Event-driven architecture for asset state changes

## Installation

```bash
composer require nkamuo/asset-bundle
```

## Configuration

Enable the bundle in your `config/bundles.php`:

```php
<?php

return [
    // ... other bundles
    Nkamuo\AssetBundle\NkamuoAssetBundle::class => ['all' => true],
];
```

## Asset Types Supported

- **Vehicles**: Trucks, trailers, containers, chassis
- **Human Resources**: Drivers, operators, technicians
- **Equipment**: Forklifts, cranes, loading equipment, GPS devices
- **Infrastructure**: Warehouses, yards, docks, fuel stations
- **Technology**: Software licenses, tracking devices, communication equipment

## Architecture Overview

### Domain Layer
- Asset entities and value objects
- Business rules and invariants
- Domain events and services

### Application Layer
- Commands and queries (CQRS)
- Application services
- Event handlers

### Infrastructure Layer
- Doctrine ORM repositories
- Symfony Messenger integration
- Ecotone framework configuration

## Usage

### Basic Asset Creation

```php
use Nkamuo\AssetBundle\Application\Command\CreateAssetCommand;

$command = new CreateAssetCommand(
    assetNumber: 'TRUCK-001',
    name: 'Ford F-150 Pickup',
    type: AssetType::VEHICLE,
    category: AssetCategory::TRUCK_TRACTOR,
    ownerId: $partnerId,
    specifications: ['engine' => 'V8', 'capacity' => '2000kg']
);

$commandBus->dispatch($command);
```

### Asset Provisioning

```php
use Nkamuo\AssetBundle\Application\Command\CreateAssetProvisionCommand;

$command = new CreateAssetProvisionCommand(
    assetId: $assetId,
    providerId: $providerId,
    recipientId: $recipientId,
    type: ProvisionType::LEASED,
    startDate: new \DateTimeImmutable(),
    terms: ['monthly_rate' => 2500, 'mileage_rate' => 0.15]
);

$commandBus->dispatch($command);
```

## License

MIT License. See [LICENSE](LICENSE) file for details.

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on how to contribute to this project.
