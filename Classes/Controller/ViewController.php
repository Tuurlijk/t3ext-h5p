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

use H5PCore;
use MichielRoos\H5p\Adapter\Core\CoreFactory;
use MichielRoos\H5p\Adapter\Core\FileStorage;
use MichielRoos\H5p\Adapter\Core\Framework;
use MichielRoos\H5p\Domain\Model\Content;
use MichielRoos\H5p\Domain\Repository\ContentRepository;
use MichielRoos\H5p\Domain\Repository\ContentResultRepository;
use MichielRoos\H5p\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\ResourceFactory;
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
     * Content result repository
     *
     * @var \MichielRoos\H5p\Domain\Repository\ContentResultRepository
     */
    protected $contentResultRepository;

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
     * Inject content result repository
     * @param \MichielRoos\H5p\Domain\Repository\ContentResultRepository $contentResultRepository
     */
    public function injectContentResultRepository(ContentResultRepository $contentResultRepository)
    {
        $this->contentResultRepository = $contentResultRepository;
    }

    /**
     * Init
     */
    public function initializeAction()
    {
        $this->contentObjectRenderer = $this->configurationManager->getContentObject();

        $this->language = ($this->getLanguageService()->lang === 'default') ? 'en' : $this->getLanguageService()->lang;

        $resourceFactory = ResourceFactory::getInstance();
        $storage = $resourceFactory->getDefaultStorage();
        $this->h5pFramework = GeneralUtility::makeInstance(Framework::class, $storage);
        $this->h5pFileStorage = GeneralUtility::makeInstance(FileStorage::class, $storage);
        $this->h5pCore = GeneralUtility::makeInstance(CoreFactory::class, $this->h5pFramework, $this->h5pFileStorage, $this->language);

        $this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);

        $relativeExtensionPath = ExtensionManagementUtility::siteRelPath('h5p');
        $relativeExtensionPath = str_replace('typo3conf', '/typo3conf', $relativeExtensionPath);
        $relativeCorePath = $relativeExtensionPath . 'Resources/Public/Lib/h5p-core/';

        foreach (\H5PCore::$scripts as $script) {
            $this->pageRenderer->addJsFooterFile($relativeCorePath . $script, 'text/javascript', false, false, '', true);
        }
        foreach (\H5PCore::$styles as $style) {
            $this->pageRenderer->addCssFile($relativeCorePath . $style);
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
        /** @var Content $content */
        $content = $this->contentRepository->findByUid($data['tx_h5p_content']);
        if (!$content) {
            $this->view->assign('contentNotFound', true);
            return;
        }

        $this->pageRenderer->addJsInlineCode(
            'H5PIntegration',
            'H5PIntegration = ' . json_encode($this->getCoreSettings()) . ';'
        );

        $contentSettings = $this->getContentSettings($content);
        $contentSettings['displayOptions'] = [];
        $contentSettings['displayOptions']['frame'] = (bool)($data['tx_h5p_display_options'] & \H5PCore::DISABLE_FRAME);
        $contentSettings['displayOptions']['export'] = (bool)($data['tx_h5p_display_options'] & \H5PCore::DISABLE_DOWNLOAD);
        $contentSettings['displayOptions']['embed'] = (bool)($data['tx_h5p_display_options'] & \H5PCore::DISABLE_EMBED);
        $contentSettings['displayOptions']['copyright'] = (bool)($data['tx_h5p_display_options'] & \H5PCore::DISABLE_COPYRIGHT);
        $contentSettings['displayOptions']['icon'] = (bool)($data['tx_h5p_display_options'] & \H5PCore::DISABLE_ABOUT);
        $this->pageRenderer->addJsInlineCode(
            'H5PIntegration contents cid-' . $content->getUid(),
            'H5PIntegration.contents[\'cid-' . $content->getUid() . '\'] = ' . json_encode($contentSettings) . ';'
        );

        if ($content->getEmbedType() !== 'iframe') {
            // load JS and CSS requirements
            $contentLibrary = $content->getLibrary()->toAssocArray();

            // JS and CSS required by all libraries
            $contentLibraryWithDependencies = $this->h5pCore->loadLibrary($contentLibrary['machineName'], $contentLibrary['majorVersion'], $contentLibrary['minorVersion']);
            $this->h5pCore->findLibraryDependencies($dependencies, $contentLibraryWithDependencies);
            if (is_array($dependencies)) {
                $dependencies = $this->h5pCore->orderDependenciesByWeight($dependencies);
                foreach ($dependencies as $key => $dependency) {
                    if (strpos($key, 'preloaded-') !== 0) {
                        continue;
                    }
                    $this->loadJsAndCss($dependency['library']);
                }
            }

            // JS and CSS required by the content
            $contentDependencies = $this->h5pFramework->loadContentDependencies($data['tx_h5p_content'], 'preloaded');
            foreach ($contentDependencies as $dependency) {
                $this->loadJsAndCss($dependency);
            }

            // JS and CSS required by the main Library of the content
            $this->loadJsAndCss($contentLibrary);
        }

//
//        $contentUserDataUri = $uriBuilder->reset()
//            ->setArguments(['type' => 1560239219921, 'action' => 'contentUserData', 'h5pAction' => 'h5p_'])
//            ->buildFrontendUri();

        $this->view->assign('content', $content);
    }

    /**
     * Statistics action
     */
    public function statisticsAction()
    {
        if (!$GLOBALS['TSFE']->loginUser) {
            $this->view->assign('notLoggedIn', true);
            return;
        }

        $user = $GLOBALS['TSFE']->fe_user->user;

        $statistics = $this->contentResultRepository->findByUser((int)$user['uid']) ;
        if (!$statistics) {
            $this->view->assign('statisticsNotFound', true);
            return;
        }

        $pageIds = [];
        if (count($statistics)) {
            foreach ($statistics as $item) {
                $pageIds[$item->getPid()] = $item->getPid();
            }
        }

        if (!count($pageIds)) {
            $this->view->assign('statisticsNotFound', true);
            return;
        }

        $statisticsByPage = [];
        $pageRepository = $this->objectManager->get(PageRepository::class);
        $pages = $pageRepository->findByUids($pageIds);
        foreach ($pages as $page) {
            $statisticsByPage[$page->getUid()] = [
                'page' => $page,
                'statistics' => []
            ];
            foreach ($statistics as $item) {
                if ($item->getPid() === $page->getUid()) {
                    $statisticsByPage[$page->getUid()]['statistics'][] = $item;
                }
            }
        }

        $this->view->assign('dateFormat', $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy']);
        $this->view->assign('timeFormat', $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm']);
        $this->view->assign('statisticsByPage', $statisticsByPage);
    }

    /**
     * Get generic h5p settings
     *
     * @return array;
     */
    public function getCoreSettings()
    {
        $relativeExtensionPath = ExtensionManagementUtility::siteRelPath('h5p');
        $relativeExtensionPath = str_replace('typo3conf', '/typo3conf', $relativeExtensionPath);

        $ajaxSetFinishedUri = $this->uriBuilder->reset()
            ->setArguments(['type' => 1561098634614])
            ->setCreateAbsoluteUri(true)
            ->uriFor('finish', [], 'Ajax', 'H5p', 'ajax');

        $url = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');

        $cacheBuster = '?v=' . Framework::$version;

        $settings = [
            'baseUrl'            => $url,
            'url'                => '/fileadmin/h5p',
            'postUserStatistics' => false,
            'ajax'               => [
                'setFinished'     => $ajaxSetFinishedUri,
                'contentUserData' => '',
            ],
            'saveFreq'           => $this->h5pFramework->getOption('save_content_state') ? $this->h5pFramework->getOption('save_content_frequency') : false,
            'siteUrl'            => $url,
            'l10n'               => [
                'H5P' => $this->h5pCore->getLocalization(),
            ],
            'hubIsEnabled'       => (int)$this->h5pFramework->getOption('hub_is_enabled') === 1,
            'reportingIsEnabled' => (int)$this->h5pFramework->getOption('enable_lrs_content_types') === 1,
            'libraryConfig'      => $this->h5pFramework->getLibraryConfig(),
            'crossorigin'        => defined('H5P_CROSSORIGIN') ? H5P_CROSSORIGIN : null,
            'pluginCacheBuster'  => $cacheBuster,
            'libraryUrl'         => $url . $relativeExtensionPath . 'Resources/Public/Lib/h5p-core/js',
            'contents'           => []
        ];

        if ($GLOBALS['TSFE']->loginUser) {
            $user = $GLOBALS['TSFE']->fe_user->user;

            $name = $user['first_name'];
            if ($user['middle_name']) {
                $name .= ' ' . $user ['middle_name'];
            }
            if ($user['last_name']) {
                $name .= ' ' . $user ['last_name'];
            }

            $settings['user'] = [
                'name' => $name,
                'mail' => $user['email']
            ];
            $settings['postUserStatistics'] = $this->h5pFramework->getOption('track_user') && (bool)$user['uid'];
        }

        $relativeExtensionPath = ExtensionManagementUtility::siteRelPath('h5p');
        $relativeExtensionPath = str_replace('typo3conf', '/typo3conf', $relativeExtensionPath);
        $relativeCorePath = $relativeExtensionPath . 'Resources/Public/Lib/h5p-core/';
        foreach (H5PCore::$styles as $style) {
            $settings['core']['styles'][] = $relativeCorePath . $style . $cacheBuster;
        }
        foreach (H5PCore::$scripts as $script) {
            $settings['core']['scripts'][] = $relativeCorePath . $script . $cacheBuster;
        }
        $settings['loadedJs'] = [];
        $settings['loadedCss'] = [];

        return $settings;
    }

    /**
     * Get content settings
     *
     * @param Content $content
     * @return array;
     */
    public function getContentSettings(Content $content)
    {
        $settings = [
            'url'            => '/fileadmin/h5p',
            'library'        => sprintf(
                '%s %d.%d.%d',
                $content->getLibrary()->getMachineName(),
                $content->getLibrary()->getMajorVersion(),
                $content->getLibrary()->getMinorVersion(),
                $content->getLibrary()->getPatchVersion()
            ),
            'jsonContent'    => $content->getFiltered(),
            'fullScreen'     => false,
            'exportUrl'      => '/path/to/download.h5p',
            'embedCode'      => '',
            'resizeCode'     => '',
            'mainId'         => $content->getUid(),
            'title'          => $content->getTitle(),
            'displayOptions' => [
                'frame'     => false,
                'export'    => false,
                'embed'     => false,
                'copyright' => false,
                'icon'      => false
            ]
        ];

        if ($content->getEmbedType() === 'iframe') {
            $contentLibrary = $content->getLibrary()->toAssocArray();
            $dependencyLibrary = $this->h5pCore->loadLibrary($contentLibrary['machineName'], $contentLibrary['majorVersion'], $contentLibrary['minorVersion']);
            $this->h5pCore->findLibraryDependencies($dependencies, $dependencyLibrary);
            if (is_array($dependencies)) {
                $dependencies = $this->h5pCore->orderDependenciesByWeight($dependencies);
                foreach ($dependencies as $key => $dependency) {
                    if (strpos($key, 'preloaded-') !== 0) {
                        continue;
                    }
                    $this->setJsAndCss($dependency['library'], $settings);
                }
            }

            $contentDependencies = $this->h5pFramework->loadContentDependencies($content->getUid(), 'preloaded');
            foreach ($contentDependencies as $dependency) {
                $this->setJsAndCss($dependency, $settings);
            }

            $this->setJsAndCss($contentLibrary, $settings);
        }

        return $settings;
    }

    /**
     * Set JS and CSS
     * @param array $library
     * @param array $settings
     */
    private function setJsAndCss(array $library, array &$settings)
    {
        $name = $library['machineName'] . '-' . $library['majorVersion'] . '.' . $library['minorVersion'];
        $preloadCss = explode(',', $library['preloadedCss']);
        $preloadJs = explode(',', $library['preloadedJs']);
        $cacheBuster = '?v=' . Framework::$version;

        if (!array_key_exists('scripts', $settings)) {
            $settings['scripts'] = [];
        }

        if (!array_key_exists('styles', $settings)) {
            $settings['styles'] = [];
        }

        foreach ($preloadJs as $js) {
            $js = trim($js);
            if ($js) {
                $settings['scripts'][] = '/fileadmin/h5p/libraries/' . $name . '/' . $js . $cacheBuster;
            }
        }
        foreach ($preloadCss as $css) {
            $css = trim($css);
            if ($css) {
                $settings['styles'][] = '/fileadmin/h5p/libraries/' . $name . '/' . $css . $cacheBuster;
            }
        }
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
                $this->pageRenderer->addJsFooterFile('/fileadmin/h5p/libraries/' . $name . '/' . $js, 'text/javascript', false, false, '', true);
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
