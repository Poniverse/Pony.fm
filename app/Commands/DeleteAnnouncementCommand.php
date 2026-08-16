<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2026 Feld0.
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

namespace App\Commands;

use App\Models\Announcement;
use Illuminate\Support\Facades\Gate;

class DeleteAnnouncementCommand extends CommandBase
{
    private $_announcement;

    public function __construct(int $announcementId)
    {
        $this->_announcement = Announcement::find($announcementId);
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        return $this->_announcement !== null && Gate::allows('delete-announcement');
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        $this->_announcement->delete();

        return CommandResponse::succeed(['message' => 'Announcement deleted!']);
    }
}
