<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder {
  /**
   * Run the database seeds.
   */
  public function run(): void {
    DB::table('users')->insert([
      'name' => 'ams_admin',
      'email' => 'ams_admin@gmail.com',
      'password' => Hash::make('ams_admin')
    ]);
  }
}
