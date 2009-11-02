<?php

require_once "Site/Menu.php";
require_once "Site/Page.php";
require_once "Site/News.php";

class Site
{

	protected $_pages = array();
	protected $_menu;
	protected $_filepath;
	protected $_link_prefix_testing;
	protected $_link_prefix_final;
	protected $_output_dir;
	protected $_final_dir;
	protected $_output_order;
	protected $_template_dir;
	protected $_news;
	protected $_domain;
	protected $_rss_file;
	protected $_webmaster;
	protected $_sitemap = array();
	protected $_analytics;

	public function __construct($page_data, $menu_data, Settings $settings)
	{
		$this->_output_dir = $settings->page_output_dir;
		$this->_final_dir = $settings->page_final_dir;
		$this->_link_prefix_final = $settings->link_prefix_final;
		$this->_link_prefix_testing = $settings->link_prefix_testing;
		$this->_filepath = $settings->content_file_path;
		$this->_output_order = $settings->page_output_order;
		$this->_template_dir = $settings->page_output_template_dir;
		$this->_domain = $settings->domain_prefix;
		$this->_rss_file = $settings->rss_file;
		$this->_rss_title = $settings->rss_title;
		$this->_webmaster = $settings->webmaster;
		$this->_analytics = $settings->analytics_id;
		$this->_google_verify = $settings->google_verify;

		$this->_menu = new Site_Menu($menu_data);

		foreach($page_data as $page_name => $page_menuitem)
		{
			$this->_pages[$page_name] = new Site_Page($page_name, $page_menuitem, $this->_menu, $this->_filepath);
			if($page_name == $settings->news_page)
			{
				$this->_pages[$page_name]->set_newspage();
			}
		}

		if(!array_key_exists($settings->news_page, $this->_pages))
		{
			throw new Exception("No news page existed.");
		}

		$this->_news = new Site_News("narchives", $this->_menu, $settings);
	}

	public function generate_pages($type)
	{
		switch($type)
		{
			case "testing":
				$prefix = $this->_link_prefix_testing;
				break;
			case "final":
				$prefix = $this->_link_prefix_final;
				break;
			default:
				throw new Exception("Unrecognized type for generating pages: ".$type);
		}


		//load template data
		$template_data = array();
		foreach($this->_output_order as $out_item)
		{
			if(!preg_match("#^\(.*\)$#", $out_item))
			{
				$template_data[$out_item] = file_get_contents($this->_template_dir.$out_item.".tpl");
			}
		}

		//generate standard pages
		foreach($this->_pages as $page)
		{
			$no_template = false;
			$parts = explode("/", $page->get_filename());
			$last_part = array_pop($parts);

			if($last_part == ".htaccess.html")
			{
				$file = $this->_output_dir.$type."/".implode("/", $parts)."/.htaccess";
				$no_template = true;
			}
			else
			{
				$file = $this->_output_dir.$type."/".$page->get_filename();
				array_push($this->_sitemap, $this->_domain.$prefix.$page->get_filename());
			}

			array_unshift($parts, $type);

			$this->_ensure_directories($parts);

			if($no_template)
			{
				$data = $page->output_content();
			}
			else
			{
				$data = "";

				foreach($this->_output_order as $out_item)
				{
					switch($out_item)
					{
						case "(menu)":
							$data .= $page->output_menu();
							break;
						case "(content)":
							$data .= $page->output_breadcrumbs();
							$data .= $page->output_content();
							break;
						default:
							$data .= $template_data[$out_item];
					}
				}
			}

			$regex = array("#\[PAGE_TITLE\]#","#\[PREFIX\]#","#\[PREFIX_FINAL\]#","#\[RSS_LINK\]#","#\[WEBMASTER\]#","#\[ANALYTICS_ID\]#","#\[GOOGLE_VERIFY\]#");
			$replace = array($page->get_title(), $prefix, $this->_link_prefix_final,"", $this->_webmaster, $this->_analytics, $this->_google_verify);

			if($page->is_newspage())
			{
				$rsslink = '<link rel="alternate" type="application/rss+xml" title="'.$this->_rss_title.'" href="[PREFIX]'.$this->_rss_file.'" />';
				$data = preg_replace(array("#\[NEWS_CONTENT\]#", "#\[RSS_LINK\]#"), array($this->_news->generate_last_n(),$rsslink), $data);
			}

			file_put_contents($file, preg_replace($regex, $replace, $data));
		}

		//generate news archives
		$archives = $this->_news->generate_archives();
		foreach($archives as $adata)
		{
			$file = $this->_output_dir.$type."/".$adata["filename"];
			$parts = explode("/", $adata["filename"]);
			$last_part = array_pop($parts);
			array_unshift($parts, $type);

			$this->_ensure_directories($parts);

			$data = "";

			foreach($this->_output_order as $out_item)
			{
				switch($out_item)
				{
					case "(menu)":
						$data .= $this->_news->output_menu();
						break;
					case "(content)":
						$data .= $this->_news->output_breadcrumbs($adata["crumbs"]);
						$data .= $adata["content"];
						break;
					default:
						$data .= $template_data[$out_item];
				}
			}

			$regex = array("#\[PAGE_TITLE\]#","#\[PREFIX\]#","#\[PREFIX_FINAL\]#","#\[RSS_LINK\]#","#\[WEBMASTER\]#","#\[ANALYTICS_ID\]#","#\[GOOGLE_VERIFY\]#");
			$replace = array($adata["title"], $prefix, $this->_link_prefix_final, "", $this->_webmaster, $this->_analytics, $this->_google_verify);

			file_put_contents($file, preg_replace($regex, $replace, $data));
			array_push($this->_sitemap, $this->_domain.$prefix.$adata["filename"]);
		}

		//generate RSS file
		$regex = array("#\[PREFIX\]#","#\[PREFIX_FINAL\]#","#\[THISPAGE\]#","#\[WEBMASTER\]#");
		$replace = array($this->_domain.$prefix, $this->_domain.$this->_link_prefix_final, $this->_rss_file, $this->_webmaster);

		$file = $this->_output_dir.$type."/".$this->_rss_file;
		$parts = explode("/", $this->_rss_file);
		$last_part = array_pop($parts);
		array_unshift($parts, $type);
		$this->_ensure_directories($parts);

		file_put_contents($file, preg_replace($regex, $replace, $this->_news->generate_rss()));

		//generate sitemap.xml
		$file = $this->_output_dir.$type."/sitemap.xml";
		$parts = array($type);
		$this->_ensure_directories($parts);

		$content = '<?xml version="1.0" encoding="UTF-8"?>
	<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		foreach($this->_sitemap as $item)
		{
			$content .= '
			<url><loc>'.htmlentities($item, ENT_QUOTES).'</loc></url>';
		}
		$content .= '
	</urlset>';

		file_put_contents($file, $content);
	}

	protected function _ensure_directories($parts, $base = null)
	{
		if(is_null($base))
		{
			$base = $this->_output_dir;
		}

		for($i = 0; $i < count($parts); $i++)
		{
			$test = "";
			for($j = 0; $j <= $i; $j++)
			{
				$test .= $parts[$j]."/";
			}
			if(!is_dir($base.$test))
			{
				mkdir($base.$test);
			}
		}
	}

	public function move_pages($type)
	{
		switch($type)
		{
			case "testing":
				break;
			case "final":
				return $this->_move_pages_recursive($this->_output_dir.$type, $this->_final_dir);
				break;
			default: throw new Exception("Type must be testing or final.");
		}
	}

	protected function _move_pages_recursive($dir, $target)
	{
		$iter = new DirectoryIterator($dir);
		foreach($iter as $file)
		{
			if($file->isDot())
			{
				continue;
			}

			if($file->isDir())
			{
				$newtarget = $target.$file->getFilename()."/";
				if(!is_dir($newtarget))
				{
					mkdir($newtarget);
				}
				$this->_move_pages_recursive($file->getPathname(), $newtarget);
			}
			elseif($file->getFilename() == ".htaccess")
			{
				if(!copy($file->getPathname(), $target.$file->getFilename()))
				{
					throw new Exception("Could not copy file ".$file->getPathname()." to ".$target.$file->getFilename());
				}
			}
			else
			{
				if(!rename($file->getPathname(), $target.$file->getFilename()))
				{
					throw new Exception("Could not move file ".$file->getPathname()." to ".$target.$file->getFilename());
				}
			}
		}

		return true;
	}
}
