<?php
namespace MichielRoos\H5p\Adapter\Core;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

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
