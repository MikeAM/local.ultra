<?php
error_reporting(E_PARSE);
if($_GET['_SESSION'] != '' || $_POST['_SESSION'] != '' || $_COOKIE['_SESSION'] != '') { exit; }


############################################################################################
## Soholaunch(R) Site Management Tool
## Version Ultra
##
## Author: 			Soholaunch.com, Inc.
## Homepage:	 	http://www.soholaunch.com
############################################################################################

############################################################################################
## COPYRIGHT NOTICE
## Copyright 1999-2011 Soholaunch.com, Inc.  All Rights Reserved.
##
## This script may be used and modified in accordance to the license
## agreement attached (license.txt) except where expressly noted within
## commented areas of the code body. This copyright notice and the comments
## comments above and below must remain intact at all times.  By using this
## code you agree to indemnify Soholaunch.com, Inc, its coporate agents
## and affiliates from any liability that might arise from its use.
##
## Selling the code for this program without prior written consent is
## expressly forbidden and in violation of Domestic and International
## copyright laws.
#############################################################################################

session_start();
error_reporting(E_PARSE);

# Include core interface files
require_once("../includes/product_gui.php");

if($_GET['logout'] == 'logout'){
	session_destroy();
	header("Location: ../../index.php");
	exit;	
}

function dash_edit_page_link($pagename) {
	$link = 'page_editor/page_editor.php?currentPage='.$pagename.'&nocache='.time();
	return $link;
}

if ( $_SESSION['wizard'] == '' ) {
	if ( !is_object($global_admin_prefs) ) {
		$global_admin_prefs = new userdata('admin');
	}
	$_SESSION['wizard'] = $global_admin_prefs->get('wizard');
}

ob_start();
echo "<link rel=\"stylesheet\" href=\"http://".$_SESSION['docroot_url']."/sohoadmin/program/modules/dashboard/dashboard-styles.css\">\n";

########################
### JqPlot Graph Stats #
#######################
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"http://".$_SESSION['docroot_url']."/sohoadmin/program/includes/jqPlot/jquery.jqplot.css\" />\n";
echo '<!--[if lt IE 9]><script language="javascript" type="text/javascript" src="http://'.$_SESSION['docroot_url'].'/sohoadmin/program/includes/jqPlot/excanvas.js"></script><![endif]-->'."\n";
//echo "<script src=\"sohoadmin/client_files/jquery.min.js\" type=\"text/javascript\"></script>\n";
echo "<script language=\"javascript\" type=\"text/javascript\" src=\"http://".$_SESSION['docroot_url']."/sohoadmin/program/includes/jqPlot/jquery.jqplot.js\"></script>\n";
echo "<script language=\"javascript\" type=\"text/javascript\" src=\"http://".$_SESSION['docroot_url']."/sohoadmin/program/includes/jqPlot/plugins/jqplot.highlighter.js\"></script>\n";
echo "<script language=\"javascript\" type=\"text/javascript\" src=\"http://".$_SESSION['docroot_url']."/sohoadmin/program/includes/jqPlot/plugins/jqplot.barRenderer.js\"></script>\n";
echo "<script language=\"javascript\" type=\"text/javascript\" src=\"http://".$_SESSION['docroot_url']."/sohoadmin/program/includes/jqPlot/plugins/jqplot.categoryAxisRenderer.js\"></script>\n";

echo "<link rel=\"stylesheet\" href=\"http://".$_SESSION['docroot_url']."/sohoadmin/program/modules/dashboard/prettyPhoto.css\" type=\"text/css\" media=\"screen\" charset=\"utf-8\" />\n";
echo "<script src=\"http://".$_SESSION['docroot_url']."/sohoadmin/program/modules/dashboard/jquery.prettyPhoto.js\" type=\"text/javascript\" charset=\"utf-8\"></script>\n";

$daysback = -9;

$stat_graph_qry = mysql_query("select distinct IP, Real_Date from stats_unique where Real_Date > '".date('Y-m-d', strtotime($daysback.' days'))."' order by Real_Date");
while($get_unique = mysql_fetch_assoc($stat_graph_qry)){
	$unique_viz[$get_unique['Real_Date']][$get_unique['IP']] = 1;
}

$stat_graph_qry = mysql_query("select Hits, Month, Day, Real_Date from stats_byday where Real_Date > '".date('Y-m-d', strtotime($daysback.' days'))."' order by Real_Date");
$hits1 = '';
$orders = '';
$dates = '';
$unique_peeps = '';
$do = ($daysback+1);
$weeklyhits = 0;
while($do <= 0){
	//$dates .= "'".date('m d', strtotime($do.' days'))."',";
	$datdsp = date('Y-m-d', strtotime($do.' days'));
	$hitz[$datdsp]['hits'] = '';
	$hitz[$datdsp]['unique'] = '';
	if($do == -1){
		$hitz[$datdsp]['date'] = lang('Yesterday');
	} elseif($do == 0){
		$hitz[$datdsp]['date'] = lang('Today');
	} else {
		$hitz[$datdsp]['date'] = date('M d', strtotime($do.' days'));	
	}
	$hitz[$datdsp]['unique'] = 0;
	$hitz[$datdsp]['hits'] = 0;
	++$do;
}

$getinvoices = mysql_query("select ORDER_NUMBER, ORDER_DATE, TRANSACTION_STATUS, TOTAL_SALE from cart_invoice where STR_TO_DATE(ORDER_DATE, '%m/%d/%Y') > '".date('Y-m-d', strtotime($daysback.' days'))."' and TRANSACTION_STATUS='Paid' order by ORDER_DATE");
$invoicecount = mysql_num_rows($getinvoices);
if($invoicecount > 0){
	while($gi = mysql_fetch_assoc($getinvoices)){
		$di = explode('/', $gi['ORDER_DATE']);
		$hitz[$di['2'].'-'.$di['0'].'-'.$di['1']]['orders'] = $hitz[$di['2'].'-'.$di['0'].'-'.$di['1']]['orders'] + 1;
	}
}
$startnow = (($daysback*-1)-7);
$mo = 0;
while($ss = mysql_fetch_assoc($stat_graph_qry)){
	$hitz[$ss['Real_Date']]['unique'] = count($unique_viz[$ss['Real_Date']]);
	$hitz[$ss['Real_Date']]['hits'] = $ss['Hits'];
	if($mo >= $startnow){
		$weeklyhits = $weeklyhits + $ss['Hits'];
	}
	++$mo;
}

foreach($hitz as $hvar=>$hval){
	$dates .= "'".$hval['date']."',";
	$unique_peeps .= $hval['unique'].',';
	$hits1 .= $hval['hits'].',';
	$orders .= $hval['orders'].',';
}
$hits1 = preg_replace('/,$/', '', $hits1);
$unique_peeps = preg_replace('/,$/', '', $unique_peeps);
$dates = preg_replace('/,$/', '', $dates);
if($invoicecount > 0){
	$orders = preg_replace('/,$/', '', $orders);
}
echo "<style>\n";
echo ".jqplot-table-legend { \n";
echo "	top:0px!important;\n";
echo "	left:10px!important;\n";
echo "}\n";
echo "</style>\n";
echo "<script type=\"text/javascript\">\n";
echo "var jLabel=new Array();\n";
echo "jLabel[0]='Page&nbsp;Views';\n";
echo "jLabel[1]='Unique&nbsp;Visitors';\n";
echo "jLabel[2]='Cart&nbsp;Orders';\n";
echo '$(document).ready(function(){'."\n";
echo '	var s1 = ['.$hits1.'];'."\n";
echo '	var s2 = ['.$unique_peeps.'];'."\n";
echo '	var s3 = ['.$orders.'];'."\n";
echo '	var ticks = ['.$dates.'];'."\n";
if($invoicecount > 0){
	echo '	plot2 = $.jqplot(\'chart1\', [s1, s2, s3], {'."\n";
} else {
	echo '	plot2 = $.jqplot(\'chart1\', [s1, s2], {'."\n";	
}

echo "		series: [ \n";
echo "		{label:'Page Views', color: '#0F70D3'},\n";
echo "		{label:'Unique Visitors', color: '#EAA510'}\n";
if($invoicecount > 0){
	echo "		,{label:'Cart Orders', color: '#019700'}\n";
}
echo "		],\n";
echo "		highlighter: {\n";
echo "			show: true,\n";
echo "			showMarker:false,\n";
echo "			tooltipOffset: 4,\n";
echo "			tooltipAxes: 'y',\n";
echo '			formatString:\'<table class="jqplot-highlighter"> \
			<tr><td><span id=\"contentLabel\"></span>:</td><td>%s</td></tr> \
			</table>\''."\n";
echo "		},\n";
echo "		cursor: {\n";
echo "			style: 'pointer',     // A CSS spec for the cursor type to change the\n";
echo "			show: false\n";
echo "		}, \n";
echo "		seriesDefaults: {\n";
echo '			renderer:$.jqplot.BarRenderer,'."\n";
echo "			pointLabels: { show: false }, \n";
echo "			shadow: true,\n";
echo "			shadowAlpha: 0.05 \n";
echo "		},\n";
echo "		axes: {\n";
echo "			xaxis: {\n";
echo '				renderer: $.jqplot.CategoryAxisRenderer,'."\n";
echo "				ticks: ticks\n";
echo "			}\n";
echo "		},\n";
echo "		legend: {\n";
echo "			show:true, \n";
//echo "			location: 'ne', \n";
echo "			yoffset: 42 \n";
//echo "			yoffset: 0 \n";
echo "		} \n";
echo "	});\n";
echo "	$('#chart1').bind('jqplotDataHighlight',\n";
echo "		function (ev, seriesIndex, pointIndex, data, radius) {   \n";
echo "			$('#contentLabel').html(jLabel[seriesIndex]);\n";
echo "	});\n";
echo "});\n";
echo "</script>\n";
#################
### End JqPlot ##
################

echo "<div style=\"position:relative;width:100%;\">\n";

if($_SESSION['play_welcome_sound']==''){
	echo "<audio autoplay=\"autoplay\">\n";
	echo "  <source src=\"http://".$_SESSION['docroot_url']."/sohoadmin/client_files/sohostart.wav\" type=\"audio/wav\" />\n";

	echo "</audio> \n"; 
	$_SESSION['play_welcome_sound'] = 'played';
}


$installed = update_avail();
//if($update_available == 'yes'){
if ( update_avail() && $_SESSION['hostco']['software_updates'] != "OFF" && $_SESSION['CUR_USER_ACCESS'] == "WEBMASTER" ) {
	echo "<div style=\"position:absolute; top:-30px; right:0px;\">";
	echo "&nbsp;&nbsp;A Version Update is Available! <font style=\"color:gray;\">[".$installed['build_name']."]</font>&nbsp;\n";
	echo "<a class=\"greenButton\" href=\"../webmaster/software_updates.php?todo=checknow\"><span>Update</span></a>\n";
	echo "</div>\n";
	//echo "A Version Update is Available!&nbsp;";
}


# What should I do next?
$donext_array = array();
$n = 0;
$donext_debug = false;


$spcRez = mysql_query("SELECT * from site_specs");
$getSpec = mysql_fetch_assoc($spcRez);

//# Email address
//if ( $getSpec['df_email'] == '' || $donext_debug ) {
//	$donext_array[$n]['title'] = 'Fill-in your webmaster email address';
//	$donext_array[$n]['link'] = '../webmaster/business_info.php';
//	$donext_array[$n]['description'] = 'This will be used to email your login password to you if you forget it, and will also be the default address in the "reply to" field on all emails that go out from your website.';
//	$n++;
//}
$gh = fopen($_SESSION['cgi_bin'].'/'.startpage().'.con', 'r');
$homecon = fread($gh, filesize($_SESSION['cgi_bin'].'/'.startpage().'.con'));
fclose($gh);
# Home page

if ( !file_exists($_SESSION['cgi_bin'].'/'.startpage().'.con') || preg_match('/Welcome to your new website/i', $homecon) || strlen(str_replace("\n", '', str_replace(' ', '', strip_tags($homecon)))) < 1 || $donext_debug ) {
	$donext_array[$n]['title'] = 'Add some content to your home page';
	$donext_array[$n]['link'] = dash_edit_page_link(startpage());
	$donext_array[$n]['description'] = 'Your home page is the first page people will see when they visit your site. Get started by adding some content to it!';
	$n++;
}

# Add Pages
$result = mysql_query("select prikey from site_pages where page_name!='Search'");
if ( mysql_num_rows($result) < 2 || $donext_debug ) {
	$donext_array[$n]['title'] = 'Create some more pages!';
	$donext_array[$n]['link'] = 'create_pages.php';
	$donext_array[$n]['description'] = 'You only have the default home page created right now. You can create as many pages as you want, so go add some more!';
	$n++;
}

# Setup Menu
$result2 = mysql_query('select prikey from site_pages where main_menu > 1');
if ( (mysql_num_rows($result2) == 0 && mysql_num_rows($result) < 2) || $donext_debug ) {
	$donext_array[$n]['title'] = 'Setup menu navigation for your site!';
	$donext_array[$n]['link'] = 'auto_menu_system.php';
	$donext_array[$n]['description'] = 'This will setup your website\'s menu navigation. Pick the pages that you want on your menu and set the order that you want them in!';
	$n++;
}

#Template
if(($globalprefObj->get('site_base_template')=='Professional-Cutting_Edge-blue' || $globalprefObj->get('site_base_template')=='') && ($globalprefObj->get('what_next_select_template')=='show' || $globalprefObj->get('what_next_select_template')=='')){
	$globalprefObj->set('what_next_select_template', 'show');
} else {
	$globalprefObj->set('what_next_select_template', 'hide');
}

if((($globalprefObj->get('site_base_template')=='Professional-Cutting_Edge-blue' || $globalprefObj->get('site_base_template')=='') && $globalprefObj->get('what_next_select_template')=='show') || $donext_debug){
	$donext_array[$n]['title'] = 'Choose your site template!';
	$donext_array[$n]['link'] = 'site_templates.php';
	$donext_array[$n]['description'] = 'Change the overall look & feel of your website by choosing a different template.';
	$n++;
}

# Title of website
if ( $getSpec['df_hdrtxt'] == '' || $getSpec['df_hdrtxt'] == 'Welcome' || $donext_debug) {
	$donext_array[$n]['title'] = 'Set website title';
	$donext_array[$n]['link'] = 'site_templates.php?showTab=tab2&go=site_titletxt';
	$donext_array[$n]['description'] = 'Give your website a title.  Usually your organization or company name. This title will appear in your website\'s template and at the top of emails sent out to site visitors (e.g., shopping cart receipts).';
	$n++;
}

if($getSpec['df_slogan'] == '' || $donext_debug) {
	$donext_array[$n]['title'] = 'Set website slogan';
	$donext_array[$n]['link'] = 'site_templates.php?showTab=tab2&go=site_df_slogan';
	$donext_array[$n]['description'] = 'Give your website a slogan.  This text will appear under your website\'s title.';
	$n++;
}


# Logo image
if ( $getSpec['df_logo'] == '' || $donext_debug ) {
	$donext_array[$n]['title'] = 'Upload a logo image';
	$donext_array[$n]['link'] = 'site_templates.php?showTab=tab2';
	$donext_array[$n]['description'] = 'One of the best ways to make your site look more professional is to add you company logo to it.';
	$n++;
}


echo "<script type=\"text/javascript\">\n";
echo "function dismiss_wizard() {\n";
echo '	$(\'#widget-whatshouldidonext\').hide(\'fade\', function() {'."\n";
echo '		$(\'#whatshouldidonext-button\').show(\'fade\');'."\n";
echo '	});'."\n";
echo '	$(\'#jqresult\').load(\'http://'.$_SESSION['docroot_url'].'/sohoadmin/program/includes/preference-saver.ajax.php?thing_id=wizard&show_or_hide=hide\', function() {'."\n";
echo '		return true;'."\n";
echo '	});'."\n";
echo '}'."\n";
echo 'function show_wizard() {'."\n";
echo '	$(\'#whatshouldidonext-button\').hide(\'fade\', function() {'."\n";
echo '		$(\'#widget-whatshouldidonext\').show(\'fade\');'."\n";
echo '	});	'."\n";
echo '	$(\'#jqresult\').load(\'http://'.$_SESSION['docroot_url'].'/sohoadmin/program/includes/preference-saver.ajax.php?thing_id=wizard&show_or_hide=show\', function() {'."\n";
echo '		return true;'."\n";
echo "	});	\n";
echo "}\n";
echo "</script>\n";


if ($_SESSION['wizard'] == 'hide'){
	$wiz_button = ' style="display: block;"';
	$wiz_widget = ' style="display: none;margin:0px 0px 5px 0px;"';
} else {
	$wiz_button = ' style="display: none;"';
	$wiz_widget = ' style="display: block;margin:0px 0px 5px 0px;"';
}	



$tutorial_video_link = 'http://www.youtube.com/embed/c5SEyfzxcSM?rel=0&hd=1&fmt=22&autoplay=1&modestbranding=1&title=';
echo "<div style=\"margin:0px 0px 5px 0px; float:left;clear:none;\">\n";
echo "	<a href=\"".$tutorial_video_link."&iframe=true&width=863&height=530\" rel=\"prettyPhoto[iframe]\" id=\"videobutton\" class=\"videobtn\"><span>Watch getting started video tutorial</span></a>\n";
echo "</div>\n";

echo "<div style=\"margin:0px 0px 5px 10px; float:left;clear:none;\">\n";
echo "	<a href=\"#\" id=\"whatshouldidonext-button\" class=\"videobtn\" onclick=\"show_wizard();\"".$wiz_button."><span>What should I do next?</span></a>\n";
echo "</div>\n";

if($n == 0){
	echo "<div style=\"display:none;\">\n";	
} else {
	echo "<div style=\"margin:0px 0px 0px 0px;float:left;clear:left;width: 100%;\">\n";
}

//echo "	<a href=\"#\" id=\"whatshouldidonext-button\" class=\"videobtn\" style=\"float:left;\" onclick=\"show_wizard();\"".$wiz_button."><span>What should I do next?</span></a>\n";
echo "	<div id=\"widget-whatshouldidonext\"".$wiz_widget.">\n";
echo "		<div class=\"hdng\"><h3>What should I do next? (".$n.")</h3></div>\n";
echo "		<ol>\n";

	$max = count($donext_array);
	for ( $x = 0; $x < $max; $x++ ) {
		echo'		<li style="padding-right:5px;">'."\n";
		echo'			<h4><a href="'.$donext_array[$x]['link'].'">'.$donext_array[$x]['title'].'</a></h4>'."\n";
		echo'			<p>'.$donext_array[$x]['description'].'</p>'."\n";
		echo'		</li>'."\n";
	}

echo "		</ol>\n";
echo "		<p class=\"dismiss-button-container\"><button type=\"button\" class=\"grayButton\" onclick=\"dismiss_wizard();\"><span><span>Stop showing me this</span></span></button></p>\n";
echo "	</div>\n";
echo "</div>\n";





# hits today
$gs = mysql_query("select Hits from stats_byday where Real_Date='".date('Y-m-d')."'");
$thits=0;
while($ss = mysql_fetch_assoc($gs)){
	$thits = $thits + $ss['Hits'];	
}
# hits yesterday

$gs = mysql_query("select Hits from stats_byday where Real_Date='".date('Y-m-d', strtotime('yesterday'))."'");
$yhits = 0;
while($ss = mysql_fetch_assoc($gs)){
	$yhits = $yhits + $ss['Hits'];	
}

$gsz = mysql_query("select Hits from stats_byday where Month='".date('F')."' and Year='".date('Y')."'");
$mhits=0;
while($sss = mysql_fetch_assoc($gsz)){
	$mhits = $mhits + $sss['Hits'];	
}

$gsz = mysql_query("select Hits from stats_byday where Month='".date('F', strtotime('last month'))."' and Year='".date('Y', strtotime('last month'))."'");
$lmhits=0;
while($sss = mysql_fetch_assoc($gsz)){
	$lmhits = $lmhits + $sss['Hits'];	
}

# year total
$gsz = mysql_query("select Hits from stats_byday where Year='".date('Y', strtotime('last month'))."'");
$yrhits=0;
while($sss = mysql_fetch_assoc($gsz)){
	$yrhits = $yrhits + $sss['Hits'];
}



echo "<div class=\"box left\" id=\"widget-quick-stats\" style=\"position:relative;clear:none; float:left;\">\n";
//echo "<div class=\"half-widget box half left\" id=\"widget-quick-stats\" style=\"position:relative;clear:both; float:left;\">\n";
echo "	<div class=\"hdng\"><h3>Visitor Traffic Summary</h3></div>\n";



echo "	<ul style=\"float:left;\">\n";
echo "		<li>Hits Today: <strong>".number_format($thits)."</strong></li>\n";
echo "		<li>Hits Yesterday: <strong>".number_format($yhits)."</strong></li>\n";

echo "<li>&nbsp;</li>\n";
echo "	    <li>Hits this week: <strong>".number_format($weeklyhits)."</strong></li>\n";		

if($invoicecount > 0){
	echo "	    <li>Orders this week: <strong>".number_format($invoicecount)."</strong></li>\n";		
}
//echo "	    <li>Hits this week: <strong>869</strong></li>\n";
echo "<li>&nbsp;</li>\n";
echo "	    <li>Hits This Month: <strong>".number_format($mhits)."</strong></li>\n";
echo "	    <li>Hits Last Month: <strong>".number_format($lmhits)."</strong></li>\n";
echo "<li>&nbsp;</li>\n";
echo "	    <li><b>Total Hits This Year: <strong>".number_format($yrhits)."</strong></b></li>\n";
echo "	</ul>\n";
########################
### JqPlot Graph Stats #
#######################
//echo "<div class=\"half-widget box half left\" style=\"position:relative; clear:none; float:right;border:0px;\">\n"; //echo "	<div id=\"chart1\" style=\"height:243px; width:420px;\"></div>\n"; //echo "</div>\n";
echo "	<div id=\"chart1\" style=\"top:0px; clear:none;float:left;height:255px; width:600px;margin-right:5px;margin-left:15px;margin-top:5px;margin-bottom:10px;\"></div>\n";
#################
### End JqPlot ##
################
echo "	<!--- <br style=\"height: 100%;\"/> -->\n";
echo "	<a href=\"mods_full/statistics.php\" class=\"more\" style=\"clear:both;\">More Detailed Stats</a>\n";
echo "</div>\n";


echo "<div class=\"half-widget box half left\" id=\"widget-recent-pages\" style=\"position:relative;clear:both; float:left;\">\n";
echo "	<div class=\"hdng\"><h3>Recently Edited Pages</h3></div>\n";
echo "	<ul>\n";

$global_admin_prefs = new userdata('admin');
$recent_pages_array = $global_admin_prefs->get('recent_pages');
arsort($recent_pages_array);
$max = 5;
$rpcounter = 1;
foreach ( $recent_pages_array as $keyz=>$value ) {
	if ( $rpcounter <= 5 ) {
		if(date('M. d, Y', $value) == date('M. d, Y')){
			$lasteditdate = 'Today '.' at '.date('h:i a', $value);;
		}elseif(date('M. d, Y', $value) == date('M. d, Y', strtotime('-1 day'))){
			$lasteditdate = 'Yesterday '.' at '.date('h:i a', $value);
		} else {
			$lasteditdate = date('M. d, Y', $value).' at '.date('h:i a', $value);
		}
		echo 	'		<li onclick="document.location.href = \''.dash_edit_page_link($keyz).'\'"><a href="'.dash_edit_page_link($keyz).'">'.$keyz.'</a> (last edited '.$lasteditdate.')</li>'."\n";
	}
	$rpcounter++;
}

echo "	</ul>\n";
echo "	<a href=\"open_page.php\" class=\"more\">Full Page List</a>\n";
echo "</div>\n";


echo "<div class=\"box\" style=\"display: none;\">\n";
echo "	<div class=\"hdng\"><h3>Recent Site Activity</h3></div>\n";
echo "	<ul>\n";
echo "		 <li class=\"form\"><a href=\"#\">Contact form inquiry</a> from <a href=\"#\">Billy Jean</a></li>\n";
echo "	    <li class=\"cart\"><a href=\"#\">New purchase</a> by Billy Jean for <strong>$32.54</strong> (5/3/2011)</li>\n";
echo "	    <li class=\"cart\"><a href=\"#\">New purchase</a> by Billy Jean for <strong>$26.99</strong> (5/3/2011)</li>\n";
echo "	    <li class=\"form\"><a href=\"#\">Form inquiry</a> from Fred Ward</li>\n";
echo "	    <li class=\"cart\"><a href=\"#\">New purchase</a> by Jimmy Smith for <strong>$82.48</strong> (5/3/2011)</li>\n";
echo "	</ul>\n";
echo "</div>\n";

//echo "<div class=\"box video\" style=\"position:relative;float:left;\" id=\"widget-tutorial-videos\">\n";
//echo "<div class=\"hdng\"><h3>Tutorial Videos</h3></div>\n";
//echo "<ul id=\"tutorial-thumbs\">\n";
//$tutorial_array = array();
//function add_tutorial($idname, $caption, $url, $thumbnail) {
//	static $t = 0;
//	global $tutorial_array;
//	$tutorial_array[$t]['idname'] = $idname;
//	$tutorial_array[$t]['caption'] = $caption;
//	$tutorial_array[$t]['url'] = $url;
//	$tutorial_array[$t]['thumbnail'] = $thumbnail;
//	$t++;
//}
//add_tutorial('login', 'Log-in', 'http://securexfer.net/tutorials/player.php?tutorial=01_login_tutorial');
//add_tutorial('wizard', 'Quickstart Wizard', 'http://securexfer.net/tutorials/player.php?tutorial=02_quickstart_wizard');
//add_tutorial('create-pages', 'Create Pages', 'http://securexfer.net/tutorials/player.php?tutorial=03_new_pages');
//add_tutorial('edit-pages', 'Open/Edit Pages', 'http://securexfer.net/tutorials/player.php?tutorial=04_open_pages');
//add_tutorial('page-editor', 'Page Editor', 'http://securexfer.net/tutorials/player.php?tutorial=05_page_editor');
//add_tutorial('creating-links', 'Text Editor: Creating Links', 'http://securexfer.net/tutorials/player.php?tutorial=06_text_editor_-_creating_links');
//add_tutorial('inserting-copy', 'Copy-Pasting from MS Word', 'http://securexfer.net/tutorials/player.php?tutorial=07_text_editor_-_inserting_copy');
//add_tutorial('inserting-images', 'Text Editor: Inserting Images', 'http://securexfer.net/tutorials/player.php?tutorial=08_text_editor_-_inserting_images');
//add_tutorial('using-tables', 'Text Editor: Using Tables', 'http://securexfer.net/tutorials/player.php?tutorial=09_text_editor_-_using_tables');
//add_tutorial('templates-part1', 'Template Manager Part 1', 'http://securexfer.net/tutorials/player.php?tutorial=10_template_manager_part_1');
//add_tutorial('templates-part2', 'Template Manager Part 2', 'http://securexfer.net/tutorials/player.php?tutorial=11_template_manager_part_2');
//add_tutorial('menu-part1', 'Menu Navigation Part 1', 'http://securexfer.net/tutorials/player.php?tutorial=12_menu_navigation_part_1');
//add_tutorial('menu-part2', 'Menu Navigation Part 2', 'http://securexfer.net/tutorials/player.php?tutorial=13_menu_navigation_part_2');
//add_tutorial('files', 'Uploading & Managing Files', 'http://securexfer.net/tutorials/player.php?tutorial=14_file_manager');
//add_tutorial('stats', 'Traffic Statistics', 'http://securexfer.net/tutorials/player.php?tutorial=15_traffic_statistics');
//add_tutorial('backup', 'Backup & Restore', 'http://securexfer.net/tutorials/player.php?tutorial=16_backup_and_restore');
//add_tutorial('databases', 'Database Table Manager', 'http://securexfer.net/tutorials/player.php?tutorial=17_database_table_manager');
//add_tutorial('cart-payment', 'Shopping: Payment Options', 'http://securexfer.net/tutorials/player.php?tutorial=18_Shopping_Cart_-_Payment%20Options');
//add_tutorial('cart-display', 'Shopping: Display Settings', 'http://securexfer.net/tutorials/player.php?tutorial=19_Shopping_Cart_-_Display%20Settings');
//add_tutorial('cart-place-item', 'Shopping: Place Item on Page', 'http://securexfer.net/tutorials/player.php?tutorial=21_Shopping_Cart_-_Putting_Items_on_a_Page');
//add_tutorial('cart-tax', 'Shopping: Tax & Shipping', 'http://securexfer.net/tutorials/player.php?tutorial=22_Shopping_Cart_-_Tax_and_Shipping');
//add_tutorial('photo-album', 'Photo Albums', 'http://securexfer.net/tutorials/player.php?tutorial=23_Photo%20Albums');
//add_tutorial('webmaster', 'Webmaster Settings', 'http://securexfer.net/tutorials/player.php?tutorial=24_Webmaster');
//
//$max = count($tutorial_array);
//for ( $n = 0; $n < $max; $n++ ) {
//	echo '<div style="width:130px; height:170px; float:left;clear:none;padding:10px 4px 10px 4px; text-align:center;font:12px Arial, Helvetica, sans-serif; color:#939292;">';
//	echo '<a href="#" onclick="window.open(\''.$tutorial_array[$n]['url'].'\', \''.$tutorial_array[$n]['caption'].'\', \'width=810,height=590\');" id="'.$tutorial_array[$n]['idname'].'"><img src="dashboard/tutorial-thumbs/'.$tutorial_array[$n]['idname'].'.png" alt="'.$tutorial_array[$n]['caption'].'"/>';
//	echo '<h4 style="text-align:center;">'.$tutorial_array[$n]['caption'].'</h4></a>';
//	echo '</div>';
//}
////
////<!--- 	<li><a href="#"><img src="../includes/images/vd.png" alt="" /><h4>Login Tutorial</h4></a></li>
////   <li><a href="#"><img src="../includes/images/vd.png" alt="" /><h4>Quickstart Wizard</h4></a></li>
////	<li><a href="#"><img src="../includes/images/vd.png" alt="" /><h4>New Pages</h4></a></li>
////	<li><a href="#"><img src="../includes/images/vd.png" alt="" /><h4>Open Pages</h4></a></li>
////	<li><a href="#"><img src="../includes/images/vd.png" alt="" /><h4>Page Editor</h4></a></li> -->
//echo "</ul>\n";
//echo "<div class=\"clear\"></div>\n";
//echo "</div>\n";

echo "</div>\n";

echo "<form name=\"addframes\" action=\"../../version.php\" method=\"POST\" style=\"display:inline;\">\n";
echo "<input type=\"hidden\" name=\"gotopage\" value=\"program/modules/dashboard.php\">\n";
echo "</form>\n";

echo "<script type=\"text/javascript\">\n";
echo "if(top.location == location){\n";
echo "	document.addframes.submit();\n";
echo "}\n";
echo "</script>\n";


//echo "<script type=\"text/javascript\">\n";
//
//echo "function camsave(e) {\n";
//echo "	if(e.keyCode==115){\n";
//echo "		document.helpmehelpyou.submit();\n";
//echo "	}\n";
//echo "} \n";
//
//echo "jQuery(document).ready(function(){\n";
//echo "	camsave(event);\n";
//echo "});\n";


echo "<script type=\"text/javascript\">\n";
echo "$(document).keydown(function(event) {\n";
echo "	if (event.keyCode == '115') {\n";
echo "		document.helpmehelpyou.submit();\n";
echo "	}\n";
echo "});\n";
echo "</script>\n";


echo "<script type=\"text/javascript\" charset=\"utf-8\">\n";
echo "$(document).ready(function(){\n";
echo "	$(\"a[rel^='prettyPhoto']\").prettyPhoto({    \n";
echo "		default_width: 863,\n";
echo "		default_height: 530,\n";
echo "		autoplay: true,\n";
echo "		autoplay_slideshow: false,\n";

echo "		theme: 'dark_rounded',\n";
echo "		social_tools: false,\n";
echo "		iframe_markup: '<iframe width=\"{width}\" height=\"{height}\" src=\"{path}\" frameborder=\"0\" allowfullscreen></iframe>'\n";
echo "	});\n";

if($n==7 && $_SESSION['launch_help'] != 1){
	$_SESSION['launch_help'] = 1;
	echo "$.prettyPhoto.open('".$tutorial_video_link."&iframe=true&width=863&height=530','','');\n";
}
echo "});\n";
echo "</script>\n";

//echo "</script>\n";
echo "<form name=\"helpmehelpyou\" target=\"_BLANK\" action=\"../webmaster/helpmehelpyou.php\" method=\"GET\">\n";
echo "</form>\n";

$module_html = ob_get_contents();
ob_end_clean();

$instructions = lang("This is the Main Menu. All features can be accessed from here.")."<br/>";

# Build into standard module template
$module = new smt_module($module_html);
//$module->add_breadcrumb_link(lang("Dashboard"), "program/modules/dashboard.php");
//$module->icon_img = "program/includes/images/sohoadmin2.png";
//$module->heading_text = lang("Main Menu");
$module->description_text = $instructions;
$module->good_to_go();
?>