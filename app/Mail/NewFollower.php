<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Feld0
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

namespace App\Mail;

class NewFollower extends BaseNotification
{
    /**
     * @inheritdoc
     */
    public function build()
    {
        $creatorName = $this->initiatingUser->display_name;

        return $this->renderEmail(
            'new-follower',
            "{$creatorName} is now following you on Pony.fm!",
            [
                'creatorName' => $creatorName,
                'creatorBio' => $this->initiatingUser->bio,
            ]);
    }
}
