<?php 
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

$query = 'SELECT * FROM '.RVAC_SUGGESTIONS.'
ORDER BY name';

$suggestions = query2array($query);

$roots = rvac_get_url_roots();
foreach($suggestions as &$suggestion)
  rvac_custom_link($suggestion, $roots);

$template->assign('suggestions', $suggestions);
foreach ($conf['available_permission_levels'] as $level)
{
	if (0 == $level)
		$label = l10n('Everybody');
	else
		$label = l10n( sprintf('Level %d', $level) );
	$options[$level] = $label;
}
$template->assign('available_permission_levels', $options);

$template->assign('RVAC_ID', RVAC_ID);
?>