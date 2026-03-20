<?php

namespace App\Libraries\OpenData;

/**
 * Contract for source-specific normalization logic used by the sync service.
 */
interface SourceAdapterInterface
{
    /**
     * @param array<string, mixed> $record
     *
     * @return array<string, string|null>|null
     */
    public function normalize(array $record): ?array;
}
