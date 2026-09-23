<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMatchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('team1_id')->unsigned();
            $table->foreign('team1_id')->references('id')->on('teams')->onDelete('cascade');
            $table->bigInteger('team2_id')->unsigned();
            $table->foreign('team2_id')->references('id')->on('teams')->onDelete('cascade');
            $table->bigInteger('competition_id')->unsigned();
            $table->foreign('competition_id')->references('id')->on('competitions')->onDelete('cascade');
            $table->dateTime('match_date');
            $table->string('week');
            $table->enum('status',['pending','soon','live','first half','second half','extra time','extra first half','extra last half','penalty kicks','end the match']);
            $table->string('location');
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
        Schema::dropIfExists('matches');
    }
}
