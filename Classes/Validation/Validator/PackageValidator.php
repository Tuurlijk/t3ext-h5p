<?php

namespace MichielRoos\H5p\Validation\Validator;

use MichielRoos\H5p\Adapter\Core\FileStorage;
use MichielRoos\H5p\Adapter\Core\Framework;
use MichielRoos\H5p\Domain\Model\FileReference;
use MichielRoos\H5p\Adapter\Core\CoreFactory;
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
        $storage = $value->getOriginalResource()->getStorage();
        $framework = GeneralUtility::makeInstance(Framework::class, $storage);
        $framework->setPackageFile($value);
        $h5pFileStorage = GeneralUtility::makeInstance(FileStorage::class, $storage);
        $h5pCore = GeneralUtility::makeInstance(CoreFactory::class, $framework, $h5pFileStorage, '');
        $validator = GeneralUtility::makeInstance(\H5PValidator::class, $framework, $h5pCore);

        $success = $validator->isValidPackage();

        if (!$success) {
            /** @var Framework $framework */
            $framework = $validator->h5pF;
            $errorMessages = $framework->getMessages('error');
            foreach ($errorMessages as $code => $errorMessage) {
                $this->addError($errorMessage, $code);
            }
            unlink($validator->h5pF->getUploadedH5pPath());
        }
    }
}
