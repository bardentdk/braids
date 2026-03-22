<?php

namespace App\Services;

use Brevo\Brevo;
use Brevo\TransactionalEmails\Requests\SendTransacEmailRequest;
use Brevo\TransactionalEmails\Types\SendTransacEmailRequestSender;
use Brevo\TransactionalEmails\Types\SendTransacEmailRequestToItem;
use Brevo\TransactionalEmails\Types\SendTransacEmailRequestReplyTo;
use Brevo\TransactionalEmails\Types\SendTransacEmailRequestAttachmentItem;
use Illuminate\Support\Facades\Log;

class BrevoService
{
    protected Brevo $client;

    public function __construct()
    {
        $this->client = new Brevo(config('brevo.api_key'));
    }

    /**
     * Envoyer un email transactionnel via HTML brut.
     */
    public function send(
        string  $toEmail,
        string  $toName,
        string  $subject,
        string  $htmlContent,
        ?string $textContent = null,
        array   $attachments = []
    ): bool {
        try {
            $request = new SendTransacEmailRequest([
                'sender' => new SendTransacEmailRequestSender([
                    'email' => config('brevo.sender.email'),
                    'name'  => config('brevo.sender.name'),
                ]),
                'to' => [
                    new SendTransacEmailRequestToItem([
                        'email' => $toEmail,
                        'name'  => $toName,
                    ]),
                ],
                'replyTo' => new SendTransacEmailRequestReplyTo([
                    'email' => config('brevo.reply_to.email', config('brevo.sender.email')),
                    'name'  => config('brevo.reply_to.name',  config('brevo.sender.name')),
                ]),
                'subject'     => $subject,
                'htmlContent' => $htmlContent,
                'textContent' => $textContent,
                'attachment'  => ! empty($attachments)
                    ? array_map(fn($a) => new SendTransacEmailRequestAttachmentItem([
                        'content' => base64_encode($a['content']),
                        'name'    => $a['name'],
                    ]), $attachments)
                    : null,
            ]);

            $this->client->transactionalEmails->sendTransacEmail($request);

            Log::info("Brevo: email envoyé à {$toEmail} — {$subject}");
            return true;

        } catch (\Throwable $e) {
            Log::error("Brevo: échec envoi à {$toEmail}", [
                'subject' => $subject,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Envoyer via un Template Brevo.
     */
    public function sendTemplate(
        string $toEmail,
        string $toName,
        int    $templateId,
        array  $params      = [],
        array  $attachments = []
    ): bool {
        try {
            $request = new SendTransacEmailRequest([
                'sender' => new SendTransacEmailRequestSender([
                    'email' => config('brevo.sender.email'),
                    'name'  => config('brevo.sender.name'),
                ]),
                'to' => [
                    new SendTransacEmailRequestToItem([
                        'email' => $toEmail,
                        'name'  => $toName,
                    ]),
                ],
                'templateId' => $templateId,
                'params'     => ! empty($params) ? $params : null,
                'attachment' => ! empty($attachments)
                    ? array_map(fn($a) => new SendTransacEmailRequestAttachmentItem([
                        'content' => base64_encode($a['content']),
                        'name'    => $a['name'],
                    ]), $attachments)
                    : null,
            ]);

            $this->client->transactionalEmails->sendTransacEmail($request);

            Log::info("Brevo: template #{$templateId} envoyé à {$toEmail}");
            return true;

        } catch (\Throwable $e) {
            Log::error("Brevo: échec template #{$templateId} à {$toEmail}", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Envoyer une facture avec PDF en pièce jointe.
     */
    public function sendInvoice(
        string $toEmail,
        string $toName,
        string $invoiceNumber,
        string $htmlContent,
        string $pdfContent
    ): bool {
        return $this->send(
            toEmail:     $toEmail,
            toName:      $toName,
            subject:     "Votre facture #{$invoiceNumber} — Patricia Braids",
            htmlContent: $htmlContent,
            attachments: [[
                'content' => $pdfContent,
                'name'    => "facture-{$invoiceNumber}.pdf",
            ]]
        );
    }

    /**
     * Rappel de rendez-vous.
     */
    public function sendAppointmentReminder(
        string $toEmail,
        string $toName,
        string $serviceName,
        string $date,
        string $time
    ): bool {
        $html = view('emails.appointment-reminder', compact(
            'toName', 'serviceName', 'date', 'time'
        ))->render();

        return $this->send(
            toEmail:     $toEmail,
            toName:      $toName,
            subject:     "Rappel — {$serviceName} le {$date} à {$time}",
            htmlContent: $html,
        );
    }

    /**
     * Confirmation de commande.
     */
    public function sendOrderConfirmation(
        string $toEmail,
        string $toName,
        string $orderNumber,
        array  $items,
        float  $total
    ): bool {
        $html = view('emails.order-confirmation', compact(
            'toName', 'orderNumber', 'items', 'total'
        ))->render();

        return $this->send(
            toEmail:     $toEmail,
            toName:      $toName,
            subject:     "Confirmation de commande #{$orderNumber} — Patricia Braids",
            htmlContent: $html,
        );
    }
}