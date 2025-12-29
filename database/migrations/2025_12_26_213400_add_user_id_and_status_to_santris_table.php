<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('santris', function (Blueprint $table) {

            // 🔗 Relasi ke users (1 user = 1 santri)
            $table->unsignedBigInteger('user_id')
                ->after('id')
                ->unique();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // 🟡 Status santri
            $table->tinyInteger('status')
                ->default(0)
                ->after('grade_id')
                ->comment('0=draft,1=form_paid,2=active');
        });
    }

    public function down(): void
    {
        Schema::table('santris', function (Blueprint $table) {

            // Drop FK dulu
            $table->dropForeign(['user_id']);

            // Drop kolom
            $table->dropUnique(['user_id']);
            $table->dropColumn('user_id');

            $table->dropColumn('status');
        });
    }
};
