<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

(function () {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Sypets.Cal2calendarize',
        'web',
        'CalPluginFixer',
        'bottom',
        [
            'PluginFixer' => 'listPluginsWithProblems'
        ],
        [
            'access' => 'user,group',
            'icon' => 'EXT:beuser/Resources/Public/Icons/module-beuser.svg',
            'labels' => 'LLL:EXT:cal2calendarize/Resources/Private/Language/locallang_cal2calendarize.xlf',
        ]
    );

})();