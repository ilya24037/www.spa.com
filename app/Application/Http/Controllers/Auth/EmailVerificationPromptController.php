<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        // Уже верифицирован? → редирект
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(config('app.home', '/'));
        }

        return view('auth.verify-email');   // Blade; для Inertia замените
    }
}
