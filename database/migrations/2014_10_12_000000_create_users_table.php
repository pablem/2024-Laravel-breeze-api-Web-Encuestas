<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // DB::statement("CREATE TYPE user_role AS ENUM ('admin', 'editor', 'publicador')");

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            // $table->unsignedSmallInteger('telefono')->nullable();
            // $table->enum('role', ['admin', 'editor', 'publicador']);
            $table->string('role')->default(UserRole::Editor->value);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            //Índices
            $table->index('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // DB::statement("DROP TYPE IF EXISTS user_role CASCADE");
        Schema::dropIfExists('users');
    }
};
