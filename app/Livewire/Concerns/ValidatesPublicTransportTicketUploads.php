<?php

namespace App\Livewire\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Walidacja uploadów biletów: reguła Laravel `file` dotyczy tylko świeżych uploadów;
 * string / śmieci po rehydracji Livewire lub już zapisany `attachment_path` nie powinny wywoływać `file`.
 */
trait ValidatesPublicTransportTicketUploads
{
    protected function isTicketFileUpload(mixed $value): bool
    {
        return $value instanceof TemporaryUploadedFile || $value instanceof UploadedFile;
    }

    /**
     * @param  array<string, mixed>  $row  Koszt biletu (amount, currency, attachment?, attachment_path?)
     * @param  string  $errorKey  np. "ticketCostsByEmployee.5.attachment"
     */
    protected function validateTicketAttachmentUpload(array $row, string $errorKey): void
    {
        $path = $row['attachment_path'] ?? null;
        if (is_string($path) && trim($path) !== '') {
            return;
        }

        $attachment = $row['attachment'] ?? null;

        if ($this->isTicketFileUpload($attachment)) {
            $validator = Validator::make(
                ['attachment' => $attachment],
                ['attachment' => 'file|max:10240'],
                [
                    'attachment.file' => 'Załącznik musi być poprawnym plikiem.',
                    'attachment.max' => 'Załącznik może mieć maksymalnie 10 MB.',
                ]
            );
            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $this->addError($errorKey, $message);
                }
            }

            return;
        }

        if ($attachment) {
            $this->addError($errorKey, 'Załącznik musi być poprawnym plikiem.');
        }
    }
}
