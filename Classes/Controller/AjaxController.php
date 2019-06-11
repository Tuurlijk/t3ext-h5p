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
use MichielRoos\H5p\Domain\Repository\ContentRepository;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Class AjaxController
 */
class AjaxController extends ActionController
{
    /**
     * @var \TYPO3\CMS\Extbase\Mvc\View\JsonView
     */
    protected $view;

    /**
     * @var string
     */
    protected $defaultViewObjectName = \TYPO3\CMS\Extbase\Mvc\View\JsonView::class;

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
     * Finish action
     */
    public function finishAction()
    {
    }

    /**
     * Finish action
     */
    public function contentUserDataAction()
    {
    }
}
