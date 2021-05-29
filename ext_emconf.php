<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Migrate cal database content to calendarize',
    'description' => '',
    'category' => 'install',
    'version' => '0.0.4-dev',
    'state' => 'beta',
    'uploadfolder' => false,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'Sybille Peters',
    'author_email' => 'sypets@gmx.de',
    'author_company' => '',
    'constraints' => [
            'depends' =>
                [
                    'typo3' => '9.5.0- 10.4.99'
                ],
            'suggests' => [],
            'conflicts' => [],
        ],
    'clearcacheonload' => false,
];
