<?php

$HOOKS = array();
$HOOKS["1.0.8"] = array(
  array(
    "hook_type"       => "template",
    "action_location" => "head_bottom",
    "function_name"   => "",
    "hook_function"   => "ftgp_include_google_maps",
    "priority"        => "50"
  ),
  array(
    "hook_type"       => "template",
    "action_location" => "standalone_form_fields_head_bottom",
    "function_name"   => "",
    "hook_function"   => "ftgp_include_standalone_google_maps",
    "priority"        => "50"
  )
);