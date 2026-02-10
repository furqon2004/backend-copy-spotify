<?php

namespace App\Services;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class CloudinaryService
{
    /**
     * Upload an image to Cloudinary.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @param  string  $folder
     * @return string  Secure URL of the uploaded image
     */
    public function uploadImage($file, string $folder = 'images'): string
    {
        $result = Cloudinary::upload($file->getRealPath(), [
            'folder' => "spotify/{$folder}",
            'resource_type' => 'image',
            'transformation' => [
                'quality' => 'auto',
                'fetch_format' => 'auto',
            ],
        ]);

        return $result->getSecurePath();
    }

    /**
     * Upload an audio file to Cloudinary.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @param  string  $folder
     * @return string  Secure URL of the uploaded audio
     */
    public function uploadAudio($file, string $folder = 'audio'): string
    {
        $result = Cloudinary::upload($file->getRealPath(), [
            'folder' => "spotify/{$folder}",
            'resource_type' => 'video', // Cloudinary uses 'video' for audio files
        ]);

        return $result->getSecurePath();
    }

    /**
     * Delete a file from Cloudinary by its public ID.
     *
     * @param  string  $publicId
     * @param  string  $resourceType
     * @return mixed
     */
    public function delete(string $publicId, string $resourceType = 'image')
    {
        return Cloudinary::destroy($publicId, [
            'resource_type' => $resourceType,
        ]);
    }

    /**
     * Extract Cloudinary public ID from a full URL.
     *
     * @param  string  $url
     * @return string|null
     */
    public function getPublicIdFromUrl(string $url): ?string
    {
        $pattern = '/upload\/(?:v\d+\/)?(.+)\.\w+$/';

        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
