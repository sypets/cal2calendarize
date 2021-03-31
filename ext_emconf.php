<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Migrate cal database content to calendarize',
    'description' => '',
    'category' => 'install',
    'version' => '0.0.2',
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
                    'typo3' => '10.4.0- 10.4.99',
                    //'calendarize' => '8.1.0-8.9.99'
                ],
            'suggests' => [],
            'conflicts' => [],
        ],
    'clearcacheonload' => false,
];
