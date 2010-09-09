<?php

define("GENERATOR_ENVIRONMENT","local");

$opts = $argv;
array_shift($opts);
$testing_or_final = array_shift($opts);

if(in_array($testing_or_final, array("testing", "final")))
{
	echo "Environment: ".GENERATOR_ENVIRONMENT."\n";

	try{

		function translate_filename($pagename, $extension = ".html")
		{
			return preg_replace("#_#", "/", $pagename).$extension;
		}

		/**
		 *  Things to allow in the templates:
		 *  [PAGE_TITLE] (to add to the title bar)
		 *  [PREFIX] (for links)
		 *	[PREFIX_FINAL] (for images etc)
		 *  [NEWS_CONTENT] (on the page where the news goes)
		 *
		 */

		require_once "includes/Lib/Spyc-0.4.5.php";
		require_once "includes/Settings.php";
		require_once "includes/Site.php";

		$config_path = realpath(dirname(__FILE__))."/includes/config/";

		$setting_data = SPYC::YAMLLoad($config_path."settings.yml");

		//print_r($setting_data);

		$settings = new Settings();

		foreach($setting_data["all"] as $key => $value)
		{
			$settings->__set($key, $value);
		}

		if(!array_key_exists(GENERATOR_ENVIRONMENT, $setting_data))
		{
			throw new Exception("Unknown generator environment.");
		}

		foreach($setting_data[GENERATOR_ENVIRONMENT] as $key => $value)
		{
			$settings->__set($key, $value);
		}

		$menus = SPYC::YAMLLoad($config_path."menu.yml");
		$pages = SPYC::YAMLLoad($config_path."pages.yml");
		$site = new Site($pages, $menus, $settings); //CONTENT_FILE_PATH, PAGE_OUTPUT_DIR, PAGE_FINAL_DIR, LINK_PREFIX_TESTING, LINK_PREFIX_FINAL, PAGE_OUTPUT_ORDER, PAGE_OUTPUT_TEMPLATE_DIR, NEWS_PAGE);

		switch($testing_or_final)
		{
			case "testing":
				$site->generate_pages("testing");
				$ret = $site->move_pages("testing");
				if($ret)
				{
					echo "Success: Pages generated. Url: ".$settings->domain_prefix.$settings->link_prefix_testing."\n";
				}
				else
				{
					throw new Exception("Unable to move generated files.");
				}
				
				break;
			case "final":
				$site->generate_pages("final");
				$ret = $site->move_pages("final");
				if($ret)
				{
					echo "Success: Pages generated. Url: ".$settings->domain_prefix.$settings->link_prefix_final."\n";
				}
				else
				{
					throw new Exception("Unable to move generated files.");
				}
				break;
			default:
				throw new Exception("Unrecognized action_type supplied.");
		}
	}
	catch(Exception $e)
	{
		echo "Error: ".$e->getMessage()."\n";
	}
}
else
{
	echo "Error: No output type was specified. Please call the script on the command line with parameter either 'testing' or 'final'\n";
}
