<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Peter Deltchev
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

namespace Poniverse\Ponyfm\Commands;

use Notification;
use Poniverse\Ponyfm\Contracts\Favouritable;
use Poniverse\Ponyfm\Models\Favourite;
use Poniverse\Ponyfm\Models\Track;
use Poniverse\Ponyfm\Models\Album;
use Poniverse\Ponyfm\Models\Playlist;
use Poniverse\Ponyfm\Models\ResourceUser;
use Auth;
use DB;

class ToggleFavouriteCommand extends CommandBase
{
    private $_resourceType;
    private $_resourceId;

    public function __construct($resourceType, $resourceId)
    {
        $this->_resourceId = $resourceId;
        $this->_resourceType = $resourceType;
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        $user = Auth::user();

        return $user != null;
    }

    private function getEntityBeingFavourited():Favouritable
    {
        switch ($this->_resourceType) {
            case 'track':
                return Track::find($this->_resourceId);
            case 'album':
                return Album::find($this->_resourceId);
            case 'playlist':
                return Playlist::find($this->_resourceId);
            default:
                throw new \InvalidArgumentException('Unknown resource type given!');
        }
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        $typeId = $this->_resourceType.'_id';
        $existing = Favourite::where($typeId, '=', $this->_resourceId)->whereUserId(Auth::user()->id)->first();
        $isFavourited = false;

        if ($existing) {
            $existing->delete();
        } else {
            $fav = new Favourite();
            $fav->$typeId = $this->_resourceId;
            $fav->user_id = Auth::user()->id;
            $fav->save();
            $isFavourited = true;

            Notification::newFavourite($this->getEntityBeingFavourited(), $fav->user);
        }

        $resourceUser = ResourceUser::get(Auth::user()->id, $this->_resourceType, $this->_resourceId);
        $resourceUser->is_favourited = $isFavourited;
        $resourceUser->save();

        $resourceTable = $this->_resourceType.'s';

        // We do this to prevent a race condition. Sure I could simply increment the count columns and re-save back to the db
        // but that would require an additional SELECT and the operation would be non-atomic. If two log items are created
        // for the same resource at the same time, the cached values will still be correct with this method.

        DB::table($resourceTable)->whereId($this->_resourceId)->update([
            'favourite_count' =>
                DB::raw('(
                    SELECT
                        COUNT(id)
                    FROM
                        favourites
                    WHERE ' .
                    $typeId.' = '.$this->_resourceId.')')
        ]);

        return CommandResponse::succeed(['is_favourited' => $isFavourited]);
    }
}
