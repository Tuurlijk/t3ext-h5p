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
 * Class LibraryTranslation
 * @package MichielRoos\H5p\Domain\Model
 */
class LibraryTranslation extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var \MichielRoos\H5p\Domain\Model\Library
     */
    protected $library;

    /**
     * @var string
     */
    protected $languageCode;

    /**
     * @var string
     */
    protected $translation;

    public static function create(Library $library, $languageCode, $translation)
    {
        $translationInstance = new LibraryTranslation();
        $translationInstance->setLibrary($library);
        $translationInstance->setLanguageCode($languageCode);
        $translationInstance->setTranslation($translation);
        return $translationInstance;
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
     * @return string
     */
    public function getLanguageCode()
    {
        return $this->languageCode;
    }

    /**
     * @param string $languageCode
     */
    public function setLanguageCode($languageCode)
    {
        $this->languageCode = $languageCode;
    }

    /**
     * @return string
     */
    public function getTranslation()
    {
        return $this->translation;
    }

    /**
     * @param string $translation
     */
    public function setTranslation($translation)
    {
        $this->translation = $translation;
    }
}
