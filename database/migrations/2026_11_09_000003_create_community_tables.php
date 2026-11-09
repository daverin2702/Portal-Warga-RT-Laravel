<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Notifikasi bawaan Laravel (kanal "database")
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->text('body');
            $table->string('category', 20)->default('kegiatan'); // kegiatan | kerja bakti | siskamling | keamanan | lainnya
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 40);
            $table->string('title', 150);
            $table->text('description');
            $table->string('photo_path')->nullable(); // privat, storage/app/private
            $table->string('status', 12)->default('baru')->index(); // baru | diproses | selesai
            $table->string('response', 300)->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('emergencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('message', 300)->nullable();
            $table->string('location', 150)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolved_by', 100)->nullable();
            $table->timestamps();
        });

        // Chat: ruang "umum" (semua), "pengurus", dan "dm-<id kecil>-<id besar>" (pesan pribadi)
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->string('room', 40);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['room', 'id']);
        });

        Schema::create('chat_reads', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('room', 40);
            $table->unsignedBigInteger('last_id')->default(0);
            $table->primary(['user_id', 'room']);
        });
    }

    public function down(): void
    {
        foreach (['chat_reads', 'chat_messages', 'emergencies', 'complaints', 'announcements', 'notifications'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
