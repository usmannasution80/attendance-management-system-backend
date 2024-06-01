<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Custom\Constants;

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
  $grade = 10;
  while($grade <= 12){
    $sql = 'INSERT INTO '.env('DB_DATABASE', 'ams').'.users (name, grade, department, class) VALUES ';
    foreach($departments as $department){
      $count = 1;
      while($count <= Constants::DEPARTMENTS[$department] * 35){
        $class = ceil($count / 35);
        $sql .= '('
              . "'John $grade $department $class ___',"
              . "'$grade',"
              . "'$department',"
              . "'$class'),";
        $count++;
      }
    }
    $sql = preg_replace('/,$/i', '', $sql);
    $con = mysqli_connect(
      env('DB_HOST'),
      env('DB_USERNAME'),
      env('DB_PASSWORD'),
      env('DB_DATABASE')
    );
    mysqli_query($con, $sql);
    $grade++;
  }
});