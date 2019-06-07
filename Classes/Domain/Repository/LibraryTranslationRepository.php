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

/**
 * Class LibraryTranslationRepository
 */
class LibraryTranslationRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    public function findOneByLibraryAndLanguage($libraryUid, $language)
    {
        $query = $this->createQuery();
        $libraries = $query->matching(
            $query->logicalAnd(
                $query->equals('uid', $libraryUid),
                $query->equals('language_code', $language)
            )
        )->execute();
        return $libraries->getFirst();
    }
}
