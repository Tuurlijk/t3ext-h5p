<?php

namespace MichielRoos\H5p\Adapter\Editor;

use MichielRoos\H5p\Domain\Model\Library;
use MichielRoos\H5p\Domain\Repository\ContentTypeCacheEntryRepository;
use MichielRoos\H5p\Domain\Repository\LibraryRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

class EditorAjax implements \H5PEditorAjaxInterface
{
    /**
     * @var LibraryRepository
     */
    protected $libraryRepository;

    /**
     * @var ContentTypeCacheEntryRepository
     */
    protected $contentTypeCacheEntryRepository;

    /**
     * EditorAjax constructor.
     */
    public function __construct()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->libraryRepository = $objectManager->get(LibraryRepository::class);
        $this->contentTypeCacheEntryRepository = $objectManager->get(ContentTypeCacheEntryRepository::class);
    }

    /**
     * Gets latest library versions that exists locally
     *
     * @return array Latest version of all local libraries
     */
    public function getLatestLibraryVersions()
    {
        $this->libraryRepository->setDefaultOrderings([
            'title'        => QueryInterface::ORDER_DESCENDING,
            'majorVersion' => QueryInterface::ORDER_DESCENDING,
            'minorVersion' => QueryInterface::ORDER_DESCENDING
        ]);

        $librariesOrderedByMajorAndMinorVersion = $this->libraryRepository->findByRunnable(1);

        $versionInformation = [];
        /** @var Library $library */
        foreach ($librariesOrderedByMajorAndMinorVersion as $library) {
            $title = $library->getTitle();
            if (array_key_exists($title, $versionInformation)) {
                continue;
            }
            $versionInformation[$title] = (object)[
                'id'            => $library->getUid(),
                'machine_name'  => $library->getMachineName(),
                'title'         => $title,
                'major_version' => $library->getMajorVersion(),
                'minor_version' => $library->getMinorVersion(),
                'patch_version' => $library->getPatchVersion(),
                'restricted'    => $library->isRestricted(),
                'has_icon'      => $library->isHasIcon()
            ];
        }

        return $versionInformation;
    }

    /**
     * Get locally stored Content Type Cache. If machine name is provided
     * it will only get the given content type from the cache
     *
     * @param $machineName
     *
     * @return array|object|null Returns results from querying the database
     */
    public function getContentTypeCache($machineName = NULL)
    {
        if ($machineName != null) {
            return $this->contentTypeCacheEntryRepository->findOneByMachineName($machineName);
        }

        return $this->contentTypeCacheEntryRepository->getContentTypeCacheObjects();
    }

    /**
     * Gets recently used libraries for the current author
     *
     * @return array machine names. The first element in the array is the
     * most recently used.
     */
    public function getAuthorsRecentlyUsedLibraries()
    {
        // TODO: Implement getAuthorsRecentlyUsedLibraries() method.
        return [];
    }

    /**
     * Checks if the provided token is valid for this endpoint
     *
     * @param string $token The token that will be validated for.
     *
     * @return bool True if successful validation
     */
    public function validateEditorToken($token)
    {
        // TODO
        return true;
    }

    /**
     * Get translations for a language for a list of libraries
     *
     * @param array $libraries An array of libraries, in the form "<machineName> <majorVersion>.<minorVersion>
     * @param string $language_code
     * @return array
     */
    public function getTranslations($libraries, $language_code)
    {
        // TODO: Implement getTranslations() method.
        return [];
    }
}
