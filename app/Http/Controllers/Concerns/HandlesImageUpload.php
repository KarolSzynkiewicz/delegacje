<?php

namespace App\Http\Controllers\Concerns;

use App\Services\ImageService;
use Illuminate\Http\UploadedFile;

trait HandlesImageUpload
{
    /**
     * Handle image upload for create operations.
     */
    protected function handleImageUploadForCreate(UploadedFile $file, string $folder): string
    {
        return app(ImageService::class)->storeImage($file, $folder);
    }

    /**
     * Handle image upload for update operations.
     */
    protected function handleImageUploadForUpdate(UploadedFile $file, string $folder, ?string $oldImagePath): string
    {
        return app(ImageService::class)->handleImageUpload($file, $folder, $oldImagePath);
    }

    /**
     * Process validated data and handle image upload.
     * 
     * @param array $validated
     * @param \Illuminate\Http\Request $request
     * @param string $folder
     * @param string|null $oldImagePath
     * @return array
     */
    protected function processImageUpload(array $validated, $request, string $folder, ?string $oldImagePath = null): array
    {
        // Remove image from validated data (it's not a database column)
        unset($validated['image']);

        // Handle image upload
        if ($request->hasFile('image')) {
            if ($oldImagePath) {
                // Update operation
                $validated['image_path'] = $this->handleImageUploadForUpdate(
                    $request->file('image'),
                    $folder,
                    $oldImagePath
                );
            } else {
                // Create operation
                $validated['image_path'] = $this->handleImageUploadForCreate(
                    $request->file('image'),
                    $folder
                );
            }
        }

        return $validated;
    }
}
