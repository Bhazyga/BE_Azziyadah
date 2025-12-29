<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('santris', function (Blueprint $table) {
            $table->string('foto_kk')->nullable()->after('alamat_sekolah_asal');
            $table->string('foto_akte')->nullable()->after('foto_kk');
        });
    }

    public function down()
    {
        Schema::table('santris', function (Blueprint $table) {
            $table->dropColumn(['foto_kk', 'foto_akte']);
        });
    }
};
