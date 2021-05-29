
=========================
Calendar Plugin migration
=========================

Migrate plugins from cal to calendarize.

!!! IMPORTANT: The cal events and category relations are already handled in
the calendarize upgrade wizard. This extension handles some things which are
not handled by the wizard of calendarize, such as plugin migration.

!!! WARNING: This is a first shot of migrating plugins, it greatly simplifies
the migration and does not consider all configuration options. It is not possible
to undo the plugin migrations. Use at your own risk. Test before using in
production. Make backups.

TIP: There is a backend module "cal2calendarize" which can be used to list
and visualize the migrated plugins with problems AFTER the migration.
Currently, only a few problems are listed such as missing detailPid and
missing storagePid. Look at the extension configuration for options.


Known problems
==============

*  Only flexform (and tt_content.pages and recursive) is considered, not the
   TypoScript in the flexform and TypoScript.

*  not possible to fully map the views (switchableControllerActions)

*  cal has more category modes, calendarize has only use categories or no categories

*  not all configuration is considered and migrated

*  in cal, it is possible to select a "calendar". This is ignored.

*  in cal, the categories can be selected in the FlexFrom **and** in the tab
   "Categories". For migrating, we ignore the categories set in the tab. We
   only consider the categories selected in the flexform.


Usage
=====

To run console command, use for installations setup without Composer:

.. code-block:: shell

   php typo3/sysext/core/bin/typo3

or with Composer:

.. code-block:: shell

   php vendor/bin/typo3


In general, the usage is:


.. code-block:: shell

   php vendor/bin/typo3 cal2calendarize:migrateCalPlugins [options] <command> [uid]

The options are optional, there are 2 arguments:

1. (required) command: "migrate" or "check"
2. (optional) uid


Show help:

.. code-block:: shell

   php vendor/bin/typo3 cal2calendarize:migrateCalPlugins -h


Dry-run: show what would be migrated:

.. code-block:: shell

   php vendor/bin/typo3 cal2calendarize:migrateCalPlugins check

Migrate all (with increased verbosity):

.. code-block:: shell

   php vendor/bin/typo3 cal2calendarize:migrateCalPlugins -vvv migrate


Migrate all (with `all-actions`, description see below):

.. code-block:: shell

   php vendor/bin/typo3 cal2calendarize:migrateCalPlugins -v --all-actions migrate


Migrate only one record in tt_content with uid=13221 (e.g. for testing):

.. code-block:: shell

   php vendor/bin/typo3 cal2calendarize:migrateCalPlugins  migrate 13221

Command options
===============

`--all-actions`:

This will try to migrate all existing Controller action to a corresponding
Controller action in calendarize, not just the Controller action combinations
defined in calendarize. The result is that more action may be activated, but
you will see a warning when editing the plugin and you should manually fix
this and convert it to existing controller actions.
This is a fast and sloppy solution, which might result in less problems directly
after migrating, but more problems in the long run.

Mapping
=======

We do not use full name of the configuration settings here.

* TS: TypoScript
* flex: Flexform

+-------------------------+--------------------------------------+-------------------+
| cal                     | calendarize                          | Mapping           |
+=========================+======================================+===================+
| flex: allowedViews      | flex: switchableControllerActions    | incomplete, see   |
|                         |                                      | below             |
+-------------------------+--------------------------------------+-------------------+
| tt_content.pages        | flex: persistence.storagePid         | 100%,             |
+-------------------------+--------------------------------------+-------------------+
| TS constants: pidList   | flex: persistence.storagePid         | constant not used |
+-------------------------+--------------------------------------+-------------------+
| flex: eventViewPid      | flex: detailPid                      | 100%              |
+-------------------------+--------------------------------------+-------------------+
| flex: listViewPid       | flex: listPid                        | 100%              |
+-------------------------+--------------------------------------+-------------------+
| flex: listViewPid       | flex: listPid                        | 100%              |
+-------------------------+--------------------------------------+-------------------+
| flex: yearViewPid       | flex: yearPid                        | 100%              |
+-------------------------+--------------------------------------+-------------------+
| flex: monthViewPid      | flex: monthPid                       | 100%              |
+-------------------------+--------------------------------------+-------------------+
| flex: weekViewPid       | flex: weekPid                        | 100%              |
+-------------------------+--------------------------------------+-------------------+
| flex: dayViewPid        | flex: dayPid                         | 100%              |
+-------------------------+--------------------------------------+-------------------+
| flex:usePageBrowser     | flex: hidePagination                 | flex yes, TS no   |
+-------------------------+--------------------------------------+-------------------+
| flex:categoryMode       | no category mode                     | incomplete, see   |
|                         |                                      | below             |
+-------------------------+--------------------------------------+-------------------+
| flex:categorySelection  | insert categories into               | 100%              |
|                         | sys_category_record_mm               |                   |
+-------------------------+--------------------------------------+-------------------+


Mapping of allowedViews
-----------------------

In cal, it is possible to combine any of the allowed views. In calendarize, we
have a defined set of switchable controller actions (for example "list", "detail",
"list+detail", but **not** "month+detail").

We get exact matches for `list`, `detail` and `list+detail`. For the year, month,
etc. there is no combined view with detail. It is recommended to create a
separate page for the detail view.

We try to map as best as possible, see source code. See also option
`--all-actions`.

Mapping of category modes
-------------------------

In cal, there are several category modes:

*  Category mode=0 (show all)
*  Category mode=1 (exact): exact match
*  Category mode=2 (none): show all events which DON't contain one of the selected categories
*  Category mode=3 (any): show all events with at least ONE of the selected categories
*  Category mode=4 (minimum): show only events which contain (at least) all the
   selected categories in the plugin.


https://docs.typo3.org/typo3cms/extensions/cal/stable/_sources/ConfigureThePlugin/FiltersTab/Index.rst.txt

We cannot exactly map this to calendarize: In calendarize, there is only one
category mode.

Fairly well mapped can be modes 0 and 3. For the other modes, we use the mode
that fits best - either we use the existing categories or we don't.


+-------------------------+--------------------------------------+-------------------+
| cal                     | calendarize                          | Mapping           |
+=========================+======================================+===================+
| mode=0                  | do not use categories                | 100%              |
+-------------------------+--------------------------------------+-------------------+
| mode=1                  | same as 3                            | no exact mapping  |
+-------------------------+--------------------------------------+-------------------+
| mode=2                  | same as 0                            | very wrong        |
+-------------------------+--------------------------------------+-------------------+
| mode=3                  | just use the categories              | 100%              |
+-------------------------+--------------------------------------+-------------------+
| mode=4                  | same as 3                            | no exact mapping  |
+-------------------------+--------------------------------------+-------------------+

Mapping of categories
---------------------

The categories in cal can be defined in the flexform and in the tab "categories".
We only consider the flexform. But already existing category relations will remain.

The behaviour of the categories may be quite different from the behaviour in cal
because of the (incomplete) mapping of the categoryModes and these 2 ways of
setting categories in cal.

Mapping of starttime / endtime
------------------------------

Starttime

*  cal: flexform: view.list.starttime
*  cal: TypoScript: plugin.tx_cal_controller.view.list.event.starttime
*  calendarize:

   *  useRelativeDate=1: settings.overrideStartRelative
   *  useRelativeDate=0: settings.overrideStartdate

This can be defined in cal in 3 places: in Flexform `view.list.starttime`
in tab "TypoScript" in the Flexform or in TypoScript.
Endtime

*  cal: flexform: view.list.endtime
*  cal: TypoScript: plugin.tx_cal_controller.view.list.event.endtime
*  calendarize:

   *  useRelativeDate=1: settings.overrideEndRelative
   *  useRelativeDate=0: settings.overrideEnddate


cal:

.. code-block:: typoscript

   view.list.starttime=2011-04-01
   settings.overrideStartdate = 00:00 1-4-2011


cal configuration
=================

Can be in

* flexform
* TypoScript in flexform
* TypoScript

We currently only consider flexform.


calendarize configuration
=========================

Can be in

* flexform
* configuration record
* TypoScript

Examples
========

"old" cal Flexform

.. code-block:: xml

   <?xml version="1.0" encoding="utf-8" standalone="yes" ?>
   <T3FlexForms>
       <data>
           <sheet index="sDEF">
               <language index="lDEF">
                   <field index="allowedViews">
                       <value index="vDEF">list,search_all,ics~icslist~single_ics,event</value>
                   </field>
                   <field index="calendarName">
                       <value index="vDEF">My Calendar</value>
                   </field>
                   <field index="subscription">
                       <value index="vDEF">0</value>
                   </field>
                   <field index="weekStartDay">
                       <value index="vDEF"></value>
                   </field>
                   <field index="calendarDistance">
                       <value index="vDEF">50</value>
                   </field>
                   <field index="subscribeWithCaptcha">
                       <value index="vDEF">0</value>
                   </field>
               </language>
           </sheet>
           <sheet index="s_Cat">
               <language index="lDEF">
                   <field index="calendarMode">
                       <value index="vDEF">0</value>
                   </field>
                   <field index="calendarSelection">
                       <value index="vDEF"></value>
                   </field>
                   <field index="categoryMode">
                       <value index="vDEF">3</value>
                   </field>
                   <field index="categorySelection">
                       <value index="vDEF">359</value>
                   </field>
               </language>
           </sheet>
           <sheet index="s_Year_View">
               <language index="lDEF">
                   <field index="yearViewPid">
                       <value index="vDEF"></value>
                   </field>
               </language>
           </sheet>
           <sheet index="s_Month_View">
               <language index="lDEF">
                   <field index="monthViewPid">
                       <value index="vDEF"></value>
                   </field>
                   <field index="monthShowListView">
                       <value index="vDEF">0</value>
                   </field>
                   <field index="monthMakeMiniCal">
                       <value index="vDEF">0</value>
                   </field>
               </language>
           </sheet>
           <sheet index="s_Week_View">
               <language index="lDEF">
                   <field index="weekViewPid">
                       <value index="vDEF"></value>
                   </field>
               </language>
           </sheet>
           <sheet index="s_Day_View">
               <language index="lDEF">
                   <field index="dayViewPid">
                       <value index="vDEF"></value>
                   </field>
                   <field index="dayStart">
                       <value index="vDEF">0700</value>
                   </field>
                   <field index="dayEnd">
                       <value index="vDEF">2300</value>
                   </field>
                   <field index="gridLength">
                       <value index="vDEF">15</value>
                   </field>
               </language>
           </sheet>
           <sheet index="s_List_View">
               <language index="lDEF">
                   <field index="listViewPid">
                       <value index="vDEF">53864</value>
                   </field>
                   <field index="starttime">
                       <value index="vDEF">cal:weekstart</value>
                   </field>
                   <field index="endtime">
                       <value index="vDEF">+1 year</value>
                   </field>
                   <field index="maxEvents">
                       <value index="vDEF"></value>
                   </field>
                   <field index="maxRecurringEvents">
                       <value index="vDEF"></value>
                   </field>
                   <field index="usePageBrowser">
                       <value index="vDEF"></value>
                   </field>
                   <field index="recordsPerPage">
                       <value index="vDEF"></value>
                   </field>
                   <field index="pagesCount">
                       <value index="vDEF"></value>
                   </field>
               </language>
           </sheet>
           <sheet index="s_Event_View">
               <language index="lDEF">
                   <field index="eventViewPid">
                       <value index="vDEF">61579</value>
                   </field>
                   <field index="isPreview">
                       <value index="vDEF">1</value>
                   </field>
               </language>
           </sheet>
           <sheet index="s_Ics_View">
               <language index="lDEF">
                   <field index="showIcsLinks">
                       <value index="vDEF">0</value>
                   </field>
               </language>
           </sheet>
           <sheet index="s_Other_View">
               <language index="lDEF">
                   <field index="showSearch">
                       <value index="vDEF">0</value>
                   </field>
                   <field index="showJumps">
                       <value index="vDEF">0</value>
                   </field>
                   <field index="showCalendarSelection">
                       <value index="vDEF">0</value>
                   </field>
                   <field index="showCategorySelection">
                       <value index="vDEF">1</value>
                   </field>
                   <field index="showTomorrowEvents">
                       <value index="vDEF">0</value>
                   </field>
                   <field index="showLogin">
                       <value index="vDEF">0</value>
                   </field>
               </language>
           </sheet>
           <sheet index="s_TS_View">
               <language index="lDEF">
                   <field index="myTS">
                       <value index="vDEF"></value>
                   </field>
               </language>
           </sheet>
       </data>
   </T3FlexForms>

calendarize Flexform

.. code-block:: xml

   <?xml version="1.0" encoding="utf-8" standalone="yes" ?>
   <T3FlexForms>
    <data>
        <sheet index="main">
            <language index="lDEF">
                <field index="settings.pluginConfiguration">
                    <value index="vDEF"></value>
                </field>
                <field index="settings.useRelativeDate">
                    <value index="vDEF">0</value>
                </field>
                <field index="settings.limit">
                    <value index="vDEF"></value>
                </field>
                <field index="settings.hidePagination">
                    <value index="vDEF">0</value>
                </field>
                <field index="settings.overrideStartdate">
                    <value index="vDEF"></value>
                </field>
                <field index="settings.overrideEnddate">
                    <value index="vDEF"></value>
                </field>
                <field index="switchableControllerActions">
                    <value index="vDEF">Calendar-&gt;list;Calendar-&gt;detail</value>
                </field>
                <field index="settings.overrideStartRelative">
                    <value index="vDEF"></value>
                </field>
                <field index="settings.overrideEndRelative">
                    <value index="vDEF"></value>
                </field>
            </language>
        </sheet>
        <sheet index="general">
            <language index="lDEF">
                <field index="settings.configuration">
                    <value index="vDEF">Event</value>
                </field>
                <field index="settings.sortBy">
                    <value index="vDEF">start</value>
                </field>
                <field index="settings.sorting">
                    <value index="vDEF">ASC</value>
                </field>
                <field index="persistence.storagePid">
                    <value index="vDEF"></value>
                </field>
                <field index="persistence.recursive">
                    <value index="vDEF"></value>
                </field>
            </language>
        </sheet>
        <sheet index="pages">
            <language index="lDEF">
                <field index="settings.detailPid">
                    <value index="vDEF"></value>
                </field>
                <field index="settings.listPid">
                    <value index="vDEF"></value>
                </field>
                <field index="settings.yearPid">
                    <value index="vDEF"></value>
                </field>
                <field index="settings.quarterPid">
                    <value index="vDEF"></value>
                </field>
                <field index="settings.monthPid">
                    <value index="vDEF"></value>
                </field>
                <field index="settings.weekPid">
                    <value index="vDEF"></value>
                </field>
                <field index="settings.dayPid">
                    <value index="vDEF"></value>
                </field>
                <field index="settings.bookingPid">
                    <value index="vDEF"></value>
                </field>
            </language>
        </sheet>
    </data>
   </T3FlexForms>
