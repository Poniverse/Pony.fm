<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Feld0.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Http\Controllers;

use Illuminate\Support\Facades\App;
use App\Models\Image;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;

class ImagesController extends Controller
{
    public function getImage($id, $type, $extension)
    {
        $coverType = Image::getImageTypeFromName($type);

        if ($coverType == null) {
            abort(404);
        }

        $image = Image::find($id);
        if (! $image) {
            abort(404);
        }

        $response = response('', 200);
        $filename = $image->getFile($coverType['id']);

        if (! is_file($filename)) {
            $redirect = url('/images/icons/profile_'.Image::$ImageTypes[$coverType['id']]['name'].'.png');

            return redirect($redirect);
        }

        if (config('app.sendfile')) {
            $response->header('X-Sendfile', $filename);
        } else {
            $response->header('X-Accel-Redirect', $filename);
        }

        $response->header('Content-Disposition', "filename=\"ponyfm-i${id}-${type}.{$image->extension}\"");
        $response->header('Content-Type', $image->mime);

        $lastModified = filemtime($filename);

        $response->header('Last-Modified', $lastModified);
        $response->header('Cache-Control', 'max-age='.(60 * 60 * 24 * 7));

        return $response;
    }
}
