<?php
namespace MichielRoos\H5p\Domain\Model;


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
     * @var File
     */
    protected $resource;

    /**
     * @var ObjectStorage<Library>
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
     * @return File
     */
    public function getResource(): File
    {
        return $this->resource;
    }

    /**
     * @param File $resource
     */
    public function setResource(File $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @return ObjectStorage
     */
    public function getLibraries()
    {
        return $this->libraries;
    }

    /**
     * @param ObjectStorage $libraries
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
