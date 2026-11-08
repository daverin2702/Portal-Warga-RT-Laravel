<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Templat surat: isi HTML dengan variabel {{...}} dan kolom tambahan yang diisi saat pengajuan.
        Schema::create('letter_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->longText('body');
            $table->json('fields')->nullable();
            $table->timestamps();
        });

        // Surat: draf manual buatan Sekretaris maupun surat terbit dari pengajuan warga.
        Schema::create('letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('letter_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('household_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('number', 80)->index();
            $table->string('title', 150);
            $table->longText('body');
            $table->string('purpose', 250)->nullable();
            $table->boolean('with_letterhead')->default(true);
            $table->boolean('qr_signature')->default(true);
            $table->string('status', 20)->default('draf')->index(); // draf | diperiksa | terbit | ditolak
            $table->string('token', 32)->unique();
            $table->string('signer_name', 100)->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->json('log')->nullable();
            $table->timestamps();
        });

        // Pengajuan surat oleh warga.
        Schema::create('letter_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('letter_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('template_name', 100);
            $table->string('purpose', 250);
            $table->json('field_values')->nullable();
            $table->string('status', 20)->default('diajukan')->index(); // diajukan | diteruskan | diperiksa | disetujui | ditolak
            $table->string('note', 250)->nullable();
            $table->foreignId('letter_id')->nullable()->constrained()->nullOnDelete();
            $table->json('log')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_requests');
        Schema::dropIfExists('letters');
        Schema::dropIfExists('letter_templates');
    }
};
