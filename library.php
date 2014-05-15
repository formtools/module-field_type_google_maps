<?php

function field_type_google_maps__install($module_id)
{
  global $g_table_prefix;

  // check it's not already installed (i.e. check for the unique field type identifier)
  $field_type_info = ft_get_field_type_by_identifier("google_maps_field");
  if (!empty($field_type_info))
  {
  	return array(false, $LANG["notify_module_already_installed"]);
  }

  // find the LAST field type group. Most installations won't have the Custom Fields module installed so
  // the last group will always be "Special Fields". For installations that DO, and that it's been customized,
  // the user can always move this new field type to whatever group they want. Plus, this module will be
  // installed by default, so it's almost totally moot
  $query = mysql_query("
    SELECT group_id
    FROM   {$g_table_prefix}list_groups
    WHERE  group_type = 'field_types'
    ORDER BY list_order DESC
    LIMIT 1
  ");
  $result = mysql_fetch_assoc($query);
  $group_id = $result["group_id"]; // assumption: there's at least one field type group

  // now find out how many field types there are in the group so we can add the row with the correct list order
  $count_query = mysql_query("SELECT count(*) as c FROM {$g_table_prefix}field_types WHERE group_id = $group_id");
  $count_result = mysql_fetch_assoc($count_query);
  $next_list_order = $count_result["c"] + 1;

  mysql_query("
    INSERT INTO {$g_table_prefix}field_types (is_editable, non_editable_info, managed_by_module_id,
      field_type_name, field_type_identifier, group_id, is_file_field, is_date_field, raw_field_type_map,
      raw_field_type_map_multi_select_id, list_order, compatible_field_sizes, view_field_smarty_markup,
      edit_field_smarty_markup, php_processing, resources_css, resources_js)
    VALUES ('no', 'This module may only be edited via the Google Maps Field module.', $module_id, 'Google Maps',
      'google_maps_field', $group_id, 'no', 'no', '', NULL, $next_list_order, 'large',
      '{strip}{assign var=address value=\"\"}\r\n{assign var=coords value=\"\"}\r\n{if \$VALUE}\r\n  {assign var=parts value=\"|\"|explode:\$VALUE}\r\n  {assign var=address value=\$parts[0]}\r\n  {assign var=coords value=\$parts[1]}\r\n\r\n  {if \$view_export == \"lat_lng\"}\r\n    {\$coords}\r\n  {else}\r\n    {\$address}\r\n  {/if}\r\n{/if}{/strip}\r\n\r\n', '{assign var=address value=\"\"}\r\n{assign var=coordinates value=\"\"}\r\n{assign var=zoom_level value=\"\"}\r\n{if \$VALUE}\r\n  {assign var=parts value=\"|\"|explode:\$VALUE}\r\n  {assign var=address value=\$parts[0]}\r\n  {assign var=coords value=\$parts[1]}\r\n  {assign var=zoom_level value=\$parts[2]}\r\n{/if}\r\n\r\n<div class=\"cf_gmf_section\" id=\"cf_gmf_{\$NAME}\">\r\n  <input type=\"hidden\" class=\"cf_gmf_data\" value=\"{\$VALUE|escape}\" />\r\n  <table cellspacing=\"0\" cellpadding=\"0\" width=\"100%\">\r\n  <tr>\r\n    <td><input type=\"text\" name=\"{\$NAME}\" class=\"cf_gmf_address\" \r\n      placeholder=\"Enter Address\" value=\"{\$address|escape}\" /></td>\r\n    <td width=\"60\"><input type=\"button\" class=\"cf_gmf_update\" value=\"{\$LANG.word_update}\" /></td>\r\n  </tr>\r\n  </table>\r\n\r\n  <div id=\"cf_gmf_{\$NAME}_map\" class=\"cf_gmf {\$map_size}\"></div>\r\n  <input type=\"hidden\" name=\"{\$NAME}_coords\" value=\"{\$coords}\" />\r\n  <input type=\"hidden\" name=\"{\$NAME}_zoom\" value=\"{\$zoom_level}\" />\r\n \r\n  {if \$show_coordinates == \"yes\"}\r\n    <div class=\"medium_grey cf_gmf_coords_str\">{\$coords|default:\"&#8212;\"}</div>\r\n  {/if}\r\n  {if \$comments}\r\n    <div class=\"cf_field_comments\">{\$comments}</div>\r\n  {/if}\r\n</div>\r\n\r\n', '\$field_name = \$vars[\"field_info\"][\"field_name\"];\r\n\r\n\$value = \"\";\r\nif (!empty(\$vars[\"data\"][\$field_name])) {\r\n  \$address = \$vars[\"data\"][\$field_name];\r\n  \$coords  = \$vars[\"data\"][\"{\$field_name}_coords\"];\r\n  \$zoom    = \$vars[\"data\"][\"{\$field_name}_zoom\"];\r\n  \$value   = \"\$address|\$coords|\$zoom\";\r\n}\r\n', '.cf_gmf_address {\r\n  width: 98%;\r\n}\r\n.cf_gmf_tiny {\r\n  height: 160px; \r\n}\r\n.cf_gmf_small {\r\n  height: 250px; \r\n}\r\n.cf_gmf_medium {\r\n  height: 350px; \r\n}\r\n.cf_gmf_large {\r\n  height: 590px; \r\n}\r\n',
      '\$(function() {\r\n  var maps = {}; \r\n  var defaults = {\r\n    zoom: 3, \r\n    center: new google.maps.LatLng(42.258881, -100.195313),\r\n    mapTypeId: google.maps.MapTypeId.ROADMAP,\r\n    streetViewControl: false,\r\n    mapTypeControl: false\r\n  }\r\n      \r\n  // load any maps in the page, defaulted to whatever address was saved\r\n  \$(\".cf_gmf_section\").each(function() {\r\n    var gmf_id = \$(this).attr(\"id\");\r\n    var field_name = \$(this).find(\".cf_gmf_address\").attr(\"name\");\r\n    var data = \$(this).find(\".cf_gmf_data\").val();\r\n    var opts = defaults;\r\n    if (data != \"\") {\r\n      var parts = data.split(\"|\"); \r\n      var lat_lng = parts[1].split(\", \");\r\n      opts.zoom = parseInt(parts[2], 10);\r\n      opts.center = new google.maps.LatLng(parseFloat(lat_lng[0]), parseFloat(lat_lng[1]));\r\n    }\r\n    maps[gmf_id] = new google.maps.Map(\$(this).find(\".cf_gmf\")[0], opts);\r\n\r\n    google.maps.event.addListener(maps[gmf_id], ''zoom_changed'', function() {\r\n      \$(\"#\" + gmf_id).find(\"[name=\" + field_name + \"_zoom]\").val(maps[gmf_id].getZoom());\r\n    });\r\n  });\r\n\r\n  // out event handlers\r\n  \$(\".cf_gmf_update\").bind(\"click\", update_map);\r\n\r\n  function update_map(e) {\r\n    var gmf_div = \$(e.target).closest(\".cf_gmf_section\");\r\n    var field_name = gmf_div.find(\".cf_gmf_address\").attr(\"name\");\r\n    var map     = maps[gmf_div.attr(\"id\")];\r\n    var address = gmf_div.find(\".cf_gmf_address\").val();\r\n    var geocoder = new google.maps.Geocoder();\r\n    geocoder.geocode({ ''address'': address }, function(results, status) {\r\n      if (status == google.maps.GeocoderStatus.OK) {\r\n        var loc = results[0].geometry.location;\r\n        map.setCenter(loc);\r\n        var coords = loc.lat() + \", \" + loc.lng();\r\n        \$(gmf_div).find(\"[name=\" + field_name + \"_coords]\").val(coords);\r\n        \$(gmf_div).find(\"[name=\" + field_name + \"_zoom]\").val(map.getZoom());\r\n        \$(\".cf_gmf_coords_str\").html(coords);\r\n      }\r\n    });\r\n  }\r\n});')
  ");
  $field_type_id = mysql_insert_id();

  // map size setting
  mysql_query("INSERT INTO {$g_table_prefix}field_type_settings (field_type_id, field_label, field_setting_identifier, field_type, field_orientation, default_value_type, default_value, list_order) VALUES ($field_type_id, 'Map Size', 'map_size', 'select', 'na', 'static', 'cf_gmf_small', 1)");
  $setting_id = mysql_insert_id();
  mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options (setting_id, option_text, option_value, option_order, is_new_sort_group) VALUES ($setting_id, 'Tiny', 'cf_gmf_tiny', 1, 'yes')");
  mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options (setting_id, option_text, option_value, option_order, is_new_sort_group) VALUES ($setting_id, 'Small', 'cf_gmf_small', 2, 'yes')");
  mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options (setting_id, option_text, option_value, option_order, is_new_sort_group) VALUES ($setting_id, 'Medium', 'cf_gmf_medium', 3, 'yes')");
  mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options (setting_id, option_text, option_value, option_order, is_new_sort_group) VALUES ($setting_id, 'Large', 'cf_gmf_large', 4, 'yes')");

  // show Lat/Lng when editing setting
  mysql_query("INSERT INTO {$g_table_prefix}field_type_settings (field_type_id, field_label, field_setting_identifier, field_type, field_orientation, default_value_type, default_value, list_order) VALUES ($field_type_id, 'Show Lat/Lng when editing', 'show_coordinates', 'radios', 'horizontal', 'static', 'no', 2)");
  $setting_id = mysql_insert_id();
  mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options (setting_id, option_text, option_value, option_order, is_new_sort_group) VALUES ($setting_id, 'Yes', 'yes', 1, 'yes')");
  mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options (setting_id, option_text, option_value, option_order, is_new_sort_group) VALUES ($setting_id, 'No', 'no', 2, 'yes')");

  // View / Export
  mysql_query("INSERT INTO {$g_table_prefix}field_type_settings (field_type_id, field_label, field_setting_identifier, field_type, field_orientation, default_value_type, default_value, list_order) VALUES ($field_type_id, 'View / Export', 'view_export', 'radios', 'horizontal', 'static', 'address', 3)");
  $setting_id = mysql_insert_id();
  mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options (setting_id, option_text, option_value, option_order, is_new_sort_group) VALUES ($setting_id, 'as Lat/Lng', 'lat_lng', 1, 'yes')");
  mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options (setting_id, option_text, option_value, option_order, is_new_sort_group) VALUES ($setting_id, 'as address', 'address', 2, 'yes')");

  // comments
  mysql_query("INSERT INTO {$g_table_prefix}field_type_settings (field_type_id, field_label, field_setting_identifier, field_type, field_orientation, default_value_type, default_value, list_order) VALUES ($field_type_id, 'Field Comments', 'comments', 'textarea', 'na', 'static', '', 4)");

  return array(true, "");
}


function field_type_google_maps__uninstall($module_id)
{
  global $g_table_prefix;

  $field_type_info = ft_get_field_type_by_identifier("google_maps_field");

  if (!empty($field_type_info))
  {
    $field_type_id = $field_type_info["field_type_id"];
    mysql_query("DELETE FROM {$g_table_prefix}field_types WHERE field_type_id = $field_type_id");
    mysql_query("DELETE FROM {$g_table_prefix}field_type_settings WHERE field_type_id = $field_type_id");

    // now for some field clean-up. Delete all settings, setting options and reset any Google Map fields to input fields
    $setting_ids = array();
    foreach ($field_type_info["settings"] as $setting_info)
      $setting_ids[] = $setting_info["setting_id"];

    $setting_id_str = implode(",", $setting_ids);
    mysql_query("DELETE FROM {$g_table_prefix}field_type_setting_options WHERE setting_id IN ($setting_id_str)");
    mysql_query("DELETE FROM {$g_table_prefix}field_setting_options WHERE setting_id IN ($setting_id_str)");
    mysql_query("DELETE FROM {$g_table_prefix}field_settings WHERE setting_id IN ($setting_id_str)");

    $input_field_type_info = ft_get_field_type_by_identifier("textbox");
    $input_field_type_id = $input_field_type_info["field_type_id"];
    mysql_query("UPDATE {$g_table_prefix}form_fields SET field_type_id = $input_field_type_id WHERE field_type_id = $field_type_id");
  }

  return array(true, "");
}

