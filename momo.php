<?php
/**
 * Plugin Name: EchBay Integrate MoMo paygate into WooCommerce
 * Description: Integrate MoMo paygate into WooCommerce - Tích hợp cổng thanh toán MoMo vào website sử dụng WooCommerce
 * Version: 1.0.1
 * Plugin URI: https://echbay.com/
 * Plugin Facebook page: https://www.facebook.com/webgiare.org
 * Author: Dao Quoc Dai
 * Author URI: https://www.facebook.com/groups/wordpresseb
 * License: GPLv2 or later
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit();
}

use momo\ Gateways\ momoGateway;
use momo\ Gateways\ momoInternationalResponse;
use momo\ Traits\ Pages;

require __DIR__ . '/vendor/autoload.php';

/**
 */
class momo {
    use momo\ Traits\ Pages;


    protected $shortcodes = array();


    protected $responses;

    public function __construct() {
        $this->constants();
        add_action( 'init', array( $this, 'renderPages' ) );
        add_action( 'plugins_loaded', array( $this, 'momoInit' ) );
        add_filter( 'woocommerce_locate_template', array( $this, 'momoWoocommerceTemplates' ), 10, 3 );
        $this->loadModule();
        $this->responseListener();
    }


    public function constants() {
        define( 'EB_MOMO_URL', plugins_url( '', __FILE__ ) );
        //esc_html_e( EB_MOMO_URL );
        //die( __CLASS__ . ':' . __LINE__ );
    }


    public function momoInit() {
        add_filter( 'woocommerce_payment_gateways', array( $this, 'addPaymentMethod' ) );
    }


    public function addPaymentMethod( $methods ) {
        $methods[] = 'momo\Gateways\momoGateway';
        return $methods;
    }


    public function loadModule() {
        $this->shortcodes[] = new momo\ Shortcodes\ Thankyou;
    }

    public function responseListener() {
        if ( isset( $_GET[ 'type' ] ) ) {
            switch ( sanitize_text_field( $_GET[ 'type' ] ) ) {
                case 'international':
                    $this->responses[] = new momoInternationalResponse;
                    break;

            }
        }
    }


    public function renderPages() {
        $checkRenderPage = ( !get_option( 'momo_settings' ) ) ? false : get_option( 'momo_settings' );
        if ( $checkRenderPage != false ) return;
        if ( !empty( $this->pages ) ) {
            foreach ( $this->pages as $slug => $args ) {
                $page = new momo\ Page( $args );
            }
            update_option( 'momo_settings', true );
        }
    }

    public function momoWoocommerceTemplates( $template, $template_name, $template_path ) {
        global $woocommerce;

        $_template = $template;

        if ( !$template_path )$template_path = $woocommerce->template_url;

        $plugin_path = __DIR__ . '/woocommerce/';

        $template = locate_template(
            array(
                $template_path . $template_name,
                $template_name
            )
        );

        if ( !$template && file_exists( $plugin_path . $template_name ) )

            $template = $plugin_path . $template_name;

        if ( !$template )$template = $_template;

        return $template;
    }
}

new momo;