<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('username', 50)->unique();
            $table->string('password', 255)->nullable();
            $table->string('real_name', 50);
            $table->string('role_id', 20)->unique();
            $table->string('role', 20);
            $table->string('college', 100);
            $table->string('class', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
        });

        DB::table('user')->insert([
            'username' => 'admin',
            'password' => Hash::make('happylab'), // 请替换为实际的管理员密码
            'real_name' => '默认管理员',
            'role_id' => 'admin001',
            'role' => '管理员',
            'college' => '人工智能学院',
            'phone' => '1234567890',
            'created_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user');
    }
}
