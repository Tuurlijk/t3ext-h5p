<?php
namespace MichielRoos\H5p\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class ContentRepository
 */
class ContentRepository extends Repository
{
    /**
     * initializes any required object
     */
    public function initializeObject()
    {
        if ($this->defaultQuerySettings === null) {
            $this->defaultQuerySettings = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(QuerySettingsInterface::class);
        }
        $this->defaultQuerySettings->setRespectStoragePage(false);
    }
}
