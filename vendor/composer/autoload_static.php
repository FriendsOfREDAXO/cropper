<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit5471e79cdf6c234cef2a81460bd6959f
{
    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'Zebra_Image' => __DIR__ . '/..' . '/stefangabos/zebra_image/Zebra_Image.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInit5471e79cdf6c234cef2a81460bd6959f::$classMap;

        }, null, ClassLoader::class);
    }
}
