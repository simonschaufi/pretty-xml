<?php

namespace PrettyXml;

class Formatter
{
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
                    $deep--;
                }
            } elseif (preg_match('/<\w/', $line) && !preg_match('/<\//', $line) && !preg_match('/\/>/', $line)) {
                $str .= !$inComment ? $this->getPaddedString($line, $deep++) : $line;
            } elseif (preg_match('/<\w/', $line) && preg_match('/<\//', $line)) {
                $str .= !$inComment ? $this->getPaddedString($line, $deep) : $line;
            } elseif (preg_match('/<\//', $line)) {
                $str .= !$inComment ? $this->getPaddedString($line, --$deep) : $line;
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

        return (($str[0] ?? '') === "\n") ? substr($str, 1) : $str;
    }

    public function minify(string $xml, bool $preserveComments = false): string
    {
        if (!$preserveComments) {
            $xml = preg_replace('/<![ \r\n\t]*(--([^\-]|[\r\n]|-[^\-])*--[ \r\n\t]*)>/', '', $xml);
            $xml = preg_replace('/[ \r\n\t]+xmlns/', ' xmlns', $xml);
        }

        // minify xml declaration
        $xml = preg_replace('/\s*\?>/', '?>', $xml);

        // removes spaces around = and between attributes
        $xml = preg_replace('/\s*=\s*/', '=', $xml);

        // removes spaces between attributes
        $xml = preg_replace('/\s+/', ' ', $xml);

        // removes spaces before /> and between tags
        $xml = preg_replace('/\s*\/>/', '/>', $xml);

        // removes spaces before closing tag
        return preg_replace('/\s*>\s*</', '><', $xml);
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
        return "\n" . str_repeat($this->padChar, $depth * $this->indent) . $string;
    }
}
