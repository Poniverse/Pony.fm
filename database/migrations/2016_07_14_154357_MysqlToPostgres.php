<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Symfony\Component\Console\Output\ConsoleOutput;

class MysqlToPostgres extends Migration
{
    private $console;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->console = new ConsoleOutput();

        // Generate pgloader config
        $mysqlConnection = "from mysql://" . env('DB_USERNAME') . ":" . env('DB_PASSWORD') . "@" . env('DB_HOST') . "/" . env('DB_DATABASE');
        $postgresConnection = "into postgresql://" . env('POSTGRESQL_DB_USERNAME', 'homestead') . ":" . env('POSTGRESQL_DB_PASSWORD', 'secret') . "@" . env('POSTGRESQL_DB_HOST', 'localhost') . "/" . env('POSTGRESQL_DB_DATABASE', 'homestead');

        $header = "LOAD DATABASE";
        $body = <<<'EOD'
with truncate

CAST type datetime to timestamp using zero-dates-to-null,
     type date to timestamp using zero-dates-to-null

EXCLUDING TABLE NAMES MATCHING 'migrations';
EOD;

        $output = implode("\n", array($header, $mysqlConnection, $postgresConnection, $body));
        $configPath = base_path() . "/pfmimport.load";
        file_put_contents($configPath, $output);

        // Run pgloader
        $this->execRunWithCallback("pgloader " . $configPath);

        // Run after-import.sql
        DB::unprepared(file_get_contents(base_path() . "/database/after-import.sql"));

        // Remove pgloader config
        unlink($configPath);
    }

    private function execRunWithCallback($command)
    {
        $array = array();
        exec($command, $array);

        if (!empty($array)) {
            foreach ($array as $line) {
                $this->execCallback($line);
            }
        }
    }

    private function execCallback($line) {
        $this->console->writeln("[PGLOADER] " . $line);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
