<?php
namespace MichielRoos\H5p\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use MichielRoos\H5p\Domain\Repository\LibraryDependencyRepository;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class Library
 */
class Library extends AbstractEntity
{
    /**
     * Title
     *
     * @var string
     */
    protected string $title = '';

    /**
     * @var string
     */
    protected string $addTo = '';

    /**
     * @var \DateTime
     */
    protected \DateTime $createdAt;

    /**
     * @var \DateTime
     */
    protected \DateTime $updatedAt;

    /**
     * @var string
     */
    protected string $dropLibraryCss = '';

    /**
     * @var string
     */
    protected string $embedTypes = '';

    /**
     * @var bool
     */
    protected bool $fullscreen;

    /**
     * @var bool
     */
    protected bool $hasIcon;

    /**
     * @var string
     */
    protected string $machineName = '';

    /**
     * @var integer
     */
    protected int $majorVersion;

    /**
     * @var integer
     */
    protected int $minorVersion;

    /**
     * @var integer
     */
    protected int $patchVersion;

    /**
     * @var string
     */
    protected string $preloadedCss = '';

    /**
     * @var string
     */
    protected string $preloadedJs = '';

    /**
     * @var bool
     */
    protected bool $restricted;

    /**
     * @var bool
     */
    protected bool $runnable;

    /**
     * @var string
     */
    protected string $semantics = '';

    /**
     * @var string
     */
    protected string $tutorial_url = '';

    // Inversed relations (not in DB)
    /**
     * @var ObjectStorage<Content>
     */
    protected ObjectStorage $contents;

    /**
     * @var ObjectStorage<ContentDependency>
     */
    protected ObjectStorage $contentDependencies;

    /**
     * @var ObjectStorage<LibraryDependency>
     */
    protected ObjectStorage $libraryDependencies;

    /**
     * @var ObjectStorage<ContentDependency>
     */
    protected ObjectStorage $libraryTranslations;

    /**
     * @var string
     */
    protected string $metadataSettings = '';

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
     * @var LibraryDependencyRepository
     */
    protected LibraryDependencyRepository $libraryDependencyRepository;

    /**
     * Library constructor.
     */
    public function __construct()
    {
        $this->libraryDependencies = new ObjectStorage();
    }

    /**
     * Creates a library from a metadata array.
     *
     * @param array $libraryData
     *
     * @return Library
     * @throws \Exception
     */
    public static function createFromLibraryData(array &$libraryData): Library
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
    private static function pathsToCsv(array $library, string $key): string
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
    public function updateFromLibraryData(array $libraryData): void
    {
        $this->setUpdatedAt(new \DateTime());
        $this->setTitle($libraryData['machineName']);
        $this->setTitle($libraryData['title']);
        $this->setMachineName($libraryData['machineName']);
        $this->setMajorVersion($libraryData['majorVersion']);
        $this->setMinorVersion($libraryData['minorVersion']);
        $this->setPatchVersion($libraryData['patchVersion']);
        $this->setRunnable($libraryData['runnable']);
        $this->setHasIcon((bool)$libraryData['hasIcon']);
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
     * @return ObjectStorage
     */
    public function getContents(): ObjectStorage
    {
        return $this->contents;
    }

    /**
     * @param ObjectStorage $contents
     */
    public function setContents(ObjectStorage $contents): void
    {
        $this->contents = $contents;
    }

    /**
     * @param LibraryDependencyRepository $libraryDependencyRepository
     */
    public function injectLibraryDepencencyRepository(LibraryDependencyRepository $libraryDependencyRepository): void
    {
        $this->libraryDependencyRepository = $libraryDependencyRepository;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt(\DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return string
     */
    public function getAddTo(): string
    {
        return $this->addTo;
    }

    /**
     * @param string $addTo
     */
    public function setAddTo(string $addTo): void
    {
        $this->addTo = $addTo;
    }

    /**
     * @return bool
     */
    public function isRestricted(): bool
    {
        return $this->restricted;
    }

    /**
     * @param bool $restricted
     */
    public function setRestricted(bool $restricted): void
    {
        $this->restricted = $restricted;
    }

    /**
     * @return string
     */
    public function getTutorialUrl(): string
    {
        return $this->tutorial_url;
    }

    /**
     * @param string $tutorial_url
     */
    public function setTutorialUrl(string $tutorial_url): void
    {
        $this->tutorial_url = $tutorial_url;
    }

    /**
     * Returns the library name in a format such as
     * H5P.MultiChoice-1.12
     *
     * @return string
     */
    public function getFolderName(): string
    {
        return \H5PCore::libraryToString($this->toAssocArray(), true);
    }

    /**
     * Returns an associative array containing the library in the form that
     * H5PFramework->loadLibrary is expected to return.
     * @see H5PFramework::loadLibrary()
     */
    public function toAssocArray(): array
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

        if ($libraryDependencies->count() > 0) {
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
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Sets the title
     *
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getMachineName(): string
    {
        return $this->machineName;
    }

    /**
     * @param string $machineName
     */
    public function setMachineName(string $machineName): void
    {
        $this->machineName = $machineName;
    }

    /**
     * @return int
     */
    public function getMajorVersion(): int
    {
        return $this->majorVersion;
    }

    /**
     * @param int $majorVersion
     */
    public function setMajorVersion(int $majorVersion): void
    {
        $this->majorVersion = $majorVersion;
    }

    /**
     * @return int
     */
    public function getMinorVersion(): int
    {
        return $this->minorVersion;
    }

    /**
     * @param int $minorVersion
     */
    public function setMinorVersion(int $minorVersion): void
    {
        $this->minorVersion = $minorVersion;
    }

    /**
     * @return int
     */
    public function getPatchVersion(): int
    {
        return $this->patchVersion;
    }

    /**
     * @param int $patchVersion
     */
    public function setPatchVersion(int $patchVersion): void
    {
        $this->patchVersion = $patchVersion;
    }

    /**
     * @return string
     */
    public function getEmbedTypes(): string
    {
        return $this->embedTypes;
    }

    /**
     * @param string $embedTypes
     */
    public function setEmbedTypes(string $embedTypes): void
    {
        $this->embedTypes = $embedTypes;
    }

    /**
     * @return string
     */
    public function getPreloadedJs(): string
    {
        return $this->preloadedJs;
    }

    /**
     * @param string $preloadedJs
     */
    public function setPreloadedJs(string $preloadedJs): void
    {
        $this->preloadedJs = $preloadedJs;
    }

    /**
     * @return string
     */
    public function getPreloadedCss(): string
    {
        return $this->preloadedCss;
    }

    /**
     * @param string $preloadedCss
     */
    public function setPreloadedCss(string $preloadedCss): void
    {
        $this->preloadedCss = $preloadedCss;
    }

    /**
     * @return string
     */
    public function getDropLibraryCss(): string
    {
        return $this->dropLibraryCss;
    }

    /**
     * @param string $dropLibraryCss
     */
    public function setDropLibraryCss(string $dropLibraryCss): void
    {
        $this->dropLibraryCss = $dropLibraryCss;
    }

    /**
     * @return bool
     */
    public function isFullscreen(): bool
    {
        return $this->fullscreen;
    }

    /**
     * @param bool $fullscreen
     */
    public function setFullscreen(bool $fullscreen): void
    {
        $this->fullscreen = $fullscreen;
    }

    /**
     * @return bool
     */
    public function isRunnable(): bool
    {
        return $this->runnable;
    }

    /**
     * @param bool $runnable
     */
    public function setRunnable(bool $runnable): void
    {
        $this->runnable = $runnable;
    }

    /**
     * @return string
     */
    public function getSemantics(): string
    {
        return $this->semantics;
    }

    /**
     * @param string $semantics
     */
    public function setSemantics(string $semantics): void
    {
        $this->semantics = $semantics;
    }

    /**
     * @return bool
     */
    public function isHasIcon(): bool
    {
        return $this->hasIcon;
    }

    /**
     * @param bool $hasIcon
     */
    public function setHasIcon(bool $hasIcon): void
    {
        $this->hasIcon = $hasIcon;
    }

    /**
     * @return ObjectStorage
     */
    public function getLibraryDependencies(): ObjectStorage
    {
        return $this->libraryDependencies ?? new ObjectStorage();
    }

    /**
     * @param ObjectStorage $libraryDependencies
     */
    public function setLibraryDependencies(ObjectStorage $libraryDependencies): void
    {
        $this->libraryDependencies = $libraryDependencies;
    }

    /**
     * Returns the library name in a format such as
     * H5P.MultiChoice 1.12
     *
     * @return string
     */
    public function getString(): string
    {
        return \H5PCore::libraryToString($this->toAssocArray(), false);
    }

    /**
     * Returns this library as a stdClass object in a format that H5P expects
     * when it calls the method:
     * @return \stdClass
     * @see \H5peditorStorage::getLibraries()
     */
    public function toStdClass(): \stdClass
    {
        return (object)$this->toAssocArray();
    }

    /**
     * @return array
     */
    public function getDependentLibrariesAsLibraryObjects(): array
    {
        return $this->libraryDependencies->map(function ($libraryDependency) {
            /** @var LibraryDependency $libraryDependency */
            return $libraryDependency->getRequiredLibrary();
        })->toArray();
    }

    /**
     * @return array
     */
    public function getDependentLibraries(): array
    {
        $dependencies = $this->libraryDependencyRepository->findByRequiredLibrary($this)->toArray();
        return array_map(function ($libraryDependency) {
            /** @var LibraryDependency $libraryDependency */
            return $libraryDependency->getLibrary();
        }, $dependencies);
    }

    /**
     * @return ObjectStorage
     */
    public function getLibraryTranslations(): ObjectStorage
    {
        return $this->libraryTranslations;
    }

    /**
     * @param ObjectStorage $libraryTranslations
     */
    public function setLibraryTranslations(ObjectStorage $libraryTranslations): void
    {
        $this->libraryTranslations = $libraryTranslations;
    }

    /**
     * @return ObjectStorage
     */
    public function getCachedAssets(): ObjectStorage
    {
        return $this->cachedAssets;
    }

    /**
     * @param ObjectStorage $cachedAssets
     */
    public function setCachedAssets(ObjectStorage $cachedAssets): void
    {
        $this->cachedAssets = $cachedAssets;
    }

    /**
     * @param CachedAsset $cachedAsset
     */
    public function addCachedAsset(CachedAsset $cachedAsset): void
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
    public function getMetadataSettings(): string
    {
        return $this->metadataSettings;
    }

    /**
     * @param string $metadataSettings
     */
    public function setMetadataSettings(string $metadataSettings): void
    {
        $this->metadataSettings = $metadataSettings;
    }
}
