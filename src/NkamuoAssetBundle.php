<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * NkamuoAssetBundle - A comprehensive Symfony bundle for asset management.
 *
 * Features:
 * - Clean Architecture with CQRS pattern
 * - Ecotone framework integration
 * - Multi-asset type support
 * - Flexible billing and provisioning
 * - Event-driven architecture
 */
class NkamuoAssetBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
