<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTrackSourceColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tracks', function(Blueprint $table){
            $table->string('source', 40)->default('direct_upload');
        });

        // Mark MLPMA tracks retroactively
        // --> The default value in the database, set above, will
        //     be used automatically for all non-MLPMA tracks.
        $tracks = DB::table('tracks')
            ->join('mlpma_tracks', 'mlpma_tracks.track_id', '=', 'tracks.id');

        $tracks->whereNotNull('mlpma_tracks.id')->update(['source' => 'mlpma']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tracks', function(Blueprint $table){
            $table->dropColumn('source');
        });
    }
}
