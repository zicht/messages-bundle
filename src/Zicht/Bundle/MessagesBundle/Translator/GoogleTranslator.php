<?php
declare(strict_types=1);
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Translator;

use Google\Cloud\Translate\V2\TranslateClient;

class GoogleTranslator implements BatchTranslatorInterface
{
    /**
     * @var array
     */
    private $googleTranslateServiceAccount;

    public function __construct(array $googleTranslateServiceAccount)
    {
        $this->googleTranslateServiceAccount = $googleTranslateServiceAccount;
    }

    public function translateBatch(array $batch, string $sourceLanguage, string $targetLanguage): array
    {
        $translator = new TranslateClient(['keyFile' => $this->googleTranslateServiceAccount]);
        $results = [];
        foreach (array_chunk($batch, 100) as $chunk) {
            $results = array_merge($results, array_column($translator->translateBatch($chunk, ['source' => $sourceLanguage, 'target' => $targetLanguage, 'format' => 'text']), 'text'));
        }
        return $results;
    }
}
