<?php
namespace MichielRoos\H5p\Domain\Model;

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
use MichielRoos\H5p\Domain\Repository\LibraryDependencyRepository;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class Library
 */
class Library extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * Title
     *
     * @var string
     */
    protected $title = '';

    /**
     * @var string
     */
    protected $addTo;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var \DateTime
     */
    protected $updatedAt;

    /**
     * @var string
     */
    protected $dropLibraryCss;

    /**
     * @var string
     */
    protected $embedTypes;

    /**
     * @var bool
     */
    protected $fullscreen;

    /**
     * @var bool
     */
    protected $hasIcon;

    /**
     * @var string
     */
    protected $machineName;

    /**
     * @var integer
     */
    protected $majorVersion;

    /**
     * @var integer
     */
    protected $minorVersion;

    /**
     * @var integer
     */
    protected $patchVersion;

    /**
     * @var string
     */
    protected $preloadedCss;

    /**
     * @var string
     */
    protected $preloadedJs;

    /**
     * @var bool
     */
    protected $restricted;

    /**
     * @var bool
     */
    protected $runnable;

    /**
     * @var string
     */
    protected $semantics;

    /**
     * @var string
     */
    protected $tutorial_url;

    // Inversed relations (not in DB)

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<Content>
     */
    protected $contents;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\MichielRoos\H5p\Domain\Model\ContentDependency>
     */
    protected $contentDependencies;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\MichielRoos\H5p\Domain\Model\LibraryDependency>
     */
    protected $libraryDependencies;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\MichielRoos\H5p\Domain\Model\ContentDependency>
     */
    protected $libraryTranslations;

    /**
     * @var string
     */
    protected $metadataSettings;

//    /**
//     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<CachedAsset>
//     */
//    protected $cachedAssets;
//
//    /**
//     * @var PersistentResource
//     */
//    protected $zippedLibraryFile;
//
//    /**
//     * @var LibraryUpgradeService
//     */
//    protected $libraryUpgradeService;

    /**
     * @var \MichielRoos\H5p\Domain\Repository\LibraryDependencyRepository
     */
    protected $libraryDependencyRepository;

    /**
     * Library constructor.
     */
    public function __construct()
    {
    }

    /**
     * Creates a library from a metadata array.
     *
     * @param array $libraryData
     *
     * @return Library
     * @throws \Exception
     */
    public static function createFromLibraryData(array &$libraryData)
    {
        $libraryData['__preloadedJs'] = self::pathsToCsv($libraryData, 'preloadedJs');
        $libraryData['__preloadedCss'] = self::pathsToCsv($libraryData, 'preloadedCss');

        $libraryData['__dropLibraryCss'] = '0';
        if (isset($libraryData['dropLibraryCss'])) {
            $libs = [];
            foreach ($libraryData['dropLibraryCss'] as $lib) {
                $libs[] = $lib['machineName'];
            }
            $libraryData['__dropLibraryCss'] = implode(', ', $libs);
        }

        $libraryData['__embedTypes'] = '';
        if (isset($libraryData['embedTypes'])) {
            $libraryData['__embedTypes'] = implode(', ', $libraryData['embedTypes']);
        }
        if (!isset($libraryData['semantics'])) {
            $libraryData['semantics'] = '';
        }
        if (!isset($libraryData['hasIcon'])) {
            $libraryData['hasIcon'] = 0;
        }
        if (!isset($libraryData['fullscreen'])) {
            $libraryData['fullscreen'] = 0;
        }

        $library = new Library();
        $library->updateFromLibraryData($libraryData);
        $library->setCreatedAt(new \DateTime());
        $library->setUpdatedAt(new \DateTime());
        $library->setRestricted(false);
        $library->setTutorialUrl('');
        return $library;
    }

    /**
     * Convert list of file paths to csv
     *
     * @param array $library
     *  Library data as found in library.json files
     * @param string $key
     *  Key that should be found in $libraryData
     *
     * @return string
     *  file paths separated by ', '
     */
    private static function pathsToCsv($library, $key)
    {
        if (isset($library[$key])) {
            $paths = [];
            foreach ($library[$key] as $file) {
                $paths[] = $file['path'];
            }
            return implode(', ', $paths);
        }
        return '';
    }

    /**
     * @param array $libraryData
     *
     * @throws \Exception
     */
    public function updateFromLibraryData(array $libraryData)
    {
        $this->setUpdatedAt(new \DateTime());
        $this->setTitle($libraryData['machineName']);
        $this->setTitle($libraryData['title']);
        $this->setMachineName($libraryData['machineName']);
        $this->setMajorVersion($libraryData['majorVersion']);
        $this->setMinorVersion($libraryData['minorVersion']);
        $this->setPatchVersion($libraryData['patchVersion']);
        $this->setRunnable($libraryData['runnable']);
        $this->setHasIcon($libraryData['hasIcon'] ? true : false);
        $this->setAddTo(empty($libraryData['addTo']) ? null : json_encode($libraryData['addTo']));
        $this->setMetadataSettings($libraryData['metadataSettings']);
        if (isset($libraryData['semantics'])) {
            $this->setSemantics($libraryData['semantics']);
        }
        if (isset($libraryData['fullscreen'])) {
            $this->setFullscreen($libraryData['fullscreen']);
        }
        if (isset($libraryData['__embedTypes'])) {
            $this->setEmbedTypes($libraryData['__embedTypes']);
            $contents = $this->getContents();
            if (is_array($contents)) {
                /** @var Content $content */
                foreach ($contents as $content) {
                    /** Embed types might have changed, so we trigger a redetermination */
                    $content->getEmbedType();
                }
            }
        } elseif (isset($libraryData['embedTypes'])) {
            $this->setEmbedTypes(implode(', ', $libraryData['embedTypes']));
            $contents = $this->getContents();
            if (is_array($contents)) {
                /** @var Content $content */
                foreach ($contents as $content) {
                    /** Embed types might have changed, so we trigger a redetermination */
                    $content->getEmbedType();
                }
            }
        }
        if (isset($libraryData['__preloadedJs'])) {
            $this->setPreloadedJs($libraryData['__preloadedJs']);
        } elseif (isset($libraryData['embedTypes'])) {
            $this->setPreloadedJs(self::pathsToCsv($libraryData, 'preloadedJs'));
        }
        if (isset($libraryData['__preloadedCss'])) {
            $this->setPreloadedCss($libraryData['__preloadedCss']);
        } elseif (isset($libraryData['preloadedCss'])) {
            $this->setPreloadedCss(self::pathsToCsv($libraryData, 'preloadedCss'));
        }
        if (isset($libraryData['__dropLibraryCss'])) {
            $this->setDropLibraryCss($libraryData['__dropLibraryCss']);
        } elseif (isset($libraryData['dropLibraryCss'])) {
            $libs = [];
            foreach ($libraryData['dropLibraryCss'] as $lib) {
                $libs[] = $lib['machineName'];
            }
            $this->setDropLibraryCss(implode(', ', $libs));
        }
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $contents
     */
    public function setContents(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $contents)
    {
        $this->contents = $contents;
    }

    /**
     * @param \MichielRoos\H5p\Domain\Repository\LibraryDependencyRepository $libraryDependencyRepository
     */
    public function injectLibraryDepencencyRepository(LibraryDependencyRepository $libraryDependencyRepository)
    {
        $this->libraryDependencyRepository = $libraryDependencyRepository;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return string
     */
    public function getAddTo()
    {
        return $this->addTo;
    }

    /**
     * @param string $addTo
     */
    public function setAddTo($addTo)
    {
        $this->addTo = $addTo;
    }

    /**
     * @return bool
     */
    public function isRestricted()
    {
        return $this->restricted;
    }

    /**
     * @param bool $restricted
     */
    public function setRestricted(bool $restricted)
    {
        $this->restricted = $restricted;
    }

    /**
     * @return string
     */
    public function getTutorialUrl()
    {
        return $this->tutorial_url;
    }

    /**
     * @param string $tutorial_url
     */
    public function setTutorialUrl($tutorial_url)
    {
        $this->tutorial_url = $tutorial_url;
    }

    /**
     * Returns the library name in a format such as
     * H5P.MultiChoice-1.12
     *
     * @return string
     */
    public function getFolderName()
    {
        return \H5PCore::libraryToString($this->toAssocArray(), true);
    }

    /**
     * Returns an associative array containing the library in the form that
     * H5PFramework->loadLibrary is expected to return.
     * @see H5PFramework::loadLibrary()
     */
    public function toAssocArray()
    {
        // the keys majorVersion and major_version are both used within the h5p library classes. Same goes for minor and patch.
        $libraryArray = [
            'id'             => $this->getUid(),
            'libraryId'      => $this->getUid(),
            'name'           => $this->getTitle(),
            'machineName'    => $this->getMachineName(),
            'title'          => $this->getTitle(),
            'major_version'  => $this->getMajorVersion(),
            'majorVersion'   => $this->getMajorVersion(),
            'minor_version'  => $this->getMinorVersion(),
            'minorVersion'   => $this->getMinorVersion(),
            'patch_version'  => $this->getPatchVersion(),
            'patchVersion'   => $this->getPatchVersion(),
            'embedTypes'     => $this->getEmbedTypes(),
            'preloadedJs'    => $this->getPreloadedJs(),
            'preloadedCss'   => $this->getPreloadedCss(),
            'dropLibraryCss' => $this->getDropLibraryCss(),
            'fullscreen'     => $this->isFullscreen(),
            'runnable'       => $this->isRunnable(),
            'semantics'      => $this->getSemantics(),
            'hasIcon'        => $this->isHasIcon()
        ];

        $libraryDependencies = $this->getLibraryDependencies();

        if ($libraryDependencies instanceof ObjectStorage && $libraryDependencies->count() > 0) {
            /** @var LibraryDependency $dependency */
            foreach ($libraryDependencies as $dependency) {
                $libraryArray[$dependency->getDependencyType() . 'Dependencies'][] = [
                    'machineName'  => $dependency->getRequiredLibrary()->getMachineName(),
                    'majorVersion' => $dependency->getRequiredLibrary()->getMajorVersion(),
                    'minorVersion' => $dependency->getRequiredLibrary()->getMinorVersion()
                ];
            }
        }

        return $libraryArray;
    }

    /**
     * Returns the title
     *
     * @return string $title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets the title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getMachineName()
    {
        return $this->machineName;
    }

    /**
     * @param string $machineName
     */
    public function setMachineName($machineName)
    {
        $this->machineName = $machineName;
    }

    /**
     * @return int
     */
    public function getMajorVersion()
    {
        return $this->majorVersion;
    }

    /**
     * @param int $majorVersion
     */
    public function setMajorVersion($majorVersion)
    {
        $this->majorVersion = $majorVersion;
    }

    /**
     * @return int
     */
    public function getMinorVersion()
    {
        return $this->minorVersion;
    }

    /**
     * @param int $minorVersion
     */
    public function setMinorVersion($minorVersion)
    {
        $this->minorVersion = $minorVersion;
    }

    /**
     * @return int
     */
    public function getPatchVersion()
    {
        return $this->patchVersion;
    }

    /**
     * @param int $patchVersion
     */
    public function setPatchVersion($patchVersion)
    {
        $this->patchVersion = $patchVersion;
    }

    /**
     * @return string
     */
    public function getEmbedTypes()
    {
        return $this->embedTypes;
    }

    /**
     * @param string $embedTypes
     */
    public function setEmbedTypes($embedTypes)
    {
        $this->embedTypes = $embedTypes;
    }

    /**
     * @return string
     */
    public function getPreloadedJs()
    {
        return $this->preloadedJs;
    }

    /**
     * @param string $preloadedJs
     */
    public function setPreloadedJs($preloadedJs)
    {
        $this->preloadedJs = $preloadedJs;
    }

    /**
     * @return string
     */
    public function getPreloadedCss()
    {
        return $this->preloadedCss;
    }

    /**
     * @param string $preloadedCss
     */
    public function setPreloadedCss($preloadedCss)
    {
        $this->preloadedCss = $preloadedCss;
    }

    /**
     * @return string
     */
    public function getDropLibraryCss()
    {
        return $this->dropLibraryCss;
    }

    /**
     * @param string $dropLibraryCss
     */
    public function setDropLibraryCss($dropLibraryCss)
    {
        $this->dropLibraryCss = $dropLibraryCss;
    }

    /**
     * @return bool
     */
    public function isFullscreen()
    {
        return $this->fullscreen;
    }

    /**
     * @param bool $fullscreen
     */
    public function setFullscreen($fullscreen)
    {
        $this->fullscreen = $fullscreen;
    }

    /**
     * @return bool
     */
    public function isRunnable()
    {
        return $this->runnable;
    }

    /**
     * @param bool $runnable
     */
    public function setRunnable($runnable)
    {
        $this->runnable = $runnable;
    }

    /**
     * @return string
     */
    public function getSemantics()
    {
        return $this->semantics;
    }

    /**
     * @param string $semantics
     */
    public function setSemantics($semantics)
    {
        $this->semantics = $semantics;
    }

    /**
     * @return bool
     */
    public function isHasIcon()
    {
        return $this->hasIcon;
    }

    /**
     * @param bool $hasIcon
     */
    public function setHasIcon($hasIcon)
    {
        $this->hasIcon = $hasIcon;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getLibraryDependencies()
    {
        return $this->libraryDependencies;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $libraryDependencies
     */
    public function setLibraryDependencies(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $libraryDependencies)
    {
        $this->libraryDependencies = $libraryDependencies;
    }

    /**
     * Returns the library name in a format such as
     * H5P.MultiChoice 1.12
     *
     * @return string
     */
    public function getString()
    {
        return \H5PCore::libraryToString($this->toAssocArray(), false);
    }

    /**
     * Returns this library as a stdClass object in a format that H5P expects
     * when it calls the method:
     * @return \stdClass
     * @see \H5peditorStorage::getLibraries()
     */
    public function toStdClass()
    {
        return (object)$this->toAssocArray();
    }

    /**
     * @return array
     */
    public function getDependentLibrariesAsLibraryObjects()
    {
        return $this->libraryDependencies->map(function ($libraryDependency) {
            /** @var LibraryDependency $libraryDependency */
            return $libraryDependency->getRequiredLibrary();
        })->toArray();
    }

    /**
     * @return array
     */
    public function getDependentLibraries()
    {
        $dependencies = $this->libraryDependencyRepository->findByRequiredLibrary($this)->toArray();
        return array_map(function ($libraryDependency) {
            /** @var LibraryDependency $libraryDependency */
            return $libraryDependency->getLibrary();
        }, $dependencies);
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getLibraryTranslations()
    {
        return $this->libraryTranslations;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $libraryTranslations
     */
    public function setLibraryTranslations(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $libraryTranslations)
    {
        $this->libraryTranslations = $libraryTranslations;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getCachedAssets()
    {
        return $this->cachedAssets;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $cachedAssets
     */
    public function setCachedAssets(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $cachedAssets)
    {
        $this->cachedAssets = $cachedAssets;
    }

    /**
     * @param CachedAsset $cachedAsset
     */
    public function addCachedAsset(CachedAsset $cachedAsset)
    {
        $this->cachedAssets->add($cachedAsset);
    }

//    /**
//     * @return PersistentResource|null
//     */
//    public function getZippedLibraryFile()
//    {
//        return $this->zippedLibraryFile;
//    }
//
//    /**
//     * @param PersistentResource $zippedLibraryFile
//     */
//    public function setZippedLibraryFile(PersistentResource $zippedLibraryFile)
//    {
//        $this->zippedLibraryFile = $zippedLibraryFile;
//    }

    /**
     * @return string
     */
    public function getMetadataSettings()
    {
        return $this->metadataSettings;
    }

    /**
     * @param string $metadataSettings
     */
    public function setMetadataSettings($metadataSettings)
    {
        $this->metadataSettings = $metadataSettings;
    }
}
