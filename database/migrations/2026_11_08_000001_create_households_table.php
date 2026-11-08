<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data kependudukan per kepala keluarga (KK).
     */
    public function up(): void
    {
        Schema::create('households', function (Blueprint $table) {
            $table->id();
            $table->string('head_name', 100);
            $table->string('family_card_number', 20)->nullable()->unique();
            $table->string('head_nik', 20)->nullable();
            $table->string('birth_place', 60)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('religion', 20)->nullable();
            $table->string('occupation', 60)->nullable();
            $table->string('street', 200)->nullable();
            $table->string('block', 20)->nullable()->index();
            $table->string('house_number', 10)->nullable();
            $table->unsignedTinyInteger('members_count')->default(1);
            $table->string('residency_status', 10)->default('owner'); // owner | tenant
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('emergency_contact_name', 100)->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('household_id')->references('id')->on('households')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['household_id']);
        });
        Schema::dropIfExists('households');
    }
};
