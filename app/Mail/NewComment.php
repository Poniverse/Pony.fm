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

namespace Poniverse\Ponyfm\Mail;


class NewComment extends BaseNotification
{
    /**
     * @inheritdoc
     */
    public function build()
    {
        $creatorName = $this->initiatingUser->display_name;

        // Profile comments get a different template and subject line from
        // other types of comments.
        if ($this->activityRecord->isProfileComment()) {
            return $this->renderEmail(
                'new-comment-profile',
                $this->activityRecord->text, [
                    'creatorName' => $creatorName,
                    'comment' => $this->activityRecord->resource->content,
            ]);
        } else {
            return $this->renderEmail(
                'new-comment-content',
                $this->activityRecord->text, [
                'creatorName' => $creatorName,
                'resourceType' => $this->activityRecord->getResourceTypeString(),
                'resourceTitle' => $this->activityRecord->resource->resource->title,
                'comment' => $this->activityRecord->resource->content,
            ]);
        }

    }
}
