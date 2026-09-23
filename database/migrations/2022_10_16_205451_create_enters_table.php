<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEntersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('enters', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('match_event_id')->unsigned();
            $table->foreign('match_event_id')->references('id')->on('match_events')->onDelete('cascade');
            $table->bigInteger('player_id')->unsigned()->nullable();
            $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('enters');
    }
}
