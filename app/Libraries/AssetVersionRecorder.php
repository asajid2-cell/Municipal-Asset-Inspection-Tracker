<?php

namespace App\Libraries;

use App\Models\AssetModel;
use App\Models\AssetVersionModel;

/**
 * Writes immutable asset snapshots whenever meaningful state changes occur.
 */
class AssetVersionRecorder
{
    public function recordByAssetId(int $assetId, string $versionType, ?int $changedBy, ?string $reason = null): void
    {
        $asset = (new AssetModel())->withDeleted()->find($assetId);

        if (! is_array($asset)) {
            return;
        }

        $this->recordSnapshot($asset, $versionType, $changedBy, $reason);
    }

    /**
     * @param array<string, mixed> $asset
     */
    public function recordSnapshot(array $asset, string $versionType, ?int $changedBy, ?string $reason = null): void
    {
        (new AssetVersionModel())->insert([
            'organization_id' => (int) ($asset['organization_id'] ?? 1),
            'asset_id' => (int) $asset['id'],
            'version_type' => $versionType,
            'snapshot_json' => json_encode($asset, JSON_UNESCAPED_SLASHES),
            'reason' => $reason,
            'changed_by' => $changedBy,
            'created_at' => date('Y-m-d H:i:s'),
        ], false);
    }
}
