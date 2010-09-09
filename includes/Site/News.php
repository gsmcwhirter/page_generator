<?php

class Site_News
{

	protected $_menu;
	protected $_filepath;
	protected $_menuitem;
	protected $_newsfile;
	protected $_month_years = array();
	protected $_rss_title;
	protected $_rss_description;

	public function __construct($menuitem, Site_Menu &$menu, Settings $settings)
	{
		$this->_menu = $menu;
		$this->_filepath = $settings->content_file_path."news/";
		$this->_menuitem = $menuitem;
		$this->_rss_description = $settings->rss_description;
		$this->_rss_title = $settings->rss_title;

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
								$days = explode("-", $day, 2);
								$day = $days[0];
								if(count($days) == 2 && array_key_exists($day, $this->_month_years[$year][$month]) && !is_array($this->_month_years[$year][$month][$day]))
								{
									//$this->_month_years[$year][$month][$day] = array(0 => $this->_month_years[$year][$month][$day]);
									throw new Exception("Error Parsing News: Multiple articles on the same day not all indexed -- ".$year."/".$month."/".$day);
								}
								elseif(count($days) == 2 && array_key_exists($day, $this->_month_years[$year][$month]))
								{
									$this->_month_years[$year][$month][$day][$days[1]] = $art;
									krsort($this->_month_years[$year][$month][$day]);
								}
								elseif(count($days) == 2)
								{
									$this->_month_years[$year][$month][$day] = array($days[1] => $art);
									krsort($this->_month_years[$year][$month][$day]);
								}
								else
								{
									$this->_month_years[$year][$month][$day] = $art;
								}

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
		$data = $this->_menu->output_menu();
		$this->_menu->reset_menu();

		return $data;
	}

	public function output_breadcrumbs($crumbs)
	{
		return $this->_menu->output_breadcrumbs($crumbs);
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
		$crumb = array("index.html" => "Announcements", "news/index.html" => "News Archives");
		$title = "News Archives";
		$data = "<h1>".$title."</h1>";
		$data .= "<ul class='archive_list'>";

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
				$data .= "<a href='[PREFIX]news/".$i."/index.html'>".$text."</a>";
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
		$filename = "news/".$year."/index.html";
		$crumb = array("index.html" => "Announcements", "news/index.html" => "News Archives","news/".$year."/index.html" => $year);
		$title = $year." News Archive";
		$data = "<h1>".$title."</h1>";
		$data .= "<ul class='archive_list'>";

		for($i = 1; $i < 13; $i++)
		{
			if($year == date("Y") && $i > date("m"))
			{
				break;
			}

			$str = $this->_zerofill($i);

			$timestamp = strtotime($year."/".$str."/03 12:00pm");
			$text = date("F", $timestamp);

			$ct = array_key_exists($str, $mdata) ? $this->_get_article_count($mdata[$str]) : 0;

			if($ct != 0)
			{
				$data .= "<li>";
				$data .= "<a href='[PREFIX]news/".$year."/".$str.".html'>".$text." ".$year." (".$ct." article".($ct != 1 ? "s" : "").")</a>";
				$data .= "</li>";
			}

			//$data .= "<li>";
			//$timestamp = strtotime($year."/".$str."/03 12:00pm");
			//$text = date("F", $timestamp);
			//
			//$ct = array_key_exists($str, $mdata) ? $this->_get_article_count($mdata[$str]) : 0;
			//if($ct != 0)
			//{
			//	$data .= "<a href='[PREFIX]news/".$year."/".$str.".html'>".$text." ".$year." (".$ct." article".($ct != 1 ? "s" : "").")</a>";
			//}
			//else
			//{
			//	$data .= $text." ".$year." (no news)";
			//}
			//
			//$data .= "</li>";
		}

		$data .= "</ul>";

		return array("content" => $data, "crumbs" => $crumb, "filename" => $filename, "title" => $title);
	}

	protected function _get_article_count($articles)
	{
		$count = 0;
		foreach($articles as $art)
		{
			if(is_array($art))
			{
				$count += count($art);
			}
			else
			{
				$count += 1;
			}
		}
		return $count;
	}

	protected function _generate_archive_month($year, $month, $articles)
	{
		$mtimestamp = strtotime($year."/".$month."/03 12:00pm");
		$mname = date("F", $mtimestamp);
		$filename = "news/".$year."/".$month.".html";
		$crumb = array("index.html" => "Announcements", "news/index.html" => "News Archives", "news/".$year."/index.html" => $year, "news/".$year."/".$month.".html" => $mname);
		$title = "News for ".$mname." ".$year;
		$data = "<h1>".$title."</h1>";

		$list = array();
		foreach($articles as $art)
		{
			if(is_array($art))
			{
				foreach($art as $index => $art2)
				{
					$list[] = $year."/".$month."/".$art2;
				}
			}
			else
			{
				$list[] = $year."/".$month."/".$art;
			}
		}

		$data .= $this->_news_markup($list, false);
		return array("content" => $data, "crumbs" => $crumb, "filename" => $filename, "title" => $title);
	}

	public function generate_rss()
	{
		$data = array();

		$thisyear = date("Y");
		$thismonth = date("m");
		$thisday = date("d");

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

					if(is_array($filename))
					{
						foreach($filename as $ind => $filename2)
						{
							$data[] = $year."/".$month."/".$filename2;
						}
					}
					else
					{
						$data[] = $year."/".$month."/".$filename;
					}

				}
			}
		}

		return $this->_rss_markup($data);
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

					if(is_array($filename))
					{
						foreach($filename as $ind => $filename2)
						{
							$data[] = $year."/".$month."/".$filename2;

							if(count($data) >= $n)
							{
								$done = true;
								break;
							}
						}

					}
					else
					{
						$data[] = $year."/".$month."/".$filename;
					}

					if($done || count($data) >= $n)
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
			$days = explode("-", $day, 2);
			$day = $days[0];
			if(count($days) == 2)
			{
				$ind = $days[1];
			}
			else
			{
				$ind = 0;
			}
			$timestamp = strtotime($year."/".$month."/".$day." 12:00pm");

			$data .= "<div class='news'>";
			$data .= "<a id='article-".$year."-".$month."-".$day."-".$ind."'> </a><h2>".$title."</h2>";
			$data .= "<span class='byline'>".date("D M jS, Y", $timestamp)."</span>"; //l F js, Y

			if($split)
			{
				$content_rml = explode("[split here]", $content, 2);
				$data .= $content_rml[0];
				if(count($content_rml) != 1 && !empty($content_rml[1]))
				{
					$data .= "<p class='link' id='news_article_".$counter."_more'><a href='[PREFIX]news/".$year."/".$month.".html#".$year."-".$month."-".$day."-".$ind."' onclick='philsci.show_more(".$counter."); return false;'>read more...</a></p>";
					$data .= "<p class='link hidden' id='news_article_".$counter."_less'><a href='[PREFIX]news/".$year."/".$month.".html#".$year."-".$month."-".$day."-".$ind."' onclick='philsci.show_less(".$counter."); return false;'>...read less</a></p>";
					$data .= "<div class='more' id='news_article_".$counter."'>";
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

	protected function _rss_markup($articles)
	{
		$data = '
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
	<channel>
		<atom:link href="[PREFIX][THISPAGE]" rel="self" type="application/rss+xml" />
		<title>'.$this->_rss_title.'</title>
		<link>[PREFIX]index.html</link>
		<description>'.$this->_rss_description.'</description>
		<lastBuildDate>'.gmdate("r").'</lastBuildDate>
		<language>en-us</language>
		<ttl>1440</ttl>';
		foreach($articles as $file)
		{
			list($title, $content) = explode("%%%", file_get_contents($this->_filepath.$file), 2);
			$title = trim($title);
			$content = preg_replace("#\[split here\]#", "", trim($content));

			list($year, $month, $fname) = explode("/", $file, 3);
			list($day, $ext) = explode(".", $fname, 2);
			$days = explode("-", $day, 2);
			$day = $days[0];
			if(count($days) == 2)
			{
				$ind = $days[1];
			}
			else
			{
				$ind = 0;
			}
			$timestamp = strtotime($year."/".$month."/".$day." 12:00pm");

			$data .= '
		<item>
			<title>'.$title.'</title>
			<link>[PREFIX]news/'.$year.'/'.$month.'.html#atricle-'.$year.'-'.$month.'-'.$day.'-'.$ind.'</link>
			<guid>[PREFIX]news/'.$year.'/'.$month.'.html#article-'.$year.'-'.$month.'-'.$day.'-'.$ind.'</guid>
			<pubDate>'.gmdate("r", $timestamp).'</pubDate>
			<description><![CDATA['.$content.']]></description>
			<author>[WEBMASTER] (Philosophy of Science Association)</author>
		</item>';
		}
		$data .= '
	</channel>
</rss>';

		return $data;
	}

}
