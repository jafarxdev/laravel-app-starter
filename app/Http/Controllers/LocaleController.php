<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(array_keys(config('starter.locales')))],
        ]);

        $request->session()->put('locale', $validated['locale']);
        $previous = url()->previous();
        $target = parse_url($previous, PHP_URL_HOST) === $request->getHost() ? $previous : route('home');

        return redirect()->to($target)->withCookie(Cookie::forever('locale', $validated['locale']));
    }
}
