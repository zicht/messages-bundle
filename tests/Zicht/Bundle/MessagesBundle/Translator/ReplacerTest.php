<?php

namespace Zicht\Bundle\MessagesBundle\Translator;

use PHPUnit\Framework\TestCase;

class ReplacerTest extends TestCase
{
    public function testExtractReplacements()
    {
        $test = 'In this string you find %count% %replacements%, !Zicht !specifics and {modern} {stuff}';
        $replacer = new Replacer();;
        self::assertEquals(['%count%', '%replacements%', '!Zicht', '!specifics', '{modern}', '{stuff}'], $replacer->extractReplacements($test));
    }

    public function testGetReplacementSet()
    {
        $test = ['%count%', '!Zicht', '{stuff}'];
        $replacer = new Replacer();
        self::assertEquals(['##0##', '##1##', '##2##'], $replacer->getReplacementSet($test));
    }

    public function testRevertReplacements()
    {
        $originals = ['%count%', '%replacements%', '!Zicht', '!specifics', '{modern}', '{stuff}'];
        $replacer = new Replacer();
        $test = 'In this string you find ##0## ##1##, ##3## ##4## and ##5## ##6##';
        self::assertEquals('In this string you find %count% %replacements%, !Zicht !specifics and {modern} {stuff}', $replacer->revertReplacements($test, $originals));
    }
}
