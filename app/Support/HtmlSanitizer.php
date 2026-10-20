<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Allow-list HTML sanitizer for Quill editor output.
 */
class HtmlSanitizer
{
    /** Tags kept (attributes are filtered separately). */
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'ol', 'ul', 'li', 'a',
        'h1', 'h2', 'h3', 'blockquote', 'pre', 'code', 'span', 'img',
    ];

    /** Tags removed together with their content. */
    private const DROP_TAGS = [
        'script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet', 'noscript',
        'template', 'svg', 'math', 'form', 'input', 'button', 'textarea', 'select', 'link', 'meta',
        'base', 'title', 'head', 'audio', 'video', 'source', 'canvas', 'noembed', 'xmp',
    ];

    public static function clean(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body><div id="__root">'.$html.'</div></body></html>',
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $dom->getElementById('__root');
        if (! $root) {
            return e(strip_tags($html));
        }

        self::walk($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return trim($out);
    }

    private static function walk(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node instanceof DOMElement) {
                $tag = strtolower($node->tagName);

                if (in_array($tag, self::DROP_TAGS, true)) {
                    $parent->removeChild($node);

                    continue;
                }

                self::walk($node);

                if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                    // Unwrap unknown tags but keep their (already sanitized) children.
                    while ($node->firstChild) {
                        $parent->insertBefore($node->firstChild, $node);
                    }
                    $parent->removeChild($node);

                    continue;
                }

                if (! self::filterAttributes($node, $tag)) {
                    $parent->removeChild($node);
                }
            } elseif ($node->nodeType !== XML_TEXT_NODE) {
                // Comments, CDATA, processing instructions, etc.
                $parent->removeChild($node);
            }
        }
    }

    /** @return bool false when the element must be dropped entirely. */
    private static function filterAttributes(DOMElement $el, string $tag): bool
    {
        $keep = [];

        $class = self::safeClasses($el->getAttribute('class'));
        if ($class !== '') {
            $keep['class'] = $class;
        }

        if ($tag === 'li' && in_array($el->getAttribute('data-list'), ['bullet', 'ordered', 'checked', 'unchecked'], true)) {
            $keep['data-list'] = $el->getAttribute('data-list');
        }

        if ($tag === 'a') {
            $href = self::safeUrl($el->getAttribute('href'), ['http', 'https', 'mailto'], false);
            if ($href !== null) {
                $keep['href'] = $href;
                $keep['rel'] = 'noopener noreferrer';
                $keep['target'] = '_blank';
            }
        }

        if ($tag === 'img') {
            $src = self::safeUrl($el->getAttribute('src'), ['http', 'https'], true);
            if ($src === null) {
                return false;
            }
            $keep['src'] = $src;
            if ($el->hasAttribute('alt')) {
                $keep['alt'] = $el->getAttribute('alt');
            }
        }

        foreach (iterator_to_array($el->attributes) as $attr) {
            $el->removeAttributeNode($attr);
        }
        foreach ($keep as $name => $value) {
            $el->setAttribute($name, $value);
        }

        return true;
    }

    private static function safeClasses(string $classes): string
    {
        $out = [];
        foreach (preg_split('/\s+/', trim($classes)) ?: [] as $c) {
            if (preg_match('/^ql-[a-z0-9_-]+$/i', $c)) {
                $out[] = $c;
            }
        }

        return implode(' ', $out);
    }

    private static function safeUrl(string $url, array $schemes, bool $allowStoragePath): ?string
    {
        // Strip whitespace/control chars browsers ignore inside schemes (e.g. "java\tscript:").
        $clean = preg_replace('/[\x00-\x20\x7F]+/', '', $url) ?? '';
        if ($clean === '') {
            return null;
        }

        if ($allowStoragePath && str_starts_with($clean, '/storage/') && ! str_contains($clean, '..')) {
            return $clean;
        }

        if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', $clean, $m)) {
            return in_array(strtolower($m[1]), $schemes, true) ? $clean : null;
        }

        return null;
    }
}
