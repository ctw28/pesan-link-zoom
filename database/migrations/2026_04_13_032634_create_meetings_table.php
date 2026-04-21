<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();

            // info kegiatan
            $table->string('topic');

            // waktu
            $table->date('tanggal');
            $table->time('jam_mulai');
            $table->integer('duration'); // menit

            // pemesan
            $table->string('nama_pemesan');
            $table->string('unit');
            $table->string('no_hp');

            // zoom
            $table->text('join_url');
            $table->string('password')->nullable();

            // status
            $table->enum('status', ['scheduled', 'ongoing', 'finished', 'missing_in_zoom', 'deleted'])
                ->default('scheduled');
            $table->foreignId('zoom_account_id')->nullable();
            $table->string('zoom_meeting_id')->nullable();
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
