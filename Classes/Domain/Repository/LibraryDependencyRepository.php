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
