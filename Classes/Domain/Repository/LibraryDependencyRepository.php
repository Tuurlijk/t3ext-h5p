<?php

namespace MichielRoos\H5p\Domain\Repository;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class LibraryDependencyRepository
 */
class LibraryDependencyRepository extends Repository
{

    /**
     * initializes any required object
     */
    public function initializeObject()
    {
        if ($this->defaultQuerySettings === null) {
            $this->defaultQuerySettings = GeneralUtility::makeInstance(QuerySettingsInterface::class);
        }
        $this->defaultQuerySettings->setRespectStoragePage(false);
    }

    /**
     * @param string $library
     * @param string $requiredLibrary
     * @return array|null
     */
    public function findOneByLibraryAndRequiredLibrary($library, $requiredLibrary)
    {
        $query = $this->createQuery();

        $query->matching(
            $query->logicalAnd(
                $query->equals('library', $library),
                $query->equals('required_library', $requiredLibrary),
            )
        );

        $query->setLimit(1);

        return $query->execute()->getFirst();
    }
}