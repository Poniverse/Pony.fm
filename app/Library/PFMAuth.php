<?php
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Hashing\Hasher;

class NullHasher implements Hasher
{
    public function make($value, array $options = array())
    {
    }

    public function check($value, $hashedValue, array $options = array())
    {
    }

    public function needsRehash($hashedValue, array $options = array())
    {
    }
}

class PFMAuth extends EloquentUserProvider
{
    function __construct()
    {
        parent::__construct(new NullHasher(), 'App\User');
    }
}