<?php
namespace MichielRoos\H5p\Controller;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use MichielRoos\H5p\Adapter\Core\Framework;
use MichielRoos\H5p\Domain\Model\Content;
use MichielRoos\H5p\Domain\Repository\ContentRepository;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Class ViewController
 */
class ViewController extends ActionController
{
    /**
     * Content repository
     *
     * @var \MichielRoos\H5p\Domain\Repository\ContentRepository
     */
    protected $contentRepository;

    /**
     * @var ContentObjectRenderer
     */
    private $contentObjectRenderer;

    /**
     * @var Framework
     */
    private $h5pFramework;

    /**
     * @var PageRenderer
     */
    private $pageRenderer;

    /**
     * Inject content repository
     * @param \MichielRoos\H5p\Domain\Repository\ContentRepository $contentRepository
     */
    public function injectContentRepository(ContentRepository $contentRepository)
    {
        $this->contentRepository = $contentRepository;
    }

    /**
     * Init
     */
    public function initializeAction()
    {
        $this->contentObjectRenderer = $this->configurationManager->getContentObject();
        $this->h5pFramework = GeneralUtility::makeInstance(Framework::class);

        $this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);

        $cacheBuster = '?v=' . $this->h5pFramework::$version;

        $relativeExtensionPath = ExtensionManagementUtility::extRelPath('h5p');
        $relativeExtensionPath = str_replace('../typo3conf', '/typo3conf', $relativeExtensionPath);
        $relativeCorePath = $relativeExtensionPath . 'Resources/Public/Lib/h5p-core/';


        foreach (\H5PCore::$scripts as $script) {
            $this->pageRenderer->addJsFooterFile($relativeCorePath . $script . $cacheBuster, 'text/javascript', false, false, '');
        }
        foreach (\H5PCore::$styles as $style) {
            $this->pageRenderer->addCssFile($relativeCorePath . $style . $cacheBuster);
        }

        parent::initializeAction();
    }

    /**
     * Index action
     */
    public function indexAction()
    {
        $data = $this->contentObjectRenderer->data;
        /** @var Content $h5pContent */
        $h5pContent = $this->contentRepository->findByUid($data['tx_h5p_content']);
        if (!$h5pContent) {
            return;
        }

        // load JS and CSS requirements
        $dependencies = $this->h5pFramework->loadContentDependencies($data['tx_h5p_content'], 'preloaded');
        foreach ($dependencies as $dependency) {
            $this->loadJsAndCss($dependency);
        }

        $this->view->assign('content', $h5pContent);
        $this->view->assign('parameters', addslashes($h5pContent->getParameters()));
        $data['h5p_frame'] = ($data['tx_h5p_display_options'] & \H5PCore::DISABLE_FRAME) ? 'true' : 'false';
        $data['h5p_export'] = ($data['tx_h5p_display_options'] & \H5PCore::DISABLE_DOWNLOAD) ? 'true' : 'false';
        $data['h5p_embed'] = ($data['tx_h5p_display_options'] & \H5PCore::DISABLE_EMBED) ? 'true' : 'false';
        $data['h5p_copyright'] = ($data['tx_h5p_display_options'] & \H5PCore::DISABLE_COPYRIGHT) ? 'true' : 'false';
        $data['h5p_icon'] = ($data['tx_h5p_display_options'] & \H5PCore::DISABLE_ABOUT) ? 'true' : 'false';
        $this->view->assign('data', $data);
    }

    /**
     * Load JS and CSS
     * @param array $library
     */
    private function loadJsAndCss($library)
    {
        $name = $library['machineName'] . '-' . $library['majorVersion'] . '.' . $library['minorVersion'];
        $preloadCss = explode(',', $library['preloadedCss']);
        $preloadJs = explode(',', $library['preloadedJs']);

        foreach ($preloadJs as $js) {
            $js = trim($js);
            if ($js) {
                $this->pageRenderer->addJsFooterFile('/fileadmin/h5p/libraries/' . $name . '/' . $js, 'text/javascript', false, false, '');
            }
        }
        foreach ($preloadCss as $css) {
            $css = trim($css);
            if ($css) {
                $this->pageRenderer->addCssFile('/fileadmin/h5p/libraries/' . $name . '/' . $css);
            }
        }
    }
}
