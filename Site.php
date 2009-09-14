<?php

require_once "Site/Menu.php";
require_once "Site/Page.php";

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

	public function __construct($page_data, $menu_data, $filepath, $output_dir, $p_testing, $p_final, $output_order, $template_dir)
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
			$this->_pages[] = new Site_Page($page_name, $page_menuitem, $this->_menu, $this->_filepath);
		}
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

		Site_Page::$Textile->sethu($prefix);

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

			file_put_contents($file, preg_replace(array("#/<PAGE_TITLE>#","#/<PREFIX>#","#/<PREFIX_FINAL>#"), array($page->get_title(), $prefix, $this->_link_prefix_final), $data));

		}

		//TODO: generate news still
	}
}
