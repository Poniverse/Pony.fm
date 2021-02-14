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

namespace Poniverse\Ponyfm\Commands;

use Auth;
use Notification;
use Poniverse\Ponyfm\Models\Follower;
use Poniverse\Ponyfm\Models\ResourceUser;

class ToggleFollowingCommand extends CommandBase
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

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        $typeId = $this->_resourceType.'_id';
        $existing = Follower::where($typeId, '=', $this->_resourceId)->whereUserId(Auth::user()->id)->first();
        $isFollowed = false;

        if ($existing) {
            $existing->delete();
        } else {
            $follow = new Follower();
            $follow->$typeId = $this->_resourceId;
            $follow->user_id = Auth::user()->id;
            $follow->save();
            $isFollowed = true;

            Notification::newFollower($follow->artist, Auth::user());
        }

        $resourceUser = ResourceUser::get(Auth::user()->id, $this->_resourceType, $this->_resourceId);
        $resourceUser->is_followed = $isFollowed;
        $resourceUser->save();

        return CommandResponse::succeed(['is_followed' => $isFollowed]);
    }
}
