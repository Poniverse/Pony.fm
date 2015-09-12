<?php

use Illuminate\Support\Facades\URL;

class Gravatar
{
    public static function getUrl($email, $size = 80, $default = null, $rating = 'g')
    {
        $url = 'https://www.gravatar.com/avatar/';
        $url .= md5(strtolower(trim($email)));
        $url .= "?s=$size&r=$rating";

        if ($default != null) {
            $url .= "&d=" . $default;
        } else {
            $size = 'normal';
            if ($size == 50) {
                $size = 'thumbnail';
            } else {
                if ($size == 100) {
                    $size = 'small';
                }
            }

            $url .= "&d=" . urlencode(URL::to('/images/icons/profile_' . $size . '.png'));
        }

        return $url;
    }
}