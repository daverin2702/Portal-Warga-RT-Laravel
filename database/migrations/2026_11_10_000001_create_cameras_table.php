<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cameras', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('area', 60)->nullable()->index();    // pengelompokan, mis. "Gerbang", "Blok A"
            $table->string('location', 150)->nullable();         // titik pemasangan
            $table->string('description', 250)->nullable();
            $table->string('brand', 40)->nullable();
            $table->string('type', 12);                          // rtsp | hls | mjpeg | snapshot | embed | youtube
            $table->text('source');                              // URL sumber, terenkripsi (bisa berisi sandi kamera)
            $table->string('stream_key', 60)->nullable()->unique(); // nama aliran di gateway (khusus RTSP)
            $table->unsignedSmallInteger('refresh_seconds')->default(5); // khusus snapshot
            $table->boolean('via_server')->default(true);        // snapshot diambil lewat server portal (sandi tidak terlihat)
            $table->string('visibility', 10)->default('semua'); // semua | pengurus
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->string('last_status', 10)->nullable();       // online | offline
            $table->string('last_message', 200)->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cameras');
    }
};
