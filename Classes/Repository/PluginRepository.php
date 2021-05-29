<?php

namespace Sypets\Cal2calendarize\Repository;

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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Sypets\Cal2calendarize\Configuration\PluginFlexform;

class PluginRepository
{
    /**
     * @var VisitenkarteFlexForm
     */
    protected $pluginFlexform;


    /**
     * @var array
     */
    protected $count;

    /**
     * @var Logger
     *
     * @todo - use LoggerAwareTrait instead
     */
    protected $logger;

    public function __construct(PluginFlexform $pluginFlexform = null)
    {
        if ($pluginFlexform) {
            $this->pluginFlexform = $pluginFlexform;
        } else {
            $this->pluginFlexform = GeneralUtility::makeInstance(PluginFlexform::class);
        }

        $this->count = [
            'total' => 0,
            'ok' => 0

        ];
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }

    /**
     * Find all calendarize plugins in tt_content
     * which were migrated.
     *
     * @todo pass arguments: show all, show plugins with errors, with specific errors etc.
     */
    public function findPlugins(array $pageIds = []): array
    {
        $results = [];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');

        $constraints = [
            $queryBuilder->expr()->eq('tt_content.ctype', $queryBuilder->createNamedParameter('list')),
            $queryBuilder->expr()->eq('tt_content.list_type', $queryBuilder->createNamedParameter('calendarize_calendar')),
        ];

        if ($pageIds) {
            $constraints[] = $queryBuilder->expr()->in('p.uid', $queryBuilder->createNamedParameter($pageIds, Connection::PARAM_INT_ARRAY));
        }

        $stmt = $queryBuilder->select('tt_content.uid AS uid',
            'tt_content.header AS header',
            'tt_content.pid AS pid',
            'tt_content.list_type AS list_type',
            'tt_content.pi_flexform AS pi_flexform',
            'tt_content.sys_language_uid AS sys_language_uid',
            'p.title AS title')

            ->from('tt_content')
            ->join('tt_content', 'pages', 'p',
                $queryBuilder->expr()->eq('p.uid', $queryBuilder->quoteIdentifier('tt_content.pid'))
            )
            ->where(...$constraints)
            ->orderBy('pid')
            ->addOrderBy('sys_language_uid')
            ->addOrderBy('uid')
            ->execute();



        while ($row = $stmt->fetch()) {
            $result = [];

            $resultValue = $this->pluginFlexform->getResultsFromFlexForm($row, $result);

            // show only plugins with errors
            if ($resultValue == false) {
                $results[] = $result;
            }

            /*
            $result['errorCode'] = $errorcode;
            if ($matchType == 'ge' && $errorcode >= $code) {
                if ($errorcode >= $code) {
                    $results[] = $result;
                }
            } else if ($matchType === 'equal' && $errorcode === $code) {
                if ($errorcode == $code) {
                    $results[] = $result;
                }
            }
            */
        }

        $this->logger->debug('findPersonPlugins: found:' . count($results) . ' for plugins with errorcode=$code matchType=$matchType');

        return $results;
    }

    public function findPluginsWithErrors()
    {
        // find plugins with errors
        return $this->findPlugins();
    }


    /**
     * Get plugin information by uid in 'tt_content'
     *
     * @param int $uid
     * @return array
     */
    public function getByUid(int $uid) : array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $row = $queryBuilder->select('uid', 'pid', 'list_type', 'pi_flexform', 'sys_language_uid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
            )
            ->execute()
            ->fetch();
        if (!$row) {
            $row = [];
        }
        return $row;
    }

    /**
     * Reuse extGetTreeList from core ext:linkvalidator
     *
     * Generates a list of page uids from $id. List does not include $id itself.
     * The only pages excluded from the list are deleted pages.
     *
     * @param int $id Start page id
     * @param int $depth Depth to traverse down the page tree.
     * @param int $begin is an optional integer that determines at which level to start. use "0" from outside usage
     * @param string $permsClause Perms clause
     * @param bool $considerHidden Whether to consider hidden pages or not
     * @return array Returns the list of pages
     */
    public function getSubPages(int $id, int $depth, int $begin, string $permsClause, bool $considerHidden = false): array
    {
        $depth = (int)$depth;
        $begin = (int)$begin;
        $id = (int)$id;
        $theList = [];
        if ($depth === 0) {
            $theList[] = $id;
            return $theList;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $result = $queryBuilder
            ->select('uid', 'title', 'hidden', 'extendToSubpages')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)
                ),
                QueryHelper::stripLogicalOperatorPrefix($permsClause)
            )
            ->execute();

        while ($row = $result->fetch()) {
            if ($begin <= 0 && ($row['hidden'] == 0 || $considerHidden)) {
                $theList[] = (int) ($row['uid']);
            }
            if ($depth > 1 && (!($row['hidden'] == 1 && $row['extendToSubpages'] == 1) || $considerHidden)) {
                $theList[]= $this->getSubPages(
                    $row['uid'],
                    $depth - 1,
                    $begin - 1,
                    $permsClause,
                    $considerHidden
                );
            }
        }
        return $theList;
    }

    public function addPageTranslationsToPageList(array $theList, string $permsClause): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $result = $queryBuilder
            ->select('uid', 'title', 'hidden')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    'l10n_parent',
                    $queryBuilder->createNamedParameter($this->id, \PDO::PARAM_INT)
                ),
                QueryHelper::stripLogicalOperatorPrefix($permsClause)
            )
            ->execute();

        while ($row = $result->fetch()) {
            if ($row['hidden'] === 0 || $this->modTS['checkhidden']) {
                $theList[] = (int) ($row['uid']);
            }
        }

        return $theList;
    }


}
