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
use Illuminate\Support\Facades\Validator;

class EditAnnouncementCommand extends CommandBase
{
    private $_announcement;
    private $_input;

    public function __construct(int $announcementId, array $input)
    {
        $this->_announcement = Announcement::find($announcementId);
        $this->_input = $input;
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        return $this->_announcement !== null && Gate::allows('edit-announcement');
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        $validator = Validator::make($this->_input, CreateAnnouncementCommand::RULES);

        if ($validator->fails()) {
            return CommandResponse::fail($validator);
        }

        $this->_announcement->update([
            'title' => $this->_input['title'],
            'text_content' => $this->_input['text_content'] ?? '',
            'announcement_type_id' => $this->_input['announcement_type_id'],
            'start_time' => $this->_input['start_time'],
            'end_time' => $this->_input['end_time'],
        ]);

        return CommandResponse::succeed(['message' => 'Announcement updated!']);
    }
}
