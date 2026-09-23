<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMatchEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('match_events', function (Blueprint $table) {
            $table->id();
            $table->string('minute');
            // $table->enum('status',[
            //     'start match',
            //     'comment',
            //     'enter',
            //     'exit',
            //     'yellow card',
            //     'red card',
            //     'goal',
            //     'end first half',
            //     'start second half',
            //     'end second half',
            //     'start third half',
            //     'end third half',
            //     'start forth half',
            //     'end forth half',
            //     'penalty kicks',
            //     'end match'
            // ]);
            $table->string('status');
            $table->string('player_name')->nullable();
            $table->bigInteger('player_id')->unsigned()->nullable();
            $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
            $table->bigInteger('team_id')->unsigned()->nullable();
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            $table->bigInteger('match_id')->unsigned();
            $table->foreign('match_id')->references('id')->on('matches')->onDelete('cascade');
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
        Schema::dropIfExists('match_events');
    }
}
