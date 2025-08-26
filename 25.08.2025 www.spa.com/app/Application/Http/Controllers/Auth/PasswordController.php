<?php

namespace App\Application\Http\Controllers\Auth;

use App\Application\Http\Controllers\Controller;
use App\Domain\User\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        try {
            $result = $this->userService->changePassword(
                $request->user(),
                $validated['current_password'],
                $validated['password']
            );

            if ($result['success']) {
                return back()->with('status', $result['message']);
            } else {
                return back()->withErrors(['current_password' => $result['error']]);
            }

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Ошибка при изменении пароля']);
        }
    }
}
