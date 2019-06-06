<?php
namespace MichielRoos\H5p\Domain\Model;


/**
 * Class LibraryTranslation
 * @package MichielRoos\H5p\Domain\Model
 */
class LibraryTranslation extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    public static function create(Library $library, $languageCode, $translation)
    {
        $translationInstance = new LibraryTranslation();
        $translationInstance->setLibrary($library);
        $translationInstance->setLanguageCode($languageCode);
        $translationInstance->setTranslation($translation);
        return $translationInstance;
    }

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
