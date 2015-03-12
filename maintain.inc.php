<?php
function plugin_install($id, $version, &$errors)
{
  if (version_compare(PHP_VERSION, '5.3') < 0)
  {
    $errors[] = "PHP 5.3 required";
    return;
  }
  global $prefixeTable;

  $q = '
CREATE  TABLE `'.$prefixeTable.'suggestions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `counter` INT NOT NULL DEFAULT 0,
  `url` VARCHAR(255) DEFAULT NULL,
  `level` tinyint unsigned NOT NULL default 0,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `i_name` (`name` ASC) )';

  pwg_query( create_table_add_character_set($q) );
  
  $opts = array( 'excluded_tags'=>array(), 'excluded_albums'=>array() );
  conf_update_param('rvac_opts', addslashes(serialize($opts)) );
	
	$insert = array(
		'name' => addslashes(l10n('Help')),
		'url' => '$r/popuphelp.php?page=quick_search',
		);
	single_insert($prefixeTable.'suggestions', $insert);
}

function plugin_activate($id, $version, &$errors)
{
	global $conf;
  conf_update_param('rvac_version', @++$conf['rvac_version'] );
}

function plugin_uninstall()
{
  global $prefixeTable;
  $q = 'DROP TABLE IF EXISTS '.$prefixeTable.'suggestions';
  pwg_query($q);
  
  $q = 'DELETE FROM '.CONFIG_TABLE.'
WHERE param IN(\'rvac_opts\',\'rvac_version\')';
  pwg_query($q);
}

?>