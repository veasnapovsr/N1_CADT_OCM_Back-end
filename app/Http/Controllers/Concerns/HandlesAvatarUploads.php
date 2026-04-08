<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait HandlesAvatarUploads
{
    protected function storeAvatarUpload(Request $request, $userId)
    {
        $file = $this->extractAvatarUpload($request);
        if ($file instanceof UploadedFile) {
            return Storage::disk('public')->putFile('avatars/' . $userId, $file);
        }

        foreach (['image', 'photo', 'avatar', 'avatar_url'] as $field) {
            $storedPath = $this->storeAvatarFromDataUrl($request->input($field), $userId);
            if ($storedPath !== null) {
                return $storedPath;
            }
        }

        return null;
    }

    protected function extractAvatarUpload(Request $request)
    {
        foreach (['files', 'file', 'image', 'photo', 'avatar', 'avatar_url'] as $field) {
            $file = $this->extractAvatarFileCandidate($request->file($field));
            if ($file instanceof UploadedFile) {
                return $file;
            }
        }

        return $this->extractAvatarFileCandidate($request->allFiles());
    }

    private function extractAvatarFileCandidate($candidate)
    {
        if ($candidate instanceof UploadedFile) {
            return $candidate;
        }

        if (is_array($candidate)) {
            foreach ($candidate as $item) {
                $file = $this->extractAvatarFileCandidate($item);
                if ($file instanceof UploadedFile) {
                    return $file;
                }
            }
        }

        return null;
    }

    private function storeAvatarFromDataUrl($value, $userId)
    {
        if (!is_string($value)) {
            return null;
        }

        $image = trim($value);
        if ($image === '') {
            return null;
        }

        if (!preg_match('/^data:image\/(png|jpe?g|gif|webp|bmp);base64,/i', $image, $matches)) {
            return null;
        }

        $binary = base64_decode(substr($image, strpos($image, ',') + 1), true);
        if ($binary === false) {
            return null;
        }

        $extension = strtolower($matches[1]);
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        $path = 'avatars/' . $userId . '/avatar_' . uniqid('', true) . '.' . $extension;

        return Storage::disk('public')->put($path, $binary) ? $path : null;
    }
}