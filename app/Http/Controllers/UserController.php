<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller {
  public function get(string $id){
    return User::find($id);
  }
  public function create(){
    
  }
}
