<?php
namespace MichielRoos\H5p\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
/**
 * Class LibraryDependency
 * @package MichielRoos\H5p\Domain\Model
 */
class LibraryDependency extends AbstractEntity
{
    /**
     * @var Library
     */
    protected $library;

    /**
     * @var Library
     */
    protected $requiredLibrary;

    /**
     * @var string
     */
    protected $dependencyType;

    /**
     * LibraryDependency constructor.
     * @param Library $library
     * @param Library $requiredLibrary
     * @param string $dependencyType
     */
    public function __construct(Library $library, Library $requiredLibrary, string $dependencyType)
    {
        $this->library = $library;
        $this->requiredLibrary = $requiredLibrary;
        $this->dependencyType = $dependencyType;
    }

    /**
     * @return Library
     */
    public function getLibrary()
    {
        return $this->library;
    }

    /**
     * @param Library $library
     */
    public function setLibrary(Library $library)
    {
        $this->library = $library;
    }

    /**
     * @return Library
     */
    public function getRequiredLibrary()
    {
        return $this->requiredLibrary;
    }

    /**
     * @param Library $requiredLibrary
     */
    public function setRequiredLibrary(Library $requiredLibrary)
    {
        $this->requiredLibrary = $requiredLibrary;
    }

    /**
     * @return string
     */
    public function getDependencyType()
    {
        return $this->dependencyType;
    }

    /**
     * @param string $dependencyType
     */
    public function setDependencyType(string $dependencyType)
    {
        $this->dependencyType = $dependencyType;
    }
}
