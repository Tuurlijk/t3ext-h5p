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

use MichielRoos\H5p\Adapter\Core\CoreFactory;
use MichielRoos\H5p\Adapter\Core\FileStorage;
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
     * @var string
     */
    private $language;
    /**
     * @var FileStorage|object
     */
    private $h5pFileStorage;
    /**
     * @var CoreFactory|object
     */
    private $h5pCore;

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

        $this->language = ($this->getLanguageService()->lang === 'default') ? 'en' : $this->getLanguageService()->lang;

        $resourceFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();
        $storage = $resourceFactory->getDefaultStorage();
        $this->h5pFramework = GeneralUtility::makeInstance(Framework::class, $storage);
        $this->h5pFileStorage = GeneralUtility::makeInstance(FileStorage::class, $storage);
        $this->h5pCore = GeneralUtility::makeInstance(CoreFactory::class, $this->h5pFramework, $this->h5pFileStorage, $this->language);

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
     * Returns an instance of LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
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

        $contentLibrary = $h5pContent->getLibrary()->toAssocArray();

        $dependencyLibrary = $this->h5pCore->loadLibrary($contentLibrary['machineName'], $contentLibrary['majorVersion'], $contentLibrary['minorVersion']);

        $this->h5pCore->findLibraryDependencies($dependencies, $dependencyLibrary);

        foreach ($dependencies as $dependency) {
            $this->loadJsAndCss($dependency['library']);
        }

        $contentDependencies = $this->h5pFramework->loadContentDependencies($data['tx_h5p_content'], 'preloaded');
        foreach ($contentDependencies as $dependency) {
            $this->loadJsAndCss($dependency);
        }

        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);

        $this->view->assign('content', $h5pContent);
        $this->view->assign('parameters', addslashes($h5pContent->getFiltered()));
        $this->view->assign('ajaxSetFinished', addslashes((string)$uriBuilder->buildUriFromRoute('h5p_editor_action', ['type' => 'setFinished', 'action' => 'h5p_'])));
        $this->view->assign('ajaxContentUserData', addslashes((string)$uriBuilder->buildUriFromRoute('h5p_editor_action', ['type' => 'contentUserData', 'action' => 'h5p_'])));
        $this->view->assign('libraryConfig', $this->h5pFramework->getLibraryConfig());
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
        if (strpos($library['machineName'], 'H5PEditor') === 0) {
            return;
        }
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
