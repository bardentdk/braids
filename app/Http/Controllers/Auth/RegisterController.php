<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Client;
use App\Models\User;
use App\Enums\UserRole;
use App\Services\BrevoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function __construct(protected BrevoService $brevo) {}

    public function show(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name'     => $data['first_name'] . ' ' . $data['last_name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => UserRole::Client,
            'phone'    => $data['phone'] ?? null,
            'is_active'=> true,
        ]);

        Client::create([
            'user_id'    => $user->id,
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
            'newsletter' => $data['newsletter'] ?? false,
        ]);

        // Email de bienvenue
        try {
            $html = view('emails.welcome', ['user' => $user, 'firstName' => $data['first_name']])->render();
            $this->brevo->send(
                toEmail:     $user->email,
                toName:      $user->name,
                subject:     'Bienvenue chez Patricia Braids — Votre compte est créé',
                htmlContent: $html,
            );
        } catch (\Throwable) {}

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')->with('success', 'Bienvenue ! Votre compte a été créé.');
    }
}