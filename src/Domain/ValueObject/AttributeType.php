<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Domain\ValueObject;

/**
 * Attribute data types supported by the system.
 */
enum AttributeType: string
{
    case STRING = 'string';
    case INTEGER = 'integer';
    case FLOAT = 'float';
    case BOOLEAN = 'boolean';
    case DATE = 'date';
    case DATETIME = 'datetime';
    case ENUM = 'enum';
    case JSON = 'json';
    case FILE = 'file';
    case URL = 'url';
    case EMAIL = 'email';
    case PHONE = 'phone';
    case CURRENCY = 'currency';
    case PERCENTAGE = 'percentage';
    case COORDINATE = 'coordinate';

    /**
     * Get display name for the attribute type.
     */
    public function getDisplayName(): string
    {
        return match($this) {
            self::STRING => 'Text',
            self::INTEGER => 'Whole Number',
            self::FLOAT => 'Decimal Number',
            self::BOOLEAN => 'Yes/No',
            self::DATE => 'Date',
            self::DATETIME => 'Date & Time',
            self::ENUM => 'Selection List',
            self::JSON => 'Structured Data',
            self::FILE => 'File Upload',
            self::URL => 'Web Address',
            self::EMAIL => 'Email Address',
            self::PHONE => 'Phone Number',
            self::CURRENCY => 'Money Amount',
            self::PERCENTAGE => 'Percentage',
            self::COORDINATE => 'GPS Coordinates',
        };
    }

    /**
     * Check if this type requires validation rules.
     */
    public function requiresValidation(): bool
    {
        return match($this) {
            self::ENUM, self::JSON, self::FILE, self::URL, 
            self::EMAIL, self::PHONE, self::CURRENCY, 
            self::PERCENTAGE, self::COORDINATE => true,
            default => false,
        };
    }

    /**
     * Get default validation rules for the type.
     */
    public function getDefaultValidationRules(): array
    {
        return match($this) {
            self::EMAIL => ['format' => 'email'],
            self::URL => ['format' => 'url'],
            self::PHONE => ['pattern' => '/^[\+]?[0-9\s\-\(\)]+$/'],
            self::PERCENTAGE => ['min' => 0, 'max' => 100],
            self::COORDINATE => ['latitude' => [-90, 90], 'longitude' => [-180, 180]],
            default => [],
        };
    }

    /**
     * Check if type supports multiple values.
     */
    public function supportsMultipleValues(): bool
    {
        return match($this) {
            self::ENUM, self::FILE, self::JSON => true,
            default => false,
        };
    }
}
