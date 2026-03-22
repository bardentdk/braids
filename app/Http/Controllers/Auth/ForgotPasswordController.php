<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Models\User;
use App\Services\BrevoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ForgotPasswordController extends Controller
{
    public function __construct(protected BrevoService $brevo) {}

    public function show(Request $request): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function send(ForgotPasswordRequest $request): RedirectResponse
    {
        $user = User::where('email', $request->email)->first();

        // Sécurité : ne pas révéler si l'email existe
        if (! $user) {
            return back()->with('status', 'Si cet email existe, un lien de réinitialisation vous a été envoyé.');
        }

        // Supprimer les anciens tokens
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        $token = Str::random(64);

        DB::table('password_reset_tokens')->insert([
            'email'      => $request->email,
            'token'      => Hash::make($token),
            'created_at' => now(),
        ]);

        $resetUrl = route('password.reset', ['token' => $token, 'email' => $request->email]);

        try {
            $html = view('emails.password-reset', [
                'user'     => $user,
                'resetUrl' => $resetUrl,
                'expiry'   => '60 minutes',
            ])->render();

            $this->brevo->send(
                toEmail:     $user->email,
                toName:      $user->name,
                subject:     'Réinitialisation de votre mot de passe — Patricia Braids',
                htmlContent: $html,
            );
        } catch (\Throwable) {}

        return back()->with('status', 'Un lien de réinitialisation vous a été envoyé par email.');
    }
}