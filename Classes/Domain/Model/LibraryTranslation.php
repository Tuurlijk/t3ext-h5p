<?php

namespace MichielRoos\H5p\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class LibraryTranslation
 * @package MichielRoos\H5p\Domain\Model
 */
class LibraryTranslation extends AbstractEntity
{
    /**
     * @var Library
     */
    protected Library $library;

    /**
     * @var string
     */
    protected string $languageCode;

    /**
     * @var string
     */
    protected string $translation;

    public static function create(Library $library, $languageCode, $translation): LibraryTranslation
    {
        $translationInstance = new LibraryTranslation();
        $translationInstance->setLibrary($library);
        $translationInstance->setLanguageCode($languageCode);
        $translationInstance->setTranslation($translation);
        return $translationInstance;
    }

    /**
     * @return Library
     */
    public function getLibrary(): Library
    {
        return $this->library;
    }

    /**
     * @param Library $library
     */
    public function setLibrary(Library $library): void
    {
        $this->library = $library;
    }

    /**
     * @return string
     */
    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    /**
     * @param string $languageCode
     */
    public function setLanguageCode(string $languageCode): void
    {
        $this->languageCode = $languageCode;
    }

    /**
     * @return string
     */
    public function getTranslation(): string
    {
        return $this->translation;
    }

    /**
     * @param string $translation
     */
    public function setTranslation(string $translation): void
    {
        $this->translation = $translation;
    }
}
