<?php


namespace App\Util;


class HttpUtil
{
    public static function urlExists($url)
    {
        stream_context_set_default(array('http' => array('method' => 'HEAD')));
        $headers = get_headers($url);
        $result = false;
        if(!empty($headers)) {
            $result = strpos($headers[0], '200 OK') !== false;
        }
        stream_context_set_default(array('http' => array('method' => 'GET')));
        return $result;
    }
}
