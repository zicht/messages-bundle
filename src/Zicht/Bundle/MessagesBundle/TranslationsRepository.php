<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
 
namespace Zicht\Bundle\MessagesBundle;

/**
 * Interface TranslationsRepository
 *
 * @package Zicht\Bundle\MessagesBundle
 */
interface TranslationsRepository
{
    /**
     * Returns all translations for the passed locale and domain.
     *
     * @param string $locale
     * @param string $domain
     * @return array
     */
    public function getTranslations($locale, $domain);

    /**
     * Returns all domains that are defined in the database
     *
     * @return array
     */
    public function getDomains();
}