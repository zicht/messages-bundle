<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Translator;

interface BatchTranslatorInterface
{
    public function translateBatch(array $batch, string $sourceLanguage, string $targetLanguage): array;
}
