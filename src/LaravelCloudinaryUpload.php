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
     * @param mixed $image (UploadedFile or Intervention\Image\Image)
     * @return string
     * @throws ApiError
     */
    private function uploadAndGetUrl($image): string
    {
        $type = "auto";

        // Si l'image est une instance Intervention\Image
        if ($image instanceof \Intervention\Image\Image) {
            // Sauvegarder l'image manipulée dans un fichier temporaire
            $imagePath = tempnam(sys_get_temp_dir(), 'img_') . '.jpg';
            $image->save($imagePath);

            // Optimiser l'image avant de la télécharger
            $optimizerChain = OptimizerChainFactory::create();
            $optimizerChain->optimize($imagePath);

            return $this->cloudinary->uploadApi()->upload($imagePath, [
                'resource_type' => 'image'
            ])['secure_url'];
        }

        // Si c'est un UploadedFile
        if ($image instanceof UploadedFile) {
            if ($image->getMimeType() === 'image/svg+xml') {
                $type = "image";
            }
            $optimizerChain = OptimizerChainFactory::create();
            $optimizerChain->optimize($image->getRealPath());

            return $this->cloudinary->uploadApi()->upload($image->getRealPath(), [
                'resource_type' => $type
            ])['secure_url'];
        }

        // Si ce n'est ni l'un ni l'autre, retourner une erreur
        throw new Exception('Invalid image type');
    }

    /**
     * Upload image file and return secure_url
     * @param mixed $image (UploadedFile or Intervention\Image\Image)
     * @return string
     * @throws ApiError
     */
    private function uploadImageAndGetUrl($image): string
    {
        return $this->uploadAndGetUrl($this->reduceWidth($image));
    }

    /**
     * @throws ApiError
     * @throws Exception
     */
    public function uploadImage($image): string
    {
        if ($image instanceof UploadedFile || $image instanceof \Intervention\Image\Image || str_starts_with($image, 'data:')){
            if (str_starts_with($image, 'data:')) {
                $image = Helper::fromBase64($image);
            }
            if ($image instanceof UploadedFile && $image->getClientOriginalExtension() === 'pdf'){
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

    private function reduceWidth($image)
    {
        // Si c'est une instance Intervention\Image
        if ($image instanceof \Intervention\Image\Image) {
            if ($image->width() > 1024) {
                $image->widen(1024, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }
        }

        // Si c'est un UploadedFile
        if ($image instanceof UploadedFile) {
            $imageObj = Image::make($image);
            if ($imageObj->width() > 1024) {
                $imageObj->widen(1024, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                // Réécrire dans le fichier
                $imageObj->save($image->getRealPath(), 80);
            }
        }

        return $image;
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
