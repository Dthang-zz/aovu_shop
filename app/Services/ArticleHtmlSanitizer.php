<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Allow-list cleaner for the HTML produced by the news article editor (Summernote).
 * Keeps formatting, links, images and tables; drops scripts, event handlers, iframes and unsafe URLs.
 */
class ArticleHtmlSanitizer
{
    /**
     * Elements removed together with everything inside them.
     *
     * @var array<int, string>
     */
    private const DROPPED_ELEMENTS = [
        'script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet', 'form', 'input',
        'button', 'textarea', 'select', 'option', 'svg', 'math', 'noscript', 'template', 'link', 'meta',
        'base', 'head', 'title', 'audio', 'video', 'source', 'track', 'canvas', 'dialog',
    ];

    /**
     * @var array<string, array<int, string>> Allowed elements and their attributes ("style" is filtered separately).
     */
    private const ALLOWED_ELEMENTS = [
        'p' => ['style'], 'div' => ['style'], 'span' => ['style'], 'br' => [], 'hr' => [],
        'h1' => ['style'], 'h2' => ['style'], 'h3' => ['style'], 'h4' => ['style'], 'h5' => ['style'], 'h6' => ['style'],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [], 'strike' => [], 'del' => [],
        'sub' => [], 'sup' => [], 'small' => [], 'mark' => [], 'font' => ['color', 'style'],
        'blockquote' => ['style'], 'pre' => [], 'code' => [],
        'ul' => ['style'], 'ol' => ['style', 'start'], 'li' => ['style'],
        'a' => ['href', 'title', 'target'],
        'img' => ['src', 'alt', 'title', 'width', 'height', 'style'],
        'figure' => ['style'], 'figcaption' => [],
        'table' => ['class', 'style'], 'caption' => [], 'colgroup' => [], 'col' => ['style'],
        'thead' => [], 'tbody' => [], 'tfoot' => [], 'tr' => ['style'],
        'th' => ['colspan', 'rowspan', 'style'], 'td' => ['colspan', 'rowspan', 'style'],
    ];

    /**
     * @var array<int, string>
     */
    private const ALLOWED_STYLE_PROPERTIES = [
        'text-align', 'color', 'background-color', 'font-weight', 'font-style', 'text-decoration',
        'font-size', 'font-family', 'line-height', 'text-indent', 'vertical-align',
        'width', 'height', 'max-width', 'float', 'margin', 'margin-left', 'margin-right', 'margin-top',
        'margin-bottom', 'padding', 'padding-left', 'padding-right', 'border', 'border-collapse',
    ];

    /**
     * @var array<string, array<int, string>> Inline formatting allowed in short fields (no links, images or blocks).
     */
    private const INLINE_ELEMENTS = [
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [], 'strike' => [], 'del' => [],
        'sub' => [], 'sup' => [], 'small' => [], 'mark' => [], 'span' => ['style'], 'font' => ['color', 'style'],
    ];

    /**
     * @var array<int, string>
     */
    private const INLINE_STYLE_PROPERTIES = [
        'color', 'background-color', 'font-weight', 'font-style', 'text-decoration',
    ];

    /**
     * Elements that start a new line; in inline mode they are replaced by a line break or a space.
     *
     * @var array<int, string>
     */
    private const BLOCK_ELEMENTS = [
        'p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'pre', 'ul', 'ol', 'li', 'hr',
        'table', 'caption', 'tr', 'td', 'th', 'figure', 'figcaption',
    ];

    /**
     * @var array<string, array<int, string>> Elements allowed by the current clean() call.
     */
    private array $allowedElements = self::ALLOWED_ELEMENTS;

    /**
     * @var array<int, string>
     */
    private array $styleProperties = self::ALLOWED_STYLE_PROPERTIES;

    /**
     * @var 'br'|'space'|null
     */
    private ?string $blockSeparator = null;

    public function sanitize(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        if (! $this->containsHtml($html)) {
            return $this->plainTextToHtml($html);
        }

        return $this->clean($html, self::ALLOWED_ELEMENTS, self::ALLOWED_STYLE_PROPERTIES, null);
    }

    /**
     * Clean a short field (title, excerpt, SEO texts) down to inline formatting only.
     * Paragraphs become line breaks when $multiline, otherwise spaces; links, images and blocks are removed.
     */
    public function sanitizeInline(?string $html, bool $multiline = false): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        if (! $this->containsHtml($html)) {
            return $multiline
                ? nl2br(e(preg_replace('/\R{2,}/u', "\n", $html) ?? $html), false)
                : e(preg_replace('/\s+/u', ' ', $html) ?? $html);
        }

        $output = $this->clean(
            $html,
            self::INLINE_ELEMENTS + ($multiline ? ['br' => []] : []),
            self::INLINE_STYLE_PROPERTIES,
            $multiline ? 'br' : 'space',
        );
        $output = preg_replace('/(?:<br>\s*){2,}/', '<br>', $output) ?? $output;
        $output = preg_replace('/^(?:\s|&nbsp;|<br>)+|(?:\s|&nbsp;|<br>)+$/u', '', $output) ?? $output;

        return preg_replace('/[ \t\r\n]+/u', ' ', $output) ?? $output;
    }

    /**
     * Visible text of a (possibly formatted) field, for <title>, meta tags, slugs, alt texts and length checks.
     */
    public function plainText(?string $html): string
    {
        $html = (string) $html;
        if (! $this->containsHtml($html)) {
            return trim(preg_replace('/\s+/u', ' ', $html) ?? $html);
        }

        $html = preg_replace('/<(?:br|\/p|\/div|\/li|\/h[1-6]|\/blockquote)\b[^>]*>/i', ' ', $html) ?? $html;
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $text)) ?? $text);
    }

    /**
     * @param  array<string, array<int, string>>  $allowedElements
     * @param  array<int, string>  $styleProperties
     * @param  'br'|'space'|null  $blockSeparator  Inline mode: what block elements and disallowed <br> turn into.
     */
    private function clean(string $html, array $allowedElements, array $styleProperties, ?string $blockSeparator): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previousErrors = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div>'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);

        $root = $document->getElementsByTagName('div')->item(0);
        if ($root === null) {
            return '';
        }

        $this->allowedElements = $allowedElements;
        $this->styleProperties = $styleProperties;
        $this->blockSeparator = $blockSeparator;
        $this->cleanChildren($root);

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return trim($output);
    }

    /**
     * True when the content has no visible text and no image (e.g. an untouched editor: "<p><br></p>").
     */
    public function isBlank(?string $html): bool
    {
        $html = (string) $html;
        if (stripos($html, '<img') !== false) {
            return false;
        }

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(str_replace("\u{00A0}", ' ', $text)) === '';
    }

    /**
     * Turn legacy plain-text content into paragraphs, keeping single line breaks.
     */
    public function plainTextToHtml(string $text): string
    {
        $paragraphs = preg_split('/\R{2,}/u', trim($text)) ?: [];

        return collect($paragraphs)
            ->map(fn (string $paragraph): string => trim($paragraph))
            ->filter(fn (string $paragraph): bool => $paragraph !== '')
            ->map(fn (string $paragraph): string => '<p>'.nl2br(e($paragraph), false).'</p>')
            ->implode('');
    }

    /**
     * True when the text contains a real HTML tag or comment, so legacy text like "giá <300k>" stays plain text.
     */
    private function containsHtml(string $text): bool
    {
        $tags = implode('|', [...array_keys(self::ALLOWED_ELEMENTS), ...self::DROPPED_ELEMENTS]);

        return (bool) preg_match('/<!--|<\/?(?:'.$tags.')(?:[\s\/>])/i', $text);
    }

    private function cleanChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $child) {
            if ($child->nodeType === XML_TEXT_NODE || $child->nodeType === XML_CDATA_SECTION_NODE) {
                continue;
            }

            if (! $child instanceof DOMElement) {
                $parent->removeChild($child);

                continue;
            }

            $tag = strtolower($child->tagName);
            if (in_array($tag, self::DROPPED_ELEMENTS, true)) {
                $parent->removeChild($child);

                continue;
            }

            $this->cleanChildren($child);

            if (! array_key_exists($tag, $this->allowedElements)) {
                while ($child->firstChild !== null) {
                    $parent->insertBefore($child->firstChild, $child);
                }
                if ($this->blockSeparator !== null && ($tag === 'br' || in_array($tag, self::BLOCK_ELEMENTS, true))) {
                    $separator = $this->blockSeparator === 'br'
                        ? $child->ownerDocument->createElement('br')
                        : $child->ownerDocument->createTextNode(' ');
                    $parent->insertBefore($separator, $child);
                }
                $parent->removeChild($child);

                continue;
            }

            $this->cleanAttributes($child, $tag);

            if ($tag === 'img' && ! $child->hasAttribute('src')) {
                $parent->removeChild($child);
            }
        }
    }

    private function cleanAttributes(DOMElement $element, string $tag): void
    {
        $allowed = $this->allowedElements[$tag];

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->name);
            $value = trim($attribute->value);
            $cleanValue = in_array($name, $allowed, true) ? $this->cleanAttributeValue($tag, $name, $value) : null;

            $element->removeAttribute($attribute->name);
            if ($cleanValue !== null && $cleanValue !== '') {
                $element->setAttribute($name, $cleanValue);
            }
        }

        if ($tag === 'a' && $element->getAttribute('target') === '_blank') {
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private function cleanAttributeValue(string $tag, string $name, string $value): ?string
    {
        return match ($name) {
            'href' => $this->safeUrl($value, allowDataImage: false),
            'src' => $this->safeUrl($value, allowDataImage: true),
            'style' => $this->safeStyle($value),
            'target' => $value === '_blank' ? '_blank' : null,
            'width', 'height' => preg_match('/^\d{1,4}%?$/', $value) ? $value : null,
            'colspan', 'rowspan', 'start' => preg_match('/^\d{1,3}$/', $value) ? $value : null,
            'color' => preg_match('/^#?[0-9a-z]{1,20}$/i', $value) ? $value : null,
            'class' => $tag === 'table'
                ? implode(' ', array_filter(
                    preg_split('/\s+/', $value) ?: [],
                    fn (string $class): bool => (bool) preg_match('/^table(-[a-z]+)?$/', $class),
                ))
                : null,
            default => $value,
        };
    }

    /**
     * Allow http(s), mailto, tel and relative URLs; for images also base64 raster data URLs.
     */
    private function safeUrl(string $url, bool $allowDataImage): ?string
    {
        $normalized = preg_replace('/[\x00-\x20\x7F]+/', '', html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '';
        if ($normalized === '') {
            return null;
        }

        if ($allowDataImage && preg_match('#^data:image/(png|jpe?g|gif|webp);base64,[a-z0-9+/=]+$#i', $normalized)) {
            return $normalized;
        }

        if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', $normalized, $matches)) {
            $allowedSchemes = $allowDataImage ? ['http', 'https'] : ['http', 'https', 'mailto', 'tel'];

            return in_array(strtolower($matches[1]), $allowedSchemes, true) ? $url : null;
        }

        return str_starts_with($normalized, '//') ? null : $url;
    }

    private function safeStyle(string $style): ?string
    {
        $declarations = [];
        foreach (explode(';', $style) as $declaration) {
            if (! str_contains($declaration, ':')) {
                continue;
            }

            [$property, $value] = array_map('trim', explode(':', $declaration, 2));
            $property = strtolower($property);
            if (! in_array($property, $this->styleProperties, true)
                || $value === ''
                || preg_match('/url\s*\(|expression|javascript:|behavior|[<>\\\\]|@import/i', $value)) {
                continue;
            }

            $declarations[] = $property.': '.$value;
        }

        return $declarations === [] ? null : implode('; ', $declarations);
    }
}
