<?php

namespace App\Application\Http\Controllers\Auth;

use App\Application\Http\Controllers\Controller;
use App\Domain\User\Services\UserAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    private UserAuthService $authService;
    
    public function __construct(UserAuthService $authService)
    {
        $this->authService = $authService;
    }
    /**
     * Display the password reset link request view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        try {
            // Используем сервис для отправки ссылки согласно DDD
            $status = $this->authService->sendPasswordResetLinkViaFacade($request->input('email'));
            
            return back()->with('status', __($status));
            
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'email' => ['Ошибка при отправке ссылки для сброса пароля'],
            ]);
        }
    }
}
