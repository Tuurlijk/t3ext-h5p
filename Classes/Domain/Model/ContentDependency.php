<?php
namespace MichielRoos\H5p\Domain\Model;

/**
 * Class ContentDependency
 * @package \MichielRoos\H5p\Domain\Model
 */
class ContentDependency extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var \MichielRoos\H5p\Domain\Model\Content
     */
    protected $content;

    /**
     * @var \MichielRoos\H5p\Domain\Model\Library
     */
    protected $library;

    /**
     * @var string
     */
    protected $dependencyType;

    /**
     * @var int
     */
    protected $weight;

    /**
     * @var bool
     */
    protected $dropCss;

    /**
     * Returns an assoc array as expected by
     * @see \H5PCore::getDependenciesFiles
     *
     * @return array
     */
    public function toAssocArray()
    {
        // Not all fields from library are expected in this array, but we dont expect conflicts here.
        $libraryData = $this->getLibrary()->toAssocArray();
        return array_merge($libraryData, [
            'dropCss' => $this->isDropCss(),
            'dependencyType' => $this->getDependencyType()
        ]);
    }

    /**
     * @return \MichielRoos\H5p\Domain\Model\Content
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param \MichielRoos\H5p\Domain\Model\Content $content
     */
    public function setContent(Content $content)
    {
        $this->content = $content;
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

    /**
     * @return int
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param int $weight
     */
    public function setWeight(int $weight)
    {
        $this->weight = $weight;
    }

    /**
     * @return bool
     */
    public function isDropCss()
    {
        return $this->dropCss;
    }

    /**
     * @param bool $dropCss
     */
    public function setDropCss(bool $dropCss)
    {
        $this->dropCss = $dropCss;
    }
}
