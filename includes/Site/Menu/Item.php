<?php

class Site_Menu_Item
{
	protected $_parent;
	protected $_name;
	protected $_abbrev;
	protected $_target;
	protected $_showchildren = false;
	protected $_children = array();
	protected $_skip_before;
	protected $_show;

	public function __construct($name, $abbrev, $target, $skip_before, $show)
	{
		$this->_name = $name;
		$this->_abbrev = $abbrev;
		$this->_target = $target;
		$this->_skip_before = $skip_before;
		$this->_show = $show;
	}

	public function add_child(Site_Menu_Item &$child)
	{
		$this->_children[] = $child;
	}

	public function set_parent(Site_Menu_Item &$parent)
	{
		$this->_parent = $parent;
	}

	public function is_toplevel()
	{
		return is_null($this->_parent);
	}

	public function set_showchildren($value = true)
	{
		if($value)
		{
			$this->_showchildren = (bool)$value;
			if(!is_null($this->_parent))
			{
				$this->_parent->set_showchildren($value);
			}
		}
		else
		{
			$this->_showchildren = false;
		}

	}

	public function get_structure()
	{
		$data = array();
		if($this->_showchildren && $this->_children != array())
		{
			foreach($this->_children as $child)
			{
				$data[] = $child->get_structure();
			}
		}

		return array($this->_name, $this->_target, $data, $this->_skip_before, $this->_show);
	}

	public function &get_parent()
	{
		return $this->_parent;
	}

	public function get_abbrev()
	{
		return $this->_abbrev;
	}

	public function get_target()
	{
		return $this->_target;
	}

	public function get_text()
	{
		return $this->_name;
	}

	public function skip_before()
	{
		return $this->_skip_before;
	}

	public function should_show()
	{
		return $this->_show;
	}
}
