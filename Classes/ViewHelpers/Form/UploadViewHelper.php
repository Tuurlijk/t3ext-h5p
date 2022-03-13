<?php
namespace MichielRoos\H5p\ViewHelpers\Form;

use TYPO3Fluid\Fluid\Core\Exception;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Property\PropertyMapper;
use TYPO3\CMS\Extbase\Security\Cryptography\HashService;

/**
 * Class UploadViewHelper
 */
class UploadViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\UploadViewHelper
{
    /**
     * @var HashService
     */
    protected $hashService;

    /**
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * @param HashService $hashService
     */
    public function injectHashService(HashService $hashService): void
    {
        $this->hashService = $hashService;
    }

    /**
     * @param PropertyMapper $propertyMapper
     */
    public function injectPropertyMapper(PropertyMapper $propertyMapper): void
    {
        $this->propertyMapper = $propertyMapper;
    }

    /**
     * Render the upload field including possible resource pointer
     *
     * @return string
     * @throws Exception
     * @throws \TYPO3\CMS\Extbase\Property\Exception
     * @api
     */
    public function render(): string
    {
        $output = '';
        $resource = $this->getUploadedResource();
        if ($resource !== null) {
            $resourcePointerIdAttribute = '';
            if ($this->hasArgument('id')) {
                $resourcePointerIdAttribute = ' id="' . htmlspecialchars($this->arguments['id']) . '-file-reference"';
            }
            $resourcePointerValue = $resource->getUid();
            if ($resourcePointerValue === null) {
                // Newly created file reference which is not persisted yet.
                // Use the file UID instead, but prefix it with "file:" to communicate this to the type converter
                $resourcePointerValue = 'file:' . $resource->getOriginalResource()->getOriginalFile()->getUid();
            }
            $output .= '<input type="hidden" name="' . $this->getName() . '[submittedFile][resourcePointer]" value="' . htmlspecialchars($this->hashService->appendHmac((string)$resourcePointerValue)) . '"' . $resourcePointerIdAttribute . ' />';
            $this->templateVariableContainer->add('resource', $resource);
            $output .= $this->renderChildren();
            $this->templateVariableContainer->remove('resource');
        }
        $output .= parent::render();
        return $output;
    }

    /**
     * Return a previously uploaded resource.
     * Return NULL if errors occurred during property mapping for this property.
     *
     * @return FileReference
     * @throws \TYPO3\CMS\Extbase\Property\Exception
     */
    protected function getUploadedResource(): ?FileReference
    {
        if ($this->getMappingResultsForProperty()->hasErrors()) {
            return null;
        }
        if (is_callable([$this, 'getValueAttribute'])) {
            $resource = $this->getValueAttribute();
        } else {
            // @deprecated since 7.6 will be removed once 6.2 support is removed
            $resource = $this->getValue(false);
        }
        if ($resource instanceof FileReference) {
            return $resource;
        }
        return $this->propertyMapper->convert($resource, FileReference::class);
    }
}
