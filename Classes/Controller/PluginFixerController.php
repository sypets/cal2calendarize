<?php

declare(strict_types=1);

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
