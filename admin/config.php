<?php 
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

if (count($_POST))
  rvac_invalidate_cache();
?>