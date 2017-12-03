<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Laravel55Upgrade extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        app()->call('cache:clear');
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
