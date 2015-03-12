<?php 
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

include_once(PHPWG_ROOT_PATH.'admin/include/tabsheet.class.php');

load_language('plugin.lang', PHPWG_PLUGINS_PATH.RVAC_ID.'/');

if (!isset($_GET['tab']))
  $page['tab'] = 'config';
else
  $page['tab'] = $_GET['tab'];

$my_base_url = 'admin.php?page=plugin-'.basename(dirname(__FILE__));

$tabsheet = new tabsheet();
$tabsheet->add( 'config', l10n('Configuration'), $my_base_url.'-config');
$tabsheet->add( 'exclude', l10n('Exclude'), $my_base_url.'-exclude' );
$tabsheet->add( 'custom', l10n('Custom'), $my_base_url.'-custom' );
$tabsheet->add( 'variants', l10n('Variants'), $my_base_url.'-variants' );
$tabsheet->select($page['tab']);

$tabsheet->assign();

$my_base_url = $tabsheet->sheets[ $page['tab'] ]['url'];

include_once( dirname(__FILE__).'/functions.inc.php');
include_once( dirname(__FILE__).'/admin/functions.inc.php');
include_once( dirname(__FILE__).'/admin/'.$page['tab'].'.php');

$template->set_filename( 'rv_ac', dirname(__FILE__).'/admin/'.$page['tab'].'.tpl' );
$template->assign_var_from_handle( 'ADMIN_CONTENT', 'rv_ac');

?>