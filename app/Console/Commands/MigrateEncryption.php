<?php

namespace Poniverse\Ponyfm\Console\Commands;

use DB;
use Illuminate\Console\Command;
use Laravel\LegacyEncrypter\McryptEncrypter;

class MigrateEncryption extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate-encryption';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates from mcrypt to openssl. Needs key and mcryptKey set in config/app.php';

    /**
     * Create a new command instance.
     *
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
        $key = $this->laravel['config']['app.mcryptKey'];
        $cipher = $this->laravel['config']['app.mcryptCipher'];

        $legacy = new McryptEncrypter($key, $cipher);

        $caches = DB::select('SELECT * FROM cache');

        foreach ($caches as $cache) {
            $newValue = encrypt(
                $legacy->decrypt($cache->value)
            );

            DB::update('UPDATE cache SET value = ? WHERE key = ?', [$cache->key, $newValue]);

            $this->info("Updated {$cache->key}");
        }
    }
}
