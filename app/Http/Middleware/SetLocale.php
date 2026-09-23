<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale', $request->cookie('locale', config('app.locale')));
        $supported = array_keys(config('starter.locales'));
        $default = in_array(config('app.locale'), $supported, true) ? config('app.locale') : 'en';

        app()->setLocale(in_array($locale, $supported, true) ? $locale : $default);

        return $next($request);
    }
}
