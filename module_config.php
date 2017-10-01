<?php

$STRUCTURE = array();

$HOOKS = array(
  array(
    "hook_type"       => "template",
    "action_location" => "head_bottom",
    "function_name"   => "",
    "hook_function"   => "includeGoogleMaps",
    "priority"        => "50"
  ),
  array(
    "hook_type"       => "template",
    "action_location" => "standalone_form_fields_head_bottom",
    "function_name"   => "",
    "hook_function"   => "includeStandaloneGoogleMaps",
    "priority"        => "50"
  )
);

$FILES = array(
  "code/",
  "code/Module.class.php",
  "images/",
  "images/google_maps_icon.png",
  "index.php",
  "lang/",
  "lang/en_us.php",
  "library.php",
  "module_config.php",
  "templates/",
  "templates/index.tpl"
);
