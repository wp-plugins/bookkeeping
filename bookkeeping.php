<?php
/*
Plugin Name: Bookkeeping
Plugin URI: http://samwilson.id.au/plugins/bookkeeping/
Description: A personal financial bookkeeping system.
Version: 0.3
Author: Sam Wilson
Author URI: http://samwilson.id.au/
*/

$bookkeeping_version = '0.3';

add_action('admin_menu', 'bookkeeping_menus');
function bookkeeping_menus() {
	add_menu_page('Bookkeeping', 'Bookkeeping', 'administrator', 'bookkeeping/bookkeeping.php', 'bookkeeping_overview');
	add_submenu_page('bookkeeping/bookkeeping.php', 'Payments', 'Payments', 'administrator', 'bookkeeping_payments', 'bookkeeping_payments');
	add_submenu_page('bookkeeping/bookkeeping.php', 'Receipts', 'Receipts', 'administrator', 'bookkeeping_receipts', 'bookkeeping_receipts');
	add_submenu_page('bookkeeping/bookkeeping.php', 'Invoices', 'Invoices', 'administrator', 'bookkeeping_invoices', 'bookkeeping_invoices');
}

function bookkeeping_overview() {
    echo "<div class='wrap'>
    	<h2>Monthly Summaries</h2>
    	<h3>Payments</h3>";
    _bookkeeping_print_summary_table('payment');
    echo "<h3>Receipts</h3>";
   	_bookkeeping_print_summary_table('receipt');
   	echo "</div>";
} // end function bookkeeping_overview()

function _bookkeeping_print_summary_table($transaction_type) {
	global $wpdb;
	$table_name = $wpdb->prefix . "bookkeeping_journal";
	$cats = _bookkeeping_get_categories($transaction_type);

	echo '<table class="bookkeeping-journal"><tr class="tophead"><th></th>';
	foreach ($cats as $cat) {
		echo "<th>$cat</th>";
	}
	echo "<th>Totals</th></tr>";
	$sql = "SELECT MONTHNAME(date) AS month, YEAR(date) AS year, SUM(amount) AS total
	FROM $table_name WHERE transaction_type='$transaction_type' GROUP BY MONTHNAME(date) ORDER BY date DESC";
	$results = $wpdb->get_results($sql);
	foreach ($results as $month) {
		echo "<tr><td>" . $month->month . " " . $month->year . "</td>";
		foreach ($cats as $cat) {
			$sql = "SELECT SUM(amount) FROM $table_name
				WHERE transaction_type='$transaction_type' AND MONTHNAME(date)='" . $month->month . "'
				AND category='$cat' GROUP BY category";
			echo "<td>" . $wpdb->get_var($sql) . "</td>";
		}
		echo "<td>" . $month->total . "</td></tr>";
	}
	echo "<tr class='last-row'><td colspan='".(count($cats)+2)."'></td></tr></table>";
}

function bookkeeping_payments() {
	return _bookkeeping_get_journal('payment');
}

function bookkeeping_receipts() {
	return _bookkeeping_get_journal('receipt');
}

function bookkeeping_invoices() {
    if (!function_exists('addressbook_getselect')) {
        echo '<div id="message" class="error fade"><p><strong>You must install and activate
              the <a href="http://samwilson.id.au/blog/addressbook-plugin">addressbook
              plugin</a> to use invoices.</strong></p></div>';
    } else {    
        ?><div class="wrap">
        <form action="" method="post">
        From: <?php echo addressbook_getselect('from-address'); ?>
        To: <?php echo addressbook_getselect('to-address'); ?>
        <input type="submit" value="Generate Invoice" />
        </form>
        </div><?php
    }
}

add_action('admin_head', 'bookkeeping_adminhead');
function bookkeeping_adminhead() {
    date_default_timezone_set('Australia/Canberra');

    echo '<style type="text/css">
    
    div.bookkeeping-journal-nav {margin:1em auto}
    div.bookkeeping-journal-nav p {text-align:center}
    div.bookkeeping-journal-nav ol {list-style-type:none; border-bottom:1px solid #ccc; width:100%}
    div.bookkeeping-journal-nav li {display:inline}
    div.bookkeeping-journal-nav li a {padding:0 1em; border:1px solid #ccc; margin:0 0.3em;
        position:relative; text-decoration:none}
    div.bookkeeping-journal-nav li.curr a {border-bottom-color:#f1f1f1}
    div.bookkeeping-journal-nav li a:hover {background-color:#DDEAF4}
    
    table.bookkeeping-journal {border-collapse:collapse; margin:auto; border:1px solid red; width:98%}
    table.bookkeeping-journal td {border-bottom:1px solid lightblue; border-left:1px solid red; 
        border-right:1px solid red; width:auto; padding:0; margin:0; text-align:right}
    table.bookkeeping-journal tr.tophead th {border-bottom:3px double red; border-top:1px solid red}
    table.bookkeeping-journal tr.bottomhead th {border-top:3px double red; border-bottom:1px solid red; text-align:right}
    table.bookkeeping-journal tr.last-row td {border-bottom:1px solid red}
    table.bookkeeping-journal input {width:98%; margin:0 auto; border:0; background-color:inherit}
    table.bookkeeping-journal input.checkbox {width:auto; margin:auto}
    
    </style>';
}

function _bookkeeping_get_journal_header() {

	$curr_year = (isset($_GET['y'])) ? $_GET['y'] : date('Y');
	$curr_month = (isset($_GET['m'])) ? $_GET['m'] : date('m');
	$next_year = $curr_year + 1;
	$prev_year = $curr_year - 1;

    // Build labels
    if ($curr_month>6) {
        $prev_label = substr($curr_year-1,2).'/'.substr($curr_year,2);
        $curr_label = substr($curr_year,2).'/'.substr($curr_year+1,2);
        $next_label = substr($curr_year+1,2).'/'.substr($curr_year+2,2);
    } else {
        $prev_label = substr($curr_year-2,2).'/'.substr($curr_year-1,2);
        $curr_label = substr($curr_year-1,2).'/'.substr($curr_year,2);
        $next_label = substr($curr_year,2).'/'.substr($curr_year+1,2);
    }

    $base_uri = admin_url("admin.php?page=".$_GET['page']);
    $out = "<div class='bookkeeping-journal-nav'>
        <p>
        <span class='prev'><a href='$base_uri&y=$prev_year&m=".$curr_month."'>&laquo; $prev_label</a></span>
        <strong class='curr'>$curr_label</strong>
        <span class='next'><a href='$base_uri&y=$next_year&m=".$curr_month."'>$next_label &raquo;</a></span>
        </p>";
    $months = array(7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec',1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun');
    $out .= "<ol>";
    foreach ($months as $month_num=>$month_name) {
        if ($curr_month>6  && $month_num>6) $y=$curr_year;
        if ($curr_month<=6 && $month_num>6) $y=$curr_year-1;
        if ($curr_month>6 && $month_num<=6) $y=$curr_year+1;
        if ($curr_month<=6 && $month_num<=6) $y=$curr_year;
        if ($curr_month==$month_num) $out .= "<li class='curr'><a>$month_name</a></li>";
        else $out .= "<li><a href='$base_uri&m=$month_num&y=$y'>$month_name</a></li>";
    }
    $out .= "</ol></div>";
    return($out);
} // end _bookkeeping_get_journal_header()

function _bookkeeping_get_journal($transaction_type) {
    global $wpdb;
    $table_name = $wpdb->prefix."bookkeeping_journal";

	if (isset($_POST['add'])) {
		_bookkeeping_add_journal_entry($_POST);
	}

	$curr_year = (isset($_GET['y'])) ? $_GET['y'] : date('Y');
	$curr_month = (isset($_GET['m'])) ? $_GET['m'] : date('m');

    // Categories
    $cats = _bookkeeping_get_categories($transaction_type);
    $cat_headers = "";
    foreach ($cats as $cat) {
    	$cat_headers .= "<th>$cat</th>";
    }

	if ($transaction_type=='payment') {
		$receipt_or_invoice = 'Receipt?';
	} else {
		$receipt_or_invoice = 'Invoice?';
	}
    // Journal table
    $table = "<tr class='tophead'><th>Date</th><th>Comments</th>$cat_headers<th>Method</th><th>$receipt_or_invoice</th>
        <th colspan='2'>Business Use Component</th></tr>";
    $sql = "SELECT * FROM $table_name
                     WHERE transaction_type='$transaction_type' AND MONTH(date)='$curr_month' AND YEAR(date)='$curr_year'
                     ORDER BY date ASC";
    $results = $wpdb->get_results($sql);
    foreach ($results as $row) {
        if ($row->has_receipt_or_invoice==1) {
        	$row->has_receipt_or_invoice='Yes';
        } else {
        	$row->has_receipt_or_invoice = 'No';
        }
        $table .= "<tr><td>".$row->date."</td><td>".stripslashes($row->comments)."</td>";
        foreach ($cats as $cat) {
            if ($row->category==$cat) $table .= "<td>".$row->amount."</td>";
            else $table .= "<td></td>";
        }
        $table .= "<td>".$row->method."</td>
                   <td>".$row->has_receipt_or_invoice."</td>
                   <td>".(100-$row->private_use_component)."%</td>
                   <td>".number_format((1-($row->private_use_component/100))*$row->amount, 2)."</td>
                   </tr>";
    }
    // Column totals:
    $table .= "<tr class='bottomhead'><th colspan='2'>Totals:</th>";
    foreach ($cats as $cat) {
        $sql = "SELECT SUM(amount) AS total FROM $table_name
            WHERE transaction_type='$transaction_type' AND MONTH(date)='$curr_month' AND YEAR(date)='$curr_year'
            AND category='$cat' GROUP BY category";
        $table .= "<th>".$wpdb->get_var($sql)."</th>";
    }
    $sql = "SELECT SUM((1-(private_use_component/100))*amount) AS total FROM $table_name
            WHERE transaction_type='$transaction_type' AND MONTH(date)='$curr_month' AND YEAR(date)='$curr_year'";
    $total_business_use = number_format($wpdb->get_var($sql), 2);
    $table .= "<th></th><th></th><th></th><th>$total_business_use</th></tr>";

    $cat_colspan = round(count($cats)/2);
    $amt_colspan = count($cats) - $cat_colspan;
    print("<form method='post' action='admin.php?page=".$_GET['page']."&m=$curr_month&y=$curr_year'>".
        _bookkeeping_get_journal_header().
        "<table class='bookkeeping-journal'>$table
        <tr class='tophead' style='background-color:lightgreen'>
            <th>Date</th>
            <th>Comments</th>
            <th colspan='$cat_colspan'>Category</th>
            <th colspan='$amt_colspan'>Amount</th>
            <th>Method</th>
            <th>$receipt_or_invoice</th>
            <th colspan='2'>Private Use Component (%)</th>
        </tr>
        <tr style='background-color:lightgreen' class='last-row'>
            <td><input type='text' name='date' size='1' value='".date('Y-m-d')."' /></td>
            <td><input type='text' name='comments' size='1' /></td>
            <td colspan='$cat_colspan'><input type='text' name='category' size='1' /></td>
            <td colspan='$amt_colspan'><input type='text' name='amount' size='1' /></td>
            <td><input type='text' name='method' size='1' value='Cash' /></td>
            <td><input class='checkbox' type='checkbox' size='1' name='has_receipt_or_invoice' /></td>
            <td colspan='2'><input type='text' name='private_use_component' size='1' value='100' /></td>
        </tr>
        </table>
        <p class='submit'>
        	<input type='hidden' name='transaction_type' value='$transaction_type' />
        	<input type='submit' name='add' value='Add &raquo;' />
        </p>
        </form>");
            
} // end function bookkeeping_payments()

/**
 * Get all of the different categories that have ever been defined for a
 * particular transaction type.
 *
 * @param $transaction_type string Either 'payment' or 'receipt'.
 * @return array Numerically-indexed array of category name strings.
 */
function _bookkeeping_get_categories($transaction_type) {
    global $wpdb;
    $table_name = $wpdb->prefix."bookkeeping_journal";
    $sql = "SELECT category FROM $table_name WHERE transaction_type='$transaction_type' GROUP BY category";
    $results = $wpdb->get_results($sql);
    $cats = array();
    foreach ($results as $cat) {
        $cats[] = $cat->category;
    }
	return $cats;
} // _bookkeeping_get_categories($transaction_type)

function _bookkeeping_add_journal_entry($data) {
	global $wpdb;

	if (isset($data['has_receipt_or_invoice']) && $data['has_receipt_or_invoice']=='on') {
		$data['has_receipt_or_invoice'] = 1;
	} else {
		$data['has_receipt_or_invoice'] = 0;
	}

	$data = array(
		'transaction_type' => $data['transaction_type'],
		'date' => $data['date'],
		'category' => $data['category'],
		'method' => $data['method'],
		'has_receipt_or_invoice' => $data['has_receipt_or_invoice'],
		'amount' => $data['amount'],
		'private_use_component' => $data['private_use_component'],
		'comments' => $data['comments'],
	);
	$wpdb->insert($wpdb->prefix.'bookkeeping_journal', $data);

} // end _bookkeeping_add_journal_entry()


register_activation_hook(__FILE__, '_bookkeeping_install');
function _bookkeeping_install() {
    global $wpdb, $bookkeeping_version;
    $table_name = $wpdb->prefix."bookkeeping_journal";
    $sql = "CREATE TABLE IF NOT EXISTS " . $table_name . " (
		id int(10) NOT NULL auto_increment,
		date date NOT NULL,
		transaction_type enum('payment','receipt') NOT NULL,
		category varchar(50) NOT NULL,
		amount decimal(10,2) NOT NULL,
		private_use_component int(3) NOT NULL,
		comments varchar(100) NOT NULL,
		has_receipt_or_invoice int(1) NOT NULL default '0',
		method varchar(50) NOT NULL,
		PRIMARY KEY  (`id`)
    );";
    require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
    dbDelta($sql);
    update_option('bookkeeping_version', $bookkeeping_version);
}
