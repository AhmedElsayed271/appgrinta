<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TranslationService
{
    private const BLOCK_TAGS = [
        'p', 'div', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'blockquote', 'td', 'th', 'dd', 'dt', 'figcaption',
    ];

    /**
     * Languages written right-to-left.
     */
    private const RTL_LANGUAGES = ['ar', 'he', 'fa', 'ur'];

    /**
     * Translate text (may contain HTML) from $source to $target.
     * Uses Google Translate (unofficial gtx endpoint) first, then MyMemory as a fallback.
     * HTML structure (paragraphs, headings, lists, alignment) is preserved.
     */
    public function translate(string $text, string $source = 'ar', string $target = 'en'): ?string
    {
        if (trim($text) === '') {
            return '';
        }

        $cacheKey = 'translation:' . md5($source . '|' . $target . '|' . $text);

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $result = $this->translateUncached($text, $source, $target);

        if ($result !== null) {
            Cache::put($cacheKey, $result, 60 * 24 * 365);
        }

        return $result;
    }

    private function translateUncached(string $text, string $source, string $target): ?string
    {
        if ((bool) preg_match('/<[^>]+>/', $text)) {
            return $this->translateHtml($text, $source, $target);
        }

        $segments = preg_split('/\r\n|\r|\n/', $text) ?: [$text];

        $translatedParts = [];
        foreach ($segments as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }

            $translated = $this->translateChunks($segment, $source, $target);
            if ($translated === null) {
                return null;
            }
            $translatedParts[] = $translated;
        }

        return count($translatedParts) === 0 ? '' : implode("\n", $translatedParts);
    }

    /**
     * Translate HTML while keeping its block structure and inline tags intact.
     */
    private function translateHtml(string $html, string $source, string $target): ?string
    {
        $dom = new DOMDocument();

        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded || !$dom->documentElement) {
            return null;
        }

        $body = $this->findBody($dom);

        $blocks = [];
        $blockRe = '/^(' . implode('|', self::BLOCK_TAGS) . ')$/i';

        foreach ($body->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $this->collectBlocks($child, $blockRe, $blocks);
            }
        }

        foreach ($blocks as $block) {
            $text = trim($block->textContent);

            if ($text === '') {
                continue;
            }

            $translated = $this->translateChunks($text, $source, $target);

            if ($translated === null) {
                return null;
            }

            $firstText = $block->ownerDocument->createTextNode($translated);
            while ($block->firstChild) {
                $block->removeChild($block->firstChild);
            }
            $block->appendChild($firstText);
        }

        $rtl = $this->isRtl($target);
        foreach ($body->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $this->applyDirection($child, $rtl, $blockRe);
            }
        }

        $result = '';
        foreach ($body->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }

        return $result;
    }

    private function findBody(DOMDocument $dom): DOMElement
    {
        $node = $dom->documentElement;

        while ($node instanceof DOMElement) {
            if (strtolower($node->tagName) === 'body') {
                return $node;
            }

            $next = null;
            if ($node->firstChild) {
                $next = $node->firstChild;
            }
            while ($next === null && $node !== $dom->documentElement) {
                $next = $node->nextSibling;
                $node = $node->parentNode;
            }
            $node = $next;
        }

        return $dom->documentElement;
    }

    /**
     * Collect all text-bearing leaf elements (no nested block elements).
     * Returns true when the node contains a block-level descendant.
     */
    private function collectBlocks(DOMElement $node, string $blockRe, array &$blocks): bool
    {
        $hasBlockChild = false;

        foreach ($node->childNodes as $child) {
            if (!$child instanceof DOMElement) {
                continue;
            }

            $isBlock = (bool) preg_match($blockRe, $child->tagName);
            $childHasBlock = $this->collectBlocks($child, $blockRe, $blocks);

            $hasBlockChild = $hasBlockChild || $isBlock || $childHasBlock;
        }

        if (!$hasBlockChild && preg_match($blockRe, $node->tagName) && trim($node->textContent ?? '') !== '') {
            $blocks[] = $node;
        }

        return $hasBlockChild;
    }

    private function isRtl(string $lang): bool
    {
        return in_array(strtolower(substr($lang, 0, 2)), self::RTL_LANGUAGES, true);
    }

    /**
     * Force the text direction of every block element to match the target language,
     * overriding any alignment inherited from the source (e.g. Arabic RTL).
     */
    private function applyDirection(DOMElement $node, bool $rtl, string $blockRe): void
    {
        if (preg_match($blockRe, $node->tagName)) {
            $this->setElementDirection($node, $rtl);
        }

        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $this->applyDirection($child, $rtl, $blockRe);
            }
        }
    }

    private function setElementDirection(DOMElement $el, bool $rtl): void
    {
        $el->setAttribute('dir', $rtl ? 'rtl' : 'ltr');

        $style = preg_replace('/\b(text-align|direction)\s*:[^;]+;?/i', '', $el->getAttribute('style'));
        $style = trim($style, " ;\t\n\r");

        $rules = 'text-align: ' . ($rtl ? 'right' : 'left') . '; direction: ' . ($rtl ? 'rtl' : 'ltr') . ';';

        $el->setAttribute('style', $style === '' ? $rules : $style . '; ' . $rules);
    }

    /**
     * Translate one segment, splitting it into smaller chunks if needed to
     * stay under the providers' request size limits.
     */
    private function translateChunks(string $text, string $source, string $target): ?string
    {
        $maxChars = 400;

        if (mb_strlen($text) <= $maxChars) {
            return $this->fixSpacing($this->translateSegment($text, $source, $target));
        }

        $chunks = $this->splitOnSentenceBoundaries($text, $maxChars);

        $translated = [];
        foreach ($chunks as $chunk) {
            $part = $this->translateSegment($chunk, $source, $target);
            if ($part === null) {
                return null;
            }
            $translated[] = $this->fixSpacing($part);
        }

        return implode(' ', $translated);
    }

    private function fixSpacing(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        // Add a space after sentence-end punctuation when missing.
        $text = preg_replace('/([.!?،؛])(?=[^\s.!?،؛>])/u', '$1 ', $text);

        return $text;
    }

    private function splitOnSentenceBoundaries(string $text, int $maxChars): array
    {
        $parts = preg_split('/(?<=[،\.؛؟;])\s+/u', $text) ?: [$text];

        $chunks = [];
        $current = '';

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            if ($current !== '' && (mb_strlen($current) + mb_strlen($part)) > $maxChars) {
                $chunks[] = $current;
                $current = $part;
            } else {
                $current = $current === '' ? $part : $current . ' ' . $part;
            }
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks;
    }

    private function translateSegment(string $text, string $source, string $target): ?string
    {
        $viaGoogle = $this->googleTranslate($text, $source, $target);

        if ($viaGoogle !== null) {
            return $viaGoogle;
        }

        return $this->myMemoryTranslate($text, $source, $target);
    }

    private function googleTranslate(string $text, string $source, string $target): ?string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36',
            ])->timeout(15)->get('https://translate.googleapis.com/translate_a/single', [
                'client' => 'gtx',
                'sl'     => $source,
                'tl'     => $target,
                'dt'     => 't',
                'ie'     => 'UTF-8',
                'oe'     => 'UTF-8',
                'q'      => $text,
            ]);

            if ($response->failed()) {
                return null;
            }

            $json = $response->json();
            if (!is_array($json) || !isset($json[0]) || !is_array($json[0])) {
                return null;
            }

            $translated = '';
            foreach ($json[0] as $part) {
                if (is_array($part) && isset($part[0]) && is_string($part[0])) {
                    $translated .= $part[0];
                }
            }

            return $translated === '' ? null : $translated;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function myMemoryTranslate(string $text, string $source, string $target): ?string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Appgrinta/1.0',
            ])->timeout(20)->get('https://api.mymemory.translated.net/get', [
                'q'        => $text,
                'langpair' => $source . '|' . $target,
            ]);

            if ($response->failed()) {
                return null;
            }

            $json = $response->json();
            if (($json['responseStatus'] ?? 0) != 200) {
                return null;
            }

            $translated = $json['responseData']['translatedText'] ?? null;

            return is_string($translated) && $translated !== ''
                ? $translated
                : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}