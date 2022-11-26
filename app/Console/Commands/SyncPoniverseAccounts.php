<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Feld0.
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

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use League\OAuth2\Client\Token\AccessToken;
use Poniverse\Lib\Client;

class SyncPoniverseAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounts:sync-with-poniverse';

    /**
     * @var Client
     */
    protected $poniverse;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ensures each Pony.fm account has a valid refresh token and email address from Poniverse on file.';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->poniverse = new Client(
            config('poniverse.client_id'), config('poniverse.secret'), new \GuzzleHttp\Client());
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $usersToUpdate = User::whereLinkedToPoniverse();

        $progress = $this->output->createProgressBar($usersToUpdate->count());
        $progress->setFormat(
'<info>%message%</info>
%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%');

        $usersToUpdate
            ->orderBy('id')
            ->chunk(100, function ($users) use ($progress) {
                /** @var User $user */
                foreach ($users as $user) {
                    $progress->setMessage("Updating user ID {$user->id}...");
                    $progress->advance();

                    $this->poniverse->poniverse()->meta()
                    ->syncAccount(
                        $user->getAccessToken()->getResourceOwnerId(),
                        function (AccessToken $accessTokenInfo) use ($user) {
                            $user->setAccessToken($accessTokenInfo);
                        },
                        function (string $newEmailAddress) use ($user) {
                            $user->email = $newEmailAddress;
                            $user->save();
                        });
                }
            });

        $progress->finish();
        $this->line('');
        $this->info('All done!');

        return 0;
    }
}
