<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Custom\Constants;
use Illuminate\Database\Eloquent\Factories\Sequence;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
  $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('create_admin {name} {email} {password}', function($name, $email, $password){
  $user = new User();
  $user->name = $name;
  $user->email = $email;
  $user->password = Hash::make($password);
  $user->save();
  echo 'New admin created!';
});

Artisan::command('generate_random_users', function(){
  $departments = array_keys(Constants::DEPARTMENTS);
  User::factory()
    ->count(1000)
    ->state(new Sequence(
      fn(Sequence $sequence) => [
        'grade' => rand(10, 12),
        'department' => $departments[rand(0, count($departments)-1)],
        'class' => 1
      ]
    ))
    ->create();
  echo 'Users generated!';
});