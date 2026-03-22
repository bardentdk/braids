<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqService
{
    private string $baseUrl;
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->baseUrl = config('services.groq.base_url', 'https://api.groq.com/openai/v1');
        $this->apiKey  = config('services.groq.api_key', '');
        $this->model   = config('services.groq.model', 'llama-3.3-70b-versatile');
    }

    // ── Appel de base ─────────────────────────────────────────────────

    private function chat(array $messages, int $maxTokens = 2000, float $temperature = 0.7): string
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('Clé API Groq manquante (GROQ_API_KEY dans .env).');
        }

        $response = Http::withToken($this->apiKey)
            ->timeout(90)
            ->connectTimeout(15)
            ->retry(2, 3000)
            ->post("{$this->baseUrl}/chat/completions", [
                'model'       => $this->model,
                'messages'    => $messages,
                'max_tokens'  => $maxTokens,
                'temperature' => $temperature,
            ]);

        if ($response->failed()) {
            Log::error('Groq API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException(
                'Erreur API Groq (' . $response->status() . ') : '
                . ($response->json('error.message') ?? $response->body())
            );
        }

        return trim($response->json('choices.0.message.content') ?? '');
    }

    // ── Extraction JSON robuste ───────────────────────────────────────
    // Gère tous les cas : backticks markdown, texte avant/après, unicode

    private function extractJson(string $raw): ?array
    {
        // 1. Supprimer les backticks markdown ```json...``` ou ```...```
        $cleaned = preg_replace('/```(?:json)?\s*/i', '', $raw);
        $cleaned = preg_replace('/```\s*$/', '', $cleaned);
        $cleaned = trim($cleaned);

        // 2. Tentative directe sur le texte nettoyé
        $data = json_decode($cleaned, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return $data;
        }

        // 3. Extraire le premier bloc objet JSON { ... }
        if (preg_match('/\{[\s\S]*\}/u', $cleaned, $matches)) {
            $data = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                return $data;
            }
        }

        // 4. Extraire le premier tableau JSON [ ... ]
        if (preg_match('/\[[\s\S]*\]/u', $cleaned, $matches)) {
            $data = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                return $data;
            }
        }

        // 5. Log pour débogage
        Log::warning('GroqService: impossible de parser le JSON', [
            'raw_preview' => substr($raw, 0, 600),
            'json_error'  => json_last_error_msg(),
        ]);

        return null;
    }

    // ── Générer un article complet ────────────────────────────────────

    public function generateFullPost(
        string $subject,
        string $tone = 'expert',
        string $language = 'fr',
        ?string $keywords = null,
        int $wordCount = 600
    ): array {
        $keywordHint = $keywords
            ? "Intègre naturellement ces mots-clés SEO : {$keywords}."
            : '';

        $toneMap = [
            'expert'        => 'professionnel et expert, avec une autorité naturelle',
            'friendly'      => 'chaleureux et proche, comme une amie styliste',
            'inspirational' => 'inspirant et poétique, évoquant la beauté et la créativité',
            'educational'   => 'pédagogique et clair, accessible à tous',
        ];
        $toneDesc = $toneMap[$tone] ?? $toneMap['expert'];

        $systemPrompt = "Tu es la rédactrice du blog \"Patricia Braids Studio\", un studio premium de tresses afro à Paris. "
            . "Tu écris du contenu de blog de haute qualité en {$language}, au ton {$toneDesc}. "
            . "RÈGLE ABSOLUE : Tu réponds UNIQUEMENT avec un objet JSON valide, sans aucun texte avant ou après, sans backticks, sans markdown.";

        $userPrompt = "Rédige un article de blog d'environ {$wordCount} mots sur : \"{$subject}\"\n"
            . "Structure : introduction, sous-titres H2/H3, conseils pratiques, conclusion avec appel à l'action.\n"
            . ($keywordHint ? "{$keywordHint}\n" : '')
            . "\nRetourne EXACTEMENT ce JSON (rien d'autre, pas de backticks) :\n"
            . '{"title":"...","excerpt":"accroche 150 chars max","content":"HTML complet avec <h2> <h3> <p> <ul> <li> <strong>","meta_title":"60 chars max","meta_description":"155 chars max","meta_keywords":"mot1, mot2, mot3","tags":["tag1","tag2","tag3"],"reading_time":3}';

        $raw = $this->chat([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => $userPrompt],
        ], 3500, 0.7);

        $data = $this->extractJson($raw);

        if (! $data || empty($data['title'])) {
            // Message d'erreur utile avec aperçu de ce qui a été reçu
            $preview = substr(strip_tags($raw), 0, 120);
            throw new \RuntimeException(
                "Réponse IA invalide — impossible de parser le JSON. Réessayez ou reformulez le sujet. (Reçu : \"{$preview}…\")"
            );
        }

        $data['ai_generated'] = true;
        $data['ai_model']     = $this->model;

        return $data;
    }

    // ── Améliorer un texte existant ───────────────────────────────────

    public function improveContent(string $content, string $instruction): string
    {
        return $this->chat([
            ['role' => 'system', 'content' => 'Tu es éditrice pour Patricia Braids Studio. Tu améliores du contenu de blog en français. Réponds UNIQUEMENT avec le contenu amélioré, sans commentaire ni introduction.'],
            ['role' => 'user',   'content' => "Contenu :\n\n{$content}\n\nInstruction : {$instruction}"],
        ], 2500, 0.6);
    }

    // ── Suggestions de sujets ─────────────────────────────────────────

    public function suggestTopics(string $context = '', int $count = 6): array
    {
        $raw = $this->chat([
            ['role' => 'system', 'content' => 'Tu es stratège contenu pour un blog de salon de tresses afro premium. Réponds UNIQUEMENT avec un tableau JSON, sans texte autour, sans backticks.'],
            ['role' => 'user',   'content' => "Propose {$count} idées d'articles pour \"Patricia Braids Studio\". {$context}\nFormat : [\"sujet1\",\"sujet2\"]"],
        ], 400, 0.9);

        $data = $this->extractJson($raw);
        return is_array($data) ? $data : [];
    }

    // ── Propositions de titres ────────────────────────────────────────

    public function generateTitleAndExcerpt(string $subject): array
    {
        $raw = $this->chat([
            ['role' => 'system', 'content' => 'Tu es rédactrice SEO pour un salon de tresses premium. Réponds UNIQUEMENT avec un tableau JSON, sans texte autour, sans backticks.'],
            ['role' => 'user',   'content' => "3 propositions titre+excerpt pour : \"{$subject}\"\nFormat : [{\"title\":\"...\",\"excerpt\":\"...\"}]"],
        ], 500, 0.8);

        $data = $this->extractJson($raw);
        return is_array($data) ? $data : [];
    }

    // ── Meta description ─────────────────────────────────────────────

    public function generateMetaDescription(string $title, string $excerpt): string
    {
        return $this->chat([
            ['role' => 'system', 'content' => 'Tu es experte SEO. Tu écris des meta descriptions de 155 caractères max en français. Réponds UNIQUEMENT avec la meta description, sans guillemets.'],
            ['role' => 'user',   'content' => "Titre : {$title}\nRésumé : {$excerpt}"],
        ], 100, 0.5);
    }

    // ── Info modèle ───────────────────────────────────────────────────

    public function getModelInfo(): array
    {
        return [
            'provider' => str_contains($this->baseUrl, 'groq') ? 'Groq' : 'OpenAI',
            'model'    => $this->model,
            'base_url' => $this->baseUrl,
        ];
    }
}