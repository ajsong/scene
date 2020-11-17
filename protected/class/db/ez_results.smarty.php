<?php
// ==================================================================
//  Author:    Justin Vincent (justin@visunet.ie)
//	Web:       http://php.justinvincent.com
//	Name:      EZ Results
// 	Desc:      Class to make it fast and easy to display results on your website.
//  Licence:   LGPL (No Restrictions)
//	Version:   1.1

// ==================================================================
//  Modifications by Steve Warwick Ph.D. (smarty@clickbuild.com)

// 	Name: Smart EZ Results
//  Purpose: modify this class for use with Smarty (smarty.php.net)
// 	Desc: Reduced the class to provide only the results set as an array.
//		  The only config variables required are ones to modify the data
//	      inside a link, such as naming of the link and a CSS class//
//  Licence:   LGPL (No Restrictions)
//	Version:   1.1

// added current_page to the results set as it is useful in Smarty

/* output:

print_r:
Array
(
	[num_records] => 56
	[num_pages] => 6
	[first_page] => <<
	[prev] => <
	[nav] => 1 2 3 4 5
	[next] => >
	[last_page] => >>
	[current_page] => 1
)

Set the names of the links (not the numbers) using
set_name_x()

set_name_first('name')
set_name_last('name')
set_name_prev('name')
set_name_next('name')

To set the other variables such as num_results_per_page etc. It is still
non OO $obj->num_results_per_page='x' -- see the vars.

I have decided to be different on the stylesheets. Rather than poking around
in the class to format the output, the style sheet names are pre-defined.
The format is: 'ezr_' + name of the returned variable.
For example ezr_first_page for the first_page variable and so on. If the
result will be both linked and not linked, then the unlinked stylesheet name
has _na appended eg, ezr_first_page_na

# num_records & num_pages are never links but to maintain a uniform interface
they have a style available as well.

here is an ultra simple stylesheet example
.ezr_num_records { font-weight: bold }
.ezr_num_pages { font-weight: bold }
.ezr_first_page { font-weight: bold }
.ezr_first_page_na {  }
.ezr_back { font-weight: bold }
.ezr_back_na {  }
.ezr_nav { font-weight: bold }
.ezr_nav_na {  }
.ezr_next { font-weight: bold }
.ezr_next_na {  }
.ezr_last_page { font-weight: bold }
.ezr_last_page_na {  }

WHen a result set is at the beginning or at the end I have it set so that nothing
is displayed for those items (currently commented out. To have the first, last,
next, prev always displayed, uncomment the else sections.
*/

class smart_ez_results
{
	
	/********************************************************
	 *	BASIC SETTINGS
	 */
	var $ez_sql_object        = NULL;
	var $num_results_per_page = 10;
	var $num_browse_links     = 5;
	var $hide_results         = false;
	var $set_num_results      = 0;
	var $cur_num_results      = 0;
	var $is_show_jump_page    = true;
	var $rewrite_page         = '';
	var $navigation           = '';
	var $navigations          = array();
	var $pagename             = '';
	
	/********************************************************
	 *	$ez_results->ez_results
	 *
	 *	Constructor. Allows users to use ez_sql object other than the std $db->
	 */
	function __construct($ez_sql_object='db')
	{
		$this->ez_sql_object = isset(${$ez_sql_object}) ? ${$ez_sql_object} : NULL ;
		
		// Stop annoying warnign message that comes up in new versions of PHP
		ini_set('allow_call_time_pass_reference', true);
		$this->set_name_first();
		$this->set_name_last();
		$this->set_name_prev();
		$this->set_name_next();
		
		$url = preg_replace('/^\/index\.php\//', '/', $_SERVER['PHP_SELF']).(strlen($_SERVER['QUERY_STRING'])?'?'.$_SERVER['QUERY_STRING']:'');
		$url = str_replace('/&', '/?', preg_replace('/^\/index\.php\?s=\/?/', '/', $url));
		$this->pagename = preg_replace('/^\/(\w+)&/', '/$1?', $url);
	}
	
	/********************************************************
	 *	$ez_results->set_name_x
	 *
	 *	USed to set the link names to defaults or custom
	 */
	function set_name_first($x='')
	{
		if ($x=='') {
			$this->first_page = 'First';
		} else {
			$this->first_page = $x;
		}
	}
	function set_name_prev($x='')
	{
		if ($x=='') {
			$this->prev = '<font style=font-size:10px;><</font>';
		} else {
			$this->prev = $x;
		}
	}
	function set_name_next($x='')
	{
		if ($x=='') {
			$this->next = '<font style=font-size:10px;>></font>';
		} else {
			$this->next = $x;
		}
	}
	function set_name_last($x='')
	{
		if ($x=='') {
			$this->last_page = 'Last';
		} else {
			$this->last_page = $x;
		}
	}
	
	/********************************************************
	 *	$ez_results->query_mysql
	 *
	 *	Perform results query (mysql & ezSQL) can use normal queries
	 *
	 *	$query = 'SELECT user, name, password FROM users'
	 */
	
	function query_mysql($query, $param=array())
	{
		// make sure that start row is set to zero if first call
		$this->init_start_row();
		
		// get total number of results
		$this->get_num_results($query);
		
		// Do query
		$this->results = $this->ez_sql_object->get_results($query . " LIMIT $this->num_results_per_page OFFSET {$_REQUEST['BRSR']}", $param, ARRAY_A);
		
		$this->cur_num_results = count($this->results);
	}
	
	/********************************************************
	 *	$ez_results->query_oracle
	 *
	 *	Perform an an oracle query.
	 *	Needs to be split up due to nature of oracle.
	 *
	 *	This only works for single table queries
	 *
	 *	$table       = 'TABLE_NAME'
	 *	$field_list  = 'ID, USER, PASSWORD' (must not be *)
	 *	$where       = 'ID > 50 AND ID < 60'
	 *	$order_by    = 'USER DESC'
	 */
	function query_oracle($table,$field_list,$where=false,$order_by=false)
	{
		// Make sure that start row is set to zero if first call
		$this->init_start_row();
		
		// Count total number of results
		$this->get_num_results("SELECT count(*) FROM $table ".($where?"WHERE $where ":NULL));
		
		// Do query
		$this->results = $this->ez_sql_object->get_results("SELECT $field_list FROM (SELECT ROWNUM ROW_A, $field_list FROM $table ".($where?"WHERE $where ":NULL)." ".($order_by?"ORDER BY $order_by":NULL).") B WHERE B.ROW_A >= ".$_REQUEST['BRSR']." AND B.ROW_A <= ".($_REQUEST['BRSR']+$this->num_results_per_page),ARRAY_N);
		
		$this->cur_num_results = count($this->results);
	}
	
	/********************************************************
	 *	$ez_results->get
	 *
	 *	Main function to get and format the results.
	 *	This function returns results rather than prints them to screen.
	 *
	 * - Steve Warwick: renamed/edited to match and behave  like theEzSQL function
	 *	makes interchange easier
	 */
	function get_results($q, $param=array(), $ez_sql_object=NULL)
	{
		if ($ez_sql_object) $this->ez_sql_object = $ez_sql_object;
		$this->query_mysql($q, $param);
		return $this->results;
	}
	
	/********************************************************
	 *	$ez_results->set_qs_val
	 *
	 *	Appends values to the GET query string to be carried over
	 *	during browsing - useful to change order by etc
	 */
	var $qs;
	function set_qs_val($name, $val)
	{
		if ($name != 'BRSR') {
			if (is_array($val)) {
				$vals = array();
				foreach ($val as $v) {
					//$vals[] = urlencode($v);
				}
				//$this->qs[$name] = $vals;
				$this->qs[$name] = $val;
			} else {
				//$this->qs[$name] = urlencode($val);
				$this->qs[$name] = $val;
			}
		}
	}
	
	/********************************************************
	 *	$ez_results->debug
	 *
	 *	Maps out this object and all values it contains
	 */
	function debug()
	{
		print '<pre>';
		print_r($this);
		print '</pre>';
	}
	
	/********************************************************
	 *	$ez_results->build_navigation (internal)
	 *
	 *	Main function that builds the result output.
	 *	(Note: print out is returned not printed)
	
	change the functionality - each result item is pushed into a variable
	 */
	function build_navigation()
	{
		//init
		$this->RESULT_SET = array(
			'num_records'=>'',
			'num_pages'=>'',
			'first_page'=>'',
			'prev'=>'',
			'prev_section'=>'',
			'nav'=>'',
			'next'=>'',
			'next_section'=>'',
			'last_page'=>'',
			'current_page'=>'',
			'jump_page'=>'',
		);
		$this->RESULTS_SET = array(
			'num_records'=>'',
			'num_pages'=>'',
			'current_page'=>''
		);
		
		// This is for if we are just using the navigation part
		if ( ! isset($this->num_results) ) {
			$this->num_results = $this->set_num_results;
		}
		
		// Calculate number of pages (of results)
		$this->num_pages = ($this->num_results - ($this->num_results % $this->num_results_per_page)) / $this->num_results_per_page;
		if ( $this->num_results % $this->num_results_per_page ) {
			$this->num_pages++;
		}
		
		// Calculate which page we are browsing
		$this->cur_page = ($_REQUEST['BRSR'] - ( $_REQUEST['BRSR'] % $this->num_results_per_page )) / $this->num_results_per_page;
		
		// Calculate which set of $this->num_browse_links we are browsing
		$this->cur_page_set = ($this->cur_page - ( $this->cur_page % $this->num_browse_links )) / $this->num_browse_links;
		
		// addition; steve warwick --
		// if the num pages are less than the num links make browse links
		// eq the num pages - ensures consistent navigation output
		if ($this->num_pages <= $this->num_browse_links) {
			$this->num_browse_links = $this->num_pages;
		}
		
		// Output total num records if required
		$this->RESULT_SET['num_records'] = '<span class="ezr_num_records">' . $this->num_results . '</span>';
		$this->RESULTS_SET['num_records'] = intval($this->num_results);
		
		// Output num pages if required
		$this->RESULT_SET['num_pages'] = '<span class="ezr_num_pages">' . $this->num_pages . '</span>';
		$this->RESULTS_SET['num_pages'] = intval($this->num_pages);
		
		/* modification: Steve Warwick
		If the number of pages is one there is no need for prev/next
		links etc. Only return the Total and the number of pages
		*/
		if ($this->num_pages > 1) {
			// Output back to start page
			if ( $this->cur_page != 0 ) {
				$first_page = '<span class="ezr_nav_ge"></span><a href="';
				if ($this->rewrite_page=='') {
					$first_page .= preg_replace("/\?.*/",'',$this->pagename)
						. '?BRSR=0'.'&'.$this->_http_build_query();
				} else {
					$first_page .= preg_replace("/\[P\]/i", 0, $this->rewrite_page);
				}
				$first_page .= '" class="ezr_first_page">'.$this->first_page.'</a>';
				$this->RESULT_SET['first_page'] = $first_page;
			} else {
				$this->RESULT_SET['first_page'] = '<span class="ezr_nav_ge"></span>';
			}
			/*
			else {
				if ( $this->num_pages >= $this->num_browse_links ) {
					$this->RESULT_SET['first_page'] = '<span class="ezr_first_page_na">' . $this->first_page . '</span>';
				}
			}
			*/
			
			// Output back if not on first page
			if ( $this->cur_page != 0 ) {
				$prev = '<a href="';
				if ($this->rewrite_page=='') {
					$prev .= preg_replace("/\?.*/",'',$this->pagename)
						. '?BRSR='.($_REQUEST['BRSR']-$this->num_results_per_page).'&'.$this->_http_build_query();
				} else {
					$prev .= preg_replace("/\[P\]/i", $_REQUEST['BRSR']-$this->num_results_per_page, $this->rewrite_page);
				}
				$prev .= '" class="ezr_back">'.$this->prev.'</a>';
				$this->RESULT_SET['prev'] = $prev;
			}
			/*
			else {
				if ( $this->num_pages >= $this->num_browse_links ) {
					$this->RESULT_SET['prev']='<span class="ezr_back_na">' . $this->prev.'</span> ';
				}
			}
			*/
			
			if ( $this->cur_page_set*$this->num_browse_links >= $this->num_browse_links ) {
				$prev_section = '<a href="';
				if ($this->rewrite_page=='') {
					$prev_section .= preg_replace("/\?.*/",'',$this->pagename)
						. '?BRSR='.($this->cur_page_set*$this->num_browse_links*$this->num_results_per_page-$this->num_browse_links*$this->num_results_per_page).'&'.$this->_http_build_query();
				} else {
					$prev_section .= preg_replace("/\[P\]/i", $this->cur_page_set*$this->num_browse_links*$this->num_results_per_page-$this->num_browse_links*$this->num_results_per_page, $this->rewrite_page);
				}
				$prev_section .= '" class="ezr_back_section"></a>';
				$this->RESULT_SET['prev_section'] = $prev_section;
			}
			
			// Output nav links
			if ( $this->num_results > $this->num_results_per_page )
			{
				$nav = "";
				for ( $i=($this->cur_page_set*$this->num_browse_links); $i < ($this->cur_page_set*$this->num_browse_links) + $this->num_browse_links; $i++ ) {
					if ( ($i*$this->num_results_per_page) < $this->num_results ) {
						// if current page
						if ($i==$this->cur_page) {
							$nav .= '<span class="ezr_nav_na" data-limit="'.$this->num_results_per_page.'">'.($i+1).'</span> ';
						}
						// if a nav link
						else {
							$nav .= '<span><a href="';
							if ($this->rewrite_page=='') {
								$nav .= preg_replace("/\?.*/",'',$this->pagename)
									. '?BRSR='.($i*$this->num_results_per_page).'&'.$this->_http_build_query();
							} else {
								$nav .= preg_replace("/\[P\]/i", $i*$this->num_results_per_page, $this->rewrite_page);
							}
							$nav .= '" class="ezr_nav">'.($i+1).'</a></span> ';
						}
					}
				}
				$this->RESULT_SET['nav'] = $nav;
			}
			
			if ( $_REQUEST['BRSR'] < ($this->num_pages-1)*$this->num_results_per_page ) {
				$_BRSR = $_REQUEST['BRSR']+$this->num_browse_links*$this->num_results_per_page;
				if ( $_BRSR > ($this->num_pages-1)*$this->num_results_per_page ) {
					$_BRSR = ($this->num_pages-1)*$this->num_results_per_page;
				}
				$next_section = '<a href="';
				if ($this->rewrite_page=='') {
					$next_section .= preg_replace("/\?.*/",'',$this->pagename)
						. '?BRSR='.$_BRSR.'&'.$this->_http_build_query();
				} else {
					$next_section .= preg_replace("/\[P\]/i", $_BRSR, $this->rewrite_page);
				}
				$next_section .= '" class="ezr_next_section"></a>';
				$this->RESULT_SET['next_section'] = $next_section;
			}
			
			// Output Next (if not on last page and ther are more pages than cur page
			if ( ($this->num_pages >= $this->num_browse_links) && (($_REQUEST['BRSR'] + $this->num_results_per_page) < $this->num_results) ) {
				$next = '<a href="';
				if ($this->rewrite_page=='') {
					$next .= preg_replace("/\?.*/",'',$this->pagename)
						. '?BRSR='.($_REQUEST['BRSR']+$this->num_results_per_page).'&'.$this->_http_build_query();
				} else {
					$next .= preg_replace("/\[P\]/i", $_REQUEST['BRSR']+$this->num_results_per_page, $this->rewrite_page);
				}
				$next .= '" class="ezr_next">'.$this->next.'</a>';
				$this->RESULT_SET['next'] = $next;
			}
			/*
			else {
				if ( $this->num_pages >= $this->num_browse_links ) {
					$this->RESULT_SET['next'] = '<span class="ezr_next_na">' . $this->next.'</span> ';
				}
			}
			*/
			
			// Output last page
			if ( ($this->num_pages >= $this->num_browse_links) && (($_REQUEST['BRSR'] + $this->num_results_per_page) < $this->num_results) ) {
				$last_page = '<a href="';
				if ($this->rewrite_page=='') {
					$last_page .= preg_replace("/\?.*/",'',$this->pagename)
						. '?BRSR='.($this->num_pages*$this->num_results_per_page-$this->num_results_per_page).'&'.$this->_http_build_query();
				} else {
					$last_page .= preg_replace("/\[P\]/i", $this->num_pages*$this->num_results_per_page-$this->num_results_per_page, $this->rewrite_page);
				}
				$last_page .= '" class="ezr_last_page">'.$this->last_page.'</a>';
				$this->RESULT_SET['last_page'] = $last_page;
			}
			/*
			else {
				if ($this->num_pages >= $this->num_browse_links ) {
					$this->RESULT_SET['last_page'] = '<span class="ezr_last_page_na">' . $this->last_page.'</span> ';
				}
			}
			*/
			
			if ($this->is_show_jump_page) {
				$jump_page = '<span class="ezr_nav_ge"></span>
				<input type="text" class="ezr_input" />页
				<input type="button" value="确定" class="ezr_submit" onclick="jumpPage()" />
				<script>
				$(".ezr_input").on("keydown", function(e){
					e = e||window.event;
					let code = e.which||e.keyCode;
					if(code===13)jumpPage();
				});
				function jumpPage(){
					let num_page = '.$this->num_results_per_page.';
					let down_page = '.$this->num_results_per_page.';
					let pages = $(".ezr_input").val();
				    let pager = pages * num_page - down_page;
					location.href = "';
						if ($this->rewrite_page=='') {
							$jump_page .= preg_replace("/\?.*/",'',$this->pagename)
								. "?BRSR=\"+pager+\"&".$this->_http_build_query();
						} else {
							$jump_page .= preg_replace("/\[P\]/i", "'+pager+'", $this->rewrite_page);
						}
						$jump_page .= '";
				};
				</script>';
				$this->RESULT_SET['jump_page'] = $jump_page;
			}
			
		}
		$this->RESULT_SET['last_page'] .= $this->RESULT_SET['jump_page'];
		// can be userd for the page x of y style pagination
		$this->RESULT_SET['current_page'] = $this->cur_page+($this->num_pages>0?1:0);
		$this->RESULTS_SET['current_page'] = $this->RESULT_SET['current_page'];
		
		// the results are available thru $obj->ezr_results
	}
	
	function get_navigation()
	{
		if (strlen($this->navigation)) {
			return $this->navigation;
		} else {
			$this->init_start_row();
			$this->build_navigation();
			return $this->RESULT_SET;
		}
	}
	
	function get_navigations()
	{
		if (is_array($this->navigations) && count($this->navigations)) {
			return $this->navigations;
		} else {
			return $this->RESULTS_SET;
		}
	}
	
	function set_navigation($navigation)
	{
		$this->navigation = $navigation;
	}
	
	function set_navigations($navigations)
	{
		$this->navigations = $navigations;
	}
	
	/********************************************************
	 *	$ez_results->get_num_results (internal)
	 *
	 *	Count total results for this query
	 */
	
	// modified: Steve Warwick: added the s option to the preg so thatg the
	// dot will get newlines as well.
	function get_num_results($query)
	{
		if (preg_match("/SELECT DISTINCT\(/i", trim($query))) {
			$sql = preg_replace("/SELECT DISTINCT\(([^)]+)\).*\s+FROM\b/Uis", "SELECT COUNT(DISTINCT($1)) FROM", trim($query));
		} else {
			$sql = preg_replace("/^SELECT.*\s+FROM\b/Uis", "SELECT COUNT(*) FROM", trim($query));
			$sql = preg_replace("/ ORDER BY (\s*,?.+?(A|DE)SC)+/Uis", '', $sql);
			if (stripos($sql, 'GROUP BY')!==false) $sql = "SELECT COUNT(*) FROM ({$sql}) gb";
		}
		$this->num_results = $this->ez_sql_object->get_var( $sql );
	}
	
	/********************************************************
	 *	$ez_results->init_start_row (internal)
	 *
	 *	Internal function to make sure that start row is set to zero
	 */
	function init_start_row()
	{
		if (isset($_POST['BRSR'])) $_REQUEST['BRSR'] = $_POST['BRSR'];
		
		if (isset($_GET['BRSR'])) $_REQUEST['BRSR'] = $_GET['BRSR'];
		
		if (isset($_COOKIE['BRSR'])) $_REQUEST['BRSR'] = $_COOKIE['BRSR'];
		
		// browse results start row from GET, POST, COOKIE, etc
		if ( ! isset($_REQUEST['BRSR']) || !is_numeric($_REQUEST['BRSR']) ) {
			$_REQUEST['BRSR'] = 0;
		}
	}
	
	function _http_build_query()
	{
		$qs = is_array($this->qs) ? $this->qs : array();
		return http_build_query($qs);
	}
	
}

$ezr = new smart_ez_results();
