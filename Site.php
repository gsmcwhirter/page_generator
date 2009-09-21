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
	protected $_output_order;
	protected $_template_dir;
	protected $_news;

	public function __construct($page_data, $menu_data, $filepath, $output_dir, $p_testing, $p_final, $output_order, $template_dir, $newspage)
	{
		$this->_output_dir = $output_dir;
		$this->_link_prefix_final = $p_final;
		$this->_link_prefix_testing = $p_testing;
		$this->_filepath = $filepath;
		$this->_output_order = explode(",",$output_order);
		$this->_template_dir = $template_dir;

		$this->_menu = new Site_Menu($menu_data);

		foreach($page_data as $page_name => $page_menuitem)
		{
			$this->_pages[$page_name] = new Site_Page($page_name, $page_menuitem, $this->_menu, $this->_filepath);
			if($page_name == $newspage)
			{
				$this->_pages[$page_name]->set_newspage();
			}
		}

		if(!array_key_exists($newspage, $this->_pages))
		{
			throw new Exception("No news page existed.");
		}

		$this->_news = new Site_News("narchives", $this->_menu, $this->_filepath);
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

		//Site_Page::sethu($prefix);

		$template_data = array();
		foreach($this->_output_order as $out_item)
		{
			if(!preg_match("#^\[.*\]$#", $out_item))
			{
				$template_data[$out_item] = file_get_contents($this->_template_dir.$out_item.".tpl");
			}
		}

		foreach($this->_pages as $page)
		{
			$file = $this->_output_dir.$page->get_filename();
			$data = "";

			foreach($this->_output_order as $out_item)
			{
				switch($out_item)
				{
					case "[menu]":
						$data .= $page->output_menu();
						break;
					case "[content]":
						$data .= $page->output_breadcrumbs();
						$data .= $page->output_content();
						break;
					default:
						$data .= $template_data[$out_item];
				}
			}

			$regex = array("#\[PAGE_TITLE\]#","#\[PREFIX\]#","#\[PREFIX_FINAL\]#");
			$replace = array($page->get_title(), $prefix, $this->_link_prefix_final);

			if($page->is_newspage())
			{
				$data = preg_replace("#\[NEWS_CONTENT\]#", $this->_news->generate_last_n(), $data);
			}

			file_put_contents($file, preg_replace($regex, $replace, $data));


		}

		$archives = $this->_news->generate_archives();
		foreach($archives as $adata)
		{
			$file = $this->_output_dir.$adata["filename"];
			$parts = explode("/", $adata["filename"]);
			$last_part = array_pop($parts);

			for($i = 0; $i < count($parts); $i++)
			{
				$test = "";
				for($j = 0; $j <= $i; $j++)
				{
					$test .= $parts[$j]."/";
				}
				if(!is_dir($this->_output_dir.$test))
				{
					mkdir($this->_output_dir.$test);
				}
			}

			$data = "";

			foreach($this->_output_order as $out_item)
			{
				switch($out_item)
				{
					case "[menu]":
						$data .= $this->_news->output_menu();
						break;
					case "[content]":
						$data .= $this->_news->output_breadcrumbs($adata["crumbs"]);
						$data .= $adata["content"];
						break;
					default:
						$data .= $template_data[$out_item];
				}
			}

			$regex = array("#\[PAGE_TITLE\]#","#\[PREFIX\]#","#\[PREFIX_FINAL\]#");
			$replace = array($adata["title"], $prefix, $this->_link_prefix_final);

			file_put_contents($file, preg_replace($regex, $replace, $data));
		}
	}
}
