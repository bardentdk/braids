<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // ── Général ────────────────────────────
            ['key' => 'site_name',        'value' => 'Patricia Braids',                    'type' => 'string',  'group' => 'general', 'label' => 'Nom du site',             'is_public' => true],
            ['key' => 'site_tagline',     'value' => 'L\'art des tresses, sublimé pour vous','type' => 'string','group' => 'general', 'label' => 'Slogan',                   'is_public' => true],
            ['key' => 'site_description', 'value' => 'Studio de tresses premium à Paris. Réservez votre session d\'exception.', 'type' => 'string', 'group' => 'general', 'label' => 'Description', 'is_public' => true],
            ['key' => 'site_email',       'value' => 'contact@patricia-braids.com',        'type' => 'string',  'group' => 'general', 'label' => 'Email de contact',        'is_public' => true],
            ['key' => 'site_phone',       'value' => '+33 6 12 34 56 78',                  'type' => 'string',  'group' => 'general', 'label' => 'Téléphone',               'is_public' => true],
            ['key' => 'site_address',     'value' => 'Paris, France',                      'type' => 'string',  'group' => 'general', 'label' => 'Adresse',                 'is_public' => true],
            ['key' => 'currency',         'value' => 'EUR',                                'type' => 'string',  'group' => 'general', 'label' => 'Devise',                  'is_public' => true],
            ['key' => 'currency_symbol',  'value' => '€',                                  'type' => 'string',  'group' => 'general', 'label' => 'Symbole devise',          'is_public' => true],

            // ── Réseaux sociaux ─────────────────────
            ['key' => 'social_instagram', 'value' => 'https://instagram.com/patricia.braids', 'type' => 'string', 'group' => 'social', 'label' => 'Instagram',              'is_public' => true],
            ['key' => 'social_tiktok',    'value' => '',                                   'type' => 'string',  'group' => 'social', 'label' => 'TikTok',                   'is_public' => true],
            ['key' => 'social_facebook',  'value' => '',                                   'type' => 'string',  'group' => 'social', 'label' => 'Facebook',                 'is_public' => true],
            ['key' => 'social_youtube',   'value' => '',                                   'type' => 'string',  'group' => 'social', 'label' => 'YouTube',                  'is_public' => true],

            // ── Boutique ────────────────────────────
            ['key' => 'shop_enabled',         'value' => '1',   'type' => 'boolean', 'group' => 'shop', 'label' => 'Boutique active',          'is_public' => true],
            ['key' => 'shop_tax_rate',        'value' => '20',  'type' => 'float',   'group' => 'shop', 'label' => 'Taux TVA (%)',             'is_public' => false],
            ['key' => 'shop_free_shipping_at','value' => '75',  'type' => 'float',   'group' => 'shop', 'label' => 'Livraison gratuite dès (€)','is_public' => true],
            ['key' => 'shop_shipping_cost',   'value' => '4.90','type' => 'float',   'group' => 'shop', 'label' => 'Frais de livraison (€)',   'is_public' => true],

            // ── RDV & Agenda ────────────────────────
            ['key' => 'booking_enabled',         'value' => '1',  'type' => 'boolean', 'group' => 'booking', 'label' => 'Réservations actives',        'is_public' => true],
            ['key' => 'booking_advance_days',     'value' => '60', 'type' => 'integer', 'group' => 'booking', 'label' => 'Résa max à l\'avance (jours)','is_public' => false],
            ['key' => 'booking_min_notice_hours', 'value' => '24', 'type' => 'integer', 'group' => 'booking', 'label' => 'Délai minimum résa (heures)',  'is_public' => false],
            ['key' => 'booking_cancellation_hours','value' => '48','type' => 'integer', 'group' => 'booking', 'label' => 'Annulation possible avant (h)', 'is_public' => true],

            // ── Facturation ─────────────────────────
            ['key' => 'invoice_prefix',      'value' => 'FAC',                      'type' => 'string',  'group' => 'invoice', 'label' => 'Préfixe facture',        'is_public' => false],
            ['key' => 'invoice_due_days',    'value' => '30',                       'type' => 'integer', 'group' => 'invoice', 'label' => 'Délai paiement (jours)', 'is_public' => false],
            ['key' => 'invoice_footer_text', 'value' => 'Merci pour votre confiance !', 'type' => 'string', 'group' => 'invoice', 'label' => 'Texte pied de facture', 'is_public' => false],
            ['key' => 'invoice_siret',       'value' => '',                         'type' => 'string',  'group' => 'invoice', 'label' => 'SIRET',                  'is_public' => false],
            ['key' => 'invoice_iban',        'value' => '',                         'type' => 'string',  'group' => 'invoice', 'label' => 'IBAN',                   'is_public' => false],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}