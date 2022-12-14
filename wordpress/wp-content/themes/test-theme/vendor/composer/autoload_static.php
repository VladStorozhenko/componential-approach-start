<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitb72b06fbc96123d1c3c836f9e2531724
{
    public static $prefixLengthsPsr4 = array (
        'b' => 
        array (
            'builderScripts\\' => 15,
        ),
        'I' => 
        array (
            'Inc\\' => 4,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'builderScripts\\' => 
        array (
            0 => __DIR__ . '/../..' . '/builder-scripts',
        ),
        'Inc\\' => 
        array (
            0 => __DIR__ . '/../..' . '/inc',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitb72b06fbc96123d1c3c836f9e2531724::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitb72b06fbc96123d1c3c836f9e2531724::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitb72b06fbc96123d1c3c836f9e2531724::$classMap;

        }, null, ClassLoader::class);
    }
}
