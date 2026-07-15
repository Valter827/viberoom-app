<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'display_name' => ['nullable', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:32', 'min:2', 'alpha_dash', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'birth_day' => ['required', 'integer', 'between:1,31'],
            'birth_month' => ['required', 'integer', 'between:1,12'],
            'birth_year' => ['required', 'integer', 'between:1900,'.(now()->year - 13)],
            'marketing_opt_in' => ['nullable', 'boolean'],
        ]);

        $birthdate = null;
        try {
            $birthdate = \Carbon\Carbon::createFromDate(
                (int) $request->birth_year,
                (int) $request->birth_month,
                (int) $request->birth_day
            );
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'birth_day' => __('Please enter a valid date of birth.'),
            ]);
        }

        if ($birthdate->diffInYears(now()) < 13) {
            throw ValidationException::withMessages([
                'birth_day' => __('You must be at least 13 years old to register.'),
            ]);
        }

        $user = User::create([
            'name' => $request->display_name ?: $request->username,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'birthdate' => $birthdate,
            'marketing_opt_in' => $request->boolean('marketing_opt_in'),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
