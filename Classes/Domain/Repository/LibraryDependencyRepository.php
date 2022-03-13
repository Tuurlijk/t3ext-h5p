<?php
namespace MichielRoos\H5p\Domain\Repository;

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
            $this->defaultQuerySettings = $this->objectManager->get(QuerySettingsInterface::class);
        }
        $this->defaultQuerySettings->setRespectStoragePage(false);
    }

    /**
     * @param string $library
     * @param string $requiredLibrary
     * @return object|null
     */
    public function findOneByLibraryAndRequiredLibrary($library, $requiredLibrary)
    {
        $query = $this->createQuery();
        $dependencies = $query->matching(
            $query->logicalAnd([$query->equals('library', $library), $query->equals('required_library', $requiredLibrary)])
        )->execute();
        if ($dependencies->count()) {
            return $dependencies->getFirst();
        }
        return null;
    }
}
