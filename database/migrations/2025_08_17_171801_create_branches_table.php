<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->ulid();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('location')->nullable();
            $table->timestamps();
        });

        // Pivot table to link staff to branches
        Schema::create('branch_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_user');
        Schema::dropIfExists('branches');
    }
};
