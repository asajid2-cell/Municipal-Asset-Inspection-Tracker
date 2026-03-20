<?php

namespace App\Libraries\OpenData;

use InvalidArgumentException;

/**
 * Registers source adapters so normalization logic is pluggable instead of hard-wired.
 */
class SourceAdapterRegistry
{
    /**
     * @param array<string, SourceAdapterInterface> $adapters
     */
    public function __construct(private array $adapters)
    {
    }

    public function adapter(string $sourceKey): SourceAdapterInterface
    {
        if (! isset($this->adapters[$sourceKey])) {
            throw new InvalidArgumentException('No source adapter is registered for ' . $sourceKey . '.');
        }

        return $this->adapters[$sourceKey];
    }
}
