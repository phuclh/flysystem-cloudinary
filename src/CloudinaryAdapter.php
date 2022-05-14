<?php

namespace Phuclh\Cloudinary;

use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Api\Exception\ApiError;
use Cloudinary\Api\Exception\NotFound;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Cloudinary;
use Illuminate\Support\Str;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToSetVisibility;

class CloudinaryAdapter implements FilesystemAdapter
{
    public function __construct(
        protected Cloudinary $client
    ) {
    }

    public function getClient(): Cloudinary
    {
        return $this->client;
    }

    public function getUrl($path): string
    {
        if ($path == '/') {
            return $path;
        }

        try {
            $resource = $this->getResource(Str::beforeLast($path, '.'));

            return $resource['secure_url'] ?? '';
        } catch (NotFound $e) {
            return '';
        }
    }

    public function fileExists(string $path): bool
    {
        try {
            $this->adminApi()->asset($path);
        } catch (NotFound $e) {
            return false;
        }

        return true;
    }

    public function directoryExists(string $path): bool
    {
        return $this->fileExists($path);
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $tempFile = tmpfile();

        fwrite($tempFile, $contents);

        $this->writeStream($path, $tempFile, $config);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $publicId = $config->get('public_id', $path);

        $resourceType = $config->get('resource_type', 'auto');

        $fileExtension = pathinfo($publicId, PATHINFO_EXTENSION);

        $newPublicId = $fileExtension ? substr($publicId, 0, -(strlen($fileExtension) + 1)) : $publicId;

        $uploadOptions = [
            'public_id' => $newPublicId,
            'resource_type' => $resourceType,
        ];

        $resourceMetadata = stream_get_meta_data($contents);

        $this->upload($resourceMetadata['uri'], $uploadOptions);
    }

    public function read(string $path): string
    {
        $resource = (array)$this->adminApi()->asset($path);

        return file_get_contents($resource['secure_url']);
    }

    public function readStream(string $path)
    {
        $resource = (array)$this->adminApi()->asset($path);

        return fopen($resource['secure_url'], 'rb');
    }

    public function delete(string $path): void
    {
        $this->uploadApi()->destroy($path);
    }

    public function deleteDirectory(string $path): void
    {
        try {
            $this->adminApi()->deleteAssetsByPrefix($path);
        } catch (ApiError $e) {
            throw UnableToDeleteDirectory::atLocation($path, $e->getPrevious()->getMessage(), $e);
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        try {
            $this->adminApi()->createFolder($path);
        } catch (ApiError $e) {
            throw UnableToCreateDirectory::atLocation($path, $e->getMessage());
        }
    }

    public function setVisibility(string $path, string $visibility): void
    {
        throw UnableToSetVisibility::atLocation($path, 'Adapter does not support visibility controls.');
    }

    public function visibility(string $path): FileAttributes
    {
        // Noop
        return new FileAttributes($path);
    }

    public function mimeType(string $path): FileAttributes
    {
        return new FileAttributes(
            $path,
            null,
            null,
            null,
            $this->prepareMimetype($this->getResource($path))['mimetype']
        );
    }

    public function lastModified(string $path): FileAttributes
    {
        return new FileAttributes(
            $path,
            null,
            null,
            $this->prepareTimestamp($this->getResource($path))['timestamp']
        );
    }

    public function fileSize(string $path): FileAttributes
    {
        return new FileAttributes(
            $path,
            $this->prepareSize($this->getResource($path))['size']
        );
    }

    public function listContents(string $path = '', bool $deep = false): iterable
    {
        $resources = [];

        // get resources array
        $response = null;
        do {
            $response = (array)$this->adminApi()->assets(
                [
                    'type' => 'upload',
                    'prefix' => $path,
                    'max_results' => 500,
                    'next_cursor' => $response['next_cursor'] ?? null,
                ]
            );
            $resources = array_merge($resources, $response['resources']);
        } while (array_key_exists('next_cursor', $response));

        // Parse resources
        foreach ($resources as $i => $resource) {
            $resources[$i] = $this->prepareResourceMetadata($resource);
        }

        return $resources;
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $this->copy($source, $destination, $config);
        $this->delete($source);
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $this->uploadApi()->upload($source, ['public_id' => $destination]);
    }

    public function getResource($path): array
    {
        return (array)$this->adminApi()->asset($path);
    }

    protected function adminApi(): AdminApi
    {
        return $this->client->adminApi();
    }

    protected function uploadApi(): UploadApi
    {
        return $this->client->uploadApi();
    }

    protected function upload($file, $options = []): void
    {
        $this->uploadApi()->upload($file, $options);
    }

    protected function prepareResourceMetadata($resource): array
    {
        $resource['type'] = 'file';
        $resource['path'] = $resource['public_id'];
        $resource = array_merge($resource, $this->prepareSize($resource));
        $resource = array_merge($resource, $this->prepareTimestamp($resource));
        $resource = array_merge($resource, $this->prepareMimetype($resource));

        return $resource;
    }

    protected function prepareSize($resource): array
    {
        $size = $resource['bytes'];

        return compact('size');
    }

    protected function prepareTimestamp($resource): array
    {
        $timestamp = strtotime($resource['created_at']);

        return compact('timestamp');
    }

    protected function prepareMimetype($resource): array
    {
        $mimetype = $resource['resource_type'];

        return compact('mimetype');
    }
}
