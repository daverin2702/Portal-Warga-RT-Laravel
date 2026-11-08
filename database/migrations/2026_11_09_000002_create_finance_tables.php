<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jenis tagihan (IPL, Kas RT, dan jenis lain yang ditambahkan Bendahara)
        Schema::create('bill_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->string('description', 150)->nullable();
            $table->unsignedInteger('amount')->default(0);
            $table->string('allocation', 80)->default('Kas umum'); // masuk ke alokasi kas ini saat lunas
            $table->boolean('mandatory')->default(false);          // ikut terbit saat "Terbitkan tagihan wajib"
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Tagihan per keluarga per periode
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bill_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 60);              // salinan nama jenis tagihan
            $table->char('period', 7)->index();      // YYYY-MM
            $table->string('allocation', 80);
            $table->unsignedInteger('amount');
            $table->string('status', 12)->default('belum')->index(); // belum | menunggu | lunas
            $table->string('payment_method', 40)->nullable();
            $table->string('proof_path')->nullable(); // disimpan privat di storage/app/private
            $table->timestamp('proof_uploaded_at')->nullable();
            $table->string('rejection_note', 200)->nullable();
            $table->string('receipt_number', 30)->nullable()->unique();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['household_id', 'name', 'period']);
        });

        // Buku kas: pemasukan langsung berlaku; pengeluaran lewat persetujuan Wakil dan Ketua
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->string('type', 8);                // masuk | keluar
            $table->string('category', 60);
            $table->string('allocation', 80)->default('Kas umum');
            $table->string('description', 200)->nullable();
            $table->unsignedInteger('amount');
            $table->string('status', 12)->default('disetujui')->index(); // diajukan | diperiksa | disetujui | ditolak
            $table->foreignId('bill_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('log')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
        Schema::dropIfExists('bills');
        Schema::dropIfExists('bill_types');
    }
};
