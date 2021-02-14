<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Feld0
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

namespace App\Http\Controllers\Api\Web;

use Auth;
use App\Http\Controllers\ApiControllerBase;
use App\Models\Image;
use App\Models\User;
use Response;

class ImagesController extends ApiControllerBase
{
    public function getOwned(User $user)
    {
        $this->authorize('get-images', $user);

        $query = Image::where('uploaded_by', $user->id);
        $images = [];

        foreach ($query->get() as $image) {
            $images[] = [
                'id' => $image->id,
                'urls' => [
                    'small' => $image->getUrl(Image::SMALL),
                    'normal' => $image->getUrl(Image::NORMAL),
                    'thumbnail' => $image->getUrl(Image::THUMBNAIL),
                    'original' => $image->getUrl(Image::ORIGINAL)
                ],
                'filename' => $image->filename
            ];
        }

        return Response::json($images, 200);
    }
}
