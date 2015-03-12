<?php 
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

$rvac_conf = rvac_get_conf();

if (!empty($_POST))
{
  foreach( array('excluded_albums','excluded_tags') as $k)
  {
    if (!empty($_POST[$k]))
    {
      $rvac_conf[$k] = $_POST[$k];
      foreach($rvac_conf[$k] as &$v)
      {
        $v=intval($v);
      }
      unset($v);
    }
    else
      $rvac_conf[$k] = array();
  }
  rvac_save_conf($rvac_conf);
  rvac_invalidate_cache();
}

$query = 'SELECT id, name, uppercats, global_rank, COUNT(image_id) AS counter
  FROM '.CATEGORIES_TABLE.' LEFT JOIN '.IMAGE_CATEGORY_TABLE.'
  ON id = category_id
  GROUP BY id
  ORDER BY global_rank';
$albums = query2array($query);
usort($albums, 'global_rank_compare');

display_select_categories($albums, (array)$rvac_conf['excluded_albums'], 'albums', false );


$query = 'SELECT id, name, COUNT(image_id) AS counter
  FROM '.TAGS_TABLE.' LEFT JOIN '.IMAGE_TAG_TABLE.'
  ON id = tag_id
  GROUP BY id
  ORDER BY url_name';
$tags = query2array($query);
$tags_select = array();
foreach($tags as $tag)
  $tags_select[ $tag['id'] ] = trigger_change('render_tag_name', $tag['name']) . ' '.$tag['counter'];

$template->assign( array(
  'U_FORM' => $my_base_url,
  'tags' => $tags_select,
  'tags_selected' => (array)$rvac_conf['excluded_tags'],
) );
?>