<?php

require_once("../../global/library.php");

use FormTools\Modules;
$module = Modules::initModulePage("admin");

$success = true;
$message = "";
if (isset($_POST["update"])) {
    list($success, $message) = $module->updateSettings($_POST["google_maps_key"]);
}

$settings = Modules::getModuleSettings();

$page_vars = array(
    "g_success" => $success,
    "g_message" => $message,
    "google_maps_key" => isset($settings["google_maps_key"]) ? $settings["google_maps_key"] : ""
);
$module->displayPage("templates/index.tpl", $page_vars);
