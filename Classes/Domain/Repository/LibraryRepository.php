<?php

namespace MichielRoos\H5p\Domain\Repository;

use MichielRoos\H5p\Domain\Model\Library;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class LibraryRepository
 */
class LibraryRepository extends Repository
{
    /**
     * @var array
     */
    protected $defaultOrderings = [
        'title'        => QueryInterface::ORDER_ASCENDING,
        'majorVersion' => QueryInterface::ORDER_ASCENDING,
        'minorVersion' => QueryInterface::ORDER_ASCENDING,
        'patchVersion' => QueryInterface::ORDER_ASCENDING
    ];

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
     * @param integer $id
     * @throws IllegalObjectTypeException
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

        $query->matching(
            $query->logicalAnd(
                $query->equals('name', $library->getTitle()),
                $query->logicalNot($query->equals('uid', $library->getUid())),
                $query->logicalOr(
                    $query->greaterThan('majorVersion', $library->getMajorVersion()),
                    $query->logicalAnd(
                        $query->equals('majorVersion', $library->getMajorVersion()),
                        $query->greaterThan('minorVersion', $library->getMinorVersion())
                    )
                )
            )
        );

        return $query->execute();
    }

    public function getPatchedLibrary($name, $majorVersion, $minorVersion, $patchVersion)
    {
        $query = $this->createQuery();

        $query->matching(
            $query->logicalAnd(
                $query->equals('name', $name),
                $query->equals('majorVersion', $majorVersion),
                $query->equals('minorVersion', $minorVersion),
                $query->equals('patchVersion', $patchVersion)
            )
        );

        $query->setLimit(1);

        return $query->execute()->getFirst();
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
     * @return object|null
     */
    public function findOneByMachinenameMajorVersionAndMinorVersion($libraryName, $majorVersion, $minorVersion = 0)
    {
        $query = $this->createQuery();

        $query->matching(
            $query->logicalAnd(
                $query->equals('machine_name', $libraryName),
                $query->equals('major_version', $majorVersion),
                $query->equals('minor_version', $minorVersion)
            )
        );

        $query->setLimit(1);

        return $query->execute()->getFirst();
    }

    /**
     * @param string $libraryName
     * @param int $majorVersion
     * @param int $minorVersion
     * @return object|null
     */
    public function findOneByNameMajorVersionAndMinorVersion($libraryName, $majorVersion, $minorVersion = 0)
    {
        $query = $this->createQuery();

        $query->matching(
            $query->logicalAnd(
                $query->equals('title', $libraryName),
                $query->equals('major_version', $majorVersion),
                $query->equals('minor_version', $minorVersion)
            )
        );

        $query->setLimit(1);

        return $query->execute()->getFirst();
    }

    /**
     * @return array
     */
    public function getLibraryAddons()
    {
        $query = $this->createQuery();
        $sql   = <<<EOS
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