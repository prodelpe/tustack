<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /** Nobody types a name, an email and a password twice in less than this. */
    private const MINIMUM_SECONDS_TO_FILL = 3;

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
        // A field no person can see: whoever fills it is reading the html, not
        // the page. It is answered as if it had worked, so nothing is learnt.
        if ($request->filled('website')) {
            return redirect()->route('home');
        }

        if ($this->wasFilledTooFast($request)) {
            throw ValidationException::withMessages([
                'name' => __('Please wait a moment and try again.'),
            ]);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }

    private function wasFilledTooFast(Request $request): bool
    {
        try {
            $renderedAt = (int) Crypt::decryptString((string) $request->input('rendered_at'));
        } catch (DecryptException) {
            return true;
        }

        return now()->timestamp - $renderedAt < self::MINIMUM_SECONDS_TO_FILL;
    }
}
