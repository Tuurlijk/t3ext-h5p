<?php
namespace MichielRoos\H5p\Domain\Model;

use TYPO3\CMS\Core\Resource\ResourceInterface;
/**
 * Class FileReference
 */
class FileReference extends \TYPO3\CMS\Extbase\Domain\Model\FileReference
{
    /**
     * Uid of a sys_file
     *
     * @var integer
     */
    protected int $originalFileIdentifier;

    /**
     * @param ResourceInterface $originalResource
     */
    public function setOriginalResource(ResourceInterface $originalResource): void
    {
        $this->setFileReference($originalResource);
    }

    /**
     * @param \TYPO3\CMS\Core\Resource\FileReference $originalResource
     */
    private function setFileReference(\TYPO3\CMS\Core\Resource\FileReference $originalResource): void
    {
        $this->originalResource = $originalResource;
        $this->originalFileIdentifier = (int)$originalResource->getOriginalFile()->getUid();
    }
}
