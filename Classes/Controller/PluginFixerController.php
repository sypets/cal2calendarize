<?php

declare(strict_types=1);

namespace Sypets\Cal2calendarize\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Sypets\Cal2calendarize\Repository\PluginRepository;

class PluginFixerController extends ActionController
{

    /**
     * @var PluginRepository
     */
    protected $pluginRepository;

    public function __construct()
    {
        $this->pluginRepository = GeneralUtility::makeInstance(PluginRepository::class);
    }

    public function listPluginsWithProblemsAction()
    {
        $currentPageId = (int)GeneralUtility::_GP('id');
        $results = $this->pluginRepository->findPlugins();

        $this->view->assign('results', $results);
        $this->view->assign('pageId', $currentPageId);
    }
}
