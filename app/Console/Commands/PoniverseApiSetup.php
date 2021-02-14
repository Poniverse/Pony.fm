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

namespace Poniverse\Ponyfm\Console\Commands;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Console\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class PoniverseApiSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'poni:api-setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calls upon Pixel Wavelength for a Poniverse API key.';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->output->getFormatter()->setStyle('bold', new OutputFormatterStyle(null, null, ['bold']));

        $this->comment('Sign in with your Poniverse account! Your password won\'t be stored locally.');
        $this->line('');
        $this->comment('This sets up your Poniverse API credentials, which are necessary for Pony.fm\'s integration with Poniverse to work.');
        $this->line('');
        $this->comment('If you don\'t have a Poniverse account, create one at: <bold>https://poniverse.net/register</bold>');
        $username = $this->ask('Your Poniverse username');
        $password = $this->secret('Your Poniverse password');

        // log in
        $client = new Client(['base_uri' => 'https://api.poniverse.net/v1/dev/']);

        try {
            $response = $client->post('api-credentials', [
                'headers' => ['accept' => 'application/json'],
                'auth' => [$username, $password],
                'query' => ['app' => 'Pony.fm'],
            ]);
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 401) {
                $this->error('Incorrect username or password! Please try again.');
                exit();
            } else {
                var_dump($e->getResponse()->getBody());
                throw $e;
            }
        }

        $json = json_decode($response->getBody());
        $clientId = $json->id;
        $clientSecret = $json->secret;

        // save new key to .env
        $this->setEnvironmentVariable('PONI_CLIENT_ID', $this->laravel['config']['poniverse.client_id'], $clientId);
        $this->setEnvironmentVariable('PONI_CLIENT_SECRET', $this->laravel['config']['poniverse.secret'], $clientSecret);

        $this->info('Client ID and secret set!');
    }

    protected function setEnvironmentVariable($key, $oldValue, $newValue)
    {
        $path = base_path('.env');

        // Detect the specific "null" value.
        if ($oldValue === null) {
            $oldValue = 'null';
        }

        if (file_exists($path)) {
            file_put_contents($path, str_replace(
                "$key=".$oldValue,
                "$key=".$newValue,
                file_get_contents($path)
            ));
        } else {
            $this->error('Please run `vagrant up`!');
        }
    }
}
