<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageService
{
    /**
     * Store uploaded image and return the path.
     */
    public function storeImage(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, 'public');
    }

    /**
     * Delete image if it exists.
     */
    public function deleteImage(?string $imagePath): void
    {
        if ($imagePath && Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }
    }

    /**
     * Handle image upload for create/update operations.
     * Returns the image path if uploaded, or null.
     */
    public function handleImageUpload(?UploadedFile $file, string $directory, ?string $oldImagePath = null): ?string
    {
        if ($file) {
            // Delete old image if exists (for updates)
            if ($oldImagePath) {
                $this->deleteImage($oldImagePath);
            }
            
            return $this->storeImage($file, $directory);
        }

        return null;
    }
}
