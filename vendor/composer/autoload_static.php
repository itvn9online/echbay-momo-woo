<?php


namespace Composer\Autoload;

class ComposerStaticInit4mc3uo77pbgarf3eptgt05jqmd
{
    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'momo\\Traits\\' => 17,
            'momo\\Shortcodes\\' => 21,
            'momo\\Gateways\\' => 19,
            'momo\\' => 10,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'momo\\Traits\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src/traits',
        ),
        'momo\\Shortcodes\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src/shortcodes',
        ),
        'momo\\Gateways\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src/gateways',
        ),
        'momo\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'momo\\Facades\\FacadeResponse' => __DIR__ . '/../..' . '/src/FacadeResponse.php',
        'momo\\Gateways\\momoGateway' => __DIR__ . '/../..' . '/src/gateways/momoGateway.php',
        'momo\\Gateways\\momoInternationalResponse' => __DIR__ . '/../..' . '/src/gateways/momoInternationalResponse.php',
        'momo\\Page' => __DIR__ . '/../..' . '/src/Page.php',
        'momo\\Responses\\momoResponse' => __DIR__ . '/../..' . '/src/momoResponse.php',
        'momo\\Shortcodes\\Thankyou' => __DIR__ . '/../..' . '/src/shortcodes/Thankyou.php',
        'momo\\Traits\\Pages' => __DIR__ . '/../..' . '/src/traits/Pages.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit4mc3uo77pbgarf3eptgt05jqmd::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit4mc3uo77pbgarf3eptgt05jqmd::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit4mc3uo77pbgarf3eptgt05jqmd::$classMap;

        }, null, ClassLoader::class);
    }
}
