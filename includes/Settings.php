<?php

class Settings
{
	protected $_set = array();
	protected $_data = array(
		"content_file_path" => null,
		"link_prefix_testing" => null,
		"link_prefix_final" => null,
		"domain_prefix" => null,
		"page_output_dir" => null,
		"page_final_dir" => null,
		"page_output_order" => null,
		"page_output_template_dir" => null,
		"news_page" => null,
		"rss_file" => null,
		"rss_title" => null,
		"rss_description" => null,
		"webmaster" => null,
		"analytics_id" => null,
		"google_verify" => null
	);

	public function __construct()
	{}

	public function __set($name, $value)
	{
		if(in_array($name, $this->_set))
		{
			throw new Exception("Trying to set a setting that has already been entered.");
		}

		if(!array_key_exists($name, $this->_data))
		{
			throw new Exception("Trying to set an unrecognized setting.");
		}

		$this->_set[] = $name;
		return $this->_data[$name] = $value;
	}

	public function __get($name)
	{
		if(!array_key_exists($name, $this->_data))
		{
			throw new Exception("Trying to get value for an unrecognized setting.");
		}

		if(!in_array($name, $this->_set))
		{
			throw new Exception("Trying to get value for a setting that has not been entered.");
		}

		return $this->_data[$name];
	}
}
