<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable {
  use HasApiTokens, HasFactory, Notifiable;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'name',
    'email',
    'grade',
    'department',
    'class',
    'password',
  ];

  /**
   * The attributes that should be hidden for serialization.
   *
   * @var array<int, string>
   */
  protected $hidden = [
    'password',
    'remember_token',
  ];

  /**
   * The attributes that should be cast.
   *
   * @var array<string, string>
   */
  protected $casts = [
    'email_verified_at' => 'datetime',
    'password' => 'hashed',
  ];

  protected $appends = [
    'is_admin',
    'is_teacher',
    'is_student'
  ];

  protected function isAdmin() : Attribute {
    return Attribute::make(
      get : fn () => !!$this->password
    );
  }

  protected function isStudent() : Attribute {
    return Attribute::make(
      get : fn () => !!$this->grade
    );
  }

  protected function isTeacher() : Attribute {
    return Attribute::make(
      get : fn () => !$this->grade
    );
  }

}
