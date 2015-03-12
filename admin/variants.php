<?php 
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');


$items = array();
$rules = rvac_load_variant_rules();
foreach($rules as $k => $rule)
{
  $rule['key'] = $k;
  $items[] = $rule;
}

$template->assign('variants', $items);

$template->assign('RVAC_ID', RVAC_ID);
?>