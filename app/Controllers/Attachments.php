<?php

namespace App\Controllers;

use App\Models\AttachmentModel;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Streams attachment downloads through the app so writable files stay private.
 */
class Attachments extends BaseController
{
    public function download(int $attachmentId)
    {
        $attachment = (new AttachmentModel())->findDownloadable($attachmentId);

        if ($attachment === null) {
            throw PageNotFoundException::forPageNotFound('Attachment not found.');
        }

        $path = WRITEPATH . 'uploads/' . $attachment['storage_path'];

        if (! is_file($path)) {
            throw PageNotFoundException::forPageNotFound('Stored attachment file not found.');
        }

        return $this->response->download($path, null)->setFileName((string) $attachment['original_name']);
    }
}
