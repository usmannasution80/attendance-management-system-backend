<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AttendanceListController;
use App\Http\Controllers\FileController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;

Route::get('{path}', function(){
  include(public_path().'/index.html');
})->where('path', '^((?!api).)*$');

Route::prefix('api')->group(function(){

  Route::prefix('user')->group(function(){

    Route::get('/', [UserController::class, 'index']);
    Route::get('index', [UserController::class, 'index']);
    Route::post('update', [UserController::class, 'update'])
      ->middleware('admin');
    Route::get('download-cards', [UserController::class, 'download_cards']);
    Route::get('{id}', [UserController::class, 'get'])
      ->where('id', '[0-9]+');
    Route::post('create', [UserController::class, 'create'])
      ->middleware('admin');
    Route::post('delete/{id}', [UserController::class, 'delete'])
      ->middleware('admin');

  });

  Route::prefix('attendance')->group(function(){

    Route::post('set', [AttendanceListController::class, 'set']);
    Route::get('/list/{date}', [AttendanceListController::class, 'get']);

  });

});

require __DIR__.'/auth.php';
