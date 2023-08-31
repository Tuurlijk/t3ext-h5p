<?php

namespace MichielRoos\H5p\Validation\Validator;

use MichielRoos\H5p\Adapter\Core\CoreFactory;
use MichielRoos\H5p\Adapter\Core\FileStorage;
use MichielRoos\H5p\Adapter\Core\Framework;
use MichielRoos\H5p\Adapter\Core\FrameworkFactory;
use MichielRoos\H5p\Domain\Model\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * Class PackageValidator
 */
class PackageValidator extends AbstractValidator
{
    /**
     * Validates the given package
     *
     * @param FileReference $value
     * @return bool
     */
    protected function isValid($value): void
    {
        $frameworkFactory = GeneralUtility::makeInstance(FrameworkFactory::class);
        $framework        = $frameworkFactory->create();

        $storage = $value->getOriginalResource()->getStorage();
        $framework->setPackageFile($value);
        $h5pFileStorage = GeneralUtility::makeInstance(FileStorage::class, $storage);
        $h5pCore        = GeneralUtility::makeInstance(CoreFactory::class, $framework, $h5pFileStorage, '');
        $validator      = GeneralUtility::makeInstance(\H5PValidator::class, $framework, $h5pCore);

        $success = $validator->isValidPackage();

        if (!$success) {
            /** @var Framework $framework */
            $framework     = $validator->h5pF;
            $errorMessages = $framework->getMessages('error');
            foreach ($errorMessages as $code => $errorMessage) {
                $this->addError($errorMessage, $code);
            }
            unlink($validator->h5pF->getUploadedH5pPath());
        }
    }
}
