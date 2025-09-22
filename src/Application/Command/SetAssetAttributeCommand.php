<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Application\Command;

use Symfony\Component\Uid\Ulid;

/**
 * Command to set a custom attribute value for an asset.
 */
readonly class SetAssetAttributeCommand
{
    public function __construct(
        public Ulid $assetId,
        public string $attributeKey,
        public mixed $value
    ) {}
}
