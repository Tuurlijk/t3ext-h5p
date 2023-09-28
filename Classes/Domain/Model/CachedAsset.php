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
    protected File $resource;

    /**
     * @var ObjectStorage<Library>
     */
    protected ObjectStorage $libraries;

    /**
     * @var string
     */
    protected string $hashKey;

    /**
     * @var string
     */
    protected string $type;

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
    public function setResource(File $resource): void
    {
        $this->resource = $resource;
    }

    /**
     * @return ObjectStorage
     */
    public function getLibraries(): ObjectStorage
    {
        return $this->libraries;
    }

    /**
     * @param ObjectStorage $libraries
     */
    public function setLibraries(ObjectStorage $libraries): void
    {
        $this->libraries = $libraries;
    }

    /**
     * @param Library $library
     */
    public function addLibrary(Library $library): void
    {
        $this->libraries->attach($library);
    }

    /**
     * @return string
     */
    public function getHashKey(): string
    {
        return $this->hashKey;
    }

    /**
     * @param string $hashKey
     */
    public function setHashKey(string $hashKey): void
    {
        $this->hashKey = $hashKey;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }
}
