<?php

namespace FriendsOfRedaxo\Cropper\Cropper;

use RuntimeException;

final class ZebraImageAdapter
{
    private mixed $instance;

    public function __construct()
    {
        $className = 'stefangabos\\Zebra_Image\\Zebra_Image';

        if (!class_exists($className)) {
            throw new RuntimeException('Zebra_Image is not available.');
        }

        $this->instance = new $className();
    }

    public function setJpegQuality(int $quality): void
    {
        $this->instance->jpeg_quality = $quality;
    }

    public function setPngCompression(int $compression): void
    {
        $this->instance->png_compression = $compression;
    }

    public function setPreserveTime(bool $preserveTime): void
    {
        $this->instance->preserve_time = $preserveTime;
    }

    public function setSourcePath(string $sourcePath): void
    {
        $this->instance->source_path = $sourcePath;
    }

    public function setTargetPath(string $targetPath): void
    {
        $this->instance->target_path = $targetPath;
    }

    public function getTargetPath(): string
    {
        return (string) $this->instance->target_path;
    }

    public function flipHorizontal(): void
    {
        $this->call('flip_horizontal');
    }

    public function flipVertical(): void
    {
        $this->call('flip_vertical');
    }

    public function flipBoth(): void
    {
        $this->call('flip_both');
    }

    public function rotate(int $angle): void
    {
        $this->call('rotate', $angle);
    }

    public function crop(int $left, int $top, int $right, int $bottom): void
    {
        $this->call('crop', $left, $top, $right, $bottom);
    }

    public function getError(): int
    {
        return (int) $this->instance->error;
    }

    private function call(string $method, mixed ...$arguments): void
    {
        $this->instance->{$method}(...$arguments);
    }
}