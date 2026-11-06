<?php

namespace App\Services\Media;

use App\Services\Media\Drivers\LocalDriver;
use InvalidArgumentException;

class MediaDriverManager
{
    protected array $drivers = [];

    public function driver(?string $name = null): MediaDriverInterface
    {
        $name = $name ?? config('media.default_driver');

        if (!isset($this->drivers[$name])) {
            $this->drivers[$name] = $this->resolve($name);
        }

        return $this->drivers[$name];
    }

    protected function resolve(string $name): MediaDriverInterface
    {
        $config = config("media.drivers.{$name}");

        if (!$config) {
            throw new InvalidArgumentException("Media driver [{$name}] is not configured.");
        }

        return match ($name) {
            'local' => new LocalDriver($config['disk']),
            // 's3' => new S3Driver($config['disk']),
            default => throw new InvalidArgumentException("Unsupported media driver [{$name}]."),
        };
    }
}
