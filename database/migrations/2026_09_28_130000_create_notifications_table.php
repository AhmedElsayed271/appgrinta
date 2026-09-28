<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('client_id')->unsigned();
            $table->string('type')->nullable();
            $table->string('image')->nullable();
            $table->text('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
            $table->index(['client_id', 'is_read']);
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });

        Schema::create('notification_translations', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('notification_id')->unsigned();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('locale')->index();
            $table->unique(['notification_id', 'locale']);
            $table->foreign('notification_id')->references('id')->on('notifications')->onDelete('cascade');
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
        Schema::dropIfExists('notification_translations');
        Schema::dropIfExists('notifications');
    }
}