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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Sypets\Cal2calendarize\Service\MigrateCalPluginsService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MigratePluginsCommand extends Command
{
    /**
     * @var SymfonyStyle
     */
    protected $io;

    /**
     * @var MigrateCalPluginsService
     */
    protected $migrateCalPluginService;

    public function __construct($name = null)
    {
        parent::__construct($name);
    }

    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure(): void
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
            ->addOption(
                'all-actions',
                null,
                InputOption::VALUE_NONE,
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
        $numberOfPlugins = $this->migrateCalPluginService->countPlugins();
        $this->io->writeln((sprintf('Number of plugins found: %d', $numberOfPlugins)));

        if ($numberOfPlugins > 0) {
            return true;
        }
        return false;
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

        $this->migrateCalPluginService = GeneralUtility::makeInstance(MigrateCalPluginsService::class, $this->io);

        $this->io->title($this->getDescription());

        if (!$this->hasPluginsToMigrate()) {
            return 0;
        }

        // check if default XML can be loaded
        $url = ExtensionManagementUtility::extPath('cal2calendarize', 'Configuration/DefaultFlexform.xml');
        $xml = file_get_contents($url);
        if (!$xml) {
            $this->io->error('Unable to load XML ' . $url);
            return 1;
        }

        $command = $input->getArgument('cmd');
        switch ($command) {
            case 'migrate':
                $dryRun = false;
                break;

            case 'check':
                $dryRun = true;
                break;

            default:
                $this->io->error('Must pass argument \'migrate\' or \'check\'');
                return 1;
        }

        $migrateUid = (int)$input->getArgument('uid');
        if ($migrateUid !== 0) {
            $this->io->writeln($command . ' only this uid=' . $migrateUid);
        } else {
            $this->io->writeln($command . ' all');
        }

        $useAllActions = $input->getOption('all-actions');
        if ($useAllActions === true) {
            $this->io->writeln('Use all actions');
        } else {
            $this->io->writeln('Use only actions defined in switchableControllerActions');
        }

        $this->migrateCalPluginService->migratePlugins($migrateUid, $useAllActions, $dryRun);

        return 0;
    }
}
