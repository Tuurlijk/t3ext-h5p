<?php
namespace MichielRoos\H5p\Adapter\Core;

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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use MichielRoos\H5p\Domain\Model\CachedAsset;
use MichielRoos\H5p\Domain\Model\ConfigSetting;
use MichielRoos\H5p\Domain\Model\Content;
use MichielRoos\H5p\Domain\Model\ContentDependency;
use MichielRoos\H5p\Domain\Model\ContentTypeCacheEntry;
use MichielRoos\H5p\Domain\Model\FileReference;
use MichielRoos\H5p\Domain\Model\Library;
use MichielRoos\H5p\Domain\Model\LibraryDependency;
use MichielRoos\H5p\Domain\Model\LibraryTranslation;
use MichielRoos\H5p\Domain\Repository\CachedAssetRepository;
use MichielRoos\H5p\Domain\Repository\ConfigSettingRepository;
use MichielRoos\H5p\Domain\Repository\ContentDependencyRepository;
use MichielRoos\H5p\Domain\Repository\ContentRepository;
use MichielRoos\H5p\Domain\Repository\ContentTypeCacheEntryRepository;
use MichielRoos\H5p\Domain\Repository\LibraryDependencyRepository;
use MichielRoos\H5p\Domain\Repository\LibraryRepository;
use MichielRoos\H5p\Domain\Repository\LibraryTranslationRepository;
use stdClass;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Class Framework
 */
class Framework implements \H5PFrameworkInterface, SingletonInterface
{
    /**
     * @var string
     */
    public static $version = '1.0.2';

    /**
     * @var ContentTypeCacheEntryRepository
     */
    protected $contentTypeCacheEntryRepository;

    /**
     * @var \H5PCore
     */
    protected $h5pCore;

    /**
     * @var PersistenceManager
     */
    protected $persistenceManager;

    /**
     * Path to a temporary folder where uploaded H5P content is processed.
     * Needs to be stable during one request.
     *
     * @var string
     */
    protected $uploadedH5pFolderPath;

    /**
     * Path to a temporary H5P file.
     * Needs to be stable during one request.
     *
     * @var string
     */
    protected $uploadedH5pPath;

    /**
     * @var array
     */
    private $messages = [];

    /**
     * @var mixed|\TYPO3\CMS\Core\Database\DatabaseConnection
     */
    private $databaseLink;

    /**
     * @var string
     */
    private $localTmpFile;

    /**
     * @var ResourceStorage
     */
    private $storage;

    /**
     * @var FileReference
     */
    private $package;

    /**
     * @var LibraryRepository|object
     */
    private $libraryRepository;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var ConfigSettingRepository|object
     */
    private $configSettingRepository;

    /**
     * @var LibraryTranslationRepository|object
     */
    private $libraryTranslationRepository;

    /**
     * @var ContentRepository|object
     */
    private $contentRepository;

    /**
     * @var LibraryDependencyRepository|object
     */
    private $libraryDependencyRepository;

    /**
     * @var ContentDependencyRepository
     */
    private $contentDependencyRepository;

    /**
     * @var CachedAssetRepository|object
     */
    private $cachedAssetRepository;

    /**
     * H5pFrameworkService constructor.
     * @param ResourceStorage $storage
     */
    public function __construct(ResourceStorage $storage = null)
    {
        $this->storage = $storage;
        $this->databaseLink = $GLOBALS['TYPO3_DB'];
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $this->cachedAssetRepository = $this->objectManager->get(CachedAssetRepository::class);
        $this->configSettingRepository = $this->objectManager->get(ConfigSettingRepository::class);
        $this->contentRepository = $this->objectManager->get(ContentRepository::class);
        $this->contentDependencyRepository = $this->objectManager->get(ContentDependencyRepository::class);
        $this->contentTypeCacheEntryRepository = $this->objectManager->get(ContentTypeCacheEntryRepository::class);
        $this->libraryRepository = $this->objectManager->get(LibraryRepository::class);
        $this->libraryDependencyRepository = $this->objectManager->get(LibraryDependencyRepository::class);
        $this->libraryTranslationRepository = $this->objectManager->get(LibraryTranslationRepository::class);
    }

    /**
     * Set the current package file to operate on
     *
     * @param \MichielRoos\H5p\Domain\Model\FileReference $file
     */
    public function setPackageFile(FileReference $file)
    {
        $this->package = $file;
        $this->localTmpFile = $this->package->getOriginalResource()->getForLocalProcessing();
    }

    /**
     * Returns info for the current platform
     *
     * @return array
     *   An associative array containing:
     *   - name: The name of the platform, for instance "Wordpress"
     *   - version: The version of the platform, for instance "4.0"
     *   - h5pVersion: The version of the H5P plugin/module
     * @throws \TYPO3\CMS\Core\Package\Exception
     */
    public function getPlatformInfo()
    {
        return [
            'name'       => 'TYPO3',
            'version'    => TYPO3_version,
            'h5pVersion' => ExtensionManagementUtility::getExtensionVersion('h5p')
        ];
    }

    /**
     * Fetches a file from a remote server using HTTP GET
     *
     * @param string $url Where you want to get or send data.
     * @param array $data Data to post to the URL.
     * @param bool $blocking Set to 'FALSE' to instantly time out (fire and forget).
     * @param string $stream Path to where the file should be saved.
     * @return string The content (response body). NULL if something went wrong
     */
    public function fetchExternalData($url, $data = null, $blocking = true, $stream = '')
    {
        $client = new Client();
        $options = [
            // if $blocking is set, we want to do a synchronous request
            'synchronous' => $blocking,
            // post data goes in form_params
            'form_params' => $data
        ];

        // if we have something in $stream, we pass it into the sink
        if ($stream) {
            $options['sink'] = $stream;
        }

        try {
            // if $data is provided, we do a POST request - otherwise it's a GET
            $response = $client->request($data === null ? 'GET' : 'POST', $url, $options);
            if ($response->getStatusCode() === 200) {
                return $response->getBody()->getSize() ? $response->getBody()->getContents() : true;
            }
        } catch (GuzzleException $e) {
            $this->setErrorMessage($e->getMessage(), 'failed-fetching-external-data');
        }
        return false;
    }

    /**
     * Show the user an error message
     *
     * @param string $message The error message
     * @param string $code An optional code
     */
    public function setErrorMessage($message, $code = null)
    {
        $this->messages['error'][] = (object)[
            'code'    => $code,
            'message' => $message
        ];
    }

    /**
     * Set the tutorial URL for a library. All versions of the library is set
     *
     * @param string $machineName
     * @param string $tutorialUrl
     */
    public function setLibraryTutorialUrl($machineName, $tutorialUrl)
    {
        // TODO: Implement setLibraryTutorialUrl() method.
    }

    /**
     * Show the user an information message
     *
     * @param string $message
     *  The error message
     */
    public function setInfoMessage($message)
    {
        $this->messages['info'][] = $message;
    }

    /**
     * Return messages
     *
     * @param string $type 'info' or 'error'
     * @return string[]
     */
    public function getMessages($type)
    {
        if (empty($this->messages[$type])) {
            return null;
        }
        $messages = $this->messages[$type];
        $this->messages[$type] = [];
        return $messages;
    }

    /**
     * Translation function
     *
     * @param string $message
     *  The english string to be translated.
     * @param array $replacements
     *   An associative array of replacements to make after translation. Incidences
     *   of any key in this array are replaced with the corresponding value. Based
     *   on the first character of the key, the value is escaped and/or themed:
     *    - !variable: inserted as is
     *    - @variable: escape plain text to HTML
     *    - %variable: escape text and theme as a placeholder for user-submitted
     *      content
     * @return string Translated string
     * Translated string
     */
    public function t($message, $replacements = [])
    {
        // Insert !var as is, escape @var and emphasis %var.
        foreach ($replacements as $key => $replacement) {
            $message = str_replace($key, htmlspecialchars($replacement), $message);
        }

        return $message;
    }

    /**
     * Get URL to file in the specific library
     * @param string $libraryFolderName
     * @param string $fileName
     * @return string URL to file
     */
    public function getLibraryFileUrl($libraryFolderName, $fileName)
    {
        $file = $this->storage->getFile('/h5p/libraries/' . $libraryFolderName . '/' . $fileName);
        return '/' . ltrim($file->getPublicUrl(), '/');
    }

    /**
     * Get the Path to the last uploaded h5p
     *
     * @return string
     *   Path to the folder where the last uploaded h5p for this session is located.
     */
    public function getUploadedH5pFolderPath()
    {
        if (!$this->uploadedH5pFolderPath) {
            $this->uploadedH5pFolderPath = $this->getInjectedH5PCore()->fs->getTmpPath();
        }
        return $this->uploadedH5pFolderPath;
    }

    /**
     * @return \H5PCore|CoreFactory|object
     */
    protected function getInjectedH5PCore()
    {
        if ($this->h5pCore === null) {
            $language = ($this->getLanguageService()->lang === 'default') ? 'en' : $this->getLanguageService()->lang;
            $resourceFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();
            $storage = $resourceFactory->getDefaultStorage();
            $h5pFramework = GeneralUtility::makeInstance(Framework::class, $storage);
            $h5pFileStorage = GeneralUtility::makeInstance(FileStorage::class, $storage);
            $this->h5pCore = GeneralUtility::makeInstance(CoreFactory::class, $h5pFramework, $h5pFileStorage, $language);

        }
        return $this->h5pCore;
    }

    /**
     * Returns an instance of LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Get the path to the last uploaded h5p file
     *
     * @return string
     *   Path to the last uploaded h5p
     */
    public function getUploadedH5pPath()
    {
        if (!$this->uploadedH5pPath) {
            $this->uploadedH5pPath = $this->getInjectedH5PCore()->fs->getTmpPath() . '.h5p';
        }
        return $this->uploadedH5pPath;
    }

    /**
     * Get a list of the current installed libraries
     *
     * @return array
     *   Associative array containing one entry per machine name.
     *   For each machineName there is a list of libraries(with different versions)
     */
    public function loadLibraries()
    {
        $installedLibraries = $this->libraryRepository->findAll();

        $versionsArray = [];
        foreach ($installedLibraries as $library) {
            /** @var Library $library */
            $versionsArray[$library->getTitle()][] = $library->toStdClass();
        }

        return $versionsArray;
    }

    /**
     * Returns the URL to the library admin page
     *
     * @return string
     *   URL to admin page
     */
    public function getAdminUrl()
    {
        // TODO: Implement getAdminUrl() method.
    }

    /**
     * Get id to an existing library.
     * If version number is not specified, the newest version will be returned.
     *
     * @param string $machineName
     *   The librarys machine name
     * @param int $majorVersion
     *   Optional major version number for library
     * @param int $minorVersion
     *   Optional minor version number for library
     * @return int
     *   The id of the specified library or FALSE
     */
    public function getLibraryId($machineName, $majorVersion = null, $minorVersion = null)
    {
        $where = 'machine_name = :machineName';
        $arguments = [':machineName' => $machineName];

        if ($majorVersion !== null) {
            // Look for major version
            $where .= ' AND major_version = :major';
            $arguments[':major'] = $majorVersion;
            if ($minorVersion !== null) {
                // Look for minor version
                $where .= ' AND minor_version = :minor';
                $arguments[':minor'] = $minorVersion;
            }
        }

        $statement = $this->databaseLink->prepare_SELECTquery(
            'uid',
            'tx_h5p_domain_model_library',
            $where,
            '',
            'major_version DESC, minor_version DESC, patch_version DESC',
            1,
            $arguments
        );
        $statement->execute($arguments);
        if ($statement->rowCount() > 0) {
            $row = $statement->fetch();
            return $row['uid'];
        }

        return false;
    }

    /**
     * Get file extension whitelist
     *
     * The default extension list is part of h5p, but admins should be allowed to modify it
     *
     * @param bool $isLibrary
     *   TRUE if this is the whitelist for a library. FALSE if it is the whitelist
     *   for the content folder we are getting
     * @param string $defaultContentWhitelist
     *   A string of file extensions separated by whitespace
     * @param string $defaultLibraryWhitelist
     *   A string of file extensions separated by whitespace
     * @return string
     */
    public function getWhitelist($isLibrary, $defaultContentWhitelist, $defaultLibraryWhitelist)
    {
        // TODO: Get this value from a settings page.
        $whitelist = $defaultContentWhitelist;
        if ($isLibrary) {
            $whitelist .= ' ' . $defaultLibraryWhitelist;
        }
        return $whitelist;
    }

    /**
     * Is the library a patched version of an existing library?
     *
     * @param object $library
     *   An associative array containing:
     *   - machineName: The library machineName
     *   - majorVersion: The librarys majorVersion
     *   - minorVersion: The librarys minorVersion
     *   - patchVersion: The librarys patchVersion
     * @return bool
     *   TRUE if the library is a patched version of an existing library
     *   FALSE otherwise
     */
    public function isPatchedLibrary($library)
    {
        $arguments = [
            ':machineName'  => $library['machineName'],
            ':majorVersion' => $library['majorVersion'],
            ':minorVersion' => $library['minorVersion']
        ];
        $statement = $this->databaseLink->prepare_SELECTquery(
            'patch_version',
            'tx_h5p_domain_model_library',
            'machine_name = :machineName
                AND major_version = :majorVersion
                AND minor_version = :minorVersion',
            '',
            'major_version DESC, minor_version DESC, patch_version DESC',
            1,
            $arguments
        );
        $statement->execute($arguments);
        if ($statement->rowCount() > 0) {
            $row = $statement->fetch();
            $result = $row['patch_version'] < $library['patchVersion'];
        }

        return $result;
    }

    /**
     * Is H5P in development mode?
     *
     * @return bool
     *  TRUE if H5P development mode is active
     *  FALSE otherwise
     */
    public function isInDevMode()
    {
        return true;
    }

    /**
     * Is the current user allowed to update libraries?
     *
     * @return bool
     *  TRUE if the user is allowed to update libraries
     *  FALSE if the user is not allowed to update libraries
     */
    public function mayUpdateLibraries()
    {
        return true;
    }

    /**
     * Store data about a library
     *
     * Also fills in the libraryId in the libraryData object if the object is new
     *
     * @param object $libraryData
     *   Associative array containing:
     *   - libraryId: The id of the library if it is an existing library.
     *   - title: The library's name
     *   - machineName: The library machineName
     *   - majorVersion: The library's majorVersion
     *   - minorVersion: The library's minorVersion
     *   - patchVersion: The library's patchVersion
     *   - runnable: 1 if the library is a content type, 0 otherwise
     *   - fullscreen(optional): 1 if the library supports fullscreen, 0 otherwise
     *   - embedTypes(optional): list of supported embed types
     *   - preloadedJs(optional): list of associative arrays containing:
     *     - path: path to a js file relative to the library root folder
     *   - preloadedCss(optional): list of associative arrays containing:
     *     - path: path to css file relative to the library root folder
     *   - dropLibraryCss(optional): list of associative arrays containing:
     *     - machineName: machine name for the librarys that are to drop their css
     *   - semantics(optional): Json describing the content structure for the library
     *   - language(optional): associative array containing:
     *     - languageCode: Translation in json format
     * @param bool $new
     * @return void
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \Exception
     */
    public function saveLibraryData(&$libraryData, $new = true)
    {
        $library = null;
        if ($new) {
            $library = Library::createFromLibraryData($libraryData);
            $this->libraryRepository->add($library);
            // Persist and re-read the entity to generate the library ID in the DB and fill the field
            $this->persistenceManager->persistAll();
            $library = $this->libraryRepository->findByIdentifier($this->persistenceManager->getIdentifierByObject($library));
            $libraryData['libraryId'] = $library->getUid();
        } else {
            /** @var Library $library */
            $library = $this->libraryRepository->findOneByUid($libraryData['libraryId']);
            if ($library === null) {
                throw new Exception("Library with ID " . $libraryData['libraryId'] . " could not be found!");
            }
            $library->updateFromLibraryData($libraryData);
            $this->libraryRepository->update($library);
            $this->deleteLibraryDependencies($libraryData['libraryId']);
        }

        // Update languages
        $translations = $this->libraryTranslationRepository->findByLibrary($library);
        /** @var LibraryTranslation $translation */
        foreach ($translations as $translation) {
            $this->libraryTranslationRepository->remove($translation);
        }
        // Persist before we create new translations
        $this->persistenceManager->persistAll();

        if (isset($libraryData['language'])) {
            foreach ($libraryData['language'] as $languageCode => $translation) {
                $libraryTranslation = LibraryTranslation::create($library, $languageCode, $translation);
                $this->libraryTranslationRepository->add($libraryTranslation);
            }
        }
    }

    /**
     * Delete all dependencies belonging to given library
     *
     * @param int $libraryId
     *   Library identifier
     */
    public function deleteLibraryDependencies($libraryId)
    {
        // TODO: Implement deleteLibraryDependencies() method.
    }

    /**
     * Insert new content.
     *
     * @param array $contentData
     *   An associative array containing:
     *   - id: The content id
     *   - params: The content in json format
     *   - library: An associative array containing:
     *     - libraryId: The id of the main library for this content
     * @param int $contentMainId
     *   Main id for the content if this is a system that supports versions
     * @return int
     * @throws Exception\NotImplementedException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \Exception
     */
    public function insertContent($contentData, $contentMainId = null)
    {
        /** @var Library $library */
        $library = $this->libraryRepository->findOneByUid($contentData['library']['libraryId']);
        $content = Content::createFromContentData($contentData, $library);

        // Persist and re-read the entity to generate the content ID in the DB and fill the field
        $this->contentRepository->add($content);
        $this->persistenceManager->persistAll();
        /** @var Content $content */
        $content = $this->contentRepository->findByIdentifier($this->persistenceManager->getIdentifierByObject($content));

        return $content->getUid();
    }

    /**
     * Update old content.
     *
     * @param array $contentData
     *   An associative array containing:
     *   - id: The content id
     *   - params: The content in json format
     *   - library: An associative array containing:
     *     - libraryId: The id of the main library for this content
     * @param int $contentMainId
     *   Main id for the content if this is a system that supports versions
     * @return int
     * @throws IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \Exception
     */
    public function updateContent($contentData, $contentMainId = null)
    {
        /** @var Content $content */
        $content = $this->contentRepository->findOneByUid($contentData['id']);
        if ($content === null) {
            return 0;
        }

        /** @var Library $library */
        $library = $this->libraryRepository->findOneByUid($contentData['library']['libraryId']);
        if ($library === null) {
            return 0;
        }

        $content->updateFromContentData($contentData, $library);

        $this->contentRepository->update($content);
        return $content->getUid();
    }

    /**
     * Resets marked user data for the given content.
     *
     * @param int $contentId
     */
    public function resetContentUserData($contentId)
    {
        // TODO: Implement resetContentUserData() method.
    }

    /**
     * Save what libraries a library is depending on
     *
     * @param int $libraryId
     *   Library Id for the library we're saving dependencies for
     * @param array $dependencies
     *   List of dependencies as associative arrays containing:
     *   - machineName: The library machineName
     *   - majorVersion: The library's majorVersion
     *   - minorVersion: The library's minorVersion
     * @param string $dependency_type
     *   What type of dependency this is, the following values are allowed:
     *   - editor
     *   - preloaded
     *   - dynamic
     * @throws Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function saveLibraryDependencies($libraryId, $dependencies, $dependency_type)
    {
        $dependingLibrary = $this->libraryRepository->findOneByUid($libraryId);
        if ($dependingLibrary === null) {
            throw new Exception("The Library with ID " . $libraryId . " could not be found.");
        }

        foreach ($dependencies as $dependency) {
            // Load the library we're depending on
            /** @var Library $requiredLibrary */
            $requiredLibrary = $this->libraryRepository->findOneByMachinenameMajorVersionAndMinorVersion(
                $dependency['machineName'],
                $dependency['majorVersion'],
                $dependency['minorVersion']
            );
            // We don't have this library and thus can't register a dependency
            if ($requiredLibrary === null) {
                continue;
            }
            /** @var LibraryDependency $existingDependency */
            $existingDependency = $this->libraryDependencyRepository->findOneByLibraryAndRequiredLibrary($dependingLibrary, $requiredLibrary);
            if ($existingDependency !== null) {
                // Dependency exists, only update the type
                $existingDependency->setDependencyType($dependency_type);
                $this->libraryDependencyRepository->update($existingDependency);
            } else {
                // Depedency does not exist, create it
                $dependency = new LibraryDependency($dependingLibrary, $requiredLibrary, $dependency_type);
                $this->libraryDependencyRepository->add($dependency);
            }
        }
        $this->persistenceManager->persistAll();
    }

    /**
     * Give an H5P the same library dependencies as a given H5P
     *
     * @param int $contentId
     *   Id identifying the content
     * @param int $copyFromId
     *   Id identifying the content to be copied
     * @param int $contentMainId
     *   Main id for the content, typically used in frameworks
     *   That supports versions. (In this case the content id will typically be
     *   the version id, and the contentMainId will be the frameworks content id
     */
    public function copyLibraryUsage($contentId, $copyFromId, $contentMainId = null)
    {
    }

    /**
     * Deletes content data
     *
     * @param int $contentId
     *   Id identifying the content
     */
    public function deleteContentData($contentId)
    {
        // TODO: Implement deleteContentData() method.
    }

    /**
     * Delete what libraries a content item is using
     *
     * @param int $contentId
     *   Content Id of the content we'll be deleting library usage for
     */
    public function deleteLibraryUsage($contentId)
    {
        /** @var ObjectStorage $content */
        $contentDependencies = $this->contentDependencyRepository->findByContent($contentId);
        if ($contentDependencies === null) {
            return;
        }
        foreach ($contentDependencies as $contentDependency) {
            $this->contentDependencyRepository->remove($contentDependency);
        }

        // Persist, because directly afterwards saveLibraryUsage() might be called
        $this->persistenceManager->persistAll();
    }

    /**
     * Saves what libraries the content uses
     *
     * @param int $contentId
     *   Id identifying the content
     * @param array $librariesInUse
     *   List of libraries the content uses. Libraries consist of associative arrays with:
     *   - library: Associative array containing:
     *     - dropLibraryCss(optional): comma separated list of machineNames
     *     - machineName: Machine name for the library
     *     - libraryId: Id of the library
     *   - type: The dependency type. Allowed values:
     *     - editor
     *     - dynamic
     *     - preloaded
     * @throws IllegalObjectTypeException
     */
    public function saveLibraryUsage($contentId, $librariesInUse)
    {
        /** @var Content $content */
        $content = $this->contentRepository->findOneByUid((int)$contentId);
        if ($content === null) {
            return;
        }

        $dropLibraryCssList = [];
        foreach ($librariesInUse as $dependencyData) {
            if (!empty($dependencyData['library']['dropLibraryCss'])) {
                $dropLibraryCssList = array_merge($dropLibraryCssList,
                    explode(', ', $dependencyData['library']['dropLibraryCss']));
            }
        }

        foreach ($librariesInUse as $dependencyData) {
            $contentDependency = new ContentDependency();
            $contentDependency->setContent($content);
            $contentDependency->setLibrary($this->libraryRepository->findOneByUid($dependencyData['library']['libraryId']));
            $contentDependency->setDependencyType($dependencyData['type']);
            $contentDependency->setDropCss(in_array($dependencyData['library']['machineName'], $dropLibraryCssList));
            $contentDependency->setWeight($dependencyData['weight']);
            $this->contentDependencyRepository->add($contentDependency);
        }
        $this->persistenceManager->persistAll();
    }

    /**
     * Get number of content/nodes using a library, and the number of
     * dependencies to other libraries
     *
     * @param int $libraryId
     *   Library identifier
     * @param bool $skipContent
     *   Flag to indicate if content usage should be skipped
     * @return array
     *   Associative array containing:
     *   - content: Number of content using the library
     *   - libraries: Number of libraries depending on the library
     */
    public function getLibraryUsage($libraryId, $skipContent = false)
    {
        /*
         *     return array(
      'content' => $skipContent ? -1 : intval($wpdb->get_var($wpdb->prepare(
          "SELECT COUNT(distinct c.id)
          FROM {$wpdb->prefix}h5p_libraries l
          JOIN {$wpdb->prefix}h5p_contents_libraries cl ON l.id = cl.library_id
          JOIN {$wpdb->prefix}h5p_contents c ON cl.content_id = c.id
          WHERE l.id = %d",
          $id)
        )),
      'libraries' => intval($wpdb->get_var($wpdb->prepare(
          "SELECT COUNT(*)
          FROM {$wpdb->prefix}h5p_libraries_libraries
          WHERE required_library_id = %d",
          $id)
        ))
    );
         */
        // TODO: Implement getLibraryUsage() method.
    }

    /**
     * Loads a library
     *
     * @param string $machineName
     *   The library's machine name
     * @param int $majorVersion
     *   The library's major version
     * @param int $minorVersion
     *   The library's minor version
     * @return array|false
     *   FALSE if the library does not exist.
     *   Otherwise an associative array containing:
     *   - libraryId: The id of the library if it is an existing library.
     *   - title: The library's name
     *   - machineName: The library machineName
     *   - majorVersion: The library's majorVersion
     *   - minorVersion: The library's minorVersion
     *   - patchVersion: The library's patchVersion
     *   - runnable: 1 if the library is a content type, 0 otherwise
     *   - fullscreen(optional): 1 if the library supports fullscreen, 0 otherwise
     *   - embedTypes(optional): list of supported embed types
     *   - preloadedJs(optional): comma separated string with js file paths
     *   - preloadedCss(optional): comma separated sting with css file paths
     *   - dropLibraryCss(optional): list of associative arrays containing:
     *     - machineName: machine name for the librarys that are to drop their css
     *   - semantics(optional): Json describing the content structure for the library
     *   - preloadedDependencies(optional): list of associative arrays containing:
     *     - machineName: Machine name for a library this library is depending on
     *     - majorVersion: Major version for a library this library is depending on
     *     - minorVersion: Minor for a library this library is depending on
     *   - dynamicDependencies(optional): list of associative arrays containing:
     *     - machineName: Machine name for a library this library is depending on
     *     - majorVersion: Major version for a library this library is depending on
     *     - minorVersion: Minor for a library this library is depending on
     *   - editorDependencies(optional): list of associative arrays containing:
     *     - machineName: Machine name for a library this library is depending on
     *     - majorVersion: Major version for a library this library is depending on
     *     - minorVersion: Minor for a library this library is depending on
     */
    public function loadLibrary($machineName, $majorVersion, $minorVersion)
    {
        /** @var Library $library */
        $library = $this->libraryRepository->findOneByMachinenameMajorVersionAndMinorVersion($machineName, $majorVersion,
            $minorVersion);
        if ($library === null) {
            return false;
        }

        $dependencySet = $this->libraryDependencyRepository->findByLibrary($library);

        $dependencies = new ObjectStorage();
        foreach ($dependencySet as $dependency) {
            $dependencies->attach($dependency);
        }

        $library->setLibraryDependencies($dependencies);

        $theLib = $library->toAssocArray();

        return $theLib;
    }

    /**
     * Loads library semantics.
     *
     * @param string $machineName
     *   Machine name for the library
     * @param int $majorVersion
     *   The library's major version
     * @param int $minorVersion
     *   The library's minor version
     * @return string
     *   The library's semantics as json
     */
    public function loadLibrarySemantics($machineName, $majorVersion, $minorVersion)
    {
        /** @var Library $library */
        $library = $this->libraryRepository->findOneByMachinenameMajorVersionAndMinorVersion($machineName, $majorVersion,
            $minorVersion);
        if ($library === null) {
            return null;
        }
        return $library->getSemantics();
    }

    /**
     * Makes it possible to alter the semantics, adding custom fields, etc.
     *
     * @param array $semantics
     *   Associative array representing the semantics
     * @param string $machineName
     *   The library's machine name
     * @param int $majorVersion
     *   The library's major version
     * @param int $minorVersion
     *   The library's minor version
     */
    public function alterLibrarySemantics(&$semantics, $machineName, $majorVersion, $minorVersion)
    {
        // TODO: Implement alterLibrarySemantics() method.
    }

    /**
     * Start an atomic operation against the dependency storage
     */
    public function lockDependencyStorage()
    {
        // TODO: Implement lockDependencyStorage() method.
    }

    /**
     * Stops an atomic operation against the dependency storage
     */
    public function unlockDependencyStorage()
    {
        // TODO: Implement unlockDependencyStorage() method.
    }

    /**
     * Delete a library from database and file system
     *
     * @param stdClass $library
     *   Library object with id, name, major version and minor version.
     */
    public function deleteLibrary($library)
    {
        // TODO: Implement deleteLibrary() method.
    }

    /**
     * Load content.
     *
     * @param int $id
     *   Content identifier
     * @return array
     *   Associative array containing:
     *   - contentId: Identifier for the content
     *   - params: json content as string
     *   - embedType: csv of embed types
     *   - title: The contents title
     *   - language: Language code for the content
     *   - libraryId: Id for the main library
     *   - libraryName: The library machine name
     *   - libraryMajorVersion: The library's majorVersion
     *   - libraryMinorVersion: The library's minorVersion
     *   - libraryEmbedTypes: CSV of the main library's embed types
     *   - libraryFullscreen: 1 if fullscreen is supported. 0 otherwise.
     */
    public function loadContent($id)
    {
        $row = [];
        $statement = $this->databaseLink->prepare_PREPAREDquery(
            '
        SELECT hc.id
              , hc.title
              , hc.parameters AS params
              , hc.filtered
              , hc.slug AS slug
              , hc.user_id
              , hc.embed_type AS embedType
              , hc.disable
              , hl.id AS libraryId
              , hl.name AS libraryName
              , hl.major_version AS libraryMajorVersion
              , hl.minor_version AS libraryMinorVersion
              , hl.embed_types AS libraryEmbedTypes
              , hl.fullscreen AS libraryFullscreen
        FROM tx_h5p_contents hc
        JOIN tx_h5p_libraries hl ON hl.id = hc.library_id
        WHERE hc.id = :id',
            [':id' => (int)$id]
        );
        $result = $statement->execute();
        if ($this->databaseLink->sql_num_rows($result) > 0) {
            $row = $this->databaseLink->sql_fetch_assoc($result);
        }
        return $row;
    }

    /**
     * Load dependencies for the given content of the given type.
     *
     * @param int $id
     *   Content identifier
     * @param string $type
     *   Dependency types. Allowed values:
     *   - editor
     *   - preloaded
     *   - dynamic
     * @return array
     *   List of associative arrays containing:
     *   - libraryId: The id of the library if it is an existing library.
     *   - machineName: The library machineName
     *   - majorVersion: The library's majorVersion
     *   - minorVersion: The library's minorVersion
     *   - patchVersion: The library's patchVersion
     *   - preloadedJs(optional): comma separated string with js file paths
     *   - preloadedCss(optional): comma separated sting with css file paths
     *   - dropCss(optional): csv of machine names
     */
    public function loadContentDependencies($id, $type = null)
    {
        $dependencyArray = [];
        /** @var Content $content */
        $content = $this->contentRepository->findOneByUid((int)$id);
        if ($content === null) {
            return $dependencyArray;
        }

        $this->contentDependencyRepository->setDefaultOrderings(['weight' => QueryInterface::ORDER_ASCENDING]);
        if ($type !== null) {
            $dependencies = $this->contentDependencyRepository->findByContentAndType($content, $type);
        } else {
            $dependencies = $this->contentDependencyRepository->findByContent($content);
        }

        /** @var ContentDependency $dependency */
        foreach ($dependencies as $dependency) {
            $dependencyArray[] = $dependency->toAssocArray();
        }

        return $dependencyArray;
    }

    /**
     * Get stored setting.
     *
     * @param string $name
     *   Identifier for the setting
     * @param string $default
     *   Optional default value if settings is not set
     * @return mixed
     *   Whatever has been stored as the setting
     */
    public function getOption($name, $default = null)
    {
        $value = $default;
        $setting = $this->configSettingRepository->findOneByConfigKey($name);
        if ($setting instanceof ConfigSetting) {
            $value = $setting->getConfigValue();
        }
        return $value;
    }

    /**
     * Stores the given setting.
     * For example when did we last check h5p.org for updates to our libraries.
     *
     * @param string $name
     *   Identifier for the setting
     * @param mixed $value Data
     *   Whatever we want to store as the setting
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function setOption($name, $value)
    {
        $setting = $this->configSettingRepository->findOneByConfigKey($name);
        if ($setting instanceof ConfigSetting) {
            $setting->setConfigValue($value);
            $this->configSettingRepository->update($setting);
        } else {
            $setting = $this->objectManager->get(ConfigSetting::class, $name, $value);
            $this->configSettingRepository->add($setting);
        }
        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->persistAll();
    }

    /**
     * This will update selected fields on the given content.
     *
     * @param int $id Content identifier
     * @param array $fields Content fields, e.g. filtered or slug.
     */
    public function updateContentFields($id, $fields)
    {
        /** @var Content $content */
        $content = $this->contentRepository->findOneByUid($id);
        if ($content === null) {
            return;
        }

        foreach ($fields as $propertyName => $value) {
            ObjectAccess::setProperty($content, $propertyName, $value);
        }

        try {
            $this->contentRepository->update($content);
        } catch (IllegalObjectTypeException $ex) {
            // will never happen
        }
    }

    /**
     * Will clear filtered params for all the content that uses the specified
     * library. This means that the content dependencies will have to be rebuilt,
     * and the parameters re-filtered.
     *
     * @param int $library_id
     */
    public function clearFilteredParameters($library_id)
    {
        // TODO: Implement clearFilteredParameters() method.
    }

    /**
     * Get number of contents that has to get their content dependencies rebuilt
     * and parameters re-filtered.
     *
     * @return int
     */
    public function getNumNotFiltered()
    {
        // TODO: Implement getNumNotFiltered() method.
    }

    /**
     * Get number of contents using library as main library.
     *
     * @param int $libraryId
     * @param null $skip
     * @return void
     */
    public function getNumContent($libraryId, $skip = NULL)
    {
        $library = $this->libraryRepository->findOneByUid($libraryId);
        return $this->contentRepository->countContents($library);
    }

    /**
     * Determines if content slug is used.
     *
     * @param string $slug
     * @return bool
     */
    public function isContentSlugAvailable($slug)
    {
        return $this->contentRepository->findOneBySlug($slug) === null;
    }

    /**
     * Generates statistics from the event log per library
     *
     * @param string $type Type of event to generate stats for
     * @return array Number values indexed by library name and version
     */
    public function getLibraryStats($type)
    {
        return ['none' => 0];
    }

    /**
     * Aggregate the current number of H5P authors
     * @return int
     */
    public function getNumAuthors()
    {
        // TODO: Implement getNumAuthors() method.
    }

    /**
     * Stores hash keys for cached assets, aggregated JavaScripts and
     * stylesheets, and connects it to libraries so that we know which cache file
     * to delete when a library is updated.
     *
     * @param string $key
     *  Hash key for the given libraries
     * @param array $libraries
     *  List of dependencies(libraries) used to create the key
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function saveCachedAssets($key, $libraries)
    {
        /**
         * This is called after FileAdapter->cacheAssets and makes the assignment of
         * CachedAsset and Library.
         * @see FileAdapter::cacheAssets()
         * @see \H5PCore::getDependenciesFiles()
         */

        $cachedAssets = $this->cachedAssetRepository->findByHashKey($key);

        /** @var CachedAsset $cachedAsset */
        foreach ($cachedAssets as $cachedAsset) {
            foreach ($libraries as $libraryData) {
                /** @var Library $library */
                $library = $this->libraryRepository->findOneByUid($libraryData['libraryId']);
                if ($library === null) {
                    continue;
                }
                $cachedAsset->addLibrary($library);
                // Whitelist, as this can be called on GETs
                $this->persistenceManager->whitelistObject($library);
                $this->persistenceManager->whitelistObject($cachedAsset);
                try {
                    $this->libraryRepository->update($library);
                    $this->cachedAssetRepository->update($cachedAsset);
                } catch (IllegalObjectTypeException $ex) {
                    // Swallow, will never happen
                }
            }
        }
    }

    /**
     * Locate hash keys for given library and delete them.
     * Used when cache file are deleted.
     *
     * @param int $library_id
     *  Library identifier
     * @return array
     *  List of hash keys removed
     */
    public function deleteCachedAssets($library_id)
    {
        // TODO: Implement deleteCachedAssets() method.
    }

    /**
     * Get the amount of content items associated to a library
     * return int
     */
    public function getLibraryContentCount()
    {
        $contentCount = ['none' => 0];
        $allContent = $this->contentRepository->findAll();
        if ($allContent) {
            /** @var Content $item */
            foreach ($allContent as $item) {
                $libraryTitle = $item->getLibrary()->getTitle() . ' ' . $item->getLibrary()->getMajorVersion() . '.' . $item->getLibrary()->getMinorVersion();
                if (!array_key_exists($libraryTitle, $contentCount)) {
                    $contentCount[$libraryTitle] = 1;
                } else {
                    $contentCount[$libraryTitle]++;
                }
            }
        }
        return $contentCount;
    }

    /**
     * Will trigger after the export file is created.
     */
    public function afterExportCreated($content, $filename)
    {
        // TODO: Implement afterExportCreated() method.
    }

    /**
     * Check if user has permissions to an action
     *
     * @method hasPermission
     * @param  [H5PPermission] $permission Permission type, ref H5PPermission
     * @param  [int]           $id         Id need by platform to determine permission
     * @return bool
     */
    public function hasPermission($permission, $id = null)
    {
        return true;
        // TODO: Implement hasPermission() method.
    }

    /**
     * Replaces existing content type cache with the one passed in
     *
     * @param object $contentTypeCache Json with an array called 'libraries'
     *  containing the new content type cache that should replace the old one.
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function replaceContentTypeCache($contentTypeCache)
    {
        // Remove all entries and persist
        $this->contentTypeCacheEntryRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        // Create new entries
//        $contentTypeCache = json_decode($contentTypeCache, false);
        foreach ($contentTypeCache->contentTypes as $contentType) {
            $this->contentTypeCacheEntryRepository->add(ContentTypeCacheEntry::create($contentType));
        }
        $this->persistenceManager->persistAll();
    }

    /**
     * Load addon libraries
     *
     * @return array
     */
    public function loadAddons()
    {
        return $this->libraryRepository->getLibraryAddons();
    }

    /**
     * Load config for libraries
     *
     * @param array $libraries
     * @return array
     */
    public function getLibraryConfig($libraries = NULL)
    {
        // TODO: Implement getLibraryConfig() method.
    }

    /**
     * Checks if the given library has a higher version.
     *
     * @param array $library
     * @return boolean
     */
    public function libraryHasUpgrade($library)
    {
        // TODO: Implement libraryHasUpgrade() method.
    }

    /**
     * Convert list of file paths to csv
     *
     * @param array $libraryData
     *  Library data as found in library.json files
     * @param string $key
     *  Key that should be found in $libraryData
     * @return string
     *  file paths separated by ', '
     */
    private function pathsToCsv($libraryData, $key)
    {
        if (isset($libraryData[$key])) {
            $paths = [];
            foreach ($libraryData[$key] as $file) {
                $paths[] = $file['path'];
            }
            return implode(', ', $paths);
        }
        return '';
    }
}
