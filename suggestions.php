<?php

define('PHPWG_ROOT_PATH','../../');
include_once( PHPWG_ROOT_PATH.'include/common.inc.php' );

if (!defined('RVAC_ID')) {
	set_status_header(501);
	exit;
}

include_once( 'functions.inc.php' );

$page['root_path'] = get_absolute_root_url(false);

$data = rvac_get_index();

if (isset($_GET['txt'])) {
	foreach($data['src'] as $item)
		echo implode("\t", $item)."\n";
	exit;
}


$ds = str_replace(
	array('\\/', '"q":', '"label":', '"value":', '"w":'),
	array('/', 'q:', 'label:', 'value:', 'w:'),
	json_encode($data['src']));
$js = '$.extend(RVAC, {
	dataSource:'.$ds.',
	dataSourceAltLangIndex: '.$data['altLangIndex'].',
	roots: '.json_encode($data['roots']).',
	minForAltLang: 2,
	stopLookingAfter: 80,
	suggestions: 4,
	maxSuggestions: 8
});
$.each(RVAC.dataSource, function(i,item) {
	item.q = item.q || item.label.toLowerCase()
});
RVAC.start && RVAC.start();';

header("Content-Type: application/javascript; charset=".get_pwg_charset());
echo $js;

file_put_contents(PHPWG_ROOT_PATH.rvac_get_data_file(), $js);
?>