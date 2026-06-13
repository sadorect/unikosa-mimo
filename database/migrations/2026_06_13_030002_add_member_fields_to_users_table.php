<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->date('date_of_birth')->nullable()->after('phone');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('date_of_birth');
            $table->foreignId('graduating_set_id')->nullable()->after('gender')->constrained('sets')->nullOnDelete();
            $table->string('house')->nullable()->after('graduating_set_id');
            $table->string('country')->nullable()->after('house');
            $table->string('city')->nullable()->after('country');
            $table->string('profession')->nullable()->after('city');
            $table->text('bio')->nullable()->after('profession');
            $table->json('skills')->nullable()->after('bio');
            $table->json('social_links')->nullable()->after('skills');
            $table->string('avatar')->nullable()->after('social_links');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('avatar');
            $table->foreignId('chapter_id')->nullable()->after('status')->constrained('chapters')->nullOnDelete();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['graduating_set_id']);
            $table->dropForeign(['chapter_id']);
            $table->dropColumn([
                'phone', 'date_of_birth', 'gender', 'graduating_set_id',
                'house', 'country', 'city', 'profession', 'bio',
                'skills', 'social_links', 'avatar', 'status', 'chapter_id',
                'deleted_at',
            ]);
        });
    }
};
