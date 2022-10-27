<?php
/**
 * 
 * 
 * @author daidq - itvn9online@gmail.com
 * @since  1.0.2
 */

namespace momo\Gateways;

use momo\Responses\momoResponse;
use momo\Gateways\momoGateway;

class momoInternationalResponse extends momoResponse
{

    public function __construct()
    {
        parent::__construct();
    }

    public function getResponseDescription($responseCode = -1)
    {
        if ($responseCode * 1 === 0) {
            $result = "Giao dịch thanh toán thành công qua MoMo";
        } else {
            $result = "Giao dịch không thành công";
        }

        return $result;
    }


    public function thankyou()
    {
        $gateway = new momoGateway;
        return $gateway->get_option('receipt_return_url');
    }


}