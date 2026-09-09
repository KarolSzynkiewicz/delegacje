<?php

namespace Tests\Unit;

use App\Models\Attachment;
use PHPUnit\Framework\TestCase;

class AttachmentPreviewTest extends TestCase
{
    public function test_images_pdf_and_txt_are_previewable(): void
    {
        $this->assertTrue($this->attachment('photo.png')->isImage());
        $this->assertTrue($this->attachment('scan.PDF')->isPdf());
        $this->assertTrue($this->attachment('notes.txt')->isText());
        $this->assertTrue($this->attachment('photo.webp')->isPreviewable());
        $this->assertFalse($this->attachment('pack.zip')->isPreviewable());
    }

    public function test_preview_kind_and_mime_follow_the_extension(): void
    {
        $this->assertSame('image', $this->attachment('a.jpg')->previewKind());
        $this->assertSame('pdf', $this->attachment('a.pdf')->previewKind());
        $this->assertSame('text', $this->attachment('a.txt')->previewKind());
        $this->assertSame('file', $this->attachment('a.docx')->previewKind());
        $this->assertSame('image/png', $this->attachment('a.png')->mimeType());
        $this->assertSame('application/pdf', $this->attachment('a.pdf')->mimeType());
        $this->assertSame('application/octet-stream', $this->attachment('a.zip')->mimeType());
    }

    private function attachment(string $name): Attachment
    {
        return new Attachment([
            'original_name' => $name,
            'file_path' => 'attachments/comments/'.$name,
        ]);
    }
}
