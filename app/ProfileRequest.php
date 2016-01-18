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

namespace Poniverse\Ponyfm;

use DB;

class ProfileRequest
{
    private $_id = null;
    private $_data = null;

    public static function load($data)
    {
        $req = new ProfileRequest();
        $req->_data = json_decode($data);

        return $req;
    }

    /**
     * @return ProfileRequest
     */
    public static function create()
    {
        $req = new ProfileRequest();
        $req->_id = uniqid();

        return $req;
    }

    private function __construct()
    {
        $this->_data = ['log' => []];
    }

    public function toArray()
    {
        return $this->_data;
    }

    public function toString()
    {
        return json_encode($this->_data);
    }

    public function getId()
    {
        return $this->_id;
    }

    public function recordQueries()
    {
        $this->_data['queries'] = [];

        foreach (DB::getQueryLog() as $query) {
            if (starts_with($query['query'], 'select * from `cache` where')) {
                continue;
            }

            if (starts_with($query['query'], 'delete from `cache` where')) {
                continue;
            }

            if (starts_with($query['query'], 'insert into `cache`')) {
                continue;
            }

            $this->_data['queries'][] = $query;
        }
    }

    /**
     * @param string $level
     * @param string $message
     */
    public function log($level, $message, $context)
    {
        $this->_data['log'][] = [
            'level' => $level,
            'message' => $message
        ];
    }
}
