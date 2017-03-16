<?php
/**
 * @author Boudewijn Schoon <boudewijn@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Iterator;

use Doctrine\DBAL\Driver\Statement;

/**
 * Class MessageTranslationIterator
 *
 * This is a helper class that takes an iterator
 *
 * @package Zicht\Bundle\MessagesBundle\Iterator
 */
class MessageTranslationIterator implements \Iterator
{
    /** @var Statement */
    protected $statement;

    /** @var mixed */
    protected $keyKey;

    /** @var mixed */
    protected $valueKey;

    /** @var integer */
    protected $key;

    /** @var mixed */
    protected $value;

    public function __construct(Statement $statement, $keyKey = 'key', $valueKey = 'value')
    {
        $this->statement = $statement;
        $this->keyKey = $keyKey;
        $this->valueKey = $valueKey;
        $this->key = null;
        $this->value = null;
    }

    public function next()
    {
        if (null === ($row = $this->statement->fetch())) {
            $this->key = null;
            $this->value = null;
        } else {
            $this->key = $row[$this->keyKey];
            $this->value = $row[$this->valueKey];
        }
    }

    public function valid()
    {
        return null !== $this->key && null !== $this->value;
    }

    public function key()
    {
        return $this->key;
    }

    public function current()
    {
        return $this->value;
    }

    public function rewind()
    {
        throw new \RuntimeException('rewind is not supported for RowIterator');
    }
}
