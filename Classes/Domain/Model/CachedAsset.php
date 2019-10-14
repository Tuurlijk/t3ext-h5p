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

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;


/**
 * Class CachedAsset
 * @package MichielRoos\H5p\Domain\Model
 */
class CachedAsset extends AbstractEntity
{

    /**
     * @var \TYPO3\CMS\Core\Resource\File
     */
    protected $resource;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<Library>
     */
    protected $libraries;

    /**
     * @var string
     */
    protected $hashKey;

    /**
     * @var string
     */
    protected $type;

    public function __construct()
    {
        $this->libraries = new ObjectStorage();
    }

    /**
     * @return \TYPO3\CMS\Core\Resource\File
     */
    public function getResource(): File
    {
        return $this->resource;
    }

    /**
     * @param \TYPO3\CMS\Core\Resource\File $resource
     */
    public function setResource(File $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getLibraries()
    {
        return $this->libraries;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $libraries
     */
    public function setLibraries(ObjectStorage $libraries)
    {
        $this->libraries = $libraries;
    }

    /**
     * @param Library $library
     */
    public function addLibrary(Library $library)
    {
        $this->libraries->attach($library);
    }

    /**
     * @return string
     */
    public function getHashKey()
    {
        return $this->hashKey;
    }

    /**
     * @param string $hashKey
     */
    public function setHashKey(string $hashKey)
    {
        $this->hashKey = $hashKey;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }
}
