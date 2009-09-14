<?php
$path = realpath(dirname(__FILE__));

define("PAGE_FILE_PATH", $path."/pages/");
define("LINK_PREFIX_TESTING", "/page_generator/output/");
define("LINK_PREFIX_FINAL", "/page_generator/");
define("PAGE_OUTPUT_DIR", $path."/output/");
define("PAGE_OUTPUT_ORDER", "header,[menu],separator,[content],footer"); //{menu} and {content} are special indicators
define("PAGE_OUTPUT_TEMPLATE_DIR", $path."/templates/");

unset($path);

/**
 *  Things to allow in the templates:
 *  /<PAGE_TITLE> (to add to the title bar)
 *  /<PREFIX> (for links)
 *	/<PREFIX_FINAL> (for images etc)
 *
 *
 */

require_once "Lib/Spyc.php";
require_once "Site.php";

$menus = SPYC::YAMLLoad("config/menu.yml");
$pages = SPYC::YAMLLoad("config/pages.yml");
$site = new Site($pages, $menus, PAGE_FILE_PATH, PAGE_OUTPUT_DIR, LINK_PREFIX_TESTING, LINK_PREFIX_FINAL, PAGE_OUTPUT_ORDER, PAGE_OUTPUT_TEMPLATE_DIR);

$site->generate_pages("testing");
