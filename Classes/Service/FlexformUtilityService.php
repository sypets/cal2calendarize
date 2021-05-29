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

namespace Sypets\Cal2calendarize\Service;

use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

class FlexformUtilityService
{

    /**
     * @var SimpleXMLElement
     */
    protected $simpleXml;


    /** @var FlexformUtilityService */
    protected $flexFormService;

    /**
     * @var FlexFormTools
     */
    protected $flexformTools;

    /**
     * @var string
     */
    protected $errorMsg;

    public function __construct()
    {
        $this->flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
        $this->flexformTools = GeneralUtility::makeInstance(FlexFormTools::class);
        $this->initialize();
    }

    protected function initialize(): bool
    {
        $url = ExtensionManagementUtility::extPath('cal2calendarize', 'Configuration/DefaultFlexform.xml');
        $xml = file_get_contents($url);
        if (!$xml) {
            return false;
        }
        $this->simpleXml = new \SimpleXMLElement($xml);

        return true;
    }

    public function getErrorMsg(): string
    {
        return $this->errorMsg;
    }

    public function setFlexformValue(string $sheet, string $field, $value): bool
    {
        // e.g. /T3FlexForms/data/sheet[@index='general']/language[@index='lDEF']/field[@index='persistence.storagePid']/value[@index='vDEF']
        $xpath = "/T3FlexForms/data/sheet[@index='"
            . $sheet
            . "']/language[@index='lDEF']/field[@index='"
            . $field
            . "']/value[@index='vDEF']";
        $node = $this->simpleXml->xpath($xpath);
        if (isset($node[0][0])) {
            $node[0][0] = $value;
            $this->errorMsg = '';
            return true;
        }
        $this->errorMsg = 'Unable to set value xpath=' . $xpath;
        return false;
    }


    public function getXml()
    {
        return $this->simpleXml->asXML();
    }

    /**
     * Convert an arry into a FlexForm XML string
     *
     * @param array $input
     * @param bool $addPrologue
     * @return string
     */
    public function array2FlexformXml(array $input, bool $addPrologue = false): string
    {
        $output = $this->flexformTools->flexArray2Xml($input, $addPrologue);

        return $output;
    }

    public function convertFlexFormContentToArray(string $flexformXml): array
    {
        return $this->flexFormService->convertFlexFormContentToArray($flexformXml);
    }

}
