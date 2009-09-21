<?php

class Site_News
{

	protected $_menu;
	protected $_filepath;
	protected $_menuitem;
	protected $_newsfile;
	protected $_month_years = array();

	public function __construct($menuitem, Site_Menu &$menu, $filepath)
	{
		$this->_menu = $menu;
		$this->_filepath = $filepath."news/";
		$this->_menuitem = $menuitem;

		$this->_parse_directory();
	}

	protected function _parse_directory()
	{
		if(is_dir($this->_filepath))
		{
			$iter = new DirectoryIterator($this->_filepath);
			foreach($iter as $file)
			{
				if($file->isDot())
				{
					continue;
				}

				$year = $file->getFilename();

				if($file->isDir() && is_numeric($year))
				{
					$this->_month_years[$year] = array();
					$miter = new DirectoryIterator($file->getPathname());

					foreach($miter as $month_file)
					{
						if($month_file->isDot())
						{
							continue;
						}

						$month = $month_file->getFilename();

						if($month_file->isDir() && is_numeric($month))
						{
							$this->_month_years[$year][$month] = array();
							$aiter = new DirectoryIterator($month_file->getPathname());

							foreach($aiter as $article)
							{
								if($article->isDot())
								{
									continue;
								}

								$art = $article->getFilename();
								list($day, $ext) = explode(".", $art, 2);
								$this->_month_years[$year][$month][$day] = $art;
							}

							krsort($this->_month_years[$year][$month]);
						}
					}

					krsort($this->_month_years[$year]);
				}
			}

			krsort($this->_month_years);
		}
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
				$target = '[PREFIX]'.translate_filename($target);
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

	public function output_breadcrumbs($crumbs)
	{
		$data = array();

		foreach($crumbs as $href => $text)
		{
			$data[] = "<span class='crumb'><a href='[PREFIX]".$href.".html'>".$text."</a></span>";
		}

		return "<div class='breadcrumbs'>".implode("<span class='crumb_sep'>&raquo;</span>", $data)."</div>";
	}

	public function generate_archives()
	{
		$data = array();

		$data[] = $this->_generate_archive();

		foreach($this->_month_years as $year => $mdata)
		{
			$data[] = $this->_generate_archive_year($year, $mdata);
			foreach($mdata as $month => $articles)
			{
				$data[] = $this->_generate_archive_month($year, $month, $articles);
			}
		}
		return $data;
	}

	protected function _zerofill($i)
	{
		$str = (string)$i;
		return (strlen($str) == 1) ? "0".$str : $str;
	}

	protected function _generate_archive()
	{
		$filename = "news/index.html";
		$crumb = array("index" => "Welcome", "news/index" => "News Archive");
		$title = "News Archive";
		$data = "<h1>".$title."</h1>";
		$data .= "<ul>";

		$keys = array_keys($this->_month_years);
		sort($keys);
		$min = array_shift($keys);
		$max = array_pop($keys);

		if(is_null($max))
		{
			$max = $min;
		}

		for($i = $max; $i >= $min; $i--)
		{
			$data .= "<li>";
			$text = $i;

			if(array_key_exists($i, $this->_month_years))
			{
				$data .= "<a href='[PREFIX]news/".$i.".html'>".$text."</a>";
			}
			else
			{
				$data = $text;
			}
			$data .= "</li>";
		}

		$data .= "</ul>";

		return array("content" => $data, "crumbs" => $crumb, "filename" => $filename, "title" => $title);
	}

	protected function _generate_archive_year($year, $mdata)
	{
		$filename = "news/".$year.".html";
		$crumb = array("index" => "Welcome", "news/index" => "News Archive","news/".$year => $year);
		$title = $year." News Archive";
		$data = "<h1>".$title."</h1>";
		$data .= "<ul class='bullet'>";

		for($i = 1; $i < 13; $i++)
		{
			if($year == date("Y") && $i > date("m"))
			{
				break;
			}

			$str = $this->_zerofill($i);

			$data .= "<li>";
			$timestamp = strtotime($year."/".$str."/03 12:00pm");
			$text = date("F", $timestamp);

			$ct = array_key_exists($str, $mdata) ? count($mdata[$str]) : 0;
			if($ct != 0)
			{
				$data .= "<a href='[PREFIX]news/".$year."/".$str.".html'>".$text." (".$ct." article".($ct != 1 ? "s" : "").")</a>";
			}
			else
			{
				$data .= $text." (no news)";
			}

			$data .= "</li>";
		}

		$data .= "</ul>";

		return array("content" => $data, "crumbs" => $crumb, "filename" => $filename, "title" => $title);
	}

	protected function _generate_archive_month($year, $month, $articles)
	{
		$mtimestamp = strtotime($year."/".$month."/03 12:00pm");
		$mname = date("F", $mtimestamp);
		$filename = "news/".$year."/".$month.".html";
		$crumb = array("index" => "Welcome", "news/index" => "News Archive", "news/".$year => $year, "news/".$year."/".$month => $mname);
		$title = "News for ".$mname." ".$year;
		$data = "<h1>".$title."</h1>";

		$list = array();
		foreach($articles as $art)
		{
			$list[] = $year."/".$month."/".$art;
		}

		$data .= $this->_news_markup($list, false);
		return array("content" => $data, "crumbs" => $crumb, "filename" => $filename, "title" => $title);
	}

	public function generate_last_n($n = 5)
	{
		$data = array();

		$thisyear = date("Y");
		$thismonth = date("m");
		$thisday = date("d");

		$done = false;
		foreach($this->_month_years as $year => $monthdata)
		{
			if($year > $thisyear)
			{
				continue;
			}

			foreach($monthdata as $month => $articles)
			{
				if($year == $thisyear && $month > $thismonth)
				{
					continue;
				}

				foreach($articles as $day => $filename)
				{
					if($year == $thisyear && $month == $thismonth && $day > $thisday)
					{
						continue;
					}

					$data[] = $year."/".$month."/".$filename;

					if(count($data) >= $n)
					{
						$done = true;
						break;
					}
				}

				if($done)
				{
					break;
				}
			}

			if($done)
			{
				break;
			}
		}

		return $this->_news_markup($data);
	}

	protected function _news_markup($list, $split = true)
	{
		$data = "";
		$counter = 0;
		foreach($list as $file)
		{
			list($title, $content) = explode("%%%", file_get_contents($this->_filepath.$file), 2);
			$title = trim($title);
			$content = trim($content);

			list($year, $month, $fname) = explode("/", $file, 3);
			list($day, $ext) = explode(".", $fname, 2);
			$timestamp = strtotime($year."/".$month."/".$day." 12:00pm");

			$data .= "<div class='news'>";
			$data .= "<h2>".$title."</h2>";
			$data .= "<span class='byline'>".date("D M jS, Y", $timestamp)."</span>"; //l F js, Y

			if($split)
			{
				$content_rml = explode("[split here]", $content, 2);
				$data .= $content_rml[0];
				if(count($content_rml) != 1 && !empty($content_rml[1]))
				{
					$data .= "<p class='link' id='news_article_".$counter."_more'><a href='[PREFIX]news/".$year."/".$month.".html' onclick='philsci.show_more(".$counter."); return false;'>read more...</a></p>";
					$data .= "<p class='link hidden' id='news_article_".$counter."_less'><a href='[PREFIX]news/".$year."/".$month.".html' onclick='philsci.show_less(".$counter."); return false;'>...read less</a></p>";
					$data .= "<div class='hidden' id='news_article_".$counter."'>";
					$data .= $content_rml[1];
					$data .= "</div>";

					$counter++;
				}
			}
			else
			{
				$data .= preg_replace("#\[split here\]#", "", $content);
			}
			$data .= "</div>";
		}

		return $data;
	}

}
