<?php

namespace PrettyXml;

class Formatter
{
    private int $depth = 0;

    private int $indent = 4;

    private string $padChar = ' ';

    private bool $preserveWhitespace = false;

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
        $output = '';
        $this->depth = 0;

        $parts = $this->getXmlParts($xml);

        if (strpos($parts[0], '<?xml') === 0) {
            $output = array_shift($parts) . PHP_EOL;
        }

        foreach ($parts as $key => $part) {
            $element = preg_replace('/<([a-zA-Z0-9\-_]+).*/', "$1", $part);

            if ($element && isset($parts[$key+1]) && preg_replace('~</(.*)>~', "$1", $parts[$key+1]) === $element) {
                $output .= $this->getOutputForPart($part, '');
            } else {
                $output .= $this->getOutputForPart($part);
            }
        }

        return trim(preg_replace('~>'.$this->padChar.'+<~', '><', $output));
    }

    private function getXmlParts(string $xml): array
    {
        $withNewLines = preg_replace('/(>)(<)(\/*)/', "$1\n$2$3", trim($xml));
        return explode("\n", $withNewLines);
    }

    private function getOutputForPart(string $part, $eol = PHP_EOL): string
    {
        $output = '';
        $this->runPre($part);

        if ($this->preserveWhitespace) {
            $output .= $part . $eol;
        } else {
            $part = trim($part);
            $output .= $this->getPaddedString($part) . $eol;
        }

        $this->runPost($part);

        return $output;
    }

    private function runPre(string $part): void
    {
        if ($this->isClosingTag($part)) {
            $this->depth--;
        }
    }

    private function runPost(string $part): void
    {
        if ($this->isOpeningCdataTag($part) && $this->isClosingCdataTag($part)) {
            return;
        }
        if ($this->isOpeningTag($part)) {
            $this->depth++;
        }
        if ($this->isClosingCdataTag($part)) {
            $this->preserveWhitespace = false;
        }
        if ($this->isOpeningCdataTag($part)) {
            $this->preserveWhitespace = true;
        }
    }

    private function getPaddedString(string $part): string
    {
        return str_pad($part, strlen($part) + ($this->depth * $this->indent), $this->padChar, STR_PAD_LEFT);
    }

    private function isOpeningTag(string $part): bool
    {
        return (bool) preg_match('/^<[^\/]*>$/', $part);
    }

    private function isClosingTag(string $part): bool
    {
        return (bool) preg_match('/^\s*<\//', $part);
    }

    private function isOpeningCdataTag(string $part): bool
    {
        return strpos($part, '<![CDATA[') !== false;
    }

    private function isClosingCdataTag(string $part): bool
    {
        return strpos($part, ']]>') !== false;
    }
}
