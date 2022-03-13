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
