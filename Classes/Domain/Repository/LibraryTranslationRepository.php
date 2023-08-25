<?php

namespace MichielRoos\H5p\Domain\Repository;

use MichielRoos\H5p\Domain\Model\Library;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class LibraryTranslationRepository
 */
class LibraryTranslationRepository extends Repository
{
    /**
     * initializes any required object
     */
    public function initializeObject(): void
    {
        if ($this->defaultQuerySettings === null) {
            $this->defaultQuerySettings = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(QuerySettingsInterface::class);
        }
        $this->defaultQuerySettings->setRespectStoragePage(false);
    }

    /**
     * @param Library $library
     * @param $language
     * @return object|null
     */
    public function findOneByLibraryAndLanguage($library, $language): ?object
    {
        $query = $this->createQuery();
        $libraries = $query->matching(
            $query->logicalAnd([$query->equals('library', $library->getUid()), $query->equals('language_code', $language)])
        )->execute();
        if ($libraries->count()) {
            return $libraries->getFirst();
        }
        return null;
    }
}
