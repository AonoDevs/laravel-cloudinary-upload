<?php

namespace AonoDevs\LaravelCloudinaryUpload;

use Illuminate\Support\Facades\Validator;

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
            if (request()->hasFile($image)) {
                $validator = Validator::make(['image' => request()->file($image)], [
                    'image' => 'required|image|mimes:jpg,jpeg,png,gif|max:20048',
                ]);
                if ($validator->passes()) {
                    $value = $cloudinaryService->uploadImage($validator->validated()['image']);
                    $model[$image] = (!str_contains($value, 'upload/f_auto,q_auto')) ? str_replace('upload/', 'upload/f_auto,q_auto/', $value) : $value;
                }
            }
        }
        foreach ($model->cloudinary_video ?? [] as $video) {
            if (request()->hasFile($video)) {
                $validator = Validator::make(['video' => request()->file($video)], [
                    'video' => 'required|mimes:avi,mp4,mov,webm|max:200000',
                ]);
                if ($validator->passes()) $model[$video] = $cloudinaryService->uploadVideo($validator->validated()['video']);
            }
        }
    }
}
