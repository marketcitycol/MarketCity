<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Limpia HTML que viene de WordPress/Elementor o del editor del Backoffice:
 * deja solo etiquetas de texto (títulos, párrafos, listas, tablas, enlaces) y quita
 * estilos, scripts, formularios y atributos. Los enlaces solo pueden ser relativos, https, mailto o tel.
 */
class HtmlSanitizer
{
    private const ALLOWED = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'br', 'hr', 'ul', 'ol', 'li', 'strong', 'b', 'em', 'i', 'u', 'a', 'blockquote', 'table', 'thead', 'tbody', 'tr', 'th', 'td'];

    private const DROP = ['script', 'style', 'noscript', 'iframe', 'form', 'input', 'button', 'select', 'textarea', 'svg', 'img', 'video', 'audio', 'object', 'embed', 'head', 'title', 'meta', 'link'];

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }
        $doc = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        $root = $doc->getElementById('root') ?? $doc->documentElement;
        self::walk($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }
        // Quita párrafos vacíos y espacios repetidos que deja Elementor.
        $out = preg_replace(['#<p>(\s|&nbsp;|<br>)*</p>#u', "/\n{3,}/"], ['', "\n\n"], $out);

        return trim($out);
    }

    private static function walk(DOMNode $node): void
    {
        for ($i = $node->childNodes->length - 1; $i >= 0; $i--) {
            $child = $node->childNodes->item($i);
            if ($child->nodeType === XML_COMMENT_NODE) {
                $node->removeChild($child);
                continue;
            }
            if (! $child instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($child->nodeName);
            if (in_array($tag, self::DROP, true)) {
                $node->removeChild($child);
                continue;
            }
            self::walk($child);
            if (! in_array($tag, self::ALLOWED, true)) {
                // Desenvuelve: conserva el contenido, quita la etiqueta (div, span, section…).
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }
            $href = $tag === 'a' ? trim($child->getAttribute('href')) : null;
            while ($child->attributes->length) {
                $child->removeAttribute($child->attributes->item(0)->nodeName);
            }
            if ($tag === 'a') {
                if ($href !== null && preg_match('#^(/|https://|mailto:|tel:|\#)#i', $href) && ! str_starts_with($href, '//')) {
                    $child->setAttribute('href', $href);
                    if (str_starts_with($href, 'https://')) {
                        $child->setAttribute('rel', 'noopener');
                    }
                }
            }
        }
    }
}
