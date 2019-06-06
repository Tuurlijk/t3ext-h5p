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

use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\File\ExtendedFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class FileStorage
 */
class FileStorage implements \H5PFileStorage, SingletonInterface
{
    /**
     * @var string|string
     */
    private $basePath;

    /**
     * @var ResourceStorage
     */
    private $storage;

    /**
     * FileStorageService constructor.
     *
     * @param ResourceStorage $storage
     * @param string $path
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderWritePermissionsException
     */
    public function __construct(ResourceStorage $storage, $path = 'h5p')
    {
        $this->storage = $storage;
        $this->basePath = $path ?: 'h5p';

        // Ensure base directories exist
        foreach (['cachedassets', 'content', 'editor/images', 'exports', 'libraries', 'packages'] as $name) {
            $folder = GeneralUtility::makeInstance(Folder::class, $this->storage, $this->basePath . DIRECTORY_SEPARATOR . $name, $name);
            if (!$this->storage->hasFolder($folder->getIdentifier())) {
                $this->storage->createFolder($this->basePath . DIRECTORY_SEPARATOR . $name);
            }
        }
    }

    /**
     * Store the library folder.
     *
     * @param array $library
     *  Library properties
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException
     * @throws \TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderWritePermissionsException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientUserPermissionsException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InvalidPathException
     */
    public function saveLibrary($library)
    {
        $name = \H5PCore::libraryToString($library, true);
        $destination = $this->basePath . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $name;

        $destinationFolder = GeneralUtility::makeInstance(Folder::class, $this->storage, $destination, $name);
        if ($this->storage->hasFolder($destinationFolder->getIdentifier())) {
            $this->storage->deleteFolder($destinationFolder, true);
        }
        $this->storage->createFolder($destination);

        $source = $library['uploadDirectory'];
        /** @var \SplFileInfo $fileInfo */
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $fileInfo) {
            $pathName = $fileInfo->getPathname();
            $dir = str_replace($source, '', $pathName);
            $dir = ltrim($dir, '/');
            if ($fileInfo->isDir()) {
                $this->storage->createFolder($destination . DIRECTORY_SEPARATOR . $dir);
            }
            if ($fileInfo->isFile()) {
                $targetDirectory = ltrim(str_replace($source, '', $fileInfo->getPath()), DIRECTORY_SEPARATOR);
                $destinationFolder = GeneralUtility::makeInstance(Folder::class, $this->storage, $destination . DIRECTORY_SEPARATOR . $targetDirectory, $targetDirectory);
                $this->storage->addFile($fileInfo->getPathname(), $destinationFolder, $fileInfo->getFilename());
            }
        }
    }

    /**
     * Store the content folder.
     *
     * @param string $source
     *  Path on file system to content directory.
     * @param array $content
     *  Content properties
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException
     * @throws \TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderWritePermissionsException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientUserPermissionsException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InvalidPathException
     */
    public function saveContent($source, $content)
    {
        $destination = $this->basePath . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR . $content['id'];

        // Remove any old content
        $destinationFolder = GeneralUtility::makeInstance(Folder::class, $this->storage, $destination, $content['id']);
        if ($this->storage->hasFolder($destinationFolder->getIdentifier())) {
            $this->storage->deleteFolder($destinationFolder, true);
        }
        $this->storage->createFolder($destination);

        /** @var \SplFileInfo $fileInfo */
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $fileInfo) {
            $pathName = $fileInfo->getPathname();
            $dir = str_replace($source, '', $pathName);
            $dir = ltrim($dir, '/');
            if ($fileInfo->isDir()) {
                $this->storage->createFolder($destination . DIRECTORY_SEPARATOR . $dir);
            }
            if ($fileInfo->isFile()) {
                $targetDirectory = ltrim(str_replace($source, '', $fileInfo->getPath()), DIRECTORY_SEPARATOR);
                $destinationFolder = GeneralUtility::makeInstance(Folder::class, $this->storage, $destination . DIRECTORY_SEPARATOR . $targetDirectory, $targetDirectory);
                $this->storage->addFile($fileInfo->getPathname(), $destinationFolder, $fileInfo->getFilename());
            }
        }
    }

    /**
     * Remove content folder.
     *
     * @param array $content
     *  Content properties
     */
    public function deleteContent($content)
    {
        // TODO: Implement deleteContent() method.
    }

    /**
     * Creates a stored copy of the content folder.
     *
     * @param string $id
     *  Identifier of content to clone.
     * @param int $newId
     *  The cloned content's identifier
     */
    public function cloneContent($id, $newId)
    {
        // TODO: Implement cloneContent() method.
    }

    /**
     * Get path to a new unique tmp folder.
     *
     * @return string
     *  Path
     */
    public function getTmpPath()
    {
        $relativeFilename = 'typo3temp/var/h5p/' . sha1(microtime());
        $destination = PATH_site . $relativeFilename;
        GeneralUtility::mkdir_deep($destination);
        return $destination;
    }

    /**
     * Fetch content folder and save in target directory.
     *
     * @param int $id
     *  Content identifier
     * @param string $target
     *  Where the content folder will be saved
     */
    public function exportContent($id, $target)
    {
        // TODO: Implement exportContent() method.
    }

    /**
     * Fetch library folder and save in target directory.
     *
     * @param array $library
     *  Library properties
     * @param string $target
     *  Where the library folder will be saved
     */
    public function exportLibrary($library, $target)
    {
        // TODO: Implement exportLibrary() method.
    }

    /**
     * Save export in file system
     *
     * @param string $source
     *  Path on file system to temporary export file.
     * @param string $filename
     *  Name of export file.
     */
    public function saveExport($source, $filename)
    {
        // TODO: Implement saveExport() method.
    }

    /**
     * Removes given export file
     *
     * @param string $filename
     */
    public function deleteExport($filename)
    {
        // TODO: Implement deleteExport() method.
    }

    /**
     * Check if the given export file exists
     *
     * @param string $filename
     * @return bool
     */
    public function hasExport($filename)
    {
        // TODO: Implement hasExport() method.
    }

    /**
     * Will concatenate all JavaScrips and Stylesheets into two files in order
     * to improve page performance.
     *
     * @param array $files
     *  A set of all the assets required for content to display
     * @param string $key
     *  Hashed key for cached asset
     */
    public function cacheAssets(&$files, $key)
    {
        // TODO: Implement cacheAssets() method.
    }

    /**
     * Will check if there are cache assets available for content.
     *
     * @param string $key
     *  Hashed key for cached asset
     * @return array
     */
    public function getCachedAssets($key)
    {
        // TODO: Implement getCachedAssets() method.
    }

    /**
     * Remove the aggregated cache files.
     *
     * @param array $keys
     *   The hash keys of removed files
     */
    public function deleteCachedAssets($keys)
    {
        // TODO: Implement deleteCachedAssets() method.
    }

    /**
     * Read file content of given file and then return it.
     *
     * @param string $file_path
     * @return string contents
     */
    public function getContent($file_path)
    {
        // TODO: Implement getContent() method.
    }

    /**
     * Save files uploaded through the editor.
     * The files must be marked as temporary until the content form is saved.
     *
     * @param \H5peditorFile $file
     * @param int $contentId
     * @return \H5peditorFile
     * @throws \TYPO3\CMS\Core\Resource\Exception
     */
    public function saveFile($file, $contentId)
    {
        // This creates an editortempfile, attaches a resource, publishes that resource, and returns a file ID.

        // If we have a content id set, we assign the file directly to a content element.
        // In the current implementation, this doesn't happen yet - all uploaded editor files are
        // first saved as an EditorTempfile. Therefore, we throw an Excaption if we get a content id.
        if (is_int($contentId) && $contentId > 0) {
            throw new \Exception("Uploading files directly to a Content element is not supported yet.");
        }

        $data = [];
        $namespace = key($_FILES);
        $storageId = $this->storage->getUid();
        $targetFalDirectory = $storageId . ':/h5p/editor/' . $file->getType() . 's';
        $editorFilename = $file->getName();


        $this->registerUploadField($data, $namespace, $targetFalDirectory, $editorFilename);

        $fileProcessor = GeneralUtility::makeInstance(ExtendedFileUtility::class);
        $fileProcessor->init([], []);
        $fileProcessor->setActionPermissions();
        $fileProcessor->start($data);
        $fileProcessor->setExistingFilesConflictMode(DuplicationBehavior::REPLACE);

        $result = $fileProcessor->processData();

        return $file;
    }

    /**
     * @param array &$data
     * @param string $namespace
     * @param string $fieldName
     * @param string $targetDirectory
     * @return void
     */
    protected function registerUploadField(array &$data, $namespace, $targetDirectory = '1:/_temp_/', $editorFilename)
    {
        if (!isset($data['upload'])) {
            $data['upload'] = [];
        }
        $counter = count($data['upload']) + 1;

        $keys = array_keys($_FILES[$namespace]);
        foreach ($keys as $key) {
            if ($key === 'name') {
                $_FILES['upload_' . $counter][$key] = $editorFilename;
            } else {
                $_FILES['upload_' . $counter][$key] = $_FILES[$namespace][$key];
            }
        }
        $data['upload'][$counter] = [
            'data'   => $counter,
            'target' => $targetDirectory,
        ];
    }

    /**
     * Copy a file from another content or editor tmp dir.
     * Used when copy pasting content in H5P.
     *
     * @param string $file path + name
     * @param string|int $fromId Content ID or 'editor' string
     * @param int $toId Target Content ID
     */
    public function cloneContentFile($file, $fromId, $toId)
    {
        // TODO: Implement cloneContentFile() method.
    }

    /**
     * Copy a content from one directory to another. Defaults to cloning
     * content from the current temporary upload folder to the editor path.
     *
     * @param string $source path to source directory
     * @param string $contentId Id of content
     *
     * @return object Object containing h5p json and content json data
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException
     * @throws \TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderWritePermissionsException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientUserPermissionsException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InvalidPathException
     */
    public function moveContentDirectory($source, $contentId = null)
    {
        if ($source === null) {
            return null;
        }

        $destinationFolder = '';
        if ($contentId !== null) {
            $destination = $this->basePath . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR . $contentId;
            $destinationFolder = $contentId;
        } else {
            $destination = $this->basePath . DIRECTORY_SEPARATOR . 'editor';
        }

        // Remove any old content
        if ($destinationFolder !== '') {
            $oldFolder = GeneralUtility::makeInstance(Folder::class, $this->storage, $destination, $destinationFolder);
            if ($this->storage->hasFolder($oldFolder->getIdentifier())) {
                $this->storage->deleteFolder($oldFolder, true);
            }
            $this->storage->createFolder($destination);
        }

        /** @var \SplFileInfo $fileInfo */
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $fileInfo) {
            $pathName = $fileInfo->getPathname();
            $dir = str_replace($source, '', $pathName);
            $dir = ltrim($dir, '/');
            if ($fileInfo->isDir()) {
                $this->storage->createFolder($destination . DIRECTORY_SEPARATOR . $dir);
            }
            if ($fileInfo->isFile()) {
                $targetDirectory = ltrim(str_replace($source, '', $fileInfo->getPath()), DIRECTORY_SEPARATOR);
                $destinationFolder = GeneralUtility::makeInstance(Folder::class, $this->storage, $destination . DIRECTORY_SEPARATOR . $targetDirectory, $targetDirectory);
                $this->storage->addFile($fileInfo->getPathname(), $destinationFolder, $fileInfo->getFilename());
            }
        }

        $contentSource = $source . DIRECTORY_SEPARATOR . 'content';
        // Return the actual content data as JSON, these get handed to the editor for editing by the user
        $h5pJson = $this->getContent($source . DIRECTORY_SEPARATOR . 'h5p.json');
        $contentJson = $this->getContent($contentSource . DIRECTORY_SEPARATOR . 'content.json');

        /**
         * !!HACK!!: Unfortunately, imported persistent resources always have lowercase file endings.
         * In case sensitive file systems, uploaded pictures with uppercase endings do not work.
         * Our only option is to replace them into lowercase. Ew!
         */
        $contentJson = preg_replace_callback('/"images.*\.(.*)"/U', function ($matches) {
            return str_replace($matches[1], strtolower($matches[1]), $matches[0]);
        }, $contentJson);

        return (object)[
            'h5pJson'     => $h5pJson,
            'contentJson' => $contentJson
        ];
    }

    /**
     * Checks to see if content has the given file.
     * Used when saving content.
     *
     * @param string $file path + name
     * @param int $contentId
     * @return string|int File ID or NULL if not found
     */
    public function getContentFile($file, $contentId)
    {
        // TODO: Implement getContentFile() method.
    }

    /**
     * Remove content files that are no longer used.
     * Used when saving content.
     *
     * @param string $file path + name
     * @param int $contentId
     */
    public function removeContentFile($file, $contentId)
    {
        // TODO: Implement removeContentFile() method.
    }

    /**
     * Check if server setup has write permission to
     * the required folders
     *
     * @return bool True if server has the proper write access
     */
    public function hasWriteAccess()
    {
        // TODO: Implement hasWriteAccess() method.
    }

    /**
     * Check if the library has a presave.js in the root folder
     *
     * @param string $libraryName
     * @param string $developmentPath
     * @return bool
     */
    public function hasPresave($libraryName, $developmentPath = null)
    {
        // TODO: Implement hasPresave() method.
    }

    /**
     * Check if upgrades script exist for library.
     *
     * @param string $machineName
     * @param int $majorVersion
     * @param int $minorVersion
     * @return string Relative path
     */
    public function getUpgradeScript($machineName, $majorVersion, $minorVersion)
    {
        // TODO: Implement getUpgradeScript() method.
    }
}
