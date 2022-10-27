<?php
/**
 * 
 * @author daidq - itvn9online@gmail.com
 * @since  1.0.1
 */

namespace momo\Traits;

trait Pages
{
    protected $pages = array(
        'thank-you' => array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => 'Payment Success',
            'post_content' => ''
        )
    );
}