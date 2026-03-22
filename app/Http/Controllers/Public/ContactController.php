<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\ContactRequest;
use App\Models\Setting;
use App\Services\BrevoService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function __construct(protected BrevoService $brevo) {}

    public function show(): Response
    {
        return Inertia::render('Public/Contact', [
            'settings' => [
                'site_email'      => Setting::get('site_email'),
                'site_phone'      => Setting::get('site_phone'),
                'site_address'    => Setting::get('site_address'),
                'social_instagram'=> Setting::get('social_instagram'),
                'social_tiktok'   => Setting::get('social_tiktok'),
            ],
        ]);
    }

    public function send(ContactRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $adminEmail = Setting::get('site_email', 'contact@patricia-braids.com');

        // Email à Patricia
        $htmlAdmin = view('emails.contact-admin', ['data' => $data])->render();
        $this->brevo->send(
            toEmail:     $adminEmail,
            toName:      'Patricia Braids',
            subject:     "[Contact] {$data['subject']} — {$data['name']}",
            htmlContent: $htmlAdmin,
        );

        // Email de confirmation à l'expéditeur
        try {
            $htmlClient = view('emails.contact-confirm', ['data' => $data])->render();
            $this->brevo->send(
                toEmail:     $data['email'],
                toName:      $data['name'],
                subject:     'Votre message a bien été reçu — Patricia Braids',
                htmlContent: $htmlClient,
            );
        } catch (\Throwable) {}

        return back()->with('success', 'Votre message a été envoyé. Patricia vous répondra dans les meilleurs délais.');
    }
}