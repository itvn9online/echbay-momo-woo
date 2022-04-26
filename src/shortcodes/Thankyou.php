<?php
/**
 * 
 * 
 * @author daidq - itvn9online@gmail.com
 * @since  1.0.0
 */

namespace momo\ Shortcodes;

class Thankyou {
    public function __construct() {
        add_shortcode( 'momo_thankyou', array( $this, 'callback' ) );
    }


    public function callback( $atts ) {
        $content = "<div style=\"margin-left: 100px;width: 250px;float: left\">";
        $content .= "<div style=\"color: red;font-size: 20px\">" . sanitize_text_field( $_GET[ "message" ] ) . "</div>";
        $content .= "<div>Mã giao dich:&nbsp<b>" . sanitize_text_field( $_GET[ "orderId" ] ) . "</b></div>";
        $content .= "<div>Số tiền: &nbsp<b>" . sanitize_text_field( $_GET[ "amount" ] ) . "</b></div>";
        $content .= "<div><a style=\"color: green\" href=" . get_site_url() . ">Về trang chủ</a></div>";
        $content .= "</div>";
        esc_html_e( $content );
    }

}