<?php
declare(strict_types=1);
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Translator;

class Replacer
{
    public function extractReplacements(string $value = null): array
    {
        if (null === $value) {
            return [];
        }
        preg_match_all('/(\%(\w*?)\%|\{(\w*?)\})|(!\w+)/', $value, $matches);
        return $matches[0];
    }

    public function getReplacementSet(array $replacements): array
    {
        $replacementSet = [];
        foreach ($replacements as $index => $match) {
            // failsafe use "##", the firstly tested and integrated Google Translate API handles quite this well.
            $replacementSet[] = sprintf('##%d##', $index);
        }
        return $replacementSet;
    }

    public function revertReplacements(string $value, array $replacements): string
    {
        preg_match_all('/\##(.*?)\##/', $value, $matches);
        return str_replace($matches[0], $replacements, $value);
    }
}