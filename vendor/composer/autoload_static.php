<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit3d09535f4425c04647504ae0fce5df13
{
    public static $prefixLengthsPsr4 = array (
        'I' => 
        array (
            'Inc\\' => 4,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Inc\\' => 
        array (
            0 => __DIR__ . '/../..' . '/inc',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit3d09535f4425c04647504ae0fce5df13::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit3d09535f4425c04647504ae0fce5df13::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
