<?php

namespace App\Http\Controllers\Auth;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\ApiUser;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Illuminate\Support\MessageBag;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Auth\Events\Registered;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $result = ApiUser::register(
            (array) $request->only([
                'name',
                'email',
                'password',
                'password_confirmation',
            ])
        );

        $success = \is_object($result) && $result instanceof ApiUser;

        if ($success) {
            Auth::login($result);
            Session::put('token', $result->token);

            // event(new Registered($result));

            Auth::login($result);

            return redirect(RouteServiceProvider::HOME);
        }

        $errors = \is_object($result) && $result instanceof MessageBag
            ? $result->all()
            : [
                'email' => __('Error on register'),
                'message' => __('Error on register'),
            ];

        throw ValidationException::withMessages($errors ?: []);
    }
}
