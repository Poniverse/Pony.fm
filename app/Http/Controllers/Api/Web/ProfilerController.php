<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\ProfileRequest;
use Cache;
use Config;
use Response;

class ProfilerController extends Controller
{
    public function getRequest($id)
    {
        if (!Config::get('app.debug')) {
            return;
        }

        $key = 'profiler-request-' . $id;
        $request = Cache::get($key);
        if (!$request) {
            exit();
        }

        Cache::forget($key);

        return Response::json(['request' => ProfileRequest::load($request)->toArray()], 200);
    }
}
