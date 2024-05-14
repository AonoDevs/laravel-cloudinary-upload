<?php

namespace AonoDevs\LaravelCloudinaryUpload;


use Cloudinary\Api\Exception\ApiError;
use Cloudinary\Cloudinary;
use Exception;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Facades\Image;
use Spatie\ImageOptimizer\OptimizerChainFactory;

class LaravelCloudinaryUpload
{
    public ?Cloudinary $cloudinary = null;

    /**
     * Cloudinary constructor.
     */
    public function __construct()
    {
        $this->cloudinary = new Cloudinary(config('cloudinary-upload.url'));
    }

    /**
     * Upload file and return secure_url
     * @param UploadedFile $real_path
     * @return string
     * @throws ApiError
     */
    private function uploadAndGetUrl(UploadedFile $real_path): string
    {
        $type = "auto";
        if ($real_path->getMimeType() != 'image/svg+xml') $type = "image";
        $optimizerChain = OptimizerChainFactory::create();
        $optimizerChain->optimize($real_path->getRealPath());
        return $this->cloudinary->uploadApi()->upload($real_path->getRealPath(), [
            'resource_type' => $type
        ])['secure_url'];
    }

    /**
     * Upload image file and return secure_url
     * @param UploadedFile $real_path
     * @return string
     * @throws ApiError
     */
    private function uploadImageAndGetUrl(UploadedFile $real_path): string
    {
        return $this->uploadAndGetUrl($this->reduceWidth($real_path));
    }

    /**
     * @throws ApiError
     * @throws Exception
     */
    public function uploadImage($image): string
    {
        if ($image instanceof UploadedFile || str_starts_with($image, 'data:')){
            if (str_starts_with($image, 'data:')) {
                $image = Helper::fromBase64($image);
            }
            if ($image->getClientOriginalExtension() === 'pdf'){
                return $this->uploadAndGetUrl($image);
            }else{
                return $this->uploadImageAndGetUrl($image);
            }
        }
        return $this->fakerAndCloudinaryImage($image);
    }

    /**
     * @throws ApiError
     * @throws Exception
     */
    public function uploadVideo($video): string
    {
        if ($video instanceof UploadedFile){
            return $this->uploadAndGetUrl($video);
        }
        return $this->fakerAndCloudinaryImage($video);
    }

    /** Accept faker and cloudinary image or throw exception
     * @throws ApiError
     * @throws Exception
     */
    public function fakerAndCloudinaryImage($file): string
    {
        if (str_starts_with($file, 'https://via.placeholder.com/') || str_starts_with($file, 'https://res.cloudinary.com/')){
            return $file;
        }
        throw new Exception('Cloudinary Upload : Type is not supported');
    }

    private function reduceWidth(UploadedFile $real_path): UploadedFile
    {
        if ($real_path->getMimeType() != 'image/svg+xml') {
            $image = Image::make($real_path);
            if ($image->width() > 1024) {
                $image->widen(1024, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $image->save($real_path->getRealPath(), 80);
            }
        }
        return $real_path;
    }

    public function delete(String $publicID, bool $isVideo = false){
        if ($publicID){
            if ($isVideo) return $this->cloudinary->uploadApi()->destroy($publicID, ["resource_type" => "video"]);
            return $this->cloudinary->uploadApi()->destroy($publicID);
        }
        return null;
    }

    public function download(string $name, $public_ids = []){
        return $this->cloudinary->uploadApi()->downloadZipUrl([
            'target_public_id' => $name.'.zip',
            'public_ids' => $public_ids
        ]);
    }
}
