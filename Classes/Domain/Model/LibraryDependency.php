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

/**
 * Class LibraryDependency
 * @package MichielRoos\H5p\Domain\Model
 */
class LibraryDependency extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var \MichielRoos\H5p\Domain\Model\Library
     */
    protected $library;

    /**
     * @var \MichielRoos\H5p\Domain\Model\Library
     */
    protected $requiredLibrary;

    /**
     * @var string
     */
    protected $dependencyType;

    /**
     * LibraryDependency constructor.
     * @param \MichielRoos\H5p\Domain\Model\Library $library
     * @param \MichielRoos\H5p\Domain\Model\Library $requiredLibrary
     * @param string $dependencyType
     */
    public function __construct(Library $library, Library $requiredLibrary, string $dependencyType)
    {
        $this->library = $library;
        $this->requiredLibrary = $requiredLibrary;
        $this->dependencyType = $dependencyType;
    }

    /**
     * @return \MichielRoos\H5p\Domain\Model\Library
     */
    public function getLibrary()
    {
        return $this->library;
    }

    /**
     * @param \MichielRoos\H5p\Domain\Model\Library $library
     */
    public function setLibrary(Library $library)
    {
        $this->library = $library;
    }

    /**
     * @return \MichielRoos\H5p\Domain\Model\Library
     */
    public function getRequiredLibrary()
    {
        return $this->requiredLibrary;
    }

    /**
     * @param \MichielRoos\H5p\Domain\Model\Library $requiredLibrary
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
