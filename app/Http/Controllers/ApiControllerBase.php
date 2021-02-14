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

namespace Poniverse\Ponyfm\Http\Controllers;

use Poniverse\Ponyfm\Commands\CommandBase;
use Response;

abstract class ApiControllerBase extends Controller
{
    /**
     * NOTE: This function is used by the v1 API. If the response JSON format
     * it returns changes, don't break the API!
     *
     * @param CommandBase $command
     * @return \Illuminate\Http\JsonResponse
     */
    protected function execute(CommandBase $command)
    {
        if (! $command->authorize()) {
            return $this->notAuthorized();
        }

        $result = $command->execute();
        if ($result->didFail()) {
            return Response::json([
                'message' => 'Validation failed',
                'errors' => $result->getMessages(),
            ], $result->getStatusCode());
        }

        return Response::json($result->getResponse(), $result->getStatusCode());
    }

    public function notAuthorized()
    {
        return Response::json(['message' => 'You may not do this!'], 403);
    }

    public function notFound($message)
    {
        return Response::json(['message' => $message], 403);
    }
}
