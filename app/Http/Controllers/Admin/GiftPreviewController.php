<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGiftPreviewRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class GiftPreviewController extends Controller
{
    /**
     * Fetch a product page and extract Open Graph data so the gift form can be prefilled.
     */
    public function store(StoreGiftPreviewRequest $request): JsonResponse
    {
        try {
            $response = Http::timeout(8)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Geboortelijst/1.0)'])
                ->get($request->string('url')->toString());
        } catch (\Throwable) {
            $response = null;
        }

        if ($response === null || ! $response->successful()) {
            throw ValidationException::withMessages([
                'url' => 'De pagina kon niet opgehaald worden. Vul de gegevens handmatig in.',
            ]);
        }

        $html = $response->body();

        return response()->json([
            'title' => $this->metaContent($html, 'og:title') ?? $this->pageTitle($html),
            'description' => $this->metaContent($html, 'og:description') ?? $this->metaContent($html, 'description'),
            'image_url' => $this->metaContent($html, 'og:image'),
            'price' => $this->price($html),
        ]);
    }

    /**
     * Read the content of a meta tag by property or name, in either attribute order.
     */
    protected function metaContent(string $html, string $key): ?string
    {
        $quoted = preg_quote($key, '/');

        $patterns = [
            "/<meta[^>]+(?:property|name)=[\"']{$quoted}[\"'][^>]*content=[\"']([^\"']*)[\"']/i",
            "/<meta[^>]+content=[\"']([^\"']*)[\"'][^>]*(?:property|name)=[\"']{$quoted}[\"']/i",
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches) === 1 && trim($matches[1]) !== '') {
                return html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5);
            }
        }

        return null;
    }

    protected function pageTitle(string $html): ?string
    {
        if (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $matches) === 1) {
            return html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5);
        }

        return null;
    }

    /**
     * Extract a price in cents from Open Graph, product, or JSON-LD markup.
     */
    protected function price(string $html): ?int
    {
        $raw = $this->metaContent($html, 'og:price:amount')
            ?? $this->metaContent($html, 'product:price:amount');

        if ($raw === null && preg_match('/"price"\s*:\s*"?([0-9]+(?:[.,][0-9]{1,2})?)"?/', $html, $matches) === 1) {
            $raw = $matches[1];
        }

        if ($raw === null) {
            return null;
        }

        $normalized = str_replace(',', '.', str_replace(['€', ' '], '', $raw));

        if (! is_numeric($normalized)) {
            return null;
        }

        $cents = (int) round((float) $normalized * 100);

        return $cents > 0 ? $cents : null;
    }
}
