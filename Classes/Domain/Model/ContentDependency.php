<?php
namespace MichielRoos\H5p\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
/**
 * Class ContentDependency
 * @package \MichielRoos\H5p\Domain\Model
 */
class ContentDependency extends AbstractEntity
{
    /**
     * @var Content
     */
    protected Content $content;

    /**
     * @var Library
     */
    protected Library $library;

    /**
     * @var string
     */
    protected string $dependencyType;

    /**
     * @var integer
     */
    protected int $weight;

    /**
     * @var bool
     */
    protected bool $dropCss;

    /**
     * Returns an assoc array as expected by
     * @see \H5PCore::getDependenciesFiles
     *
     * @return array
     */
    public function toAssocArray(): array
    {
        // Not all fields from library are expected in this array, but we dont expect conflicts here.
        $libraryData = $this->getLibrary()->toAssocArray();
        return array_merge($libraryData, [
            'dropCss' => $this->isDropCss(),
            'dependencyType' => $this->getDependencyType()
        ]);
    }

    /**
     * @return Content
     */
    public function getContent(): Content
    {
        return $this->content;
    }

    /**
     * @param Content $content
     */
    public function setContent(Content $content): void
    {
        $this->content = $content;
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
    public function getDependencyType(): string
    {
        return $this->dependencyType;
    }

    /**
     * @param string $dependencyType
     */
    public function setDependencyType(string $dependencyType): void
    {
        $this->dependencyType = $dependencyType;
    }

    /**
     * @return int
     */
    public function getWeight(): int
    {
        return $this->weight;
    }

    /**
     * @param int $weight
     */
    public function setWeight(int $weight): void
    {
        $this->weight = $weight;
    }

    /**
     * @return bool
     */
    public function isDropCss(): bool
    {
        return $this->dropCss;
    }

    /**
     * @param bool $dropCss
     */
    public function setDropCss(bool $dropCss): void
    {
        $this->dropCss = $dropCss;
    }
}
