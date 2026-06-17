<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Conservative allowlist HTML sanitizer for rich-text content produced by the
 * admin RichEditor or submitted by members. Strips any tag/attribute not on the
 * allowlist, removes javascript:/data: URLs, and drops on* event handlers.
 *
 * This is intentionally small; for richer needs swap in ezyang/htmlpurifier.
 */
class HtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'b', 'strong', 'i', 'em', 'u', 's', 'strike',
        'ul', 'ol', 'li', 'a', 'h1', 'h2', 'h3', 'h4',
        'blockquote', 'code', 'pre', 'span', 'div',
    ];

    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'title', 'target', 'rel'],
    ];

    private const ALLOWED_URL_SCHEMES = ['http', 'https', 'mailto'];

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        // Wrap so the fragment parses with a known root; force UTF-8.
        $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="__root__">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $root = $dom->getElementById('__root__');
        if (! $root) {
            return '';
        }

        self::sanitizeNode($root);

        $output = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $output .= $dom->saveHTML($child);
        }

        return trim($output);
    }

    private static function sanitizeNode(DOMNode $node): void
    {
        // Iterate over a static copy since we mutate the tree.
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->nodeName);

                if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                    // Unwrap: drop the tag but keep its (sanitized) children/text.
                    self::sanitizeNode($child);
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                    continue;
                }

                self::cleanAttributes($child, $tag);
                self::sanitizeNode($child);
            }
        }
    }

    private static function cleanAttributes(DOMElement $element, string $tag): void
    {
        $allowed = self::ALLOWED_ATTRIBUTES[$tag] ?? [];

        foreach (iterator_to_array($element->attributes) as $attr) {
            $name = strtolower($attr->name);

            if (! in_array($name, $allowed, true)) {
                $element->removeAttribute($attr->name);
                continue;
            }

            if ($name === 'href' && ! self::isSafeUrl($attr->value)) {
                $element->removeAttribute($attr->name);
            }
        }

        // Harden external links opened in new tabs.
        if ($tag === 'a' && $element->getAttribute('target') === '_blank') {
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private static function isSafeUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '' || str_starts_with($url, '#') || str_starts_with($url, '/')) {
            return true;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return $scheme === '' || in_array($scheme, self::ALLOWED_URL_SCHEMES, true);
    }
}
