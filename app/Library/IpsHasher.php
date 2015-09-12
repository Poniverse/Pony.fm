<?php

use Illuminate\Contracts\Hashing\Hasher;

class IpsHasher implements Hasher
{
    public function make($value, array $options = array())
    {
        return md5(md5($options['salt']) . md5(static::ips_sanitize($value)));
    }

    public function check($value, $hashedValue, array $options = array())
    {
        return static::make($value, ['salt' => $options['salt']]) === $hashedValue;
    }

    public function needsRehash($hashedValue, array $options = array())
    {
        return false;
    }

    static public function ips_sanitize($value)
    {
        $value = str_replace('&', '&amp;', $value);
        $value = str_replace('\\', '&#092;', $value);
        $value = str_replace('!', '&#33;', $value);
        $value = str_replace('$', '&#036;', $value);
        $value = str_replace('"', '&quot;', $value);
        $value = str_replace('<', '&lt;', $value);
        $value = str_replace('>', '&gt;', $value);
        $value = str_replace('\'', '&#39;', $value);

        return $value;
    }
}