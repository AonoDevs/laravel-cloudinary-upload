# Package to upload images and videos on cloudinary and save their url into model's database

[![Latest Version on Packagist](https://img.shields.io/packagist/v/aonodevs/laravel-cloudinary-upload.svg?style=flat-square)](https://packagist.org/packages/aonodevs/laravel-cloudinary-upload)
[![Total Downloads](https://img.shields.io/packagist/dt/aonodevs/laravel-cloudinary-upload.svg?style=flat-square)](https://packagist.org/packages/aonodevs/laravel-cloudinary-upload)

This package provides a trait that adds cloudinary upload behavior to a Eloquent model.

It will save the url returned by cloudinary when you want to save an image or video for creation or modification. 

Old images or videos will be removed from cloudinary if the model is deleted or updated.

## Installation

You can install the package via composer:

```bash
composer require aonodevs/laravel-cloudinary-upload
```


You can publish the config file with:

```bash
php artisan vendor:publish --tag="cloudinary-upload-config"
```

This is the contents of the published config file:

```php
return [
    /*
     * Your cloudinary upload url
     */
    "url" => env('CLOUDINARY_URL')
];
```

## Usage
To add cloudinary upload behaviour to your model you must:

1. Use the trait AonoDevs\LaravelCloudinaryUpload\CloudinaryTrait.
2. Write your different fillable attribute which will take into account the url record returned by cloudinary. You must differentiate between images and videos using `$cloudinary_image` and `$cloudinary_video` respectively

## Exemple

```php
use AonoDevs\LaravelCloudinaryUpload\CloudinaryTrait;
// ...

class Article extends Model
{
    use CloudinaryTrait;

    protected $fillable = [
        'title',
        'header_img', // Image
        'footer_img', // Image
        'content_video', // Video
    ];

    protected array $cloudinary_image = ['header_img', 'footer_img'];
    
    protected array $cloudinary_video = ['content_video'];
    
    // ...
}
```
If you don't set a value `$cloudinary_image` or `$cloudinary_image` the package will assume that none of your attributes will need to be a cloudinary one and will not run cloudinary upload and url saving.

## Credits

- [AonoDevs](https://github.com/AonoDevs)
