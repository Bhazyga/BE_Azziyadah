<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kegiatans', function (Blueprint $table) {

            $table->string('slug')->nullable()->after('judul');

            $table->enum('jenjang', ['MI', 'MTS', 'MA', 'YAYASAN'])->change();
        });
    }

    public function down(): void
    {
        Schema::table('kegiatans', function (Blueprint $table) {
            $table->dropColumn('slug');

            $table->enum('jenjang', ['MI', 'MTS', 'MA'])->change();
        });
    }
};
