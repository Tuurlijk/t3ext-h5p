<?php

namespace MichielRoos\H5p\Adapter\Editor;

use H5peditorFile;
use MichielRoos\H5p\Adapter\Core\Framework;
use MichielRoos\H5p\Domain\Model\Library;
use MichielRoos\H5p\Domain\Model\LibraryTranslation;
use MichielRoos\H5p\Domain\Repository\LibraryRepository;
use MichielRoos\H5p\Domain\Repository\LibraryTranslationRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

class EditorStorage implements \H5peditorStorage
{
    /**
     * @var LibraryRepository
     */
    protected $libraryRepository;

    /**
     * @var LibraryTranslationRepository|object
     */
    private $libraryTranslationRepository;

    /**
     * EditorStorage constructor.
     */
    public function __construct()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->libraryRepository = $objectManager->get(LibraryRepository::class);
        $this->libraryTranslationRepository = $objectManager->get(LibraryTranslationRepository::class);
    }

    /**
     * Load language file(JSON) from database.
     * This is used to translate the editor fields(title, description etc.)
     *
     * @param string $machineName The machine readable name of the library(content type)
     * @param int $majorVersion Major part of version number
     * @param int $minorVersion Minor part of version number
     * @param string $language Language code
     * @return string Translation in JSON format
     */
    public function getLanguage($machineName, $majorVersion, $minorVersion, $language)
    {
        $translation = false;
        $library = $this->libraryRepository->findOneByMachinenameMajorVersionAndMinorVersion($machineName, $majorVersion, $minorVersion);
        /** @var LibraryTranslation $translation */
        $libraryTranslation = $this->libraryTranslationRepository->findOneByLibraryAndLanguage($library, $language);
        if ($libraryTranslation instanceof LibraryTranslation) {
            $translation = $libraryTranslation->getTranslation();
        }
        return $translation;
    }

    /**
     * Load a list of available language codes from the database.
     *
     * @param string $machineName The machine readable name of the library(content type)
     * @param int $majorVersion Major part of version number
     * @param int $minorVersion Minor part of version number
     * @return array List of possible language codes
     */
    public function getAvailableLanguages($machineName, $majorVersion, $minorVersion)
    {
        // Note that the parameter $machineName is contains $name instead
        $translationCodes = ['en'];
        $library = $this->libraryRepository->findOneByMachinenameMajorVersionAndMinorVersion($machineName, $majorVersion, $minorVersion);
        $translations = $this->libraryTranslationRepository->findByLibrary($library->getUid());
        /** @var LibraryTranslation $translation */
        foreach ($translations as $translation) {
            $translationCodes[] = $translation->getLanguageCode();
        }
        return $translationCodes;
    }

    /**
     * "Callback" for mark the given file as a permanent file.
     * Used when saving content that has new uploaded files.
     *
     * @param int $fileId
     */
    public function keepFile($fileId)
    {
        // TODO: Implement keepFile() method.
        \MichielRoos\H5p\Utility\MaintenanceUtility::methodMissing(__CLASS__, __FUNCTION__);
    }

    /**
     * Decides which content types the editor should have.
     *
     * Two usecases:
     * 1. No input, will list all the available content types.
     * 2. Libraries supported are specified, load additional data and verify
     * that the content types are available. Used by e.g. the Presentation Tool
     * Editor that already knows which content types are supported in its
     * slides.
     *
     * @param array $libraries List of library names + version to load info for
     * @return array List of all libraries loaded
     */
    public function getLibraries($libraries = NULL)
    {
        $librariesWithDetails = [];

        if ($libraries !== null) {
            // Get details for the specified libraries only.
            foreach ($libraries as $libraryData) {
                /** @var Library $library */
                $library = $this->libraryRepository->findOneByMachinenameMajorVersionAndMinorVersion(
                    $libraryData->name,
                    $libraryData->majorVersion,
                    $libraryData->minorVersion
                );
                if ($library === null || $library->getSemantics() === null) {
                    continue;
                }
                // Library found, add details to list
                $libraryData->tutorialUrl = $library->getTutorialUrl();
                $libraryData->title = $library->getTitle();
                $libraryData->runnable = $library->isRunnable();
                $libraryData->restricted = false; // for now
                $libraryData->metadataSettings = json_decode($library->getMetadataSettings());
                // TODO: Implement the below correctly with auth check
                // $libraryData->restricted = $super_user ? FALSE : $library->isRestricted();
                $librariesWithDetails[] = $libraryData;
            }
            // Done, return list with library details
            return $librariesWithDetails;
        }

        // Load all libraries that have semantics and are runnable
        $this->libraryRepository->setDefaultOrderings(['title' => QueryInterface::ORDER_ASCENDING]);
        $libraries = $this->libraryRepository->findByRunnable([true]);
        /** @var Library $library */
        foreach ($libraries as $library) {
            if ($library->getSemantics() === null) {
                continue;
            }
            $libraryData = $library->toStdClass();
            // Make sure we only display the newest version of a library.
            foreach ($librariesWithDetails as $key => $existingLibrary) {
                if ($libraryData->name === $existingLibrary->name) {

                    // Found library with same name, check versions
                    if (($libraryData->majorVersion === $existingLibrary->majorVersion &&
                            $libraryData->minorVersion > $existingLibrary->minorVersion) ||
                        ($libraryData->majorVersion > $existingLibrary->majorVersion)) {
                        // This is a newer version
                        $existingLibrary->isOld = true;
                    } else {
                        // This is an older version
                        $libraryData->isOld = true;
                    }
                }
            }

            // Check to see if content type should be restricted
            $libraryData->restricted = false; // for now
            // TODO: Implement the below correctly with auth check
            // $libraryData->restricted = $super_user ? FALSE : $library->isRestricted();

            // Add new library
            $librariesWithDetails[] = $libraryData;
        }
        return $librariesWithDetails;
    }

    /**
     * Alter styles and scripts
     *
     * @param array $files
     *  List of files as objects with path and version as properties
     * @param array $libraries
     *  List of libraries indexed by machineName with objects as values. The objects
     *  have majorVersion and minorVersion as properties.
     */
    public function alterLibraryFiles(&$files, $libraries)
    {
        // TODO: Implement alterLibraryFiles() method.
        \MichielRoos\H5p\Utility\MaintenanceUtility::methodMissing(__CLASS__, __FUNCTION__);
    }

    /**
     * Saves a file or moves it temporarily. This is often necessary in order to
     * validate and store uploaded or fetched H5Ps.
     *
     * @param string $data Uri of data that should be saved as a temporary file
     * @param boolean $move_file Can be set to TRUE to move the data instead of saving it
     *
     * @return bool|object Returns false if saving failed or the path to the file
     *  if saving succeeded
     */
    public static function saveFileTemporarily($data, $move_file)
    {
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        $storage = $resourceFactory->getDefaultStorage();
        $h5pFramework    = GeneralUtility::makeInstance(Framework::class, $storage);

        $path = $h5pFramework->getUploadedH5pPath();

        if ($move_file) {
            // Move so core can validate the file extension.
            rename($data, $path);
        }
        else {
            // Create file from data
            file_put_contents($path, $data);
        }

        return (object) array (
            'dir' => dirname($path),
            'fileName' => basename($path)
        );
    }

    /**
     * Marks a file for later cleanup, useful when files are not instantly cleaned
     * up. E.g. for files that are uploaded through the editor.
     *
     * @param H5peditorFile
     * @param $content_id
     */
    public static function markFileForCleanup($file, $content_id)
    {
        // TODO: Implement markFileForCleanup() method.
        \MichielRoos\H5p\Utility\MaintenanceUtility::methodMissing(__CLASS__, __FUNCTION__);
    }

    /**
     * Clean up temporary files
     *
     * @param string $filePath Path to file or directory
     */
    public static function removeTemporarilySavedFiles($filePath)
    {
        if (is_dir($filePath)) {
            \H5PCore::deleteFileTree($filePath);
        }
        elseif (is_file($filePath)) {
            unlink($filePath);
        }
    }
}
