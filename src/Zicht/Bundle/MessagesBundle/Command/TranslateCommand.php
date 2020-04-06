<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Util\XliffUtils;
use Symfony\Component\Yaml\Yaml;
use Zicht\Bundle\MessagesBundle\Translator\BatchTranslatorInterface;

class TranslateCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'zicht:messages:translate';

    /** @var  BatchTranslatorInterface */
    private $batchTranslator;

    /**
     * @param BatchTranslatorInterface $batchTranslator
     */
    public function setBatchTranslator(BatchTranslatorInterface $batchTranslator)
    {
        $this->batchTranslator = $batchTranslator;
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED)
            ->addOption('--source', '-s', InputOption::VALUE_REQUIRED, 'The source-language.')
            ->addOption('--target', '-t', InputOption::VALUE_OPTIONAL, 'The target-language. Override if auto-discovered target is not in line with your translation API specs.');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // possibly an override, e.g. we use "nn" for Norwegian, but your api only supports "no".
        // Or es-mx or es-co, but only es is supported.
        $target = $input->getOption('target');
        $source = $input->getOption('source');

        $file = new File($input->getArgument('file'));
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
        $doc = new \DOMDocument();
        $doc->loadXML(file_get_contents($file->getPathname()));

        if ('1.2' !== XliffUtils::getVersionNumber($doc)) {
            throw new \UnexpectedValueException('Can only handle 1.2 implementations of currently');
        }

        $xpath = new \DOMXPath($doc);
        $namespace = 'urn:oasis:names:tc:xliff:document:1.2';
        $xpath->registerNamespace('xliff', $namespace);

        /** @var \DOMElement $fileNode */
        foreach ($xpath->query('//xliff:file') as $fileNode) {
            $batch = [];
            $allOriginalReplacements = [];
            foreach ($fileNode->getElementsByTagName('trans-unit') as $translation) {
                $nodeValue = $translation->getElementsByTagName('source')->item(0)->nodeValue;
                $originalReplacements = $this->getOriginalReplacements($nodeValue);
                $allOriginalReplacements[] = $originalReplacements;
                $replacementSet = $this->getReplacementSet($originalReplacements);
                $batch[] = str_replace($originalReplacements, $replacementSet, $nodeValue);
                break;
            }
            $results = $this->translateValues($batch, $source, ($target ? $target : $fileNode->getAttribute('target-language')));
            foreach ($fileNode->getElementsByTagName('trans-unit') as $unitIndex => $translation) {
                $translation->getElementsByTagName('target')->item(0)->nodeValue = $this->revertReplacements($results[$unitIndex], $allOriginalReplacements[$unitIndex]);
                break;
            }
        }
        (new Filesystem())->dumpFile($file->getPathname(), $doc->saveXML());
    }

    private function handleYaml(File $file, string $source, string $target)
    {
        $yaml = Yaml::parseFile($file->getPathname());

        $loader = new ArrayLoader();
        $catalogue = $loader->load($yaml, $target, 'auto-translate');

        $batch = [];
        $allOriginalReplacements = [];
        foreach ($catalogue->all('auto-translate') as $translationKey => $translationValue) {
            $originalReplacements = $this->getOriginalReplacements($translationValue);
            $allOriginalReplacements[] = $originalReplacements;
            $replacementSet = $this->getReplacementSet($originalReplacements);
            $batch[] = str_replace($originalReplacements, $replacementSet, $translationValue);
        }

        $results = $this->translateValues($batch, $source, $target);
        $i = 0;
        foreach (array_keys($catalogue->all('auto-translate')) as $translationKey) {
            $catalogue->set($translationKey, $this->revertReplacements($results[$i], $allOriginalReplacements[$i]), 'auto-translate');
            $i++;
        }
        (new Filesystem())->dumpFile($file->getPathname(), Yaml::dump($catalogue->all('auto-translate'), 3, 4));
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
        if (null === $value) {
            return [];
        }
        preg_match_all('/\%(.*?)\%/', $value, $matches);
        return $matches[0];
    }

    private function getReplacementSet(array $originalReplacements)
    {
        $replacementSet = [];
        foreach ($originalReplacements as $index => $match) {
            // failsafe use "##", the firstly tested and integrated Google Translate API handles quite this well.
            $replacementSet[] = sprintf('##%d##', $index);
        }
        return $replacementSet;
    }

    private function revertReplacements(string $value, $orginalValues)
    {
        preg_match_all('/\##(.*?)\##/', $value, $matches);
        return str_replace($matches[0], $orginalValues, $value);
    }
}
