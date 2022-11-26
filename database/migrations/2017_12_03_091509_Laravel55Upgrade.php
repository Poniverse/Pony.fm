<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Laravel55Upgrade extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Artisan::call('cache:clear');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // same thing
        $this->up();
    }
}
