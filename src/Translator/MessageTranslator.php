<?php
declare(strict_types=1);
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Translator;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Translation\Dumper\XliffFileDumper;
use Symfony\Component\Translation\Dumper\YamlFileDumper;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\Util\XliffUtils;
use Symfony\Component\Yaml\Yaml;

class MessageTranslator
{
    /** @var  BatchTranslatorInterface|null */
    private $batchTranslator;

    /**
     * @param BatchTranslatorInterface $batchTranslator
     */
    public function setBatchTranslator(BatchTranslatorInterface $batchTranslator)
    {
        $this->batchTranslator = $batchTranslator;
    }

    public function translate(File $file, string $source, string $target = null)
    {
        if (null === $this->batchTranslator) {
            throw new \LogicException('No BatchTranslatorInterface found. Please implement one according to the documentation in zicht/messages-bundle');
        }

        switch ($file->getExtension()) {
            case 'xlf':
                $this->handleXlf($file, $source, $target);
                break;
            case 'yaml':
            case 'yml':
                if (!$target) {
                    throw new \UnexpectedValueException('Please provide the targetlanguage: --target=xx');
                }
                $this->handleYaml($file, $source, $target);
                break;
        }
    }

    private function handleXlf(File $file, string $source, string $target = null)
    {
        $domain = 'auto-translate';
        $loader = new XliffFileLoader();
        $catalogue = $loader->load($file->getPathname(), $target, $domain);
        $this->updateCatalogue($catalogue, $domain, $source, $target);
        (new Filesystem())->dumpFile($file->getPathname(), (new XliffFileDumper())->formatCatalogue($catalogue, $domain));
    }

    private function handleYaml(File $file, string $source, string $target)
    {
        $domain = 'auto-translate';
        $loader = new YamlFileLoader();
        $catalogue = $loader->load($file->getPathname(), $target, $domain);
        $this->updateCatalogue($catalogue, $domain, $source, $target);
        (new Filesystem())->dumpFile($file->getPathname(), (new YamlFileDumper())->formatCatalogue($catalogue, $domain));
    }

    private function updateCatalogue(MessageCatalogueInterface $catalogue, string $domain, string $source, string $target)
    {
        $batch = [];
        $allOriginalReplacements = [];
        foreach ($catalogue->all($domain) as $translationKey => $translationValue) {
            $originalReplacements = $this->getOriginalReplacements($translationValue);
            $allOriginalReplacements[] = $originalReplacements;
            $replacementSet = $this->getReplacementSet($originalReplacements);
            $batch[] = str_replace($originalReplacements, $replacementSet, $translationValue);
        }

        $results = $this->translateValues($batch, $source, $target);
        $i = 0;
        foreach (array_keys($catalogue->all($domain)) as $translationKey) {
            $catalogue->set($translationKey, $this->revertReplacements($results[$i], $allOriginalReplacements[$i]), $domain);
            $i++;
        }
    }

    private function translateValues(array $batch, string $sourceLanguage, string $targetLanguage): array
    {
        $results = $this->batchTranslator->translateBatch($batch, $sourceLanguage, $targetLanguage);
        if (count($results) !== count($batch)) {
            throw new \UnexpectedValueException(sprintf('Expected %d results from translateBatch. Found %d', count($batch), count($results)));
        }
        return $results;
    }

    private function getOriginalReplacements(string $value = null)
    {
        return (new Replacer())->extractReplacements($value);
    }

    private function getReplacementSet(array $originalReplacements)
    {
        return (new Replacer())->getReplacementSet($originalReplacements);
    }

    private function revertReplacements(string $value, $orginalValues)
    {
        return (new Replacer())->revertReplacements($value, $orginalValues);
    }
}