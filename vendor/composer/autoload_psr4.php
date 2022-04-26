<?php


$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);

return array(
    'momo\\Traits\\' => array($baseDir . '/src/traits'),
    'momo\\Shortcodes\\' => array($baseDir . '/src/shortcodes'),
    'momo\\Gateways\\' => array($baseDir . '/src/gateways'),
    'momo\\' => array($baseDir . '/src'),
);
