<?php

namespace Kriegerhost\Services\Helpers;

use Illuminate\Support\Arr;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Contracts\Foundation\Application;

class AssetHashService
{
    /**
     * Location of the manifest file generated by gulp.
     */
    public const MANIFEST_PATH = './assets/manifest.json';

    /**
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    private $filesystem;

    /**
     * @var \Illuminate\Contracts\Foundation\Application
     */
    private $application;

    /**
     * @var array|null
     */
    protected static $manifest;

    /**
     * AssetHashService constructor.
     */
    public function __construct(Application $application, FilesystemManager $filesystem)
    {
        $this->application = $application;
        $this->filesystem = $filesystem->createLocalDriver(['root' => public_path()]);
    }

    /**
     * Modify a URL to append the asset hash.
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function url(string $resource): string
    {
        $file = last(explode('/', $resource));
        $data = Arr::get($this->manifest(), $file) ?? $file;

        return str_replace($file, Arr::get($data, 'src') ?? $file, $resource);
    }

    /**
     * Return the data integrity hash for a resource.
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function integrity(string $resource): string
    {
        $file = last(explode('/', $resource));
        $data = array_get($this->manifest(), $file, $file);

        return Arr::get($data, 'integrity') ?? '';
    }

    /**
     * Return a built CSS import using the provided URL.
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function css(string $resource): string
    {
        $attributes = [
            'href' => $this->url($resource),
            'rel' => 'stylesheet preload',
            'as' => 'style',
            'crossorigin' => 'anonymous',
            'referrerpolicy' => 'no-referrer',
        ];

        if (config('kriegerhost.assets.use_hash')) {
            $attributes['integrity'] = $this->integrity($resource);
        }

        $output = '<link';
        foreach ($attributes as $key => $value) {
            $output .= " $key=\"$value\"";
        }

        return $output . '>';
    }

    /**
     * Return a built JS import using the provided URL.
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function js(string $resource): string
    {
        $attributes = [
            'src' => $this->url($resource),
            'crossorigin' => 'anonymous',
        ];

        if (config('kriegerhost.assets.use_hash')) {
            $attributes['integrity'] = $this->integrity($resource);
        }

        $output = '<script';
        foreach ($attributes as $key => $value) {
            $output .= " $key=\"$value\"";
        }

        return $output . '></script>';
    }

    /**
     * Get the asset manifest and store it in the cache for quicker lookups.
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function manifest(): array
    {
        return self::$manifest ?: self::$manifest = json_decode(
            $this->filesystem->get(self::MANIFEST_PATH),
            true
        );
    }
}
