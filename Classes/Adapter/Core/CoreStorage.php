<?php
namespace MichielRoos\H5p\Adapter\Core;


use TYPO3\CMS\Core\SingletonInterface;

/**
 * Class CoreStorage
 *
 * Methods savePackage and saveLibraries are overridden because we need to
 * be able to easily call saveLibraryUsage
 */
class CoreStorage extends \H5PStorage implements SingletonInterface
{

    /**
     * Saves a H5P file
     *
     * @param null $content
     * @param int $contentMainId
     *  The main id for the content we are saving. This is used if the framework
     *  we're integrating with uses content id's and version id's
     * @param bool $skipContent
     * @param array $options
     * @return bool TRUE if one or more libraries were updated
     * TRUE if one or more libraries were updated
     * FALSE otherwise
     */
    public function savePackage($content = null, $contentMainId = null, $skipContent = false, $options = []): bool
    {
        if ($this->h5pC->mayUpdateLibraries()) {
            // Save the libraries we processed during validation
            $this->saveLibraries();
        }

        if (!$skipContent) {
            $basePath = $this->h5pF->getUploadedH5pFolderPath();
            $current_path = $basePath . '/content';

            // Save content
            if ($content === null) {
                $content = [];
            }
            if (!is_array($content)) {
                $content = ['id' => $content];
            }

            // Find main library version
            foreach ($this->h5pC->mainJsonData['preloadedDependencies'] as $dep) {
                if ($dep['machineName'] === $this->h5pC->mainJsonData['mainLibrary']) {
                    $dep['libraryId'] = $this->h5pC->getLibraryId($dep);
                    $content['library'] = $dep;
                    break;
                }
            }

            $content['params'] = file_get_contents($current_path . '/content.json');

            if (isset($options['disable'])) {
                $content['disable'] = $options['disable'];
            }
            $content['id'] = $this->h5pC->saveContent($content, $contentMainId);
            $this->contentId = $content['id'];

            // Save dependencies
            $dependencies = [];
            $nextWeight = 1;

            foreach (['dynamic', 'editor', 'preloaded'] as $type) {
                $typeDepencency = $type . 'Dependencies';
                if (array_key_exists($typeDepencency, $content) && is_array($content[$typeDepencency])) {
                    foreach ($content[$typeDepencency] as $dependency) {
                        $library = $this->h5pF->loadLibrary($dependency['machineName'], $dependency['majorVersion'], $dependency['minorVersion']);

                        // Find all dependencies for this library
                        $depKey = $type . '-' . $library['machineName'];
                        if (!isset($dependencies[$depKey])) {
                            $dependencies[$depKey] = [
                                'library' => $library,
                                'type'    => $type
                            ];

                            $nextWeight = $this->h5pC->findLibraryDependencies($dependencies, $library, $nextWeight);
                            $dependencies[$depKey]['weight'] = $nextWeight++;
                        }
                    }
                }
            }

            $this->h5pF->saveLibraryUsage($content['id'], $dependencies);

            try {
                // Save content folder contents
                $this->h5pC->fs->saveContent($current_path, $content);
            } catch (\Exception $e) {
                $this->h5pF->setErrorMessage($e->getMessage(), 'save-content-failed');
            }

            // Remove temp content folder
            \H5PCore::deleteFileTree($basePath);
        }
    }

    /**
     * Helps savePackage.
     *
     * @return int Number of libraries saved
     */
    private function saveLibraries(): int
    {
        // Keep track of the number of libraries that have been saved
        $newOnes = 0;
        $oldOnes = 0;

        // Go through libraries that came with this package
        foreach ($this->h5pC->librariesJsonData as $libString => &$library) {
            // Find local library identifier
            $libraryId = $this->h5pC->getLibraryId($library, $libString);

            // Assume new library
            $new = true;
            if ($libraryId) {
                // Found old library
                $library['libraryId'] = $libraryId;

                if ($this->h5pF->isPatchedLibrary($library)) {
                    // This is a newer version than ours. Upgrade!
                    $new = false;
                } else {
                    $library['saveDependencies'] = false;
                    // This is an older version, no need to save.
                    continue;
                }
            }

            // Indicate that the dependencies of this library should be saved.
            $library['saveDependencies'] = true;

            // Save library meta data
            $this->h5pF->saveLibraryData($library, $new);

            // Save library folder
            $this->h5pC->fs->saveLibrary($library);

            // Remove cached assets that uses this library
            if ($this->h5pC->aggregateAssets && isset($library['libraryId'])) {
                $removedKeys = $this->h5pF->deleteCachedAssets($library['libraryId']);
                $this->h5pC->fs->deleteCachedAssets($removedKeys);
            }

            // Remove tmp folder
            \H5PCore::deleteFileTree($library['uploadDirectory']);

            if ($new) {
                $newOnes++;
            } else {
                $oldOnes++;
            }
        }

        // Go through the libraries again to save dependencies.
        foreach ($this->h5pC->librariesJsonData as &$library) {
            if (!$library['saveDependencies']) {
                continue;
            }

            // TODO: Should the table be locked for this operation?

            // Remove any old dependencies
            $this->h5pF->deleteLibraryDependencies($library['libraryId']);

            // Insert the different new ones
            if (isset($library['preloadedDependencies'])) {
                $this->h5pF->saveLibraryDependencies($library['libraryId'], $library['preloadedDependencies'], 'preloaded');
            }
            if (isset($library['dynamicDependencies'])) {
                $this->h5pF->saveLibraryDependencies($library['libraryId'], $library['dynamicDependencies'], 'dynamic');
            }
            if (isset($library['editorDependencies'])) {
                $this->h5pF->saveLibraryDependencies($library['libraryId'], $library['editorDependencies'], 'editor');
            }

            // Make sure libraries dependencies, parameter filtering and export files gets regenerated for all content who uses this library.
            $this->h5pF->clearFilteredParameters($library['libraryId']);
        }

        // Tell the user what we've done.
        if ($newOnes && $oldOnes) {
            if ($newOnes === 1) {
                if ($oldOnes === 1) {
                    // Singular Singular
                    $message = $this->h5pF->t('Added %new new H5P library and updated %old old one.', ['%new' => $newOnes, '%old' => $oldOnes]);
                } else {
                    // Singular Plural
                    $message = $this->h5pF->t('Added %new new H5P library and updated %old old ones.', ['%new' => $newOnes, '%old' => $oldOnes]);
                }
            } else {
                // Plural
                if ($oldOnes === 1) {
                    // Plural Singular
                    $message = $this->h5pF->t('Added %new new H5P libraries and updated %old old one.', ['%new' => $newOnes, '%old' => $oldOnes]);
                } else {
                    // Plural Plural
                    $message = $this->h5pF->t('Added %new new H5P libraries and updated %old old ones.', ['%new' => $newOnes, '%old' => $oldOnes]);
                }
            }
        } elseif ($newOnes) {
            if ($newOnes === 1) {
                // Singular
                $message = $this->h5pF->t('Added %new new H5P library.', ['%new' => $newOnes]);
            } else {
                // Plural
                $message = $this->h5pF->t('Added %new new H5P libraries.', ['%new' => $newOnes]);
            }
        } elseif ($oldOnes) {
            if ($oldOnes === 1) {
                // Singular
                $message = $this->h5pF->t('Updated %old H5P library.', ['%old' => $oldOnes]);
            } else {
                // Plural
                $message = $this->h5pF->t('Updated %old H5P libraries.', ['%old' => $oldOnes]);
            }
        }

        if (isset($message)) {
            $this->h5pF->setInfoMessage($message);
        }

        return $newOnes + $oldOnes;
    }
}
