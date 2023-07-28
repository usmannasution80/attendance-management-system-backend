<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\App;

class CheckLanguage{
  /**
   * Handle an incoming request.
   *
   * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
   */
  public function handle(Request $request, Closure $next): Response{
    if(isset($_COOKIE['lang'])){
      if(in_array($_COOKIE['lang'], ['id', 'en'])){
        App::setLocale($_COOKIE['lang']);
      }
    }
    return $next($request);
  }
}
