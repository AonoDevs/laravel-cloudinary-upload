<?php

namespace AonoDevs\LaravelCloudinaryUpload;

use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

class Helper{
    public static function fromBase64(string $base64File): UploadedFile
    {
        if (!preg_match('/^data:(.*?);base64,/', $base64File, $matches)) {
            throw new \InvalidArgumentException('Base64 string is not valid');
        }

        $mimeType = $matches[1];
        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'application/pdf' => 'pdf',
            default => null,
        };

        if (!$extension) {
            throw new \RuntimeException("Unsupported MIME type: $mimeType");
        }

        $fileData = base64_decode(Arr::last(explode(',', $base64File)));

        $tempFile = tmpfile();
        $tempFilePath = stream_get_meta_data($tempFile)['uri'];

        file_put_contents($tempFilePath, $fileData);

        $tempFileObject = new File($tempFilePath);
        $filename = 'upload.' . $extension;

        $file = new UploadedFile(
            $tempFileObject->getPathname(),
            $filename,
            $mimeType,
            0,
            true
        );

        app()->terminating(function () use ($tempFile) {
            fclose($tempFile);
        });

        return $file;
    }
}
