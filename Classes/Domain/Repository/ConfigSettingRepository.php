<?php
namespace MichielRoos\H5p\Domain\Repository;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class ConfigSettingRepository
 */
class ConfigSettingRepository extends Repository
{
    /**
     * initializes any required object
     */
    public function initializeObject(): void
    {
        if ($this->defaultQuerySettings === null) $this->defaultQuerySettings = GeneralUtility::makeInstance(QuerySettingsInterface::class);
        $this->defaultQuerySettings->setRespectStoragePage(false);
    }
}
