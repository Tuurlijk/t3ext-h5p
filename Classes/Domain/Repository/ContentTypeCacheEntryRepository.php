<?php
namespace MichielRoos\H5p\Domain\Repository;

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
use MichielRoos\H5p\Domain\Model\ContentTypeCacheEntry;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class ContentTypeCacheEntryRepository
 */
class ContentTypeCacheEntryRepository extends Repository
{
    /**
     * initializes any required object
     */
    public function initializeObject()
    {
        if ($this->defaultQuerySettings === null) {
            $this->defaultQuerySettings = $this->objectManager->get(QuerySettingsInterface::class);
        }
        $this->defaultQuerySettings->setRespectStoragePage(false);
    }

    /**
     * Returns all cache entries as an array of stdObjects, the way the H5P core
     * expects it.
     *
     * @return array
     */
    public function getContentTypeCacheObjects()
    {
        $cacheEntries = [];
        /** @var ContentTypeCacheEntry $contentTypeCacheEntry */
        foreach ($this->findAll() as $contentTypeCacheEntry) {
            $cacheEntries[] = $contentTypeCacheEntry->toStdClass();
        }
        return $cacheEntries;
    }
}
