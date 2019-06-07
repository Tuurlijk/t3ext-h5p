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
use MichielRoos\H5p\Domain\Model\Library;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * Class LibraryRepository
 */
class LibraryRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    /**
     * @var array
     */
    protected $defaultOrderings = ['title' => QueryInterface::ORDER_ASCENDING];

    /**
     * @param integer $id
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function removeByLibraryId($id)
    {
        $library = $this->findOneByUid($id);
        if ($library !== null) {
            $this->remove($library);
        }
    }

    public function findLibrariesWithNewerVersion(Library $library)
    {
        $query = $this->createQuery();

        $query->getQueryBuilder()
            ->where('e.name = ?0 AND e.libraryId != ?1 AND (e.majorVersion > ?2 OR (e.majorVersion = ?2 AND e.minorVersion > ?3))')
            ->setParameters([
                $library->getTitle(),
                $library->getUid(),
                $library->getMajorVersion(),
                $library->getMinorVersion()
            ]);

        return $query->execute();
    }

    public function getPatchedLibrary($name, $majorVersion, $minorVersion, $patchVersion)
    {
        $query = $this->createQuery();

        $query->getQueryBuilder()
            ->where('e.name = ?0 AND e.majorVersion = ?1 AND e.minorVersion = ?2 AND e.patchVersion > ?3')
            ->setParameters([
                $name,
                $majorVersion,
                $minorVersion,
                $patchVersion
            ]);

        return $query->execute();
    }

    public function findUnused()
    {
        $libs = $this->findAll()->toArray();
        return array_filter($libs, function ($library) {
            /** @var Library $library */
            return $library->getContents()->count() === 0 &&
                $library->getContentDependencies()->count() === 0 &&
                count($library->getDependentLibraries()) === 0;
        });
    }

    /**
     * @param string $libraryName
     * @param int $majorVersion
     * @param int $minorVersion
     * @return Library
     */
    public function findOneByMachinenameMajorVersionAndMinorVersion($libraryName, $majorVersion, $minorVersion = 0)
    {
        $query = $this->createQuery();
        $libraries = $query->matching(
            $query->logicalAnd(
                $query->equals('machine_name', $libraryName),
                $query->equals('major_version', $majorVersion),
                $query->equals('minor_version', $minorVersion)
            )
        )->execute();
        return $libraries->getFirst();
    }

    /**
     * @param string $libraryName
     * @param int $majorVersion
     * @param int $minorVersion
     * @return Library
     */
    public function findOneByNameMajorVersionAndMinorVersion($libraryName, $majorVersion, $minorVersion = 0)
    {
        $query = $this->createQuery();
        $libraries = $query->matching(
            $query->logicalAnd(
                $query->equals('title', $libraryName),
                $query->equals('major_version', $majorVersion),
                $query->equals('minor_version', $minorVersion)
            )
        )->execute();
        return $libraries->getFirst();
    }

    /**
     * @return array
     */
    public function getLibraryAddons()
    {
        $query = $this->createQuery();
        $sql = <<<EOS
SELECT e.uid,
       e.title         AS machineName,
       e.major_version AS majorVersion,
       e.minor_version AS minorVersion,
       e.patch_version AS patchVersion,
       e.add_to        AS addTo,
       e.preloaded_js  AS preloadedJs,
       e.preloaded_css AS preloadedCSS
FROM tx_h5p_domain_model_library e
         LEFT JOIN
     tx_h5p_domain_model_library l2
     ON e.title = l2.title
         AND (
                e.major_version < l2.major_version OR
                (e.major_version = l2.major_version AND e.minor_version < l2.minor_version)
            )
WHERE e.add_to IS NOT NULL
  AND l2.title IS NULL
EOS;

        return $query->statement($sql)->execute(true);
    }
}
