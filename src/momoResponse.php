<?php

/**
 * 
 * 
 * @author daidq - itvn9online@gmail.com
 * @since  1.0.2
 */

namespace momo\ Responses;

use momo\ Gateways\ momoGateway;
use momo\ Facades\ FacadeResponse;

abstract class momoResponse
implements FacadeResponse {
    protected $hashCode;
    public function __construct() {
        $this->action();
    }

    public function action() {
        add_action( 'wp_ajax_payment_momo_response', array( $this, 'checkResponse' ) );
        add_action( 'wp_ajax_nopriv_payment_momo_response', array( $this, 'checkResponse' ) );
        add_action( 'wp_ajax_payment_response_momo', array( $this, 'ipn_url_momo' ) );
        add_action( 'wp_ajax_nopriv_payment_response_momo', array( $this, 'ipn_url_momo' ) );
    }

    // trang này sẽ hiển thị nội dung cảm ơn khách đã đặt hàng
    public function checkResponse( $txnResponseCode ) {
        header( 'Content-type: text/html; charset=utf-8' );
        //print_r( $_GET );
        //print_r( $_POST );
        //print_r( $txnResponseCode );
        //die( __CLASS__ . ':' . __LINE__ );

        //
        $log_file = __DIR__ . '/log-' . __FUNCTION__ . '.txt';

        //
        if ( WP_DEBUG === true ) {
            ini_set( 'display_errors', 1 );
            error_reporting( E_ALL );
            ini_set( 'log_errors', 1 );
            ini_set( 'error_log', $log_file );

            //
            file_put_contents( $log_file, json_encode( $_GET ) . "\n" );
            if ( isset( $_SERVER[ 'HTTP_REFERER' ] ) ) {
                file_put_contents( $log_file, $_SERVER[ 'HTTP_REFERER' ] . "\n", FILE_APPEND );
            } else {
                file_put_contents( $log_file, json_encode( $_SERVER ) . "\n", FILE_APPEND );
            }
            file_put_contents( $log_file, $_SERVER[ 'REMOTE_ADDR' ] . "\n", FILE_APPEND );
            file_put_contents( $log_file, json_encode( $_POST ) . "\n", FILE_APPEND );
        }

        //
        global $woocommerce;
        $woocommerce->cart->get_checkout_url();
        $order = $this->getOrder( sanitize_text_field( $_GET[ "orderId" ] ) );
        //print_r( $order );

        // kiểm tra xem đơn hàng đã được thanh toán thành công chưa
        $this->ipn_result( $order );
        //die( __CLASS__ . ':' . __LINE__ );

        //
        $url = rtrim( wc_get_checkout_url(), '/' ) . '/order-received/' . $order->id . '/?key=' . $order->order_key;
        wp_redirect( $url );
        WC()->cart->empty_cart();
        exit();
    }

    private function ipn_result( $order ) {
        //echo $order->post_status . '<br>' . "\n";

        // nếu trạng thái đơn không phải là đang tạm giữ thì bỏ qua
        if ( $order->post_status != 'wc-on-hold' ) {
            return false;
        }

        //
        //die( __CLASS__ . ':' . __LINE__ );

        if ( empty( $_GET ) ) {
            return false;
        }

        // get secretkey
        $momo_gateway = new momoGateway;
        $secretKey = $momo_gateway->get_option( 'secretkey' );
        //echo 'secretKey: ' . $secretKey . '<br>' . "\n";
        $accessKey = $momo_gateway->get_option( 'access_key' );
        //echo 'accessKey: ' . $accessKey . '<br>' . "\n";

        //
        $partnerCode = sanitize_text_field( $_GET[ "partnerCode" ] );
        $orderId = sanitize_text_field( $_GET[ "orderId" ] );
        $requestId = sanitize_text_field( $_GET[ "requestId" ] );
        $amount = sanitize_text_field( $_GET[ "amount" ] );
        $orderInfo = sanitize_text_field( $_GET[ "orderInfo" ] );
        $orderType = sanitize_text_field( $_GET[ "orderType" ] );
        $transId = sanitize_text_field( $_GET[ "transId" ] );
        $resultCode = sanitize_text_field( $_GET[ "resultCode" ] );
        $message = sanitize_text_field( $_GET[ "message" ] );
        $payType = sanitize_text_field( $_GET[ "payType" ] );
        $responseTime = sanitize_text_field( $_GET[ "responseTime" ] );
        $extraData = sanitize_text_field( $_GET[ "extraData" ] );
        $m2signature = sanitize_text_field( $_GET[ "signature" ] ); // MoMo signature


        //Checksum
        $rawHash = "accessKey=" . $accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&message=" . $message . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&orderType=" . $orderType . "&partnerCode=" . $partnerCode . "&payType=" . $payType . "&requestId=" . $requestId . "&responseTime=" . $responseTime . "&resultCode=" . $resultCode . "&transId=" . $transId;

        //
        //echo 'rawHash:' . $rawHash . '<br>' . "\n";
        // ->
        $partnerSignature = hash_hmac( "sha256", $rawHash, $secretKey );

        //echo "<script>console.log('Debug huhu Objects: " . $rawHash . "' );</script>";
        //echo "<script>console.log('Debug huhu Objects: " . $partnerSignature . "' );</script>";

        //
        $returnData = [];
        $returnData[ 'RspCode' ] = $resultCode;
        $returnData[ 'Signature' ] = $m2signature;
        if ( $m2signature == $partnerSignature ) {
            if ( $resultCode == '0' ) {
                $result = '<strong>Payment status: </strong>Success';

                //
                $returnData[ 'Message' ] = 'Confirm Success';
                $transStatus = $this->getResponseDescription( $resultCode );
                $order->update_status( 'processing' );
                $order->add_order_note( __( $transStatus, 'woocommerce' ) );
                WC()->cart->empty_cart();
            } else {
                $result = '<strong>Payment status: </strong>' . $message;
            }
        } else {
            esc_html_e( 'MoMo signature:' . $m2signature );
            esc_html_e( 'Partner signature: ' . $partnerSignature );

            //
            $result = 'This transaction could be hacked, please check your signature and returned signature';
        }
        $returnData[ 'result' ] = $result;
        //echo $result . '<br>' . "\n";
        //die( __CLASS__ . ':' . __LINE__ );

        //
        return $returnData;
    }

    // trang này sẽ xác thực trạng thái đơn hàng
    public function ipn_url_momo( $txnResponseCode ) {
        //ob_clean();

        //
        header( "content-type: application/json; charset=UTF-8" );
        http_response_code( 200 ); // 200 - Everything will be 200 Oke
        //die( get_site_url() . '/test.php' );

        //
        $log_file = __DIR__ . '/log-' . __FUNCTION__ . '.txt';

        //
        if ( WP_DEBUG === true ) {
            ini_set( 'display_errors', 1 );
            error_reporting( E_ALL );
            ini_set( 'log_errors', 1 );
            ini_set( 'error_log', $log_file );

            //
            //print_r( $_GET );
            file_put_contents( $log_file, json_encode( $_GET ) . "\n" );
            if ( isset( $_SERVER[ 'HTTP_REFERER' ] ) ) {
                file_put_contents( $log_file, $_SERVER[ 'HTTP_REFERER' ] . "\n", FILE_APPEND );
            } else {
                file_put_contents( $log_file, json_encode( $_SERVER ) . "\n", FILE_APPEND );
            }
            file_put_contents( $log_file, $_SERVER[ 'REMOTE_ADDR' ] . "\n", FILE_APPEND );

            //
            if ( $_SERVER[ 'REQUEST_METHOD' ] != 'POST' ) {
                file_put_contents( $log_file, 'SERVER REQUEST METHOD: ' . $_SERVER[ 'REQUEST_METHOD' ] . ' ' . __CLASS__ . ':' . __LINE__ . "\n", FILE_APPEND );
                die( __CLASS__ . ':' . __LINE__ );
            }

            //
            if ( empty( $_POST ) ) {
                file_put_contents( $log_file, 'ERROR! empty _POST ' . __CLASS__ . ':' . __LINE__ . "\n", FILE_APPEND );
                die( __CLASS__ . ':' . __LINE__ );
            }

            //
            file_put_contents( $log_file, json_encode( $_POST ) . "\n", FILE_APPEND );
        }

        //
        $response = array();
        try {
            // get secretkey
            $momo_gateway = new momoGateway;
            $secretKey = $momo_gateway->get_option( 'secretkey' );
            //echo 'secretKey: ' . $secretKey . '<br>' . "\n";
            $accessKey = $momo_gateway->get_option( 'access_key' );
            //echo 'accessKey: ' . $accessKey . '<br>' . "\n";

            //
            $partnerCode = sanitize_text_field( $_POST[ "partnerCode" ] );
            $orderId = sanitize_text_field( $_POST[ "orderId" ] );
            $requestId = sanitize_text_field( $_POST[ "requestId" ] );
            $amount = sanitize_text_field( $_POST[ "amount" ] );
            $orderInfo = sanitize_text_field( $_POST[ "orderInfo" ] );
            $orderType = sanitize_text_field( $_POST[ "orderType" ] );
            $transId = sanitize_text_field( $_POST[ "transId" ] );
            $resultCode = sanitize_text_field( $_POST[ "resultCode" ] );
            $message = sanitize_text_field( $_POST[ "message" ] );
            $payType = sanitize_text_field( $_POST[ "payType" ] );
            $responseTime = sanitize_text_field( $_POST[ "responseTime" ] );
            $extraData = sanitize_text_field( $_POST[ "extraData" ] );
            $m2signature = sanitize_text_field( $_POST[ "signature" ] ); // MoMo signature


            //Checksum
            $rawHash = "accessKey=" . $accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&message=" . $message . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&orderType=" . $orderType . "&partnerCode=" . $partnerCode . "&payType=" . $payType . "&requestId=" . $requestId . "&responseTime=" . $responseTime . "&resultCode=" . $resultCode . "&transId=" . $transId;

            $partnerSignature = hash_hmac( "sha256", $rawHash, $secretKey );

            //
            $returnData = [];
            $returnData[ 'RspCode' ] = $resultCode;
            $returnData[ 'Signature' ] = $m2signature;
            if ( $m2signature == $partnerSignature ) {
                $response[ 'message' ] = "Received payment result success";
                if ( $resultCode == '0' ) {
                    $result = '<strong>Payment status: </strong>Success';

                    //
                    $returnData[ 'Message' ] = 'Confirm Success';
                    $transStatus = $this->getResponseDescription( $resultCode );
                    $order->update_status( 'processing' );
                    $order->add_order_note( __( $transStatus, 'woocommerce' ) );
                    WC()->cart->empty_cart();
                } else {
                    $result = '<strong>Payment status: </strong>' . $message;
                }
            } else {
                $response[ 'message' ] = "ERROR! Fail checksum";
                $result = 'This transaction could be hacked, please check your signature and returned signature';
            }
            $returnData[ 'result' ] = $result;
            if ( WP_DEBUG === true ) {
                file_put_contents( $log_file, json_encode( $returnData ) . "\n", FILE_APPEND );
            }
        } catch ( Exception $e ) {
            if ( WP_DEBUG === true ) {
                file_put_contents( $log_file, $message . "\n", FILE_APPEND );
                error_log( $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() );
            } else {
                esc_html_e( $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() );
            }
        }
        //
        $debugger = array();
        $debugger[ 'rawData' ] = $rawHash;
        $debugger[ 'momoSignature' ] = $m2signature;
        $debugger[ 'partnerSignature' ] = $partnerSignature;

        //
        $response[ 'debugger' ] = $debugger;
        if ( WP_DEBUG === true ) {
            file_put_contents( $log_file, json_encode( $response ) . "\n", FILE_APPEND );
        }

        //
        //die( __CLASS__ . ':' . __LINE__ );
        die( json_encode( $response ) );
    }

    abstract public function thankyou();

    abstract public function getResponseDescription( $responseCode );

    public function getOrder( $orderId ) {
        preg_match_all( '!\d+!', $orderId, $matches );
        $order = new\ WC_Order( $matches[ 0 ][ 0 ] );
        return $order;
    }

}