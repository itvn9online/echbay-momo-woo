<?php


$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);

return array(
    'momo\\Facades\\FacadeResponse' => $baseDir . '/src/FacadeResponse.php',
    'momo\\Gateways\\momoGateway' => $baseDir . '/src/gateways/momoGateway.php',
    'momo\\Gateways\\momoInternationalResponse' => $baseDir . '/src/gateways/momoInternationalResponse.php',
    'momo\\Page' => $baseDir . '/src/Page.php',
    'momo\\Responses\\momoResponse' => $baseDir . '/src/momoResponse.php',
    'momo\\Shortcodes\\Thankyou' => $baseDir . '/src/shortcodes/Thankyou.php',
    'momo\\Traits\\Pages' => $baseDir . '/src/traits/Pages.php',
);

