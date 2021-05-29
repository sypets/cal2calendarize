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

namespace Sypets\Cal2calendarize\Command;

use HDNET\Calendarize\Utility\HelperUtility;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Sypets\Cal2calendarize\Service\FlexformUtilityService;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MigratePluginsCommand extends Command
{

    const WRITE_LEVEL_VERBOSE = 1;
    const WRITE_LEVEL_VERY_VERBOSE = 2;

    /**
     * @var SymfonyStyle
     */
    protected $io;

    /**
     * @var bool
     */
    protected $dryRun = true;

    /**
     * @var array
     */
    protected $oldFlexFormArray;

    /**
     * @var array
     *
     * @deprecated
     */
    protected $newFlexformArray;

    /**
     * @var CalMigrationFlexformService
     */
    protected $flexFormService;

    /**
     * @var array
     * @deprecated ConfgiurationDefaultFlexform.xml is used
     */
    protected $defaultFlexformArray = [
        'switchableControllerActions' => 'Calendar->list;Calendar->detail',
        'settings' => [
            'pluginConfiguration' => '',
            'useRelativeDate' => '0',
            'configuration' => 'Event',
            'sortBy' => 'start',
            'sorting' => 'ASC',
            'detailPid' => '',
            'listPid' => '',
            'yearPid' => '',
            'quarterPid' => '',
            'monthPid' => '',
            'weekPid' => '',
            'dayPid' => '',
            'bookingPid' => ''
        ],
        'persistence' => [
            'storagePid' => '',
            'recursive' => ''
        ]
    ];


    /**
     * Relative dates see https://www.php.net/manual/en/datetime.formats.relative.php
     *
     * @var array
     */
    protected $flexformRelativeTimeMapping = [

        // cal => calendarize
        'now' => 'now',
        'cal:yesterday' => 'yesterday',
        'cal:today' => 'today',
        'cal:tomorrow' => 'today',
        // Sets the day of the first of the current month. This phrase is best used together with a month name following it.
        'cal:monthstart' => 'first day of',
        'cal:weekstart' => 'this week',
        'cal:monthstart' => 'this month',
        // there is no identical mapping, we use a similar mapping
        'cal:quarterstart' => '3 months ago',
        'cal:yearstart' => 'this year',

        // @todo may have to check for settting of what is last day of week
        'cal:weekend' => 'this sunday',
        '+1 week' => '+1 week',
        'cal:monthend' => 'last day of this month',
        '+1 month' => '+1 month',
        // there is no identical mapping, we use a similar mapping
        'cal:quarterend' => '+3 months',
        'cal:yearend' => 'last day of december this year',
        '+1 year' => '+1 year',
    ];

    protected $flexformUsePageBrowserMapping = [
        // usePageBrowser => hidePagination
        '0'  => '1',
        '1'  => '0',
        ''   => '0',
    ];

    protected $flexFormSettingsMapping = [

        // key: setting in cal
        'eventViewPid' => [
            // information for settings in calendarize
            'sheet' => 'pages',
            'field' => 'settings.detailPid',
            'default' => '',
        ],
        'listViewPid' => [
            'sheet' => 'pages',
            'field' => 'settings.listPid',
            'default' => '',
        ],
        'yearViewPid' => [
            'sheet' => 'pages',
            'field' => 'settings.yearPid',
            'default' => '',
        ],
        //'' => 'quarterPid',
        'monthViewPid' => [
            'sheet' => 'pages',
            'field' => 'settings.monthPid',
            'default' => '',
        ],
        'weekViewPid' => [
            'sheet' => 'pages',
            'field' => 'settings.weekPid',
            'default' => '',
        ],
        'dayViewPid' => [
            'sheet' => 'pages',
            'field' => 'settings.dayPid',
            'default' => '',
        ],
        //'' => 'bookingPid'

        'starttime' => [
            'sheet' => 'main',
            'field' => 'settings.overrideStartRelative',
            'default' => '',
            'valueMapping' => true
        ],
        'endtime' => [
            'sheet' => 'main',
            'field' => 'settings.overrideEndRelative',
            'default' => '',
            'valueMapping' => true
        ],
        'usePageBrowser' => [
            'sheet' => 'main',
            'field' => 'settings.hidePagination',
            'default' => '',
            'valueMapping' => true
        ],
    ];

    /*
     * Map cal allowedViews to calendarize switchableControllerActions
     */
    protected $flexformAllowedViewsCombinedMapping = [
        'list' => 'Calendar->list',
        'event' => 'Calendar->detail',
        'list,event' => 'Calendar->list;Calendar->detail',
        'search_all' => 'Calendar->search',

        // => 'Calendar->result'
        // => 'Calendar->latest'
        // => 'Calendar->single'

        // dates
        'year' => 'Calendar->year',
        // => 'Calendar->quarter'
        'month' => 'Calendar->month',
        'week' => 'Calendar->week',
        'day' => 'Calendar->day',

        // Calendar->past should be used for past events, but in cal, this is also list view
        // => 'Calendar->past'

        // => 'Booking->booking;Booking->send'
    ];

    /*
     * This maps every single possible value to the single Controller action in calendarize
     * even if this is not available in the switchableControllerAction
     */
    protected $flexformAllowedViewsSingleMapping = [
        'list' => 'Calendar->list',
        'event' => 'Calendar->detail',
        'search_all' => 'Calendar->search',
        'year' => 'Calendar->year',
        // => 'Calendar->quarter'
        'month' => 'Calendar->month',
        'week' => 'Calendar->week',
        'day' => 'Calendar->day',
        // 'admin' => ?
        // 'search_event' => ?
        // 'search_location'
        // 'search_organizer'
        // 'serach_all'
        // 'create_event~confirm_event~save_event' => ?
        // 'edit_event~confirm_event~save_event' => ?
        // 'delete_event~confirm_event~remove_event~save_exception_event' => ?
        // 'create_calendar~confirm_calendar~save_calendar' => ?
        // 'edit_calendar~confirm_calendar~save_calendar' => ?
        // 'delete_calendar~confirm_calendar~remove_calendar'
        // 'create_category~confirm_category~save_category' => ?
        // ...
        // 'organizer' => ?
        // 'location' => ?
        // 'ics~icslist~single_ics' => ?
        // 'subscription'
        // 'meeting' =>
        // 'translation' =>
        // 'todo' =>
        // 'ajax' =>
    ];


    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->flexFormSettingsMapping['starttime']['valueMapping'] = $this->flexformRelativeTimeMapping;
        $this->flexFormSettingsMapping['endtime']['valueMapping'] = $this->flexformRelativeTimeMapping;
        $this->flexFormSettingsMapping['usePageBrowser']['valueMapping'] = $this->flexformUsePageBrowserMapping;
        $this->flexFormService = GeneralUtility::makeInstance(FlexformUtilityService::class);
        $this->repository = GeneralUtility::makeInstance(FlexformUtilityService::class);
    }

    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure()
    {
        $this->setDescription('Migration cal plugins to calendarize plugins')
            ->addArgument(
                'cmd',
                InputArgument::REQUIRED,
                'migrate or check'
            )
            ->addArgument(
                'uid',
                InputArgument::OPTIONAL,
                'Migrate only this uid (in tt_content). If not specified, migate all'
            )
            ->addOption('all-actions', null, InputOption::VALUE_NONE,
                'Use all available controller actions, even if not defined in switchableControllerActions',
                null
            );
    }

    /**
     * Checks whether migrations are required.
     *
     * @return bool Whether migration is required (TRUE) or not (FALSE)
     */
    public function hasPluginsToMigrate(): bool
    {
        $numberOfPlugins = $this->countPlugins();
        $this->writeln((sprintf('Number of plugins found: %d', $numberOfPlugins)));

        if ($numberOfPlugins > 0) {
            return true;
        }
        return true;
    }

    /**
     * Executes the command for showing sys_log entries
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->io->title($this->getDescription());

        $url = ExtensionManagementUtility::extPath('cal2calendarize', 'Configuration/DefaultFlexform.xml');
        $xml = file_get_contents($url);
        if (!$xml) {
            $this->io->error("Unable to load XML " . $url);
        }

        $command = $input->getArgument('cmd');
        switch ($command) {
            case 'migrate':
                $this->dryRun = false;
                break;

            case 'check':
                $this->dryRun = true;
                break;

            default:
                $this->io->error('Must pass argument \'migrate\' or \'check\'');
                return 0;
        };

        $migrateUid = (int) $input->getArgument('uid');
        if ($migrateUid !== 0) {
            $this->writeln($command . ' only this uid=' . $migrateUid);
        } else {
            $this->writeln($command . ' all');
        }

        $this->useAllActions = $input->getOption('all-actions');
        if ($this->useAllActions === true) {
            $this->writeln('Use all actions');
        } else {
            $this->writeln('Use only actions defined in switchableControllerActions');
        }

        $plugins = $this->getPluginRows('cal_controller', $migrateUid);

        foreach ($plugins as $row) {
            $uid = (int) $row['uid'];

            $this->writeln(sprintf('old flexform as XML=%s', $row['pi_flexform']), self::WRITE_LEVEL_VERY_VERBOSE);
            $this->oldFlexFormArray = $this->flexFormService->convertFlexFormContentToArray($row['pi_flexform']);
            $this->writeln(sprintf('old flexform as array =>json=%s',
                \json_encode($this->oldFlexFormArray)), self::WRITE_LEVEL_VERY_VERBOSE);

            $this->migrateFields($uid, $row);

            $this->migrateCategories($uid,$row);
        }



        return 0;
    }

    protected function writeln(string $message, int $level=self::WRITE_LEVEL_VERBOSE): void
    {
        if ($this->io->isVeryVerbose() !== true && $level >= self::WRITE_LEVEL_VERY_VERBOSE) {
            return;
        }
        if ($this->io->isVerbose() !== true && $level >= self::WRITE_LEVEL_VERBOSE) {
            return;
        }
        $this->io->writeln($message);
    }

    protected function migrateFields(int $uid, array $row)
    {
        $table = 'tt_content';

        $changedFields = $this->getChangedValues($row);
        foreach ($changedFields as $key => $value) {
            $this->writeln(sprintf('uid=%d UPDATE field %s: %s', $uid,$key, $value), self::WRITE_LEVEL_VERY_VERBOSE);
        }

        if ($this->dryRun === false) {

            // get max sorting
            $c = HelperUtility::getDatabaseConnection($table);
            $c->update(
                $table,
                $changedFields,
                ['uid' => $uid]
            );
        }

    }

    protected function migrateCategories(int $uid,array $row)
    {
        $categoryMode = (int) $this->oldFlexFormArray['categoryMode'];
        $categorySelection = $this->oldFlexFormArray['categorySelection'];

        $this->writeln(sprintf('Category mode=%d', $categoryMode));
        $this->writeln(sprintf('Category selection=%s', $categorySelection));

        switch ($categoryMode) {
            case 0:
                break;
            case 1:
                if ($categorySelection !== '') {
                    $this->io->warning('Category mode=1 (exact): we cannot map this, use mode=3');
                }
                $categoryMode = 3;
                break;
            case 2:
                $this->io->warning('Category mode=2 (none): we cannot map this, do not use categories (mode=0');
                $categoryMode = 0;
                break;
            case 3:
                // ok
                break;
            case 4:
                $this->io->warning('Category mode=4 (minimum): we cannot map this, use categories (mode=3');
                $categoryMode = 3;
                break;
        }
        if ($categoryMode !== 3 || $categorySelection === '') {
            return;
        }

        // set categories in plugin
        $this->insertCategoryRelations($uid, explode(',', $categorySelection));

    }

    /**
     * @param array $row
     * @return array changed fields
     */
    protected function getChangedValues(array $row): array
    {
        $uid = (int) $row['uid'];
        $this->writeln('');
        $this->writeln(sprintf('uid=%d, pid=%d', $uid, (int)$row['pid']));

        // storagePid
        $result = $this->flexFormService->setFlexformValue('general', 'persistence.storagePid', $row['pages']);
        if (!$result) {
            $this->io->warning('Setting value persistence.storagePid failed!:');
        }
        $this->writeln(sprintf('Migrate setting: pages=%s => persistence.storagePid', $row['pages']));

        // recursive
        $result = $this->flexFormService->setFlexformValue('general', 'persistence.recursive', $row['recursive']);
        if (!$result) {
            $this->io->warning('Setting value persistence.recursive failed!:');
        }
        $this->writeln(sprintf('Migrate setting: recursive=%s => persistence.recursive', $row['recursive']));

        // allowedViews => switchableControllerActions
        $allowedViews = $this->oldFlexFormArray['allowedViews'] ?? '';
        $this->flexFormService->setFlexformValue('main', 'switchableControllerActions',
            $this->getSwitchableControllerAction($allowedViews));

        foreach ($this->flexFormSettingsMapping as $oldKey => $new) {
            $value = $this->oldFlexFormArray[$oldKey] ?? $new['default'];

            if ($new['valueMapping'] ?? false) {
                $oldValue = $value;
                $value = $new['valueMapping'][$value] ?? $new['default'];
            }

            $result = $this->flexFormService->setFlexformValue($new['sheet'], $new['field'], $value);
            if (!$result) {
                $this->io->warning(sprintf('Error writing setting oldKey=%s error=%s',
                    $oldKey, $this->flexFormService->getErrorMsg()));
            }
            $this->writeln(sprintf('Migrate setting: %s=%s => %s.%s=%s',
                $oldKey, $oldValue, $new['sheet'], $new['field'], $value));
        }

        $xml = $this->flexFormService->getXml();

        $this->writeln(sprintf('new XML=%s', $xml), self::WRITE_LEVEL_VERY_VERBOSE);
        return [
            'CType' => 'list',
            'list_type'=> 'calendarize_calendar',
            'pages' => '',
            'recursive' => 0,
            'pi_flexform' => $xml,
        ];

    }

    /**
     * @param string $allowedView Is allowedView from Flexform in cal. Can be
     *   'event', 'list', 'event,list', 'list,day' or any other combination.
     *   The value for calendarize is restricted to available switchableControllerActions
     *
     * @return string
     */
    public function getSwitchableControllerAction(string $allowedView): string
    {
        $result = $this->flexformAllowedViewsCombinedMapping[$allowedView] ?? '';
        if ($result !== '') {
            return $result;
        }

        $this->io->warning(sprintf('No exact mapping found for allowedViews=%s', $allowedView));

        // fallback
        return $this->flexformAllowedViewsCombinedMapping['list,event'];
    }

    protected function insertCategoryRelations(int $uid, array $categorySelection): void
    {
        $table = 'sys_category_record_mm';

        // get max sorting
        $c = HelperUtility::getDatabaseConnection($table);

        // get max sorting
        $q = $c->createQueryBuilder();
        $sorting = (int) $q->select('sorting')
            ->from($table)
            ->where(
                $q->expr()->eq('tablenames', $q->createNamedParameter('tt_content')),
                $q->expr()->eq('fieldname', $q->createNamedParameter('categories')),
                $q->expr()->eq('uid_foreign', $q->createNamedParameter($uid, \PDO::PARAM_INT))
            )
            ->orderBy('sorting', 'DESC')
            ->setMaxResults(1)
            ->execute()
            ->fetchColumn(0);
        $this->writeln(sprintf('uid=%d MAX(sorting)=%d', $uid, $sorting));

        // get max sorting_foreign
        $q = $c->createQueryBuilder();
        $sortingForeign = (int) $q->select('sorting_foreign')
            ->from($table)
            ->where(
                $q->expr()->eq('tablenames', $q->createNamedParameter('tt_content')),
                $q->expr()->eq('fieldname', $q->createNamedParameter('categories')),
                $q->expr()->eq('uid_foreign', $q->createNamedParameter($uid, \PDO::PARAM_INT))
            )
            ->orderBy('sorting_foreign', 'DESC')
            ->setMaxResults(1)
            ->execute()
            ->fetchColumn(0);
        $this->writeln(sprintf('uid=%d MAX(sorting_foreign)=%d', $uid, $sortingForeign));


        foreach ($categorySelection as $key => $cat) {
            $cat = (int) $cat;
            $q = $c->createQueryBuilder();
            $count = (int) $q->count('*')
                ->from($table)
                ->where(
                    $q->expr()->eq('tablenames', $q->createNamedParameter('tt_content')),
                    $q->expr()->eq('fieldname', $q->createNamedParameter('categories')),
                    $q->expr()->eq('uid_foreign', $q->createNamedParameter($uid, \PDO::PARAM_INT)),
                    $q->expr()->eq('uid_local', $q->createNamedParameter($cat, \PDO::PARAM_INT))
                )
                ->execute()
                ->fetchColumn(0);

            // relation already exists
            if ($count > 0) {
                $this->io->writeln(sprintf('plugin <-> category relation exists, SKIP: %d => %d', $uid, $cat));
                continue;
            }

            $values = [
                'uid_foreign' => $uid,
                'uid_local' => $cat,
                'tablenames' => 'tt_content',
                'fieldname' => 'categories',
                'sorting' => $sorting++,
                'sorting_foreign' => $sortingForeign++
            ];

            // insert category
            $this->writeln(sprintf('INSERT plugin <-> category relation: %s',  \json_encode($values)));

            if ($this->dryRun === false) {
                $q->insert($table)
                    ->values(
                        $values
                    )
                    ->execute();
            }
        }
    }

    protected function countPlugins(): int
    {
        $table = 'tt_content';
        $q = HelperUtility::getDatabaseConnection($table)->createQueryBuilder();
        $q->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return (int) $q->count('uid')
            ->from($table)
            ->where(
                $q->expr()->eq('ctype', $q->createNamedParameter('list')),
                $q->expr()->eq('list_type', $q->createNamedParameter('cal_controller'))
            )
            ->execute();
    }

    /**
     * @param string $list_type
     * @param int $uid if 0, get all
     * @return array
     */
    protected function getPluginRows(string $list_type, int $uid=0): array
    {
        $table = 'tt_content';
        $q = HelperUtility::getDatabaseConnection($table)->createQueryBuilder();
        $q->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $where = [
            $q->expr()->eq('ctype', $q->createNamedParameter('list')),
            $q->expr()->eq('list_type', $q->createNamedParameter($list_type))
        ];

        if ($uid !== 0) {
            $where[] = $q->expr()->eq('uid', $q->createNamedParameter($uid, \PDO::PARAM_INT));
        }

        return $q->select('uid', 'pid', 'pi_flexform', 'pages', 'recursive', 'categories')
            ->from($table)
            ->where(...$where)
            ->execute()
            ->fetchAll();
    }

    protected function getRow(string $table, int $uid): array
    {
        $q = HelperUtility::getDatabaseConnection($table)->createQueryBuilder();
        $q->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $q->select('*')
            ->from($table)
            ->where(
                $q->expr()->eq('uid', $q->createNamedParameter($uid, \PDO::PARAM_INT))
            )
            ->execute()
            ->fetch();
    }


}
