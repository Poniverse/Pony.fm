<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Logic.
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

use Gate;
use Poniverse\Ponyfm\Models\Announcement;
use Validator;

class CreateAnnouncementCommand extends CommandBase
{
    /** @var Announcement */
    private $_announcementName;

    public function __construct($announcementName)
    {
        $this->_announcementName = $announcementName;
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        return Gate::allows('create-announcement');
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        $rules = [
            'name' => 'required|max:50',
        ];

        $validator = Validator::make([
            'name' => $this->_announcementName,
        ], $rules);

        if ($validator->fails()) {
            return CommandResponse::fail($validator);
        }

        Announcement::create([
            'title' => $this->_announcementName,
        ]);

        return CommandResponse::succeed(['message' => 'Announcement created!']);
    }
}
