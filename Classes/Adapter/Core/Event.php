<?php
namespace MichielRoos\H5p\Adapter\Core;


use TYPO3\CMS\Core\SingletonInterface;

/**
 * Class Event
 */
class Event extends \H5PEventBase implements SingletonInterface
{
    /**
     * Stores the event data in the database.
     *
     * Must be overridden by plugin.
     */
    protected function save()
    {
        // TODO: Implement save() method.
    }

    /**
     * Add current event data to statistics counter.
     *
     * Must be overridden by plugin.
     */
    protected function saveStats()
    {
        // TODO: Implement saveStats() method.
    }
}
