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
use MichielRoos\H5p\Domain\Model\LibraryDependency;

/**
 * Class LibraryDependencyRepository
 */
class LibraryDependencyRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * @param string $library
     * @param string $requiredLibrary
     * @return LibraryDependency
     */
    public function findOneByLibraryAndRequiredLibrary($library, $requiredLibrary)
    {
        $query = $this->createQuery();
        $dependencies = $query->matching(
            $query->logicalAnd(
                $query->equals('library', $library),
                $query->equals('required_library', $requiredLibrary)
            )
        )->execute();
        return $dependencies->getFirst();
    }
}
