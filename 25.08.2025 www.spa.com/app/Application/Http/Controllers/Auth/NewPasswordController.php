<?php

namespace App\Application\Http\Controllers\Auth;

use App\Application\Http\Controllers\Controller;
use App\Domain\User\Services\UserAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NewPasswordController extends Controller
{
    private UserAuthService $authService;
    
    public function __construct(UserAuthService $authService)
    {
        $this->authService = $authService;
    }
    /**
     * Display the password reset view.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        try {
            // Используем сервис для сброса пароля согласно DDD
            $status = $this->authService->resetPasswordWithToken(
                $request->only('email', 'password', 'password_confirmation', 'token')
            );
            
            return redirect()->route('login')->with('status', __($status));
            
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'email' => ['Ошибка при сбросе пароля'],
            ]);
        }
    }
}
