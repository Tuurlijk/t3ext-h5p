<?php
namespace MichielRoos\H5p\Domain\Repository;

use MichielRoos\H5p\Domain\Model\ContentTypeCacheEntry;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
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
