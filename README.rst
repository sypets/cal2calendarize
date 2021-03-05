
=========================
Calendar Plugin migration
=========================

from cal to calendarize

Known problems
==============

* Only flexform (and tt_content.pages and recursive) is read and written, not TypoScript

   *  e.g. number of entries per page is not set. (Only configurable in TypoScript in calendarize)

*  not possible to fully map the views (switchableControllerActions)

*  cal has more category modes, calendarize has only use categories or no categories

Mapping
=======

We do not use full name of the configuration settings here.

* TS: TypoScript
* flex: Flexform

+ ------------------------+--------------------------------------+-------------------|
| cal                     | calendarize                          | Mapping           |
+=========================+======================================+-------------------+
| flex: allowedViews      | flex: switchableControllerActions    | incomplete        |
+ ------------------------+--------------------------------------+-------------------+
| tt_content.pages        | flex: persistence.storagePid         | 100%              |
| TS constants: pidList   | flex: persistence.storagePid         | constant not used |
+ ------------------------+--------------------------------------+-------------------+
| pids, e.g.              |                                      | 100%              |
| flex: eventViewPid      | flex: detailPid                      |                   |
| ...                     | ...                                  |                   |
+ ------------------------+--------------------------------------+-------------------+
| flex:usePageBrowser     | flex: hidePagination                 | flex yes, TS no   |
+ ------------------------+--------------------------------------+-------------------+


cal configuration
=================

Can be in

* flexform
* TypoScript in flexform
* TypoScript


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