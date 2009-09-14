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
				throw new Exception("Site_Menu item was missing a name.");
			}

			if(array_key_exists($key, $this->_allitems))
			{
				throw new Exception("Site_Menu item key duplicate: ".$key);
			}

			$this->_allitems[$key] = new Site_Menu_Item($newdata["_name"], $key, array_key_exists("_page", $newdata) ? $newdata["_page"] : null);
			unset($newdata["_name"]);
			unset($newdata["_page"]);
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
		$this->_allitems[$item]->set_showchildren(true);
	}

	public function make_breadcrumbs($item)
	{
		$ret = array();
		$last = $this->_allitems[$item];
		while(!is_null($last))
		{
			array_unshift($ret, array($last->get_text(), $last->get_target()));
			$last = $last->get_parent();
		}

		return array($ret, $this->_allitems[$item]->get_target());
	}
}
