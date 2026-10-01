<?php
class PageNav
{
	var $total;
	var $perpage;
	var $current;
	var $url;
	function __construct($total_items, $items_perpage, $current_start, $start_name = "start", $extra_arg = "")
	{
		$this->total = intval($total_items);
		$this->perpage = intval($items_perpage);
		$this->current = intval($current_start);

		if (!empty($extra_arg) && (substr($extra_arg, -5) != '&amp;' || substr($extra_arg, -1) != '&')) {
			$extra_arg .= '&amp;';
		}

		$queryString = http_build_query([$start_name => '']);

		$this->url = $_SERVER['REQUEST_URI'] . '?' . $extra_arg . $queryString;
	}

	function renderNav($offset = 4)
	{
		if ($this->total < $this->perpage) {
			return;
		}

		$total_pages = ceil($this->total / $this->perpage);
		$ret = '';
		if ($total_pages > 1) {
			$ret = '';
			$prev = $this->current - $this->perpage;
			if ($prev >= 0) {
				$ret .= '<a href="' . $this->url . $prev . '"><u>&laquo;</u></a> ';
			}
			$counter = 1;
			$current_page = intval(floor(($this->current + $this->perpage) / $this->perpage));
			while ($counter <= $total_pages) {
				if ($counter == $current_page) {
					$ret .= '<b>(' . $counter . ')</b> ';
				} elseif (($counter > $current_page - $offset && $counter < $current_page + $offset) || $counter == 1 || $counter == $total_pages) {
					if ($counter == $total_pages && $current_page < $total_pages - $offset) {
						$ret .= '... ';
					}
					$ret .= '<a href="' . $this->url . (($counter - 1) * $this->perpage) . '">' . $counter . '</a> ';
					if ($counter == 1 && $current_page > 1 + $offset) {
						$ret .= '... ';
					}
				}
				$counter++;
			}
			$next = $this->current + $this->perpage;
			if ($this->total > $next) {
				$ret .= '<a href="' . $this->url . $next . '"><u>&raquo;</u></a> ';
			}
		}
		return $ret;
	}

	function renderNav1($offset = 4)
	{
		$ret = '<ul class="pagination">';

		$prev = $this->current - $this->perpage;
		if ($prev >= 0) {
			$ret .= '<li class="paginate_button previous" data-value="' . $prev . '"><a href="#">Trước</a></li>';
		} else {
			$ret .= '<li class="paginate_button previous disabled" data-value="' . $prev . '"><a href="#">Trước</a></li>';
		}
		$total_pages = ceil($this->total / $this->perpage);
		$counter = 1;
		$current_page = intval(floor(($this->current + $this->perpage) / $this->perpage));
		while ($counter <= $total_pages) {
			if ($counter == $current_page) {
				$ret .= '<li class="paginate_button active" data-value="' . ($counter - 1) * $this->perpage . '"><a href="#" tabindex="0">' . $counter . '</a></li>';
			} elseif (($counter > $current_page - $offset && $counter < $current_page + $offset) || $counter == 1 || $counter == $total_pages) {
				if ($counter == $total_pages && $current_page < $total_pages - $offset) {
					$ret .= '... ';
				}
				$ret .= '<li class="paginate_button" data-value="' . ($counter - 1) * $this->perpage . '"><a href="#" tabindex="0">' . $counter . '</a></li>';
				if ($counter == 1 && $current_page > 1 + $offset) {
					$ret .= '... ';
				}
			}
			$counter++;
		}
		$next = $this->current + $this->perpage;
		if ($this->total > $next) {
			$ret .= '<li class="paginate_button next" data-value="' . $next . '" ><a href="#" tabindex="0">Tiếp</a></li>';
		} else {
			$ret .= '<li class="paginate_button next disabled" data-value="' . $next . '"><a href="#" tabindex="0">Tiếp</a></li>';
		}
		$ret .= '</ul>';
		return $ret;
	}

	function renderSelect($showbutton = false)
	{
		if ($this->total < $this->perpage) {
			return;
		}
		$total_pages = ceil($this->total / $this->perpage);
		$ret = '';
		if ($total_pages > 1) {
			$ret = '<form name="pagenavform">';
			$ret .= '<select name="pagenavselect" onchange="location=this.options[this.options.selectedIndex].value;">';
			$counter = 1;
			$current_page = intval(floor(($this->current + $this->perpage) / $this->perpage));
			while ($counter <= $total_pages) {
				if ($counter == $current_page) {
					$ret .= '<option value="' . $this->url . (($counter - 1) * $this->perpage) . '" selected="selected">' . $counter . '</option>';
				} else {
					$ret .= '<option value="' . $this->url . (($counter - 1) * $this->perpage) . '">' . $counter . '</option>';
				}
				$counter++;
			}
			$ret .= '</select>';
			if ($showbutton) {
				$ret .= '&nbsp;<input type="submit" vallue="' . _GO . '" />';
			}
			$ret .= '</form>';
		}
		return $ret;
	}

	function renderImageNav($offset = 4)
	{
		if ($this->total < $this->perpage) {
			return;
		}
		$total_pages = ceil($this->total / $this->perpage);
		$ret = '';
		if ($total_pages > 1) {
			$ret = '<table><tr>';
			$prev = $this->current - $this->perpage;
			if ($prev >= 0) {
				$ret .= '<td class="pagneutral"><a href="' . $this->url . $prev . '">&lt;</a></td><td><img src="' . _IMAGE_URL . '/images/blank.gif" width="6" alt="" /></td>';
			}
			$counter = 1;
			$current_page = intval(floor(($this->current + $this->perpage) / $this->perpage));
			while ($counter <= $total_pages) {
				if ($counter == $current_page) {
					$ret .= '<td class="pagact"><b>' . $counter . '</b></td>';
				} elseif (($counter > $current_page - $offset && $counter < $current_page + $offset) || $counter == 1 || $counter == $total_pages) {
					if ($counter == $total_pages && $current_page < $total_pages - $offset) {
						$ret .= '<td class="paginact">...</td>';
					}
					$ret .= '<td class="paginact"><a href="' . $this->url . (($counter - 1) * $this->perpage) . '">' . $counter . '</a></td>';
					if ($counter == 1 && $current_page > 1 + $offset) {
						$ret .= '<td class="paginact">...</td>';
					}
				}
				$counter++;
			}
			$next = $this->current + $this->perpage;
			if ($this->total > $next) {
				$ret .= '<td><img src="' . _IMAGE_URL . '/images/blank.gif" width="6" alt="" /></td><td class="pagneutral"><a href="' . $this->url . $next . '">&gt;</a></td>';
			}
			$ret .= '</tr></table>';
		}
		return $ret;
	}
}

?>