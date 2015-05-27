<?php

class FrmHubAPIAppHelper{
    public static function generate($chars = 4, $num_segments = 4) {
        global $wpdb;

        $tokens = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $key_string = '';

        for ($i = 0; $i < $num_segments; $i++){
            $segment = '';

            for ($j = 0; $j < $chars; $j++){
                $segment .= $tokens[rand(0, 35)];
            }

            $key_string .= $segment;

            if ($i < ($num_segments - 1))
                $key_string .= '-';
        }

        return $key_string;
    }

    public static function is_frm_route() {
        return ( strpos( $_SERVER['REQUEST_URI'], '/frm/' ) === false ) ? false : true;
    }
}
