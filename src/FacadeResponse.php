<?php
/**
 * 
 * @author daidq - itvn9online@gmail.com
 * @since  1.0.2
 */

namespace momo\Facades;

interface FacadeResponse
{

    public function getResponseDescription($responseCode);

    public function checkResponse($txnResponseCode);

    public function ipn_url_momo($txnResponfseCode);

    public function getOrder($orderId);
}