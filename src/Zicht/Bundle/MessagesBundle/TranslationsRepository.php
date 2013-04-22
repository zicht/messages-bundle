<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
 
namespace Zicht\Bundle\MessagesBundle;

interface TranslationsRepository {
    function getTranslations($locale, $domain);
}