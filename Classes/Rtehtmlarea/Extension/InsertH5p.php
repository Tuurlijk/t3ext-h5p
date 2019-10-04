<?php
namespace MichielRoos\H5p\Rtehtmlarea\Extension;

use TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi;

/**
 * Insert H5p plugin for htmlArea RTE
 */
class InsertH5p extends RteHtmlAreaApi
{
    /**
     * The name of the plugin registered by the extension
     *
     * @var string
     */
    protected $pluginName = 'InsertH5p';

    /**
     * The comma-separated list of button names that the registered plugin is adding to the htmlArea RTE toolbar
     *
     * @var string
     */
    protected $pluginButtons = 'h5p';

    /**
     * The name-converting array, converting the button names used in the RTE PageTSConfing to the button id's used by the JS scripts
     *
     * @var array
     */
    protected $convertToolbarForHtmlAreaArray = [
        'h5p' => 'InsertH5p'
    ];

    /**
     * Return JS configuration of the htmlArea plugins registered by the extension
     *
     * @return string JS configuration for registered plugins
     */
    public function buildJavascriptConfiguration()
    {
        return '';
    }
}
