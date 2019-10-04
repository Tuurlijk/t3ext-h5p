<?php
namespace MichielRoos\H5p\Backend;

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class BackendJsLoader
{
    /**
     * Load require JS modules
     */
    public function loadJsModules()
    {
        if (TYPO3_MODE !== 'BE') {
            return;
        }

        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addRequireJsConfiguration([
                'paths' => [
                    'MichielRoos/H5p/Plugins/InsertH5p' => '../typo3conf/ext/h5p/Resources/Public/JavaScript/Plugins/InsertH5p',
                ],
                'shim'  => [
                    'MichielRoos/H5p/Plugins/InsertH5p' => ['exports' => 'TYPO3/CMS/Rtehtmlarea/Plugins/InsertH5p'],
                ]
            ]
        );
    }
}
