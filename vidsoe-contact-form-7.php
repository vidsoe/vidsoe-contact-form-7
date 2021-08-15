<?php

if(!defined('ABSPATH')){
    echo "Hi there! I'm just an extension, not much I can do when called directly.";
	exit;
}
require_once(plugin_dir_path(__FILE__) . 'classes/class-vidsoe-contact-form-7.php');
Vidsoe_Contact_Form_7::get_instance(__FILE__);
