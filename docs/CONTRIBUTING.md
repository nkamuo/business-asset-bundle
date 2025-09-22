# Contributing to NkamuoAssetBundle

Thank you for your interest in contributing to NkamuoAssetBundle! This document provides guidelines and information for contributors.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Workflow](#development-workflow)
- [Coding Standards](#coding-standards)
- [Testing](#testing)
- [Pull Request Process](#pull-request-process)
- [Issue Reporting](#issue-reporting)

## Code of Conduct

This project adheres to a [Contributor Covenant](https://www.contributor-covenant.org/) code of conduct. By participating, you are expected to uphold this code.

## Getting Started

### Prerequisites

- PHP 8.2 or higher
- Composer
- Git
- A Symfony application for testing (optional)

### Setting Up Development Environment

1. Fork the repository on GitHub
2. Clone your fork locally:
   ```bash
   git clone https://github.com/YOUR_USERNAME/asset-bundle.git
   cd asset-bundle
   ```

3. Install dependencies:
   ```bash
   composer install
   ```

4. Run tests to ensure everything works:
   ```bash
   composer test
   ```

## Development Workflow

### Branching Strategy

- `main` - Stable release branch
- `develop` - Development integration branch
- `feature/feature-name` - Feature development branches
- `bugfix/issue-description` - Bug fix branches
- `hotfix/critical-fix` - Critical production fixes

### Making Changes

1. Create a feature branch from `develop`:
   ```bash
   git checkout develop
   git pull origin develop
   git checkout -b feature/your-feature-name
   ```

2. Make your changes following our [coding standards](#coding-standards)

3. Add or update tests for your changes

4. Run the quality checks:
   ```bash
   composer quality
   ```

5. Commit your changes:
   ```bash
   git add .
   git commit -m "feat: add your feature description"
   ```

6. Push to your fork:
   ```bash
   git push origin feature/your-feature-name
   ```

## Coding Standards

### PHP Standards

We follow PSR-12 coding standards with some additional rules:

- Use strict types: `declare(strict_types=1);`
- Use typed properties and return types
- Use named parameters for better readability
- Follow Domain-Driven Design principles

### Code Style

We use PHP-CS-Fixer to maintain consistent code style:

```bash
# Check style issues
composer cs-check

# Fix style issues automatically
composer cs-fix
```

### Static Analysis

We use PHPStan and Psalm for static analysis:

```bash
# Run PHPStan
composer phpstan

# Run Psalm
composer psalm
```

### Architecture Guidelines

#### Clean Architecture

- **Domain Layer**: Pure business logic, no framework dependencies
- **Application Layer**: Use cases and application services
- **Infrastructure Layer**: Framework integrations and external services

#### CQRS Pattern

- Separate commands (write operations) from queries (read operations)
- Use command handlers for business logic execution
- Implement query handlers for data retrieval

#### Domain-Driven Design

- Use value objects for primitive obsession prevention
- Implement rich domain models with behavior
- Use domain events for side effects
- Keep entities focused on their core responsibilities

## Testing

### Test Structure

```
tests/
├── Unit/           # Unit tests for isolated components
├── Integration/    # Integration tests for component interaction
└── Functional/     # End-to-end functional tests
```

### Writing Tests

- Write tests before or alongside your code (TDD/BDD)
- Aim for high test coverage (>90%)
- Use descriptive test method names
- Follow the Arrange-Act-Assert pattern

### Running Tests

```bash
# Run all tests
composer test

# Run tests with coverage
composer test-coverage

# Run specific test class
vendor/bin/phpunit tests/Unit/Domain/Entity/AssetTest.php

# Run tests with filter
vendor/bin/phpunit --filter testAssetCreation
```

## Pull Request Process

### Before Submitting

1. Ensure all tests pass: `composer test`
2. Run quality checks: `composer quality`
3. Update documentation if needed
4. Add/update CHANGELOG.md entry

### PR Guidelines

1. **Title**: Use conventional commit format
   - `feat: add new feature`
   - `fix: resolve bug in asset creation`
   - `docs: update README installation guide`
   - `refactor: improve asset repository performance`

2. **Description**: Include:
   - What changes were made and why
   - Screenshots for UI changes
   - Breaking changes (if any)
   - Related issue numbers

3. **Size**: Keep PRs focused and reasonably sized
4. **Tests**: Include tests for new functionality
5. **Documentation**: Update relevant documentation

### Review Process

1. At least one maintainer review required
2. All CI checks must pass
3. No merge conflicts
4. Up-to-date with target branch

## Issue Reporting

### Bug Reports

Include the following information:

- **Environment**: PHP version, Symfony version, bundle version
- **Steps to Reproduce**: Clear, numbered steps
- **Expected Behavior**: What should happen
- **Actual Behavior**: What actually happens
- **Error Messages**: Complete error messages and stack traces
- **Code Samples**: Minimal reproducing code

### Feature Requests

Include the following information:

- **Use Case**: Describe the business need
- **Proposed Solution**: Your suggested implementation
- **Alternatives**: Other solutions you've considered
- **Additional Context**: Screenshots, mockups, etc.

### Security Issues

**Do not** report security issues publicly. Instead:

1. Email security issues to: security@nkamuo.com
2. Include a detailed description
3. Include steps to reproduce
4. We'll respond within 48 hours

## Development Resources

### Documentation

- [Symfony Documentation](https://symfony.com/doc)
- [Doctrine ORM Documentation](https://www.doctrine-project.org/projects/orm.html)
- [Clean Architecture](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)
- [Domain-Driven Design](https://www.domainlanguage.com/ddd/)

### Tools

- [PHPStorm](https://www.jetbrains.com/phpstorm/) - IDE with excellent Symfony support
- [VSCode](https://code.visualstudio.com/) - Lightweight editor with PHP extensions
- [Xdebug](https://xdebug.org/) - Debugging and profiling tool

## Recognition

Contributors will be recognized in:

- CHANGELOG.md for their contributions
- README.md contributors section
- GitHub contributors graph

## Questions?

- Create a [Discussion](https://github.com/nkamuo/business-asset-bundle/discussions)
- Join our [Community Chat](https://discord.gg/nkamuo-asset-bundle)
- Email us: contributors@nkamuo.com

Thank you for contributing to NkamuoAssetBundle! 🚛💰📊
