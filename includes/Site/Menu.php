<?php

require_once "Menu/Item.php";

class Site_Menu
{
	protected $_allitems = array();

	public function __construct(array $data)
	{
		$structure = $this->_parse_menu_data_recursive($data);
		$this->_set_structure_recursive($structure);
	}

	protected function _parse_menu_data_recursive(array $data)
	{
		$structure = array();
		foreach($data as $key => $newdata)
		{
			if(!array_key_exists("_name", $newdata))
			{
				print_r($newdata);
				throw new Exception("Site_Menu item was missing a name.");
			}

			if(array_key_exists($key, $this->_allitems))
			{
				throw new Exception("Site_Menu item key duplicate: ".$key);
			}

			$this->_allitems[$key] = new Site_Menu_Item($newdata["_name"], $key, array_key_exists("_page", $newdata) ? $newdata["_page"] : null, array_key_exists("_skip_before", $newdata) ? $newdata["_skip_before"] : null, array_key_exists("_show", $newdata) ? $newdata["_show"] : true);
			unset($newdata["_name"]);
			unset($newdata["_page"]);
			unset($newdata["_skip_before"]);
			unset($newdata["_show"]);
			$structure2 = $this->_parse_menu_data_recursive($newdata);
			$structure[$key] = $structure2;
		}

		return $structure;
	}

	protected function _set_structure_recursive(array $structure)
	{
		foreach($structure as $item => $subitems)
		{
			foreach($subitems as $subitem => $subsubs)
			{
				$this->_allitems[$item]->add_child($this->_allitems[$subitem]);
				$this->_allitems[$subitem]->set_parent($this->_allitems[$item]);
			}

			$this->_set_structure_recursive($subitems);
		}
	}

	public function menu_structure()
	{
		$data = array();

		foreach($this->_allitems as $key => $item)
		{
			if($item->is_toplevel())
			{
				$data[$key] = $item->get_structure();
			}
		}

		return $data;
	}

	public function reset_menu()
	{
		foreach($this->_allitems as $item)
		{
			$item->set_showchildren(false);
		}
	}

	public function set_page_level($item)
	{
		if($item != "_none")
		{
			$this->_allitems[$item]->set_showchildren(true);
		}
	}

	public function make_breadcrumbs($item)
	{
		if($item == "_none")
		{
			return array(array(), null);
		}

		$ret = array();
		$last = $this->_allitems[$item];
		while(!is_null($last))
		{
			array_unshift($ret, array($last->get_text(), $last->get_target()));
			$last = $last->get_parent();
		}

		return array($ret, $this->_allitems[$item]->get_target());
	}

	public function output_menu()
	{
		$struct_data = $this->menu_structure();

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
			list($text, $target, $val_data, $skip, $show) = $value;

			if($show)
			{
				if(!preg_match("#^http(s?):#", $target))
				{
					$target = '[PREFIX]'.translate_filename($target);
				}

				if($skip)
				{
					$data .= "<li class='menu_skip'>&nbsp;</li>";
				}
				$data .= "<li><a href='".$target."'>".$text."</a>";
				if($val_data != array())
				{
					$all_hidden = true;
					foreach($val_data as $val2)
					{
						if($val2[4])
						{
							$all_hidden = false;
							break;
						}
					}

					if(!$all_hidden)
					{
						$data .= "<ul>".$this->_output_menu_recursive($val_data)."</ul>";
					}
				}
				$data .= "</li>";
			}
		}
		return $data;
	}

	public function output_breadcrumbs($crumbs)
	{
		$data = array();

		foreach($crumbs as $href => $text)
		{
			$data[] = "<span class='crumb'><a href='[PREFIX]".$href."'>".$text."</a></span>";
		}

		return "<div class='breadcrumbs'>".implode("<span class='crumb_sep'>&raquo;</span>", $data)."</div>";
	}
}
