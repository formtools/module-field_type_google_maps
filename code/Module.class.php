<?php


namespace FormTools\Modules\FieldTypeGoogleMaps;

use FormTools\Core;
use FormTools\FieldTypes;
use FormTools\Hooks;
use FormTools\Module as FormToolsModule;
use FormTools\Modules;
use PDO;


class Module extends FormToolsModule
{
    protected $moduleName = "Google Maps Field";
    protected $moduleDesc = "This module adds the option to add a Google Maps field to your forms for visualizing an address on a map.";
    protected $author = "Ben Keen";
    protected $authorEmail = "ben.keen@gmail.com";
    protected $authorLink = "http://formtools.org";
    protected $version = "2.0.0";
    protected $date = "2017-09-30";
    protected $originLanguage = "en_us";

    protected $nav = array(
        "module_name" => array("index.php", false)
    );

    public function install($module_id)
    {
        $db = Core::$db;
        $LANG = Core::$L;

        // check it's not already installed (i.e. check for the unique field type identifier)
        $field_type_info = FieldTypes::getFieldTypeByIdentifier("google_maps_field");
        if (!empty($field_type_info)) {
            return array(false, $LANG["notify_module_already_installed"]);
        }

        // find the LAST field type group. Most installations won't have the Custom Fields module installed so
        // the last group will always be "Special Fields". For installations that DO, and that it's been customized,
        // the user can always move this new field type to whatever group they want. Plus, this module will be
        // installed by default, so it's almost totally moot
        $db->query("
            SELECT group_id
            FROM   {PREFIX}list_groups
            WHERE  group_type = 'field_types'
            ORDER BY list_order DESC
            LIMIT 1
        ");
        $db->execute();
        $group_id = $db->fetch(PDO::FETCH_COLUMN);

        // now find out how many field types there are in the group so we can add the row with the correct list order
        $db->query("SELECT count(*) as c FROM {PREFIX}field_types WHERE group_id = :group_id");
        $db->bind("group_id", $group_id);
        $db->execute();

        $count = $db->fetch(PDO::FETCH_COLUMN);
        $next_list_order = $count + 1;

        $resources_js =<<< END
window.googleMapsInit = function () {
  var maps = {};
  var defaultMapSettings = {
    zoom: 3,
    center: new google.maps.LatLng(42.258881, -100.195313),
    mapTypeId: google.maps.MapTypeId.ROADMAP,
    streetViewControl: false,
    mapTypeControl: false
  };

  var initMap = function () {
    var gmf_id = $(this).attr("id");
    var address_field = $(this).find(".cf_gmf_address");
    var field_name = address_field.attr("name");
    var data = $(this).find(".cf_gmf_data").val();
    var opts = $.extend(true, {}, defaultMapSettings);

    if (data) {
      var parts = data.split("|");
      if (parts.length == 3 && parts[1].length != 0) {
        var lat_lng = parts[1].split(", ");
        opts.zoom = parseInt(parts[2], 10);
        opts.center = new google.maps.LatLng(parseFloat(lat_lng[0]), parseFloat(lat_lng[1]));
      }
    }

    maps[gmf_id] = {
      map: new google.maps.Map($(this).find(".cf_gmf")[0], opts)
    };

    if (address_field.val() != "") {
      maps[gmf_id].marker = new google.maps.Marker({ position: opts.center, map: maps[gmf_id].map });
    } else {
      maps[gmf_id].marker = new google.maps.Marker();
    }

    google.maps.event.addListener(maps[gmf_id].map, 'zoom_changed', function (e) {
      $("#" + gmf_id).find("[name=" + field_name + "_zoom]").val(maps[gmf_id].map.getZoom());
    });
  };

  // googleMapsInit may fire before or after DOM ready
  $(function () {
    $(".cf_gmf_section").each(initMap);

    $(".cf_gmf_address").bind("keydown", function (e) {
      if (e.keyCode === 13) {
        $(e.target).closest(".cf_gmf_section").find(".cf_gmf_update").trigger("click");
        return false;
      }
    });

    $(".cf_gmf_update").bind("click", update_map);

    function update_map (e) {
      var gmf_div = $(e.target).closest(".cf_gmf_section");
      var field_name = gmf_div.find(".cf_gmf_address").attr("name");
      var map_info = maps[gmf_div.attr("id")];
      var map = map_info.map;
      var address = gmf_div.find(".cf_gmf_address").val();
      var geocoder = new google.maps.Geocoder();

      geocoder.geocode({'address': address}, function (results, status) {
        if (status == google.maps.GeocoderStatus.OK) {
          var loc = results[0].geometry.location;
          map.setCenter(loc);
          var coords = loc.lat() + ", " + loc.lng();
          $(gmf_div).find("[name=" + field_name + "_coords]").val(coords);
          $(gmf_div).find("[name=" + field_name + "_zoom]").val(map.getZoom());
          $(".cf_gmf_coords_str").html(coords);
          map_info.marker.setPosition(loc);
          map_info.marker.setMap(map);
        }
      });
    }
  });
};


$(function () {
  // if google maps isn't included (found by an ID on the script tag),
  if ($("#google-maps-field-lib").length) {
    return;
  }

  $(".cf_gmf_section").each(function () {
    var address_field = $(this).find(".cf_gmf_address");
    var field_name = address_field.attr("name");
    $("#cf_gmf_" + field_name + "_map").after("<div class=\"hint\">Please set your Google Maps API key in the Google Maps Field module settings.</div>");
    $("#cf_gmf_" + field_name + "_map").remove();
    $(this).find(".cf_gmf_update").hide();
  });
});
END;

        $db->query("
            INSERT INTO {PREFIX}field_types (is_editable, non_editable_info, managed_by_module_id,
                field_type_name, field_type_identifier, group_id, is_file_field, is_date_field, raw_field_type_map,
                raw_field_type_map_multi_select_id, list_order, compatible_field_sizes, view_field_rendering_type,
                view_field_smarty_markup, edit_field_smarty_markup, php_processing, resources_css, resources_js)
            VALUES (:is_editable, :non_editable_info, :managed_by_module_id, :field_type_name, :field_type_identifier,
                :group_id, :is_file_field, :is_date_field, :raw_field_type_map, :raw_field_type_map_multi_select_id, 
                :list_order, :compatible_field_sizes, :view_field_rendering_type, :view_field_smarty_markup,
                :edit_field_smarty_markup, :php_processing, :resources_css, :resources_js)
        ");
        $db->bindAll(array(
            "is_editable" => "no",
            "non_editable_info" => "This module may only be edited via the Google Maps Field module.",
            "managed_by_module_id" => $module_id,
            "field_type_name" => "Google Maps",
            "field_type_identifier" => "google_maps_field",
            "group_id" => $group_id,
            "is_file_field" => "no",
            "is_date_field" => "no",
            "raw_field_type_map" => "",
            "raw_field_type_map_multi_select_id" => null,
            "list_order" => $next_list_order,
            "compatible_field_sizes" => "large",
            "view_field_rendering_type" => "smarty",
            "view_field_smarty_markup" => "{strip}{assign var=address value=\"\"}\r\n{assign var=coords value=\"\"}\r\n{if \$VALUE}\r\n  {assign var=parts value=\"|\"|explode:\$VALUE}\r\n  {assign var=address value=\$parts[0]}\r\n  {assign var=coords value=\$parts[1]}\r\n\r\n  {if \$view_export == \"lat_lng\"}\r\n    {\$coords|default:\"\"}\r\n  {else}\r\n    {\$address}\r\n  {/if}\r\n{/if}{/strip}\r\n\r\n",
            "edit_field_smarty_markup" => "{assign var=address value=\"\"}\r\n{assign var=coordinates value=\"\"}\r\n{assign var=zoom_level value=\"\"}\r\n{if \$VALUE}\r\n  {assign var=parts value=\"|\"|explode:\$VALUE}\r\n  {assign var=address value=\$parts[0]}\r\n  {assign var=coords value=\$parts[1]}\r\n  {assign var=zoom_level value=\$parts[2]}\r\n{/if}\r\n\r\n<div class=\"cf_gmf_section\" id=\"cf_gmf_{\$NAME}\">\r\n  <input type=\"hidden\" class=\"cf_gmf_data\" value=\"{\$VALUE|escape}\" />\r\n  <table cellspacing=\"0\" cellpadding=\"0\" width=\"100%\">\r\n  <tr>\r\n    <td><input type=\"text\" name=\"{\$NAME}\" class=\"cf_gmf_address\" \r\n      placeholder=\"Enter Address\" value=\"{\$address|escape}\" /></td>\r\n    <td width=\"60\"><input type=\"button\" class=\"cf_gmf_update\" value=\"{\$LANG.word_update}\" /></td>\r\n  </tr>\r\n  </table>\r\n\r\n  <div id=\"cf_gmf_{\$NAME}_map\" class=\"cf_gmf {\$map_size}\"></div>\r\n  <input type=\"hidden\" name=\"{\$NAME}_coords\" value=\"{\$coords|default:\"\"}\" />\r\n  <input type=\"hidden\" name=\"{\$NAME}_zoom\" value=\"{\$zoom_level}\" />\r\n \r\n  {if \$show_coordinates == \"yes\"}\r\n    <div class=\"medium_grey cf_gmf_coords_str\">{\$coords|default:\"&#8212;\"}</div>\r\n  {/if}\r\n  {if \$comments}\r\n    <div class=\"cf_field_comments\">{\$comments}</div>\r\n  {/if}\r\n</div>\r\n\r\n",
            "php_processing" => "\$field_name = \$vars[\"field_info\"][\"field_name\"];\r\n\r\n\$value = \"\";\r\nif (!empty(\$vars[\"data\"][\$field_name])) {\r\n  \$address = \$vars[\"data\"][\$field_name];\r\n  \$coords  = \$vars[\"data\"][\"{\$field_name}_coords\"];\r\n  \$zoom    = \$vars[\"data\"][\"{\$field_name}_zoom\"];\r\n  \$value   = \"\$address|\$coords|\$zoom\";\r\n}\r\n",
            "resources_css" => ".cf_gmf_address {\r\n  width: 98%;\r\n}\r\n.cf_gmf_tiny {\r\n  height: 160px; \r\n}\r\n.cf_gmf_small {\r\n  height: 250px; \r\n}\r\n.cf_gmf_medium {\r\n  height: 350px; \r\n}\r\n.cf_gmf_large {\r\n  height: 590px; \r\n}\r\n",
            "resources_js" => $resources_js
        ));
        $db->execute();
        $field_type_id = $db->getInsertId();

        $db->query("
            INSERT INTO {PREFIX}field_type_validation_rules (field_type_id, rsv_rule, rule_label, rsv_field_name,
              custom_function, custom_function_required, default_error_message, list_order)
            VALUES (:field_type_id, :rsv_rule, :rule_label, :rsv_field_name, :custom_function, :custom_function_required,
              :default_error_message, :list_order)
        ");
        $db->bindAll(array(
            "field_type_id" => $field_type_id,
            "rsv_rule" => "required",
            "rule_label" => "{\$LANG.word_required}",
            "rsv_field_name" => "{\$field_name}",
            "custom_function" => "",
            "custom_function_required" => "na",
            "default_error_message" => "{\$LANG.validation_default_rule_required}",
            "list_order" => 1
        ));
        $db->execute();

        // map size setting
        $setting_id = FieldTypes::addFieldTypeSetting($field_type_id, 'Map Size', 'map_size', 'select', 'na', 'static', 'cf_gmf_small', 1);
        FieldTypes::addFieldTypeSettingOptions(array(
            array(
                "setting_id" => $setting_id,
                "option_text" => "Tiny",
                "option_value" => "cf_gmf_tiny",
                "option_order" => 1,
                "is_new_sort_group" => "yes"
            ),
            array(
                "setting_id" => $setting_id,
                "option_text" => "Small",
                "option_value" => "cf_gmf_small",
                "option_order" => 2,
                "is_new_sort_group" => "yes"
            ),
            array(
                "setting_id" => $setting_id,
                "option_text" => "Medium",
                "option_value" => "cf_gmf_medium",
                "option_order" => 3,
                "is_new_sort_group" => "yes"
            ),
            array(
                "setting_id" => $setting_id,
                "option_text" => "Large",
                "option_value" => "cf_gmf_large",
                "option_order" => 4,
                "is_new_sort_group" => "yes"
            )
        ));

        // show Lat/Lng when editing setting
        $setting_id = FieldTypes::addFieldTypeSetting($field_type_id, 'Show Lat/Lng when editing', 'show_coordinates', 'radios', 'horizontal', 'static', 'no', 2);
        FieldTypes::addFieldTypeSettingOptions(array(
            array(
                "setting_id" => $setting_id,
                "option_text" => "Yes",
                "option_value" => "yes",
                "option_order" => 1,
                "is_new_sort_group" => "yes"
            ),
            array(
                "setting_id" => $setting_id,
                "option_text" => "No",
                "option_value" => "no",
                "option_order" => 2,
                "is_new_sort_group" => "yes"
            )
        ));

        // View / Export
        $setting_id = FieldTypes::addFieldTypeSetting($field_type_id, 'View / Export', 'view_export', 'radios', 'horizontal', 'static', 'address', 3);
        FieldTypes::addFieldTypeSettingOptions(array(
            array(
                "setting_id" => $setting_id,
                "option_text" => "as Lat/Lng",
                "option_value" => "lat_lng",
                "option_order" => 1,
                "is_new_sort_group" => "yes"
            ),
            array(
                "setting_id" => $setting_id,
                "option_text" => "as_address",
                "option_value" => "address",
                "option_order" => 2,
                "is_new_sort_group" => "yes"
            )
        ));

        // comments
        FieldTypes::addFieldTypeSetting($field_type_id, 'Field Comments', 'comments', 'textarea', 'na', 'static', '', 4);

        // lastly, add our hooks to include the Google Maps library
        self::resetHooks();

        return array(true, "");
    }


    public function uninstall($module_id)
    {
        FieldTypes::deleteFieldType("google_maps_field", "textbox");
        return array(true, "");
    }


    public function includeGoogleMaps($template, $page_data)
    {
        // we only need this field on the edit pages
        $curr_page = $page_data["page"];

        if ($curr_page != "admin_edit_submission" && $curr_page != "client_edit_submission") {
            return "";
        }

        $google_maps_field_type_id = FieldTypes::getFieldTypeIdByIdentifier("google_maps_field");

        // see if the page contains one or more Google Maps fields
        $page_field_types = (isset($page_data["field_types"])) ? $page_data["field_types"] : array();

        $has_google_map_field = false;
        foreach ($page_field_types as $field_type_info) {
            if ($field_type_info["field_type_id"] == $google_maps_field_type_id) {
                $has_google_map_field = true;
                break;
            }
        }

        $settings = Modules::getModuleSettings();
        if ($has_google_map_field && isset($settings["google_maps_key"]) && !empty($settings["google_maps_key"])) {
            return "<script async defer id=\"google-maps-field-lib\" src=\"https://maps.googleapis.com/maps/api/js?key={$settings["google_maps_key"]}&callback=googleMapsInit\"></script>\n";
        }
    }


    /**
     * Added for compatibility with the Form Builder module and any future module that needs to display Form Tools
     * fields outside of the core Form Tools pages. It blithely includes the Google Maps API call for use by whatever
     * page is calling it.
     *
     * @param string $template
     * @param array $page_data
     */
    public function includeStandaloneGoogleMaps($template, $page_data)
    {
        $settings = Modules::getModuleSettings();
        if (!isset($settings["google_maps_key"]) || empty($settings["google_maps_key"])) {
            return "";
        }

        $string = "<script async defer id=\"google-maps-field-lib\" src=\"https://maps.googleapis.com/maps/api/js?key={$settings["google_maps_key"]}&callback=googleMapsInit\"></script>\n";

        // this function can either return or just echo the code directly. The Form Builder needs it returned,
        // as the template hook is called via code to keep the actual templates entered by the administrator's as simple
        // as possible
        if (isset($form_tools_all_template_hook_params["return"])) {
            return $string;
        } else {
            echo $string;
        }
    }


    public function resetHooks()
    {
        Hooks::unregisterModuleHooks("field_type_google_maps");
        Hooks::registerHook("template", "field_type_google_maps", "head_bottom", "", "includeGoogleMaps");
        Hooks::registerHook("template", "field_type_google_maps", "standalone_form_fields_head_bottom", "", "includeStandaloneGoogleMaps");
    }

    public function updateSettings($google_maps_key) {
        Modules::setModuleSettings(array(
            "google_maps_key" => $google_maps_key
        ));
        return array(true, $this->L["notify_settings_updated"]);
    }
}
