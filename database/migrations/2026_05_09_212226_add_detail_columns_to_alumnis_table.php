<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('alumnis', function (Blueprint $table) {

            $table->date('tanggal_lahir')->nullable()->after('angkatan');

            $table->string('nomor_wa')
                ->nullable()
                ->after('tanggal_lahir');

            $table->string('email')
                ->nullable()
                ->after('nomor_wa');

            $table->string('alamat')
                ->nullable()
                ->after('email');

        });
    }

    public function down(): void
    {
        Schema::table('alumnis', function (Blueprint $table) {

            $table->dropColumn([
                'tanggal_lahir',
                'nomor_wa',
                'email'
            ]);

        });
    }
};
