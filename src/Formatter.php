<?php

namespace PrettyXml;

class Formatter
{
    /**
     * Sections whose content is character data, not markup: comments, CDATA
     * blocks and doctype declarations (optionally carrying an internal subset).
     *
     * Their content must never be split on "<", never be indented and never
     * have whitespace collapsed. Alternation order does not matter, because the
     * engine matches whichever section starts first: a "<![CDATA[" inside a
     * comment is text, and a "<!--" inside a CDATA block is text.
     */
    private const ATOMIC_SECTION_PATTERN = '/<!--.*?-->|<!\[CDATA\[.*?\]\]>|<!DOCTYPE[^>\[]*(?:\[.*?\])?[^>]*>/s';

    private const PLACEHOLDER_PREFIX = '<!--PRETTY_XML_ATOMIC_SECTION_';

    private const PLACEHOLDER_SUFFIX = '-->';

    private int $indent = 4;

    private string $padChar = ' ';

    public function setIndentSize(int $indent): void
    {
        $this->indent = $indent;
    }

    public function setIndentCharacter(string $indentCharacter): void
    {
        $this->padChar = $indentCharacter;
    }

    public function format(string $xml): string
    {
        [$xml, $atomicSections] = $this->extractAtomicSections($xml);

        $lines = $this->splitIntoLines($xml);
        $inComment = false;
        $deep = 0;
        $str = '';

        foreach ($lines as $i => $line) {
            if (str_contains($line, '<!')) {
                $str .= $this->getPaddedString($line, $deep);
                $inComment = true;
                if (str_contains($line, '-->') || str_contains($line, ']>') || str_contains($line, '!DOCTYPE')) {
                    $inComment = false;
                }
            } elseif (str_contains($line, '-->') || str_contains($line, ']>')) {
                $str .= $line;
                $inComment = false;
            } elseif (
                isset($lines[$i - 1])
                && preg_match('/^<\w/', $lines[$i - 1])
                && preg_match('/^<\/\w/', $line)
                && preg_match('/^<([\w:\-.,]+)/', $lines[$i - 1], $openingTag)
                && preg_match('/^<\/([\w:\-.,]+)/', $line, $closingTag)
                && $openingTag[1] === $closingTag[1]
            ) {
                $str .= $line;
                if (!$inComment) {
                    $deep = $this->decreaseDepth($deep);
                }
            } elseif (preg_match('/<\w/', $line) && !preg_match('/<\//', $line) && !preg_match('/\/>/', $line)) {
                $str .= !$inComment ? $this->getPaddedString($line, $deep++) : $line;
            } elseif (preg_match('/<\w/', $line) && preg_match('/<\//', $line)) {
                $str .= !$inComment ? $this->getPaddedString($line, $deep) : $line;
            } elseif (preg_match('/<\//', $line)) {
                if ($inComment) {
                    $str .= $line;
                } else {
                    $deep = $this->decreaseDepth($deep);
                    $str .= $this->getPaddedString($line, $deep);
                }
            } elseif (preg_match('/\/>/', $line)) {
                $str .= !$inComment ? $this->getPaddedString($line, $deep) : $line;
            } elseif (preg_match('/<\?/', $line)) {
                $str .= $this->getPaddedString($line, $deep);
            } elseif (str_contains($line, 'xmlns:') || str_contains($line, 'xmlns=')) {
                $str .= $this->getPaddedString($line, $deep);
            } else {
                $str .= $line;
            }
        }

        $str = (($str[0] ?? '') === "\n") ? substr($str, 1) : $str;

        return $this->restoreAtomicSections($str, $atomicSections);
    }

    public function minify(string $xml, bool $preserveComments = false): string
    {
        if (!$preserveComments) {
            $xml = preg_replace('/<![ \r\n\t]*(--([^\-]|[\r\n]|-[^\-])*--[ \r\n\t]*)>/', '', $xml);
            $xml = preg_replace('/[ \r\n\t]+xmlns/', ' xmlns', $xml);
        }

        // Comments that survive must keep their content, and CDATA content is
        // significant whitespace by definition, so neither may be minified.
        [$xml, $atomicSections] = $this->extractAtomicSections($xml);

        // minify xml declaration
        $xml = preg_replace('/\s*\?>/', '?>', $xml);

        // removes spaces around = and between attributes
        $xml = preg_replace('/\s*=\s*/', '=', $xml);

        // removes spaces between attributes
        $xml = preg_replace('/\s+/', ' ', $xml);

        // removes spaces before /> and between tags
        $xml = preg_replace('/\s*\/>/', '/>', $xml);

        // removes spaces before closing tag
        $xml = preg_replace('/\s*>\s*</', '><', $xml);

        return $this->restoreAtomicSections($xml, $atomicSections);
    }

    /**
     * Replaces every atomic section with an inert single-token placeholder, so
     * that the formatter treats it as one opaque tag instead of parsing its
     * content as markup.
     *
     * @return array{0: string, 1: array<string, string>}
     */
    private function extractAtomicSections(string $xml): array
    {
        $atomicSections = [];

        $result = preg_replace_callback(
            self::ATOMIC_SECTION_PATTERN,
            static function (array $matches) use (&$atomicSections): string {
                $placeholder = self::PLACEHOLDER_PREFIX . count($atomicSections) . self::PLACEHOLDER_SUFFIX;
                $atomicSections[$placeholder] = $matches[0];

                return $placeholder;
            },
            $xml
        );

        // A catastrophic backtrack leaves the input untouched rather than empty.
        if ($result === null) {
            return [$xml, []];
        }

        return [$result, $atomicSections];
    }

    /**
     * @param array<string, string> $atomicSections
     */
    private function restoreAtomicSections(string $xml, array $atomicSections): string
    {
        if ($atomicSections === []) {
            return $xml;
        }

        return str_replace(array_keys($atomicSections), array_values($atomicSections), $xml);
    }

    /**
     * Unbalanced markup must not push the depth below the root level, where it
     * would otherwise produce a negative padding width.
     */
    private function decreaseDepth(int $deep): int
    {
        return $deep > 0 ? $deep - 1 : 0;
    }

    private function splitIntoLines(string $text): array
    {
        $text = preg_replace('/>\s*</', '><', $text);
        $text = preg_replace('/</', '~::~<', $text);
        $text = preg_replace('/\s*xmlns:/', '~::~xmlns:', $text);
        $text = preg_replace('/\s*xmlns=/', '~::~xmlns=', $text);
        return explode('~::~', $text);
    }

    private function getPaddedString(string $string, int $depth): string
    {
        $width = $depth * $this->indent;

        return "\n" . str_repeat($this->padChar, $width > 0 ? $width : 0) . $string;
    }
}
