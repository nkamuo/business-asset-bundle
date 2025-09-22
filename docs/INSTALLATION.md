# Installation Guide

## Requirements

- PHP 8.2 or higher
- Symfony 7.0 or higher
- Doctrine ORM 3.0 or higher

## Installation

### 1. Install via Composer

```bash
composer require nkamuo/business-asset-bundle
```

### 2. Enable the Bundle

Add the bundle to your `config/bundles.php`:

```php
<?php

return [
    // ... other bundles
    Nkamuo\AssetBundle\NkamuoAssetBundle::class => ['all' => true],
];
```

### 3. Configure Database

Create and run the database migrations:

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

### 4. Configuration (Optional)

Create a configuration file `config/packages/nkamuo_asset.yaml`:

```yaml
nkamuo_asset:
    billing:
        enabled: true
        currency: 'USD'
        billing_cycle: 'monthly'
    
    assets:
        default_status: 'available'
        enable_depreciation: true
    
    usage_tracking:
        enabled: true
        real_time: true
```

## Verification

Test the installation by running:

```bash
php bin/console debug:container | grep nkamuo
```

You should see the bundle services listed.
