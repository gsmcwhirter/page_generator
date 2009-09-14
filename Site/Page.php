<?php

require_once "Lib/Textile.php";

class Site_Page
{
	public static $Textile = null;

	public static function translate_filename($pagename, $extension = ".html")
	{
		return preg_replace("#_#", "/", $pagename).$extension;
	}

	protected $_breadcrumbs;
	protected $_page_name;
	protected $_title;
	protected $_content;
	protected $_menu;
	protected $_filepath;
	protected $_menuitem;

	public function __construct($page_name, $page_menuitem, Site_Menu &$menu, $filepath)
	{
		$this->_menu = $menu;
		$this->_filepath = $filepath;
		$this->_page_name = $page_name;
		$this->_menuitem = $page_menuitem;

		list($this->_title, $this->_content) = explode("%%%", file_get_contents($this->_pagename_to_filename()), 2);
		$this->_title = trim($this->_title);
		$this->_content = trim($this->_content);

		list($this->_breadcrumbs, $last_target) = $this->_menu->make_breadcrumbs($page_menuitem);
		if($last_target != $page_name)
		{
			array_push($this->_breadcrumbs, array($this->_title, $page_name));
		}

		if(is_null(self::$Textile))
		{
			self::$Textile = new Textile();
		}
	}

	protected function _pagename_to_filename($extension = ".textile")
	{
		return $this->_filepath.self::translate_filename($this->_page_name, $extension);
	}

	protected function _de_quote($text)
	{
		return preg_replace(array("!&quot;!","!&#39;!","!&#092;!"),array("\"","'",'\\'), $text);
	}

	public function get_filename($extension = ".html")
	{
		return self::translate_filename($this->_page_name, $extension);
	}

	public function get_title()
	{
		return $this->_title;
	}

	public function output_content()
	{
		return self::$Textile->TextileThis($this->_de_quote($this->_content),'','','','','external');
	}

	public function output_breadcrumbs()
	{
		$data = array();
		foreach($this->_breadcrumbs as $crumb)
		{
			$data[] = "<span class='crumb'><a href='[PREFIX]".self::translate_filename($crumb[1])."'>".$crumb[0]."</a></span>";
		}

		return "<div class='breadcrumbs'>".implode("<span class='crumb_sep'>&raquo;</span>", $data)."</div>";
	}

	public function output_menu()
	{
		$this->_menu->set_page_level($this->_menuitem);
		$struct_data = $this->_menu->menu_structure();
		$this->_menu->reset_menu();

		$data = "<div class='menu'>";
		$data .= "<ul>";
		$data .= $this->_output_menu_recursive($struct_data);
		$data .= "</ul>";
		$data .= "</div>";

		return $data;
	}

	protected function _output_menu_recursive($data_array)
	{
		$data = "";
		foreach($data_array as $value)
		{
			list($text, $target, $val_data) = $value;
			if(!preg_match("#^http(s?):#", $target))
			{
				$target = '[PREFIX]'.self::translate_filename($target);
			}
			$data .= "<li><a href='".$target."'>".$text."</a>";
			if($val_data != array())
			{
				$data .= "<ul>".$this->_output_menu_recursive($val_data)."</ul>";
			}
			$data .= "</li>";
		}
		return $data;
	}
}
