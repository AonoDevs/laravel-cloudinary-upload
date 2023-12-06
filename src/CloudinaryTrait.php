<?php

namespace AonoDevs\LaravelCloudinaryUpload;

trait CloudinaryTrait {
    public static function bootCloudinaryTrait()
    {
        static::creating(fn ($model) => self::uploadCloudinaryFiles($model));

        static::updating(fn ($model) => self::uploadCloudinaryFiles($model));

        static::updated(function ($model) {
            $oldModel = $model->getOriginal();
            $newModel = $model->getAttributes();
            $cloudinaryService = new LaravelCloudinaryUpload();
            foreach ($model->cloudinary_image ?? [] as $image) {
                if ($oldModel[$image] && $oldModel[$image] != $newModel[$image]) {
                    $cloudinaryService->delete(explode('.', array_reverse(explode('/', $oldModel[$image]))[0])[0]);
                }
            }
            foreach ($model->cloudinary_video ?? [] as $video) {
                if ($oldModel[$video] && $oldModel[$video] != $newModel[$video]) {
                    $cloudinaryService->delete(explode('.', array_reverse(explode('/', $oldModel[$video]))[0])[0], true);
                }
            }
        });

        static::deleted(function ($model) {
            $cloudinaryService = new LaravelCloudinaryUpload();
            foreach ($model->cloudinary_image ?? [] as $image) {
                $cloudinaryService->delete(explode('.', array_reverse(explode('/', $model[$image]))[0])[0]);
            }
            foreach ($model->cloudinary_video ?? [] as $video) {
                $cloudinaryService->delete(explode('.', array_reverse(explode('/', $model[$video]))[0])[0], true);
            }
        });
    }

    protected static function uploadCloudinaryFiles($model)
    {
        $cloudinaryService = new LaravelCloudinaryUpload();
        foreach ($model->cloudinary_image ?? [] as $image) {
            if ($model[$image]) {
                $value = $cloudinaryService->uploadImage($model[$image]);
                $model[$image] = (!str_contains($value, 'upload/f_auto,q_auto')) ? str_replace('upload/', 'upload/f_auto,q_auto/', $value) : $value;
            }
        }
        foreach ($model->cloudinary_video ?? [] as $video) {
            if ($model[$video]) $model[$video] = $cloudinaryService->uploadVideo($model[$video]);
        }
    }
}
