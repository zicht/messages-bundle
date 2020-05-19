<?php


namespace Zicht\Bundle\MessagesBundle\Translator;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Yaml\Yaml;

class MessageTranslatorTest extends TestCase
{
    private $translations = [
        'Eerlijk, mijn %dear%, het maakt mij geen %damn% uit',
        'Toto, volges mijn !feeling zijn we niet !in {Kansas} %anymore%',
        'Ik zie !dead %people%',
        'Houd je {friends} !close, !but {your} %enemies% %closer%',
    ];

    public function testTranslateYaml()
    {
        $translator = new MessageTranslator();
        $batchTranslator = self::getMockBuilder(BatchTranslatorInterface::class)->disableOriginalConstructor()->getMock();
        $batchTranslator->method('translateBatch')->willReturn($this->translations);
        $testFile = sys_get_temp_dir() . '/' . date('Ymd_H_i_s') . '.yml';
        (new Filesystem())->copy(new File(__DIR__ . '/../fixtures/testfile1.yml'), $testFile);

        $translator->setBatchTranslator($batchTranslator);
        $translator->translate(new File($testFile), 'en', 'nl');

        $translatedFile = Yaml::parse(file_get_contents($testFile));
        self::assertEquals($this->translations, array_values($translatedFile));

        (new Filesystem())->remove($testFile);
    }

    public function testTranslateXlf()
    {
        $translator = new MessageTranslator();
        $batchTranslator = self::getMockBuilder(BatchTranslatorInterface::class)->disableOriginalConstructor()->getMock();
        $batchTranslator->method('translateBatch')->willReturn($this->translations);
        $testFile = sys_get_temp_dir() . '/' . date('Ymd_H_i_s') . '.xlf';
        (new Filesystem())->copy(new File(__DIR__ . '/../fixtures/testfile1.xlf'), $testFile);

        $translator->setBatchTranslator($batchTranslator);
        $translator->translate(new File($testFile), 'en', 'nl');

        self::assertStringContainsString($this->translations[0], file_get_contents($testFile));
        self::assertStringContainsString($this->translations[1], file_get_contents($testFile));
        self::assertStringContainsString($this->translations[2], file_get_contents($testFile));
        self::assertStringContainsString($this->translations[3], file_get_contents($testFile));

        (new Filesystem())->remove($testFile);
    }
}