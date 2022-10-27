<?php
/**
 * 
 * 
 * @author daidq - itvn9online@gmail.com
 * @since  1.0.0
 * https://github.com/momo-wallet/payment/tree/master/php
 * https://developers.momo.vn/v3/vi/docs/payment/onboarding/test-instructions
 */

namespace momo\Gateways;

class momoGateway extends \WC_Payment_Gateway
{

    public function __construct()
    {
        $this->id = 'momo';
        //$this->icon = $this->get_option( 'logo' );
        if ($this->get_option('show_logo') != 'no') {
            //$this->icon = apply_filters( 'woocommerce_echbay_momo_icon', EB_MOMO_URL . '/images/momo_icon_square_pinkbg_RGB.png' );
            $this->icon = EB_MOMO_URL . '/images/momo_icon_square_pinkbg_RGB.png';
        }
        $this->has_fields = false;
        $this->method_title = __('MoMo', 'woocommerce');
        $this->method_description = __('Tích hợp thanh toán MoMo vào website sử dụng WooCommerce. Plugin này được cung cấp miễn phí bởi EchBay.com và WebGiaRe.org', 'noob-pay-woo');

        $this->supports = array(
            'products',
            'refunds'
        );

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->test_payment = $this->get_option('test_payment');
        $this->partner_code = $this->get_option('partner_code');
        $this->access_key = $this->get_option('access_key');
        $this->secretkey = $this->get_option('secretkey');
        $this->partner_name = $this->get_option('partner_name');
        $this->store_id = $this->get_option('store_id');
        $this->locale = $this->get_option('locale');

        if (!$this->isValidCurrency()) {
            $this->enabled = 'no';
        }

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));

        //
        $this->endpoint = 'payment.momo.vn';
        //echo $this->test_payment . '<br>' . "\n";
        if ($this->test_payment != 'no') {
            $this->endpoint = 'test-payment.momo.vn';
        }
        //echo $this->endpoint . '<br>' . "\n";
    }

    public function getPagesList()
    {
        $pagesList = array();
        $pages = get_pages();
        if (!empty($pages)) {
            foreach ($pages as $page) {
                $pagesList[$page->ID] = $page->post_title;
            }
        }
        return $pagesList;
    }

    public function init_form_fields()
    {

        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable MoMo Paygate', 'woocommerce'),
                'default' => 'yes',
            ),
            'title' => array(
                'title' => __('Tiêu đề', 'woocommerce'),
                'type' => 'text',
                'description' => 'Tiêu đề thanh toán',
                'default' => 'Thanh toán bằng ví MoMo',
                'desc_tip' => true
            ),
            'description' => array(
                'title' => __('Mô tả', 'woocommerce'),
                'type' => 'textarea',
                'description' => __('Mô tả phương thức thanh toán', 'woocommerce'),
                'default' => __('Thanh toán trực tuyến qua ví MoMo. Hỗ trợ nhiều ngân hàng và quét mã QR tiện lợi, nhanh chóng.', 'woocommerce'),
                'desc_tip' => true
            ),
            'partner_code' => array(
                'title' => __('Partner Code', 'woocommerce'),
                'type' => 'text',
                'description' => 'Partner Code MoMo cung cấp',
                'default' => '',
                'desc_tip' => true
            ),
            'access_key' => array(
                'title' => __('Access Key', 'woocommerce'),
                'type' => 'password',
                'description' => 'Access Key MoMo cung cấp',
                'default' => '',
                'desc_tip' => true
            ),
            'secretkey' => array(
                'title' => __('Secret Key', 'woocommerce'),
                'type' => 'password',
                'description' => 'Secret Key MoMo cung cấp',
                'default' => '',
                'desc_tip' => true
            ),
            'partner_name' => array(
                'title' => __('Partner Name', 'woocommerce'),
                'type' => 'text',
                'description' => '',
                'default' => 'Test',
                'desc_tip' => false
            ),
            'store_id' => array(
                'title' => __('Store Id', 'woocommerce'),
                'type' => 'text',
                'description' => '',
                'default' => 'MoMoTestStore',
                'desc_tip' => false
            ),
            'test_payment' => array(
                'title' => __('Test payment', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable test payment (Using Api Endpoint: test-payment.momo.vn)', 'woocommerce'),
                'default' => 'yes',
            ),
            'show_logo' => array(
                'title' => __('Show logo', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Display MoMo logo in checkout page.', 'woocommerce'),
                'default' => 'yes',
            ),
            'locale' => array(
                'title' => __('Locale', 'woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'description' => __('Choose your locale', 'woocommerce'),
                'desc_tip' => true,
                'default' => 'vn',
                'options' => array(
                    'vn' => 'vn',
                    'en' => 'en'
                )
            ),
        );
    }

    protected function execPostRequest($url, $data)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)));
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        //execute post
        $result = curl_exec($ch);
        //close connection
        curl_close($ch);
        return $result;
    }

    public function process_payment($order_id)
    {
        if (WP_DEBUG === true) {
            ini_set('display_errors', 1);
            error_reporting(E_ALL);
        }

        //
        //header( 'Content-type: text/html; charset=utf-8' );

        //
        $order = new \WC_Order($order_id);
        $order->update_status('on-hold');
        $order->add_order_note(__('Giao dịch chờ thanh toán hoặc chưa hoàn tất', 'woocommerce'));

        //
        return array(
            'result' => 'success',
            // v3 - https://github.com/momo-wallet/payment/tree/master/php
            'redirect' => $this->redirect($order_id), // captureWallet
            //'redirect' => $this->redirect( $order_id, 'payWithATM' ),
            // v2 - https://github.com/momo-wallet/php
            //'redirect' => $this->redirect_v2( $order_id ),
        );
    }

    public function redirect($order_id, $requestType = 'captureWallet')
    {
        $order = new \WC_Order($order_id);

        //
        $forenamefw = $order->get_billing_first_name();
        $forename = $this->convert_vi_to_en($forenamefw);
        $surnamefw = $order->get_billing_last_name();
        $surname = $this->convert_vi_to_en($surnamefw);
        $mobile = $order->get_billing_phone();
        $emailfw = $order->get_billing_email();
        $email = $this->convert_vi_to_en($emailfw);

        //
        //print_r( $order->order_total );

        //$amount = number_format( $order->order_total, 2, '.', '' ) * 100;
        $amount = $order->order_total;
        //echo $amount . '<br>' . "\n";
        $this->endpoint = 'https://' . $this->endpoint . '/v2/gateway/api/create';
        //echo $this->endpoint . '<br>' . "\n";
        //die( __CLASS__ . ':' . __LINE__ );

        /*
         * custom code for momo v3
         */
        $partnerCode = $this->partner_code;
        $accessKey = $this->access_key;
        $serectkey = $this->secretkey;
        $orderId = $order_id; // Mã đơn hàng
        $orderInfo = 'Ma giao dich thanh toan:' . $order_id . '-' . 'Ho va ten KH:' . $surname . ' ' . $forename . '-' . 'SDT:' . $mobile . '-' . 'Email:' . $email;
        //$amount = sanitize_text_field( $_POST[ "amount" ] );
        $ipnUrl = admin_url('admin-ajax.php') . '?action=payment_response_momo&type=international';
        //$ipnUrl = get_site_url() . '/test.php';
        $redirectUrl = admin_url('admin-ajax.php') . '?action=payment_momo_response&type=international';
        //$extraData = sanitize_text_field( $_POST[ "extraData" ] );

        $requestId = time() . "";
        $extraData = (isset($_POST["extraData"]) ? sanitize_text_field($_POST["extraData"]) : "");

        //before sign HMAC SHA256 signature
        $rawHash = "accessKey=" . $accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&ipnUrl=" . $ipnUrl . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&partnerCode=" . $partnerCode . "&redirectUrl=" . $redirectUrl . "&requestId=" . $requestId . "&requestType=" . $requestType;
        // ->
        $signature = hash_hmac("sha256", $rawHash, $serectkey);

        //
        $data = array(
            'partnerCode' => $partnerCode,
            'partnerName' => $this->partner_name,
            "storeId" => $this->store_id,
            'requestId' => $requestId,
            'amount' => $amount . "",
            'orderId' => $orderId . "",
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => 'vi',
            'extraData' => $extraData,
            'requestType' => $requestType,
            'signature' => $signature
        );
        //print_r( $data );
        $result = $this->execPostRequest($this->endpoint, json_encode($data));
        //print_r( $result );
        //die( __CLASS__ . ':' . __LINE__ );
        $jsonResult = json_decode($result, true); // decode json

        //Just a example, please check more in there

        //header( 'Location: ' . $jsonResult[ 'payUrl' ] );

        //
        if (isset($jsonResult['payUrl'])) {
            return $jsonResult['payUrl'];
        }
        //print_r( $result );
        //print_r( $jsonResult );
        die(json_encode([
            'result' => $result,
            'json' => $jsonResult,
            'error' => 'payUrl?',
            'code' => __CLASS__ . ':' . __LINE__
        ]));
        //return get_site_url();
    }

    /*
     * v2
     */
    public function redirect_v2($order_id, $requestType = 'captureMoMoWallet')
    {
        $order = new \WC_Order($order_id);

        //
        $forenamefw = $order->get_billing_first_name();
        $forename = $this->convert_vi_to_en($forenamefw);
        $surnamefw = $order->get_billing_last_name();
        $surname = $this->convert_vi_to_en($surnamefw);
        $mobile = $order->get_billing_phone();
        $emailfw = $order->get_billing_email();
        $email = $this->convert_vi_to_en($emailfw);

        //
        //print_r( $order->order_total );

        //$amount = number_format( $order->order_total, 2, '.', '' ) * 100;
        $amount = $order->order_total;
        //echo $amount . '<br>' . "\n";
        $this->endpoint = 'https://' . $this->endpoint . '/gw_payment/transactionProcessor';
        //die( __CLASS__ . ':' . __LINE__ );

        /*
         * custom code for momo v2
         */
        $partnerCode = $this->partner_code;
        $accessKey = $this->access_key;
        $serectkey = $this->secretkey;
        $orderId = $order_id; // Mã đơn hàng
        $orderInfo = 'Ma giao dich thanh toan:' . $order_id . '-' . 'Ho va ten KH:' . $surname . ' ' . $forename . '-' . 'SDT:' . $mobile . '-' . 'Email:' . $email;
        //$amount = sanitize_text_field( $_POST[ "amount" ] );
        $returnUrl = admin_url('admin-ajax.php') . '?action=payment_momo_response&type=international';
        $notifyurl = $returnUrl;
        //$extraData = 'merchantName=MoMo Partner';

        $requestId = time() . "";
        $extraData = (isset($_POST["extraData"]) ? sanitize_text_field($_POST["extraData"]) : "");
        //before sign HMAC SHA256 signature
        $rawHash = "partnerCode=" . $partnerCode . "&accessKey=" . $accessKey . "&requestId=" . $requestId . "&amount=" . $amount . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&returnUrl=" . $returnUrl . "&notifyUrl=" . $notifyurl . "&extraData=" . $extraData;
        $signature = hash_hmac("sha256", $rawHash, $serectkey);
        $data = array('partnerCode' => $partnerCode,
            'accessKey' => $accessKey,
            'requestId' => $requestId,
            'amount' => $amount . "",
            'orderId' => $orderId . "",
            'orderInfo' => $orderInfo,
            'returnUrl' => $returnUrl,
            'notifyUrl' => $notifyurl,
            'extraData' => $extraData,
            'requestType' => $requestType,
            'signature' => $signature);
        //print_r( $data );
        //die( __CLASS__ . ':' . __LINE__ );
        $result = $this->execPostRequest($this->endpoint, json_encode($data));
        //print_r( $result );
        //die( __CLASS__ . ':' . __LINE__ );
        $jsonResult = json_decode($result, true); // decode json

        //Just a example, please check more in there

        //
        //echo $jsonResult[ 'payUrl' ] . '<br>' . "\n";
        //header( 'Location: ' . $jsonResult[ 'payUrl' ] );
        //die( __CLASS__ . ':' . __LINE__ );

        //
        if (isset($jsonResult['payUrl'])) {
            return $jsonResult['payUrl'];
        }
        //print_r( $result );
        //print_r( $jsonResult );
        die(json_encode([
            'result' => $result,
            'json' => $jsonResult,
            'error' => 'payUrl?',
            'code' => __CLASS__ . ':' . __LINE__
        ]));
        //return get_site_url();
    }

    public function isValidCurrency()
    {
        return in_array(get_woocommerce_currency(), array('VND'));
    }

    function convert_vi_to_en($str)
    {
        $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", 'a', $str);
        $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", 'e', $str);
        $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", 'i', $str);
        $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", 'o', $str);
        $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", 'u', $str);
        $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", 'y', $str);
        $str = preg_replace("/(đ)/", 'd', $str);
        $str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", 'A', $str);
        $str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", 'E', $str);
        $str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", 'I', $str);
        $str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", 'O', $str);
        $str = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", 'U', $str);
        $str = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", 'Y', $str);
        $str = preg_replace("/(Đ)/", 'D', $str);
        return $str;
    }

    public function admin_options()
    {
        if ($this->isValidCurrency()) {
            parent::admin_options();
        } else {
?>
<div class="inline error">
    <p> <strong>
            <?php _e('Gateway Disabled', 'woocommerce'); ?>
        </strong> :
        <?php
            _e('MoMo does not support your store currency. Currently, MoMo only supports VND currency.', 'woocommerce');
        ?>
    </p>
</div>
<?php
        }
    }

}