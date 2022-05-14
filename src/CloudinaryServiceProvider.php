<?php

namespace Phuclh\Cloudinary;

use Cloudinary\Cloudinary;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CloudinaryServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('flysystem-cloudinary')
            ->hasConfigFile('cloudinary');
    }

    public function bootingPackage()
    {
        $this->app['config']['filesystems.disks.cloudinary'] = ['driver' => 'cloudinary'];

        Storage::extend('imagekit', function ($app, $config) {
            $client = new Cloudinary(config('cloudinary.cloud_url'));

            $adapter = new CloudinaryAdapter($client);

            return new FilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config
            );
        });
    }
}
