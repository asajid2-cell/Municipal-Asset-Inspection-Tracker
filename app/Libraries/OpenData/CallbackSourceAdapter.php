<?php

namespace App\Libraries\OpenData;

/**
 * Thin adapter wrapper that lets the sync service expose callback-based adapters cleanly.
 */
class CallbackSourceAdapter implements SourceAdapterInterface
{
    /**
     * @param callable(array<string, mixed>): (array<string, string|null>|null) $callback
     */
    public function __construct(private $callback)
    {
    }

    public function normalize(array $record): ?array
    {
        return ($this->callback)($record);
    }
}
