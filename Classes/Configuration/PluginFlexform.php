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

namespace Sypets\Cal2Calendarize\Configuration;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Functions for direct FlexForm array evaluation
 */
class PluginFlexform
{
    protected const EXT_NAME = 'cal2calendarize';

    public const ERROR_UNKNOWN_ERROR = 1;

    // flexform invalid
    public const ERROR_NO_FLEXFORM = 2;
    public const ERROR_EMPTY_FLEXFORM = 3;
    //public const ERROR_INVALID_FLEXFORM = 4;

    public const ERROR_NO_STORAGE_PID = 10;
    public const ERROR_NO_DETAIL_PID_AND_NO_DETAIL_ACTION = 11;

    /**
     * @var FlexFormService
     */
    protected $flexFormService;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ExtensionConfiguration
     */
    protected $conf;

    public function __construct()
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        $this->flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
        $this->conf = GeneralUtility::makeInstance(ExtensionConfiguration::class);
    }

    /**
     * @param int $errorCode
     * @return string
     *
     * @todo localize this
     */
    public function getErrorMessage(int $errorCode): string
    {
        if ($errorCode > 1 && $errorCode <= 9) {
            return 'Missing or invalid flexform';
        }

        switch ($errorCode) {
            case self::ERROR_NO_STORAGE_PID:
                $errormessage= 'Missing Startingpoint [storagePid]';
                break;
            case self::ERROR_NO_DETAIL_PID_AND_NO_DETAIL_ACTION:
                $errormessage = 'No detailPid and no detail controller action';
                break;

            default:
                $errormessage = 'Unknown error';
                break;
        }
        return $errormessage;
    }

    /**
     * @param array $values
     * @param int $uid
     * @param int $errorCode
     * @return bool
     *
     * @todo use class instead of $errorCode
     */
    public function isXmlArrayValid(array $values, int $uid, int &$errorCode): bool
    {
        if (!$values || !is_array($values)) {
            $errorCode = self::ERROR_EMPTY_FLEXFORM;
            $this->logger->warning('Empty array after cleanup flexform, uid=' . $uid);
            return false;
        }
        return true;
    }

    public function isValidValues(array $values, array &$errorCodes): bool
    {
        $storagePid = $values['storagePid'] ?? '';
        $switchableControllerActions = $values['switchableControllerActions'];
        $detailPid = (int)$values['detailPid'];

        // check if missing storagePid
        if ($storagePid === '') {
            switch ($this->conf->get(self::EXT_NAME, 'missingStorageIsError')) {
                case 'always':
                    $errorCodes[] = self::ERROR_NO_STORAGE_PID;
                    break;

                case 'ifRecursiveMissing':
                    // todo
                    $errorCodes[] = 'Can\'t check storagePid - check recursive is not implemented!';
                    break;
            }
        }

        if (!in_array('Detail', $switchableControllerActions)
            && $detailPid === 0
        ) {
            $errorCodes[] = self::ERROR_NO_DETAIL_PID_AND_NO_DETAIL_ACTION;
        }

        return true;
    }

    /**
     * Get and check results from flexform.
     *
     * Can detect several problems and add several errorCodes to $result['errorCodes']
     *
     * @param string $flexXml
     * @param array $result
     * @return bool true, if no error in plugin
     */
    public function getResultsFromFlexForm(array $row, array &$result): bool
    {
        $uid = (int)($row['uid']);
        $result = [
            'uid' => $uid,
            'header' => $row['header'],
            'pid' => $row['pid'],
            'title' => $row['title'],
            'sys_language_uid' => $row['sys_language_uid']
        ];
        $flexStr = $row['pi_flexform'] ?? '';
        if (!$flexStr) {
            $result['errorCodes'][] = self::ERROR_NO_FLEXFORM;
            return false;
        }

        $xmlArray = $this->flexFormService->convertFlexFormContentToArray($flexStr);
        // check
        $result['errorCodes']  = [];
        $errorCode = 0;
        if (!$this->isXmlArrayValid($xmlArray, $uid, $errorCode)) {
            $result['errorCodes'][] = $errorCode;
            // abort here because it does not make sense to check futher.
            return false;
        }

        $this->transformArray($xmlArray, $result);

        $this->isValidValues($result, $result['errorCodes']);
        if ($result['errorCodes']) {
            $result['errormessages'] = [];
            foreach ($result['errorCodes'] as $code) {
                $result['errormessages'][] = $this->getErrorMessage($code);
            }
        }

        $result['xmlArray'] = $xmlArray;

        return ($result['errorCodes'] ?? []) === [];
    }

    protected function transformArray(array $xmlArray, array &$result): void
    {
        $result['storagePid'] = trim($xmlArray['persistence']['storagePid'] ?? '');
        $result['switchableControllerActions'] = $this->parseSwitchableControllerActions($xmlArray['switchableControllerActions'] ?? '');
        $result['useRelativeDate'] = (bool)($xmlArray['settings']['useRelativeDate'] ?? false);
        $result['overrideEndRelative'] = $xmlArray['settings']['overrideEndRelative'] ?? '';
        $result['detailPid'] = (int)($xmlArray['settings']['detailPid'] ?? 0);
    }

    /**
     * Split up the Controller actions in switchableControllerActions string,
     * return only action names as array.
     *
     * Reused code from calendarize:
     * HDNET\Calendarize\Hooks\CmsLayout::getExtensionSummary()
     *
     * @param string $value
     * @return array
     */
    protected function parseSwitchableControllerActions(string $value): array
    {
        // @var $parts
        $parts = GeneralUtility::trimExplode(';', $value, true);
        $parts = array_map(function ($element) {
            $split = explode('->', $element);

            return ucfirst($split[1]);
        }, $parts);

        return $parts;
    }
}
