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

use MichielRoos\H5p\Domain\Model\CachedAsset;
use MichielRoos\H5p\Domain\Repository\CachedAssetRepository;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\File\ExtendedFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * Class FileStorage
 */
class FileStorage implements \H5PFileStorage, SingletonInterface
{
    /**
     * @var string
     */
    private $basePath;

    /**
     * @var string
     */
    private $folderPrefix = 'h5p/';

    /**
     * @var ResourceStorage
     */
    private $storage;

    /**
     * @var CachedAssetRepository|object
     */
    private $cachedAssetRepository;

    /**
     * @var object|PersistenceManager
     */
    private $persistenceManager;

    /**
     * @var object|ObjectManager
     */
    private $objectManager;

    /**
     * FileStorageService constructor.
     *
     * @param ResourceStorage $storage
     * @param string $path
     *
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderWritePermissionsException*@throws \TYPO3\CMS\Extbase\Object\Exception
     */
    public function __construct(ResourceStorage $storage, $path = 'h5p')
    {
        $this->storage = $storage;
        $rootLevelFolder = $this->getRootLevelFolder();
        if ($rootLevelFolder->getIdentifier() === '/h5p/') {
            $this->folderPrefix = '';
        }
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->cachedAssetRepository = $objectManager->get(CachedAssetRepository::class);
        $this->persistenceManager = $objectManager->get(PersistenceManager::class);

        // Ensure base directories exist
        foreach (['cachedassets', 'content', 'editor/audios', 'editor/images', 'editor/videos', 'exports', 'libraries', 'packages'] as $name) {
            $path = $name;
            if ($this->folderPrefix) {
                $path = $this->folderPrefix . $name;
            }
            $folder = GeneralUtility::makeInstance(
                Folder::class,
                $this->storage,
                $path,
                $name
            );
            if (!$this->storage->hasFolderInFolder($folder->getIdentifier(), $rootLevelFolder)) {
                $this->storage->createFolder($path, $rootLevelFolder);
            }
        }
    }

    /**
     * Get the rootlevel folder of the fileMount named h5p
     *
     * @return Folder
     */
    private function getRootLevelFolder() {
        $fileMounts = $this->storage->getFileMounts();
        if (!empty($fileMounts)) {
            foreach ($fileMounts as $fileMount) {
                $folder = $fileMount['folder'];
                if ($folder->getIdentifier() === '/h5p/') {
                    return $folder;
                }
            }
        }
        return $this->storage->getRootLevelFolder();
    }

    /**
     * Store the library folder.
     *
     * @param array $library
     *  Library properties
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderWritePermissionsException
     */
    public function saveLibrary($library)
    {
        $name = \H5PCore::libraryToString($library, true);
        $rootLevelFolder = $this->getRootLevelFolder();
        $destination = 'libraries/' . $name . '/';
        if ($this->folderPrefix) {
            $destination = $this->folderPrefix . $destination;
        }

        $destinationFolder = GeneralUtility::makeInstance(
            Folder::class,
            $this->storage,
            $destination,
            $name
        );
        if ($this->storage->hasFolderInFolder($destinationFolder->getIdentifier(), $rootLevelFolder)) {
            $this->storage->getFolderInFolder($destinationFolder->getIdentifier(), $rootLevelFolder)->delete();
        }
        $libraryFolder = $this->storage->createFolder($destination, $rootLevelFolder);

        $source = str_replace("\\", '/', $library['uploadDirectory']);
        /** @var \SplFileInfo $fileInfo */
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $fileInfo) {
            $pathName = $fileInfo->getPathname();
            $pathName = str_replace("\\", '/', $pathName);
            $dir = str_replace($source, '', $pathName);
            $dir = ltrim($dir, '/');
            if ($fileInfo->isDir()) {
                $this->storage->createFolder($dir, $libraryFolder);
            }
            if ($fileInfo->isFile()) {
                $targetDirectory = ltrim(str_replace($source, '', $fileInfo->getPath()), '/');
                if (!$libraryFolder->hasFolder($targetDirectory)) {
                    $destinationFolder = $libraryFolder->createFolder($targetDirectory);
                } else {
                    $destinationFolder = $libraryFolder->getSubfolder($targetDirectory);
                }
                $destinationFolder->addFile($fileInfo->getPathname(), $fileInfo->getFilename());
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
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderWritePermissionsException
     */
    public function saveContent($source, $content)
    {
        $rootLevelFolder = $this->getRootLevelFolder();
        $destination = 'content/' . $content['id'] . '/';
        if ($this->folderPrefix) {
            $destination = $this->folderPrefix . $destination;
        }

        // Remove any old content
        $destinationFolder = GeneralUtility::makeInstance(
            Folder::class,
            $this->storage,
            $destination,
            $content['id']
        );
        if ($this->storage->hasFolderInFolder($destinationFolder->getIdentifier(), $rootLevelFolder)) {
            $this->storage->getFolderInFolder($destinationFolder->getIdentifier(), $rootLevelFolder)->delete();
        }
        $contentFolder = $this->storage->createFolder($destination, $rootLevelFolder);

        /** @var \SplFileInfo $fileInfo */
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $fileInfo) {
            $pathName = $fileInfo->getPathname();
            $pathName = str_replace("\\", '/', $pathName);
            $dir = str_replace($source, '', $pathName);
            $dir = ltrim($dir, '/');
            if ($fileInfo->isDir()) {
                $this->storage->createFolder($dir, $contentFolder);
            }
            if ($fileInfo->isFile()) {
                $targetDirectory = ltrim(str_replace($source, '', $fileInfo->getPath()), '/');
                if (!$contentFolder->hasFolder($targetDirectory)) {
                    $destinationFolder = $contentFolder->createFolder($targetDirectory);
                } else {
                    $destinationFolder = $contentFolder->getSubfolder($targetDirectory);
                }
                $destinationFolder->addFile($fileInfo->getPathname(), $fileInfo->getFilename());
            }
        }
    }

    /**
     * Remove content folder.
     *
     * @param array $content
     *  Content properties
     *
     * @throws \MichielRoos\H5p\Exception\MethodNotImplementedException
     * @throws \MichielRoos\H5p\Exception\MethodNotImplementedException
     */
    public function deleteContent($content)
    {
        // TODO: Implement deleteContent() method.
        \MichielRoos\H5p\Utility\MaintenanceUtility::methodMissing(__CLASS__, __FUNCTION__);
    }

    /**
     * Creates a stored copy of the content folder.
     *
     * @param string $id
     *  Identifier of content to clone.
     * @param int $newId
     *  The cloned content's identifier
     *
     * @throws \MichielRoos\H5p\Exception\MethodNotImplementedException
     * @throws \MichielRoos\H5p\Exception\MethodNotImplementedException
     */
    public function cloneContent($id, $newId)
    {
        // TODO: Implement cloneContent() method.
        \MichielRoos\H5p\Utility\MaintenanceUtility::methodMissing(__CLASS__, __FUNCTION__);
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
        $destination = Environment::getPublicPath() . '/' . $relativeFilename;
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
     *
     * @throws \MichielRoos\H5p\Exception\MethodNotImplementedException
     * @throws \MichielRoos\H5p\Exception\MethodNotImplementedException
     */
    public function exportContent($id, $target)
    {
        // TODO: Implement exportContent() method.
        \MichielRoos\H5p\Utility\MaintenanceUtility::methodMissing(__CLASS__, __FUNCTION__);
    }

    /**
     * Fetch library folder and save in target directory.
     *
     * @param array $library
     *  Library properties
     * @param string $target
     *  Where the library folder will be saved
     *
     * @throws \MichielRoos\H5p\Exception\MethodNotImplementedException
     * @throws \MichielRoos\H5p\Exception\MethodNotImplementedException
     */
    public function exportLibrary($library, $target)
    {
        // TODO: Implement exportLibrary() method.
        \MichielRoos\H5p\Utility\MaintenanceUtility::methodMissing(__CLASS__, __FUNCTION__);
    }

    /**
     * Save export in file system
     *
     * @param string $source
     *  Path on file system to temporary export file.
     * @param string $filename
     *  Name of export file.
     *
     * @throws \MichielRoos\H5p\Exception\MethodNotImplementedException
     * @throws \MichielRoos\H5p\Exception\MethodNotImplementedException
     */
    public function saveExport($source, $filename)
    {
        // TODO: Implement saveExport() method.
        \MichielRoos\H5p\Utility\MaintenanceUtility::methodMissing(__CLASS__, __FUNCTION__);
    }

    /**
     * Removes given export file
     *
     * @param string $filename
     *
     * @throws \MichielRoos\H5p\Exception\MethodNotImplementedException
     * @throws \MichielRoos\H5p\Exception\MethodNotImplementedException
     */
    public function deleteExport($filename)
    {
        // TODO: Implement deleteExport() method.
        \MichielRoos\H5p\Utility\MaintenanceUtility::methodMissing(__CLASS__, __FUNCTION__);
    }

    /**
     * Check if the given export file exists
     *
     * @param string $filename
     *
     * @return bool
     * @throws \MichielRoos\H5p\Exception\MethodNotImplementedException
     * @throws \MichielRoos\H5p\Exception\MethodNotImplementedException
     */
    public function hasExport($filename)
    {
        // TODO: Implement hasExport() method.
        \MichielRoos\H5p\Utility\MaintenanceUtility::methodMissing(__CLASS__, __FUNCTION__);
    }

    /**
     * Will concatenate all JavaScrips and Stylesheets into two files in order
     * to improve page performance.
     *
     * @param array $files
     *  A set of all the assets required for content to display
     * @param string $key
     *  Hashed key for cached asset
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function cacheAssets(&$files, $key)
    {
        /**
         * The files we get here are published H5P library CSS and JS files.
         * We create and publish the PersistentResource objects and CachedAsset objects
         * here and make the assignment to libraries later when we have that information
         * in H5PFramework->saveCachedAssets().
         * @see H5PFramework::saveCachedAssets()
         * @see \H5PCore::getDependenciesFiles
         */
        foreach ($files as $type => $assets) {
            if (empty($assets)) {
                continue;
            }

            $content = '';
            foreach ($assets as $asset) {
                // Get content from asset file
                $assetContent = file_get_contents(FLOW_PATH_WEB . $asset->path);
                $cssRelPath = preg_replace('/[^\/]+$/', '', $asset->path);

                // Get file content and concatenate
                if ($type === 'scripts') {
                    $content .= $assetContent . ";\n";
                } else {
                    // Rewrite relative URLs used inside stylesheets
                    // TODO: This doesn't work correctly yet
                    $content .= preg_replace_callback(
                            '/url\([\'"]?([^"\')]+)[\'"]?\)/i',
                            function ($matches) use ($cssRelPath) {
                                if (preg_match("/^(data:|([a-z0-9]+:)?\/)/i", $matches[1]) === 1) {
                                    return $matches[0]; // Not relative, skip
                                }
                                return 'url("../../..' . $cssRelPath . $matches[1] . '")';
                            },
                            $assetContent) . "\n";
                }
            }

            $ext = ($type === 'scripts' ? 'js' : 'css');
            $persistentResource = $this->resourceManager->importResourceFromContent($content, $key . '.' . $ext);
            // Create the CachedAsset object here
            $cachedAsset = new CachedAsset();
            $cachedAsset->setHashKey($key);
            $cachedAsset->setType($type);
            $cachedAsset->setResource($persistentResource);
            $this->cachedAssetRepository->add($cachedAsset);
            // whitelist, as this can be called on GET requests
            $this->persistenceManager->whitelistObject($cachedAsset);

            $files[$type] = [
                (object)[
                    'path'    => $this->resourceManager->getPublicPersistentResourceUri($persistentResource),
                    'version' => ''
                ]
            ];
        }
        // Persist, so the cachedasset objects can be found in H5PFramework->saveCachedAssets
        $this->persistenceManager->persistAll();
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
        $files = [];

        $cachedAssets = $this->cachedAssetRepository->findByHashKey($key);

        /** @var CachedAsset $cachedAsset */
        foreach ($cachedAssets as $cachedAsset) {
            if ($cachedAsset->getType() === 'scripts') {
                $files['scripts'] = [
                    (object)[
                        'path'    => $this->resourceManager->getPublicPersistentResourceUri($cachedAsset->getResource()),
                        'version' => ''
                    ]
                ];
            }
            if ($cachedAsset->getType() === 'styles') {
                $files['styles'] = [
                    (object)[
                        'path'    => $this->resourceManager->getPublicPersistentResourceUri($cachedAsset->getResource()),
                        'version' => ''
                    ]
                ];
            }
        }

        return empty($files) ? null : $files;
    }

    /**
     * Remove the aggregated cache files.
     *
     * @param array $keys
     *   The hash keys of removed files
     *
     * @throws \MichielRoos\H5p\Exception\MethodNotImplementedException
     * @throws \MichielRoos\H5p\Exception\MethodNotImplementedException
     */
    public function deleteCachedAssets($keys)
    {
        // TODO: Implement deleteCachedAssets() method.
        \MichielRoos\H5p\Utility\MaintenanceUtility::methodMissing(__CLASS__, __FUNCTION__);
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
        $rootLevelFolder = $this->getRootLevelFolder();
        $prefix = '';
        if ($this->folderPrefix) {
            $prefix = $this->folderPrefix . $prefix;
        }
        if ($rootLevelFolder->getIdentifier() === '/h5p/') {
            $prefix = 'h5p/' . $prefix;
        }

        $data = [];
        $namespace = key($_FILES);
        $storageId = $this->storage->getUid();
        $editorFilename = $file->getName();

        // Prepare directory
        if (empty($contentId)) {
            // Should be in editor tmp folder
            $targetFalDirectory = $storageId . ':' . $prefix . 'editor/' . $file->getType() . 's';
        } else {
            // Should be in content folder
            $targetFalDirectory = $storageId . ':' . $prefix . 'content/' . $contentId . '/' . $file->getType() . 's';
        }

        $this->registerUploadField($data, $namespace, $targetFalDirectory, $editorFilename);

        $fileProcessor = GeneralUtility::makeInstance(ExtendedFileUtility::class);
        $fileProcessor->setActionPermissions();
        $fileProcessor->start($data);
        $fileProcessor->setExistingFilesConflictMode(DuplicationBehavior::REPLACE);

        $result = $fileProcessor->processData();

        return $file;
    }

    /**
     * @param array &$data
     * @param string $namespace
     * @param string $targetDirectory
     * @param $editorFilename
     * @return void
     */
    protected function registerUploadField(array &$data, $namespace, $targetDirectory = '1:/_temp_/', $editorFilename = '')
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
     * @throws \TYPO3\CMS\Core\Resource\Exception\AbstractFileOperationException
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderWritePermissionsException
     */
    public function cloneContentFile($file, $fromId, $toId)
    {
        $rootLevelFolder = $this->getRootLevelFolder();

        if ($fromId === 'editor') {
            $sourcePath = $this->folderPrefix . 'editor';
        } else {
            $sourcePath = $this->folderPrefix . 'content/' . $fromId;
        }

        $destinationPath = $this->folderPrefix . 'content/' . $toId . '/' . dirname($file);

        if (!$this->storage->hasFolderInFolder($destinationPath, $rootLevelFolder)) {
            $this->storage->createFolder($destinationPath, $rootLevelFolder);
        }
        $destinationFolder = $this->storage->getFolderInFolder($destinationPath, $rootLevelFolder);

        $sourceFolder = $this->storage->getFolderInFolder($sourcePath, $rootLevelFolder);

        if ($sourceFolder->hasFile($file)) {
            $sourceFile = $this->storage->getFileInFolder($sourcePath . '/' . $file, $rootLevelFolder);
            $this->storage->copyFile($sourceFile, $destinationFolder);
        }
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

        $rootLevelFolder = $this->getRootLevelFolder();

        $destinationFolder = '';
        if ($contentId !== null) {
            $destination = $this->folderPrefix . 'content/' . $contentId;
            $destinationFolder = $contentId;
        } else {
            $destination = $this->folderPrefix . 'editor';
        }

        // Remove any old content
        if ($destinationFolder !== '') {
            /** @var Folder $oldFolder */
            $oldFolder = GeneralUtility::makeInstance(
                Folder::class,
                $this->storage,
                $destination,
                $destinationFolder
            );
            if ($this->storage->hasFolderInFolder($oldFolder->getIdentifier(), $rootLevelFolder)) {
                $this->storage->deleteFolder($oldFolder, true);
            }
            if (!$this->storage->hasFolder($destination)) {
                $this->storage->createFolder($destination, $rootLevelFolder);
            }
        }

        /** @var \SplFileInfo $fileInfo */
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $fileInfo) {
            $pathName = $fileInfo->getPathname();
            $dir = str_replace($source, '', $pathName);
            $dir = ltrim($dir, '/');
            if (strpos($dir, 'content') === 0) {
                $dir = substr_replace($dir, '', 0, strlen('content'));
                $dir = ltrim($dir, '/');
            }
            if ($fileInfo->isDir() && !$this->storage->hasFolder($destination . '/' . $dir)) {
                $this->storage->createFolder($destination . '/' . $dir, $rootLevelFolder);
            } elseif ($fileInfo->isFile()) {
                $targetDirectory = ltrim(str_replace($source, '', $fileInfo->getPath()), '/');
                if (strpos($targetDirectory, 'content') === 0) {
                    $targetDirectory = substr_replace($targetDirectory, '', 0, strlen('content'));
                    $targetDirectory = ltrim($targetDirectory, '/');
                }
                $destinationFolder = GeneralUtility::makeInstance(
                    Folder::class,
                    $this->storage,
                    $rootLevelFolder->getIdentifier() . '/' . $destination . '/' . $targetDirectory,
                    $targetDirectory
                );
                $this->storage->addFile($fileInfo->getPathname(), $destinationFolder, $fileInfo->getFilename());
            }
        }

        $contentSource = $source . '/content';
        // Return the actual content data as JSON, these get handed to the editor for editing by the user
        $h5pJson = $this->getContent($source . '/h5p.json');
        $contentJson = $this->getContent($contentSource . '/content.json');

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
     * Read file content of given file and then return it.
     *
     * @param string $file_path
     *
     * @return string contents
     * @throws \MichielRoos\H5p\Exception\MethodNotImplementedException
     * @throws \MichielRoos\H5p\Exception\MethodNotImplementedException
     */
    public function getContent($file_path)
    {
        // TODO: Implement getContent() method.
        \MichielRoos\H5p\Utility\MaintenanceUtility::methodMissing(__CLASS__, __FUNCTION__);
    }

    /**
     * Checks to see if content has the given file.
     * Used when saving content.
     *
     * @param string $file path + name
     * @param int $contentId
     *
     * @return string|int File ID or NULL if not found
     * @throws \MichielRoos\H5p\Exception\MethodNotImplementedException
     */
    public function getContentFile($file, $contentId)
    {
        // TODO: Implement getContentFile() method.
        \MichielRoos\H5p\Utility\MaintenanceUtility::methodMissing(__CLASS__, __FUNCTION__);
    }

    /**
     * Remove content files that are no longer used.
     * Used when saving content.
     *
     * @param string $file path + name
     * @param int $contentId
     *
     * @throws \MichielRoos\H5p\Exception\MethodNotImplementedException
     */
    public function removeContentFile($file, $contentId)
    {
        // TODO: Implement removeContentFile() method.
        \MichielRoos\H5p\Utility\MaintenanceUtility::methodMissing(__CLASS__, __FUNCTION__);
    }

    /**
     * Check if server setup has write permission to
     * the required folders
     *
     * @return bool True if server has the proper write access
     * @throws \MichielRoos\H5p\Exception\MethodNotImplementedException
     * @throws \MichielRoos\H5p\Exception\MethodNotImplementedException
     */
    public function hasWriteAccess()
    {
        // TODO: Implement hasWriteAccess() method.
        \MichielRoos\H5p\Utility\MaintenanceUtility::methodMissing(__CLASS__, __FUNCTION__);
    }

    /**
     * Check if the library has a presave.js in the root folder
     *
     * @param string $libraryName
     * @param string $developmentPath
     *
     * @return bool
     * @throws \MichielRoos\H5p\Exception\MethodNotImplementedException
     * @throws \MichielRoos\H5p\Exception\MethodNotImplementedException
     */
    public function hasPresave($libraryName, $developmentPath = null)
    {
        // TODO: Implement hasPresave() method.
        \MichielRoos\H5p\Utility\MaintenanceUtility::methodMissing(__CLASS__, __FUNCTION__);
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
        $folderPrefix = $this->folderPrefix ?: '';
        $upgradesFilePath = "/{$folderPrefix}libraries/{$machineName}-{$majorVersion}.{$minorVersion}/upgrades.js";
        if ($this->storage->hasFile($upgradesFilePath)) {
            $file = $this->storage->getFile($upgradesFilePath);
            $path = '/' . ltrim($file->getPublicUrl(), '/');
            if ($this->folderPrefix) {
                $path = str_replace('/fileadmin/' . $folderPrefix, '/', $path);
            }
            return $path;
        }

        return NULL;
    }

    /**
     * Store the given stream into the given file.
     *
     * @param string $path
     * @param string $file
     * @param resource $stream
     * @return bool
     */
    public function saveFileFromZip($path, $file, $stream)
    {
        $filePath = $path . '/' . $file;

        // Make sure the directory exists first
        $matches = array();
        preg_match('/(.+)\/[^\/]*$/', $filePath, $matches);
        GeneralUtility::mkdir_deep($matches[1]);

        // Store in local storage folder
        return file_put_contents($filePath, $stream);
    }
}
