<?php /*
Plugin Name: RV autocomplete
Version: 2.10.a
Description: Autocompletes the quick search with albums, tags or custom suggestions
Plugin URI: http://piwigo.org/ext/extension_view.php?eid=694
Author: rvelices
Author URI: http://www.modusoptimus.com/
*/
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

define('RVAC_ID', $plugin['id']);

global $prefixeTable;
define('RVAC_SUGGESTIONS', $prefixeTable.'suggestions');

function rvac_get_data_file() {
	global $user,$conf;
	$f = PWG_COMBINED_DIR.'acds-';
	$f .= $conf['rvac_version'];
	$keys = array( $user['language'], $user['nb_total_images'], $user['level'], strlen($user['forbidden_categories']) );
	$f .= '-'.base_convert(crc32( implode('-', $keys ) ), 10, 36);
	$f .= '.js';
	return $f;
}

add_event_handler('get_admin_plugin_menu_links', function ($menu) {
	$menu[] = array(
			'NAME' => 'Autocomplete',
			'URL' => 'admin.php?page=plugin-'.RVAC_ID
	);
	return $menu;
}
);

add_event_handler('ws_add_methods', function($srv_arr) {
  include_once( 'admin/functions.inc.php' );
  rvac_ws_add_methods($srv_arr);
});


add_event_handler('blockmanager_apply', function($mb_arr)	{
  if ($mb_arr[0]->get_id() != 'menubar' )
    return;

  global $template;

	$plug_root = get_root_url().'plugins/'.RVAC_ID.'/';
  $core_src = $plug_root.'res/suggest-core.js';

  $data_src = rvac_get_data_file();
  if (file_exists(PHPWG_ROOT_PATH.$data_src))
    $data_src = get_root_url().$data_src;
  else
    $data_src = $plug_root.'suggestions.php';

  $fs = 'var RVAC={root:"'.$plug_root.'"};
$("#qsearchInput").one("focus", function() {
var s;
';
  foreach (array($data_src,$core_src) as $src)
    $fs .='s=document.createElement("script");s.type="text/javascript";s.async=true;s.src="'.$src.'";document.body.appendChild(s);
';

  $css_src = get_root_url().'plugins/'.RVAC_ID.'/res/dark-hive/custom.css';
  $fs .= 's="'.$css_src.'";
if (document.createStyleSheet) document.createStyleSheet(s); else $("head").append($("<link rel=\'stylesheet\' href=\'"+s+"\' type=\'text/css\'>"));';
  $fs .= '
});';
  $template->block_footer_script( array(), $fs);
  $template->func_combine_script( array('id'=>'jquery','load'=>'footer'));
}
);

add_event_handler('qsearch_expression_parsed', 'rvac_on_qsearch_expression_parsed', EVENT_HANDLER_PRIORITY_NEUTRAL, dirname(__FILE__).'/functions.inc.php');
?>