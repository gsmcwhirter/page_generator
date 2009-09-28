<?php
$messages = array("testing" => "", "final" => "", "error" => "");

if(strtolower($_SERVER["REQUEST_METHOD"]) == "post")
{
	try{

		if(!array_key_exists("action_type", $_POST))
		{
			throw new Exception("No action_type specified in the request.");
		}

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

		require_once "includes/Lib/Spyc.php";
		require_once "includes/Settings.php";
		require_once "includes/Site.php";

		$path = realpath(dirname(__FILE__));

		$settings = new Settings();

		$settings->content_file_path = $path."/includes/content/";
		$settings->link_prefix_testing = "/page_generator/output/testing/";
		$settings->link_prefix_final = "/page_generator/target/";
		$settings->page_output_dir = $path."/output/";
		$settings->page_final_dir = $path."/target/";
		$settings->page_output_order = array("header", "[content]", "separator", "[menu]","footer");
		$settings->page_output_template_dir = $path."/includes/templates/";
		$settings->news_page = "index";
		$settings->domain_prefix = "http://www.example.com";
		$settings->rss_file = "news.rss";
		$settings->rss_title = "Example Site";
		$settings->rss_description = "";
		$settings->webmaster = "webmaster@example.com";

		unset($path);

		$menus = SPYC::YAMLLoad("includes/config/menu.yml");
		$pages = SPYC::YAMLLoad("includes/config/pages.yml");
		$site = new Site($pages, $menus, $settings); //CONTENT_FILE_PATH, PAGE_OUTPUT_DIR, PAGE_FINAL_DIR, LINK_PREFIX_TESTING, LINK_PREFIX_FINAL, PAGE_OUTPUT_ORDER, PAGE_OUTPUT_TEMPLATE_DIR, NEWS_PAGE);

		switch($_POST["action_type"])
		{
			case "testing":
				$site->generate_pages("testing");
				$messages["testing"] = "Pages generated. <a href='".$settings->link_prefix_testing."'>Go there</a>.";
				break;
			case "final":
				$site->generate_pages("final");
				$ret = $site->move_pages("final");
				if($ret)
				{
					$messages["final"] = "Pages generated. <a href='".$settings->link_prefix_final."'>Go there</a>.";
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
		$messages["error"] = "Error: ".$e->getMessage();
	}
}
?>
<html lang="en" style="width: 100%; height: 100%;">
<head>
</head>
<body style="text-align: center; width: 100%; height: 100%; background-color: #888;">
	<div style="margin: 10px auto; width: 800px; background-color: #fff; overflow: auto;">
		<p><?php echo $messages["error"] ?></p>
		<div style="float: left; width: 385px; margin-left: 10px;">
			<p><?php echo $messages["testing"] ?></p>
			<form method="post">
				<input type="hidden" name="action_type" id="action_type_testing" value="testing" />
				<button type="submit">generate testing pages</button>
			</form>
		</div>
		<div style="float: right; width: 385px; margin-right: 10px;">
			<p><?php echo $messages["final"] ?></p>
			<form method="post">
				<input type="hidden" name="action_type" id="action_type_final" value="final" />
				<button type="submit">generate final pages</button>
			</form>
		</div>
	</div>
</body>
</html>
