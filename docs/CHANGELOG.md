# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Development and contribution documentation
- Code quality tools configuration

## [0.0.1] - 2025-01-20

### Added
- Initial release of NkamuoAssetBundle
- Complete asset management system with Clean Architecture
- CQRS pattern implementation with command handlers
- Domain entities: Asset, AssetProvision, AssetRateCard, AssetUsageEvent, AssetBillingEvent
- Value objects for type-safe operations: AssetType, AssetCategory, AssetStatus, ProvisionType, ProvisionStatus, RateType, UsageType, BillingEventStatus
- Application commands and command handlers for asset operations
- Repository interfaces with Doctrine ORM implementation
- Domain and application services for business logic
- Comprehensive test suite with 25 tests and 101 assertions
- Symfony 7.3 compatibility
- PHP 8.2+ requirement
- Money library integration for financial calculations
- Symfony UID integration for entity identifiers
- Flexible billing system with multiple rate types:
  - Fixed rates
  - Per-mile rates
  - Per-hour rates
  - Per-load rates
  - Percentage-based rates
  - Tiered pricing
- Asset provisioning system supporting:
  - Owned assets
  - Leased assets
  - Rented assets
  - Subcontracted assets
  - Shared assets
  - Borrowed assets
  - Consignment assets
- Usage tracking and event recording
- Bundle configuration with dependency injection
- PHPUnit testing framework setup

### Features
- **Asset Management**: Complete lifecycle management for transportation and logistics assets
- **Billing & Provisioning**: Advanced billing system with flexible rate structures
- **Usage Tracking**: Real-time usage event recording and analytics
- **Clean Architecture**: Domain-driven design with clear separation of concerns
- **CQRS Pattern**: Command Query Responsibility Segregation for scalable operations
- **Type Safety**: Comprehensive use of PHP 8.2+ features and type declarations

### Documentation
- Comprehensive README with installation and usage examples
- API documentation for all public interfaces
- Architecture overview and design principles
- Contributing guidelines
- MIT license

[Unreleased]: https://github.com/nkamuo/business-asset-bundle/compare/v0.0.1...HEAD
[0.0.1]: https://github.com/nkamuo/business-asset-bundle/releases/tag/v0.0.1
