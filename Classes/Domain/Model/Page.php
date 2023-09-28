<?php
namespace MichielRoos\H5p\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
/**
 * Class Page
 */
class Page extends AbstractEntity
{
    /**
     * Title
     *
     * @var string
     */
    protected string $title = '';

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
}
