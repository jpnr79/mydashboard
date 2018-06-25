<?php
/*
 -------------------------------------------------------------------------
 MyDashboard plugin for GLPI
 Copyright (C) 2015 by the MyDashboard Development Team.
 -------------------------------------------------------------------------

 LICENSE

 This file is part of MyDashboard.

 MyDashboard is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 MyDashboard is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with MyDashboard. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/**
 * This helper class provides some static functions that are useful for widget class
 */
class PluginMydashboardHelper {

   /**
    * get the delay between two automatic refreshing
    * @return int
    */
   static function getAutomaticRefreshDelay() {
      return self::getPreferenceField('automatic_refresh_delay');
   }

   /**
    * Check if automatic refreshing is enable or not
    * @return boolean, TRUE if automatic refresh is enabled, FALSE otherwise
    */
   static function getAutomaticRefresh() {
      return (self::getPreferenceField('automatic_refresh') == 1) ? true : false;
   }

   /**
    * Get the number of widgets in width in the configuration
    * @return int
    */
   static function getNumberOfWidgetsInWidth() {

      return self::getPreferenceField('nb_widgets_width');
   }

   /**
    * Check if user wants dashboard to replace central interface
    * @return boolean, TRUE if dashboard must replace, FALSE otherwise
    */
   static function getReplaceCentral() {
      return self::getPreferenceField("replace_central");
   }

   /**
    * @return mixed
    */
   static function getDisplayPlugins() {
      return self::getConfigField("display_plugin_widget");
   }

   /**
    * @return mixed
    */
   static function getDisplayMenu() {
      return self::getConfigField("display_menu");
   }

   /**
    * @return mixed
    */
   static function getReplaceCentralConf() {
      return self::getConfigField("replace_central");
   }

   /**
    * @return mixed
    */
   static function getGoogleApiKey() {
      return self::getConfigField("google_api_key");
   }

   /**
    * Get a specific field of the config
    *
    * @param string $fieldname
    *
    * @return mixed
    */
   private static function getConfigField($fieldname) {
      $config = new PluginMydashboardConfig();
      if (!$config->getFromDB(Session::getLoginUserID())) {
         $config->initConfig();
      }
      $config->getFromDB("1");

      return (isset($config->fields[$fieldname])) ? $config->fields[$fieldname] : 0;
   }

   /**
    * Get a specific field of the config
    *
    * @param string $fieldname
    *
    * @return mixed
    */
   private static function getPreferenceField($fieldname) {
      $preference = new PluginMydashboardPreference();
      if (!$preference->getFromDB(Session::getLoginUserID())) {
         $preference->initPreferences(Session::getLoginUserID());
      }
      $preference->getFromDB(Session::getLoginUserID());

      return (isset($preference->fields[$fieldname])) ? $preference->fields[$fieldname] : 0;
   }

   static function getGraphHeader($params) {

      $graph = "<div class='bt-row'>";
      if ($params["export"] == true) {
         $graph .= "<div class='bt-col-md-8 left'>";
      } else {
         $graph .= "<div class='bt-col-md-12 left'>";
      }
      if (count($params["criterias"]) > 0) {
         $graph .= PluginMydashboardHelper::getForm($params["widgetId"], $params["onsubmit"], $params["opt"], $params["criterias"]);
      }
      $graph .= "</div>";
      if ($params["export"] == true) {
         $graph .= "<div class='bt-col-md-2 center'>";
         $name  = $params['name'];
         $graph .= "<button class='btn btn-primary btn-sm' onclick='downloadGraph(\"$name\");'>" . __("Save as PNG", "mydashboard") . "</button>";
         $graph .= "</div>";
      }
      $graph .= "</div>";
      if ($params["canvas"] == true) {
         if ($params["nb"] < 1) {
            $graph .= "<div align='center'><br><br><h3><span class ='maint-color'>";
            $graph .= __("No item found");
            $graph .= "</span></h3></div>";
         }
         $graph .= "<div id=\"chart-container\" class=\"chart-container\">"; // style="position: relative; height:45vh; width:45vw"
         $graph .= "<canvas id=\"$name\"></canvas>";
         $graph .= "</div>";
      }

      return $graph;
   }


   /**
    * @param $table
    * @param $params
    *
    * @return string
    */
   private static function getSpecificEntityRestrict($table, $params) {

      if (isset($params['entities_id']) && $params['entities_id'] == "") {
         $params['entities_id'] = $_SESSION['glpiactive_entity'];
      }
      if (isset($params['entities_id']) && ($params['entities_id'] != -1)) {
         if (isset($params['sons']) && ($params['sons'] != "") && ($params['sons'] != 0)) {
            $entities = " AND `$table`.`entities_id` IN  (" . implode(",", getSonsOf("glpi_entities", $params['entities_id'])) . ") ";
         } else {
            $entities = " AND `$table`.`entities_id` = " . $params['entities_id'] . " ";
         }
      } else {
         if (isset($params['sons']) && ($params['sons'] != "") && ($params['sons'] != 0)) {
            $entities = " AND `$table`.`entities_id` IN  (" . implode(",", getSonsOf("glpi_entities", $_SESSION['glpiactive_entity'])) . ") ";
         } else {
            $entities = " AND `$table`.`entities_id` = " . $_SESSION['glpiactive_entity'] . " ";
         }
      }
      return $entities;
   }

   static function manageCriterias($params) {

      $criterias = $params['criterias'];

      if (Session::isMultiEntitiesMode()) {
         if (in_array("entities_id", $criterias)) {
            if (isset($params['preferences']['prefered_entity'])
                && $params['preferences']['prefered_entity'] > 0
                && count($params['opt']) < 1) {
               $opt['entities_id'] = $params['preferences']['prefered_entity'];
            } elseif (isset($params['opt']['entities_id'])
                      && $params['opt']['entities_id'] > 0) {
               $opt['entities_id'] = $params['opt']['entities_id'];
            } else {
               $opt['entities_id'] = $_SESSION['glpiactive_entity'];
            }
         }
         $crit['crit']['sons'] = 0;
         if (in_array("is_recursive", $criterias)) {
            if (!isset($params['opt']['sons'])) {
               $opt['sons'] = $_SESSION['glpiactive_entity_recursive'];
            } else {
               $opt['sons'] = $params['opt']['sons'];
            }
            $crit['crit']['sons'] = $opt['sons'];
         }

         if (isset($opt)) {
            $crit['crit']['entities_id'] = self::getSpecificEntityRestrict("glpi_tickets", $opt);
            $crit['crit']['entity']      = $opt['entities_id'];
         }
      } else {
         $crit['crit']['entities_id'] = null;
         $crit['crit']['entity']      = null;
         $crit['crit']['sons']      = null;
      }
      $crit['crit']['groups_id'] = 0;
      if (in_array("groups_id", $criterias)) {
         if (isset($params['preferences']['prefered_group'])
             && $params['preferences']['prefered_group'] > 0
             && !isset($params['opt']['groups_id'])) {
            $opt['groups_id']          = $params['preferences']['prefered_group'];
            $crit['crit']['groups_id'] = $params['preferences']['prefered_group'];
         } else if (isset($params['opt']['groups_id'])
                    && $params['opt']['groups_id'] > 0) {
            $opt['groups_id']          = $params['opt']['groups_id'];
            $crit['crit']['groups_id'] = $params['opt']['groups_id'];
         }
      }
      $opt['type']          = 0;
      $crit['crit']['type'] = "AND 1 = 1";
      if (in_array("type", $criterias)) {
         if (isset($params['opt']["type"])
             && $params['opt']["type"] > 0) {
            $opt['type']          = $params['opt']['type'];
            $crit['crit']['type'] = " AND `glpi_tickets`.`type` = '" . $params['opt']["type"] . "' ";
         }
      }

      $year  = intval(strftime("%Y"));
      $month = intval(strftime("%m") - 1);

      if (in_array("month", $criterias)) {
         if ($month > 0) {
            $year        = strftime("%Y");
            $opt["year"] = $year;
         } else {
            $month = 12;
         }
         if (isset($params['opt']["month"])
             && $params['opt']["month"] > 0) {
            $month        = $params['opt']["month"];
            $opt['month'] = $params['opt']['month'];
         } else {
            $opt["month"] = $month;
         }
      }

      if (in_array("year", $criterias)) {
         if (isset($params['opt']["year"])
             && $params['opt']["year"] > 0) {
            $year        = $params['opt']["year"];
            $opt['year'] = $params['opt']['year'];
         } else {
            $opt["year"] = $year;
         }
      }

      $nbdays                    = date("t", mktime(0, 0, 0, $month, 1, $year));
      $crit['crit']['date']      = "(`glpi_tickets`.`date` >= '$year-$month-01 00:00:01' 
                              AND `glpi_tickets`.`date` <= ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY) )";
      $crit['crit']['closedate'] = "(`glpi_tickets`.`closedate` >= '$year-$month-01 00:00:01' 
                              AND `glpi_tickets`.`closedate` <= ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY) )";

      $opt["users_id"] = $_SESSION['glpiID'];
      if (in_array("users_id", $criterias)) {
         if (isset($params['opt']['users_id']) && Session::haveRight("plugin_activity_all_users", 1)) {
            $opt["users_id"]          = $params['opt']['users_id'];
            $crit['crit']['users_id'] = $params['opt']['users_id'];
         }
      }

      $default = array(CommonITILObject::INCOMING,
                       CommonITILObject::ASSIGNED,
                       CommonITILObject::PLANNED,
                       CommonITILObject::WAITING);
      $crit['crit']['status'] = $default;
      $opt['status']          = $default;
      if (in_array("status", $criterias)) {
         $status = [];

         if (isset($params['opt']["status_1"])
                         && $params['opt']["status_1"] > 0) {
            $status[] = CommonITILObject::INCOMING;
         }
         if (isset($params['opt']["status_2"])
             && $params['opt']["status_2"] > 0) {
            $status[] = CommonITILObject::ASSIGNED;
         }
         if (isset($params['opt']["status_3"])
             && $params['opt']["status_3"] > 0) {
            $status[] = CommonITILObject::PLANNED;
         }
         if (isset($params['opt']["status_4"])
             && $params['opt']["status_4"] > 0) {
            $status[] = CommonITILObject::WAITING;
         }
         if (isset($params['opt']["status_5"])
             && $params['opt']["status_5"] > 0) {
            $status[] = CommonITILObject::SOLVED;
         }
         if (isset($params['opt']["status_6"])
             && $params['opt']["status_6"] > 0) {
            $status[] = CommonITILObject::CLOSED;
         }

         if (count($status) > 0){
            $opt['status']          = $status;
            $crit['crit']['status'] = $status;
         }
      }
      if (in_array("begin", $criterias)) {
         if (isset($params['opt']['begin'])
             && $params['opt']["begin"] > 0) {
            $opt["begin"]          = $params['opt']['begin'];
            $crit['crit']['begin'] = $params['opt']['begin'];
         } else {
            $opt["begin"] = date("Y-m-d");
         }
      }
      if (in_array("end", $criterias)) {
         if (isset($params['opt']['end'])
             && $params['opt']["end"] > 0) {
            $opt["end"]          = $params['opt']['end'];
            $crit['crit']['end'] = $params['opt']['end'];
         } else {
            $opt["end"] = date("Y-m-d");
         }
      }
      $crit['opt'] = $opt;

      return $crit;
   }

   /**
    * Get a form header, this form header permit to update data of the widget
    * with parameters of this form
    *
    * @param int  $widgetId
    * @param      $gsid
    * @param bool $onsubmit
    *
    * @return string , like '<form id=...>'
    */
   static function getFormHeader($widgetId, $gsid, $onsubmit = false) {
      $formId = uniqid('form');
      $rand   = mt_rand();
      $form   = "<script type='text/javascript'>
               $(document).ready(function () {
                   $('#plugin_mydashboard_add_criteria$rand').on('click', function (e) {
                       $('#plugin_mydashboard_see_criteria$rand').width(300);
                       $('#plugin_mydashboard_see_criteria$rand').toggle();
                   });
                 });
                </script>";

      $form .= "<div id='plugin_mydashboard_add_criteria$rand'><i class=\"fa fa-bars fa-2x\"></i></div>";
      $form .= "<div class='plugin_mydashboard_menuWidget' id='plugin_mydashboard_see_criteria$rand'>";
      if ($onsubmit) {
         $form .= "<form id='" . $formId . "' action='' "
                  . "onsubmit=\"refreshWidgetByForm('" . $widgetId . "','" . $gsid . "','" . $formId . "'); return false;\">";
      } else {
         $form .= "<form id='" . $formId . "' action='' onsubmit='return false;' ";
         $form .= "onchange=\"refreshWidgetByForm('" . $widgetId . "','" . $gsid . "','" . $formId . "');\">";
      }
      return $form;
   }

   static function getForm($widgetId, $onsubmit = false, $opt, $criterias) {

      $gsid = PluginMydashboardWidget::getGsID($widgetId);

      $form = self::getFormHeader($widgetId, $gsid, $onsubmit);

      $count = count($criterias);
      if (Session::isMultiEntitiesMode()) {
         if (in_array("entities_id", $criterias)) {
            $form   .= "<span class='md-widgetcrit'>";
            $params = ['name'                => 'entities_id',
                       'display'             => false,
                       'width'               => '100px',
                       'value'               => isset($opt['entities_id']) ? $opt['entities_id'] : $_SESSION['glpiactive_entity'],
                       'display_emptychoice' => true

            ];
            $form   .= __('Entity');
            $form   .= "&nbsp;";
            $form   .= Entity::dropdown($params);
            $form   .= "</span>";
            if ($count > 1) {
               $form .= "</br></br>";
            }
         }
         if (in_array("is_recursive", $criterias)) {
            $form    .= "<span class='md-widgetcrit'>";
            $form    .= __('Recursive') . "&nbsp;";
            $paramsy = [
               'display' => false];
            $form    .= Dropdown::showYesNo('sons', $opt['sons'], -1, $paramsy);
            $form    .= "</span>";
            if ($count > 1) {
               $form .= "</br></br>";
            }

         }
      }
      if (in_array("groups_id", $criterias)) {
         $gparams = ['name'      => 'groups_id',
                     'display'   => false,
                     'value'     => isset($opt['groups_id']) ? $opt['groups_id'] : 0,
                     'entity'    => $_SESSION['glpiactiveentities'],
                     'condition' => '`is_assign`'
         ];
         $form    .= "<span class='md-widgetcrit'>";
         $form    .= __('Group');
         $form    .= "&nbsp;";
         $form    .= Group::dropdown($gparams);
         $form    .= "</span>";
         if ($count > 1) {
            $form .= "</br></br>";
         }
      }
      if (in_array("type", $criterias)) {
         $form .= "<span class='md-widgetcrit'>";
         $type = 0;
         if (isset($opt["type"])
             && $opt["type"] > 0) {
            $type = $opt["type"];
         }
         $form .= __('Type');
         $form .= "&nbsp;";
         $form .= Ticket::dropdownType('type', ['value'               => $type,
                                                'display'             => false,
                                                'display_emptychoice' => true]);
         $form .= "</span>";
         if ($count > 1) {
            $form .= "</br></br>";
         }
      }
      if (in_array("year", $criterias)) {
         $form           .= "<span class='md-widgetcrit'>";
         $annee_courante = strftime("%Y");
         if (isset($opt["year"])
             && $opt["year"] > 0) {
            $annee_courante = $opt["year"];
         }
         $form .= __('Year', 'mydashboard');
         $form .= "&nbsp;";
         $form .= self::YearDropdown($annee_courante);
         $form .= "</span>";
         if ($count > 1) {
            $form .= "</br></br>";
         }
      }
      if (in_array("month", $criterias)) {
         $form .= __('Month', 'mydashboard');
         $form .= "&nbsp;";
         $form .= self::monthDropdown("month", (isset($opt['month']) ? $opt['month'] : 0));
         $form .= "&nbsp;";
         if ($count > 1) {
            $form .= "</br></br>";
         }
      }
      if (in_array("users_id", $criterias)) {
         $params = array('name'     => "users_id",
                         'value'    => $opt['users_id'],
                         'right'    => "interface",
                         'comments' => 1,
                         'entity'   => $_SESSION["glpiactiveentities"],
                         'width'    => '50%',
                         'display'  => false
         );
         $form   .= __('User');
         $form   .= "&nbsp;";
         $form   .= User::dropdown($params);
         if ($count > 1) {
            $form .= "</br></br>";
         }
      }

      if (in_array("status", $criterias)) {
         $form    .= _n('Status', 'Statuses', 2) . "&nbsp;";
         $default = array(CommonITILObject::INCOMING,
                          CommonITILObject::ASSIGNED,
                          CommonITILObject::PLANNED,
                          CommonITILObject::WAITING);

         $i = 1;
         foreach (Ticket::getAllStatusArray() as $svalue => $sname) {
            $form .= '<input type="hidden" name="status_' . $svalue . '" value="0" /> ';
            $form .= '<input type="checkbox" name="status_' . $svalue . '" value="1"';

            if (in_array($svalue, $opt['status'])) {
               $form .= ' checked="checked"';
            }
            if (count($opt['status']) < 1 && in_array($svalue, $default)){
               $form .= ' checked="checked"';
            }

            $form .= ' /> ';
            $form .= $sname;
            if ($i % 2 == 0) {
               $form .= "<br>";
            } else {
               $form .= "&nbsp;";
            }
            $i++;
         }
         if ($count > 1) {
            $form .= "</br></br>";
         }
      }
      if (in_array("begin", $criterias)) {
         $form .= __('Start');
         $form .= "&nbsp;";
         $form .= Html::showDateField("begin", array('value' => $opt['begin'], 'maybeempty' => false, 'display' => false));
         $form .= "&nbsp;";
         if ($count > 1) {
            $form .= "</br></br>";
         }
      }
      if (in_array("end", $criterias)) {
         $form .= __('End');
         $form .= "&nbsp;";
         $form .= Html::showDateField("end", array('value' => $opt['end'], 'maybeempty' => false, 'display' => false));
         $form .= "&nbsp;";
         if ($count > 1) {
            $form .= "</br></br>";
         }
      }
      $form .= self::getFormFooter();

      return $form;
   }

   static function getFormFooter() {

      $form = "</form>";
      $form .= "</div>";

      return $form;
   }

   /**
    * Get a link to be used as a widget title
    *
    * @param        $pathfromrootdoc
    * @param        $text
    * @param string $title
    *
    * @return string
    */
   static function getATag($pathfromrootdoc, $text, $title = "") {
      global $CFG_GLPI;
      $title = ($title !== "") ? "title=$title" : "";
      return "<a href='" . $CFG_GLPI['root_doc'] . "/" . $pathfromrootdoc . "' $title target='_blank'>" . $text . "</a>";
   }

   /**
    * Return an unique id for a widget,
    * (can only be used on temporary plugins,
    *  because the id must represent the widget
    *  and every once this function is called it generates a new id)
    *
    * @return string
    */
   static function getUniqueWidgetId() {
      return uniqid("id_");
   }

   /**
    * Extract the content of the HTML script tag in an array 2D (line, column),
    * Useful for datatables
    *
    * @param array 2D $arrayToEval
    *
    * @return array of string (each string is a script line)
    */
   static function extractScriptsFromArray($arrayToEval) {
      $scripts = [];
      if (is_array($arrayToEval)) {
         if (!is_array($arrayToEval)) {
            return $scripts;
         }
         foreach ($arrayToEval as $array) {
            if (!is_array($array)) {
               break;
            }
            foreach ($array as $arrayLine) {
               $scripts = array_merge($scripts, self::extractScriptsFromString($arrayLine));
            }
         }
      }
      return $scripts;
   }

   /**
    * Get an array of scripts found in a string
    *
    * @param string $stringToEval , a HTML string with potentially script tags
    *
    * @return array of string
    */
   static function extractScriptsFromString($stringToEval) {
      $scripts = [];
      if (gettype($stringToEval) == "string") {
         $stringToEval = str_replace(["'", "//<![CDATA[", "//]]>"], ['"', "", ""], $stringToEval);
         //             $stringToEval = preg_replace('/\s+/', ' ', $stringToEval);

         if (preg_match_all("/<script[^>]*>([\s\S]+?)<\/script>/i", $stringToEval, $matches)) {
            foreach ($matches[1] as $match) {
               //                     $match = preg_replace('/(\/\/[[:alnum:]_ ]+)/', '', $match);
               //                     $match = preg_replace('#^\s*//.+$#m', "", $match);
               $scripts[] = $match;
            }
         }
      }
      return $scripts;
   }

   /**
    * Get a string without scripts from stringToEval,
    * it strips script tags
    *
    * @param string $stringToEval , the string that you want without scripts
    *
    * @return string with no scripts
    */
   static function removeScriptsFromString($stringToEval) {
      //      $stringWOScripts = "";
      //      if (gettype($stringToEval) == "string") {
      //         $stringWOScripts = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $stringToEval);
      //      }
      //      return $stringWOScripts;
      return $stringToEval;
   }


   /**
    * This method permit to avoid problems with function in JSon datas (example : tickFormatter)<br>
    * It's used to clean Json data needed to fill a widget<br>
    * Things like "function_to_call" => "function(){...}"
    * are replaced to look like "function_to_call" => function(){}<br>
    * This replacement cause the <b>return value</b> not being a valid Json object (<b>don't call json_decode on
    * it</b>), but it's necessary because some jquery plugins need functions and not string of function
    *
    * @param type $datas , a formatted array of datas
    * @param type $options , a formatted array of options
    *
    * @return a string formatted in JSon (most of the time, because in real JSon you can't have function)
    */
   static function safeJsonData($datas, $options) {
      $value_arr    = [];
      $replace_keys = [];
      foreach ($options as & $option) {
         if (is_array($option)) {
            foreach ($option as $key => & $value) {
               // Look for values starting with 'function('

               if (is_string($value) && strpos($value, 'function(') === 0) {
                  // Store function string.
                  $value_arr[] = $value;
                  // Replace function string in $option with a 'unique' special key.
                  $value = '%' . $key . '%';
                  // Later on, we'll look for the value, and replace it.
                  $replace_keys[] = '"' . $value . '"';
               }
            }
         }
      }

      $json = str_replace($replace_keys,
                          $value_arr,
                          json_encode([
                                         'data'    => $datas,
                                         'options' => $options
                                      ]));

      return $json;
   }

   /**
    * Cleans and encodes in json an array
    * Things like "function_to_call" => "function(){...}"
    * are replaced to look like "function_to_call" => function(){}
    * This replacement cause the return not being a valid Json object (don't call json_decode on it),
    * but it's necessary because some jquery plugins need functions and not string of function
    *
    * @param mixed $array , the array that needs to be cleaned and encoded in json
    *
    * @return string a json encoded array
    */
   static function safeJson($array) {
      $value_arr    = [];
      $replace_keys = [];
      foreach ($array as $key => & $value) {

         if (is_string($value) && strpos($value, 'function(') === 0) {
            // Store function string.
            $value_arr[] = $value;
            // Replace function string in $option with a 'unique' special key.
            $value = '%' . $key . '%';
            // Later on, we'll look for the value, and replace it.
            $replace_keys[] = '"' . $value . '"';
         }

      }

      $json = str_replace($replace_keys, $value_arr, json_encode($array));

      return $json;
   }

   /**
    * @param $widgettype
    * @param $query
    *
    * @return PluginMydashboardDatatable|PluginMydashboardHBarChart|PluginMydashboardHtml|PluginMydashboardLineChart|PluginMydashboardPieChart|PluginMydashboardVBarChart
    */
   static function getWidgetsFromDBQuery($widgettype, $query/*$widgettype,$table,$fields,$condition,$groupby,$orderby*/) {
      global $DB;

      if (stripos(trim($query), "SELECT") === 0) {

         $result = $DB->query($query);
         $tab    = [];
         if ($result) {
            while ($row = $DB->fetch_assoc($result)) {
               $tab[] = $row;
            }
            $linechart = false;
            $chart     = false;
            switch ($widgettype) {
               case 'datatable':
               case 'table' :
                  $widget = new PluginMydashboardDatatable();
                  break;
               case 'hbarchart':
                  $chart  = true;
                  $widget = new PluginMydashboardHBarChart();
                  break;
               case 'vbarchart':
                  $chart  = true;
                  $widget = new PluginMydashboardVBarChart();
                  break;
               case 'piechart':
                  $chart  = true;
                  $widget = new PluginMydashboardPieChart();
                  break;
               case 'linechart':
                  $linechart = true;
                  $widget    = new PluginMydashboardLineChart();
                  break;
            }
            //            $widget = new PluginMydashboardHBarChart();
            //        $widget->setTabNames(array('Category','Count'));
            if ($chart) {
               $newtab = [];
               foreach ($tab as $key => $line) {
                  $line             = array_values($line);
                  $newtab[$line[0]] = $line[1];
                  unset($tab[$key]);
               }
               $tab = $newtab;
            } else if ($linechart) {
               //TODO format for linechart
            } else {
               //$widget->setTabNames(array('Category','Count'));
            }
            $widget->setTabDatas($tab);

         }
      } else {
         $widget = new PluginMydashboardHtml();
         $widget->debugError(__('Not a valid SQL SELECT query', 'mydashboard'));
         $widget->debugNotice($query);
      }

      return $widget;
   }

   /*
    * @Create an HTML drop down menu
    *
    * @param string $name The element name and ID
    *
    * @param int $selected The month to be selected
    *
    * @return string
    *
    */
   static function YearDropdown($selected = null) {

      $year = date("Y") - 3;
      for ($i = 0; $i <= 3; $i++) {
         $elements[$year] = $year;

         $year++;
      }
      $opt = ['value'   => $selected,
              'display' => false];

      return Dropdown::showFromArray("year", $elements, $opt);
   }

   /*
    *
    * @Create an HTML drop down menu
    *
    * @param string $name The element name and ID
    *
    * @param int $selected The month to be selected
    *
    * @return string
    *
    */
   static function monthDropdown($name = "month", $selected = null) {

      $monthsarray = Toolbox::getMonthsOfYearArray();

      $opt = ['value'   => $selected,
              'display' => false];

      return Dropdown::showFromArray($name, $monthsarray, $opt);
   }
}
