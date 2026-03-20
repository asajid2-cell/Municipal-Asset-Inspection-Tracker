<?php

namespace App\Controllers;

use App\Libraries\MobileSyncService;
use App\Models\OfflineSyncConflictModel;
use App\Models\OfflineSyncPacketModel;
use App\Models\UserModel;

/**
 * Offline field packet and sync-conflict screens for mobile-style workflows.
 */
class MobileOps extends BaseController
{
    public function index(): string
    {
        $organizationId = $this->currentOrganizationId();

        return view('mobile_ops/index', [
            'pageTitle' => 'Mobile Field Ops',
            'activeNav' => 'mobile',
            'packets' => (new OfflineSyncPacketModel())->recentForOrganization($organizationId, 20),
            'conflicts' => (new OfflineSyncConflictModel())->openForOrganization($organizationId, 20),
            'staff' => (new UserModel())->inspectionStaff(),
            'errors' => session()->getFlashdata('errors') ?? [],
        ]);
    }

    public function createPacket()
    {
        $assignedUserId = (int) $this->request->getPost('assigned_user_id');
        $packetName = trim((string) $this->request->getPost('packet_name'));

        if ($assignedUserId < 1 || $packetName === '') {
            return redirect()->to(site_url('mobile-ops'))->with('warning', 'Packet name and assigned user are required.');
        }

        (new MobileSyncService())->createInspectionPacket(
            $this->currentOrganizationId(),
            $assignedUserId,
            $packetName,
            ['organization_id' => $this->currentOrganizationId()]
        );

        return redirect()->to(site_url('mobile-ops'))->with('success', 'Offline packet prepared.');
    }

    public function recordConflict()
    {
        $packetId = (int) $this->request->getPost('packet_id');
        $assetId = trim((string) $this->request->getPost('asset_id')) === '' ? null : (int) $this->request->getPost('asset_id');
        $conflictType = trim((string) $this->request->getPost('conflict_type'));
        $localPayload = trim((string) $this->request->getPost('local_payload_json'));
        $serverPayload = trim((string) $this->request->getPost('server_payload_json'));

        if ($packetId < 1 || $conflictType === '' || $localPayload === '') {
            return redirect()->to(site_url('mobile-ops'))->with('warning', 'Conflict type and local payload are required.');
        }

        (new MobileSyncService())->recordConflict(
            $this->currentOrganizationId(),
            $packetId,
            $assetId,
            $conflictType,
            json_decode($localPayload, true) ?: ['raw' => $localPayload],
            $serverPayload === '' ? null : (json_decode($serverPayload, true) ?: ['raw' => $serverPayload])
        );

        return redirect()->to(site_url('mobile-ops'))->with('success', 'Sync conflict captured.');
    }
}
