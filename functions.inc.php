<?php

function rvac_get_conf()
{
	global $conf;
	if (is_array($conf['rvac_opts']))
		return $conf['rvac_opts'];
	$conf['rvac_opts'] = unserialize($conf['rvac_opts']);
	return $conf['rvac_opts'];
}

function rvac_save_conf($cnf)
{
	global $conf;
	$conf['rvac_opts'] = $cnf;
	conf_update_param('rvac_opts', addslashes(serialize($conf['rvac_opts'])) );
}

function rvac_get_url_roots()
{
	$roots = array(
		'r' => get_root_url()
	);

	$url = make_index_url( array('tags'=>array( array('id'=>123,'name'=>123,'url_name'=>123))) );
	$url = substr($url, 0, strpos($url, '123'));
	$roots['t'] = $url;

	$url = make_index_url( array('category'=>array('id'=>123,'name'=>123,'permalink'=>123)) );
	$url = substr($url, 0, strpos($url, '123'));
	$roots['a'] = $url;

	return $roots;
}


function rvac_normalize($name)
{
	$invalid = '&()".,:;-';
	$name = strtr($name, $invalid, str_repeat(' ', strlen($invalid)));
	$name = pwg_transliterate($name);
	for($i=0; $i<3; $i++)
		$name = str_replace('  ', ' ', $name);
	return $name;
}

function rvac_get_elem($item, $key)
{
	$l = $item['names'][$key];
	$q = $item['q_names'][$key];
	$ret = array(
		'label' => $l,
		'value' => $item['url'],
		);
	if ($q != strtolower($l))
		$ret['q'] = $q;
	if ($item['counter'] > 0)
		$ret['w'] = intval($item['counter']);
	return $ret;
}

function rvac_get_index()
{
	global $user;

	$rvac_conf = rvac_get_conf();
	$root_url = get_root_url();
	$items = array();

	$roots = rvac_get_url_roots();


	$query = 'SELECT id,name FROM '.TAGS_TABLE;
	if ( !empty($rvac_conf['excluded_tags']) )
		$query .= ' WHERE id NOT IN ('.implode(',',$rvac_conf['excluded_tags']).')';
	$tag_names = query2array($query, 'id','name');

	$url_len = strlen( $roots['t'] );
	foreach( get_available_tags() as $row)
	{
		if (!isset($tag_names[$row['id']])) continue;
		$row['type'] = 't';
		$row['url'] = '$t/' . substr( make_index_url( array('tags'=>array($row)) ), $url_len);
		$row['name'] = $tag_names[ $row['id'] ];
		$items[] = $row;
	}


	$query = 'SELECT id,name,permalink,nb_images AS counter
	FROM '.CATEGORIES_TABLE.' INNER JOIN '.USER_CACHE_CATEGORIES_TABLE.'
	ON id = cat_id AND user_id = '.$user['id'];
	if ( !empty($rvac_conf['excluded_albums']) )
		$query .= ' WHERE id NOT IN ('.implode(',',$rvac_conf['excluded_albums']).')';

	$url_len = strlen( $roots['a'] );

	$result = pwg_query($query);
	while ($row = pwg_db_fetch_assoc($result))
	{
		$row['type'] = 'a';
		$row['url'] = '$a/' . substr( make_index_url( array('category'=>$row) ), $url_len);
		$items[] = $row;
	}

	$query = 'SELECT name,counter,url FROM '.RVAC_SUGGESTIONS.' WHERE level<='.$user['level'];
	$result = pwg_query($query);
	while ($row = pwg_db_fetch_assoc($result))
	{
		$row['type'] = 's';
		if (empty($row['url']))
			$row['url'] = -1; // special use in js to submit form (cannot put 0 because jquery ui tests often item.value)
		$items[] = $row;
	}

	array_walk($items, function(&$obj) {
		$alt_names = trigger_change('get_tag_alt_names', array(), $obj['name']);

		if (empty($alt_names))
			$alt_names['default'] = $obj['name'];
		else
		{
			if (!isset($alt_names['default']) && isset($alt_names[0]))
			{
				$alt_names['default'] = $alt_names[0];
				unset($alt_names[0]);
			}
		}

		$obj['names'] = $alt_names;
		if ('s' !== $obj['type'])
		{
			$obj['q_names'] = $alt_names;
			foreach ($obj['q_names'] as &$q)
				$q = rvac_normalize($q);
		}
		else
		{
			foreach ($obj['names'] as $l => &$name)
			{
				$pos = strpos($name, '\\');
				if ($pos === false)
				{
					$q = rvac_normalize($name);
				}
				else
				{
					$q = substr($name, 0, $pos);
					if (-1 === $obj['url'] && 'default'==$l)
						$obj['url'] = 'q='.$q;

					$q = rvac_normalize($q);
					$name = substr_replace($name, '', $pos, 1);
				}
				$obj['q_names'][$l] = $q;
			}
		}

	});

	$type_order = array_flip( array('t','a','s') );
	usort($items, function($a, $b) use($type_order) {
		$d = $a['counter']-$b['counter'];
		if ($d) return -$d;
		$d = $type_order[$a['type']] - $type_order[$b['type']];
		if ($d) return $d;
		return strcmp($a['q_names']['default'],$b['q_names']['default']);
	});

	//var_export($items);
	$res = array();
	$res_alt = array();
	$lang = substr($user['language'],0,2);
	array_walk($items, function(&$obj) use($lang, &$res, &$res_alt) {
		$key = $lang;
		if (empty($obj['q_names'][$key]))
			$key = 'default';
		if (!empty($obj['q_names'][$key]))
			$res[] = rvac_get_elem($obj, $key);
		else
			$key = null;
		if ($key !== null) {
			foreach( array_keys($obj['q_names']) as $k) {
				if ($k==$key) continue;
				if (strncmp($obj['q_names'][$key],$obj['q_names'][$k],4)==0) continue;
				$res_alt[] = rvac_get_elem($obj, $k);
			}
		}
		else {
			foreach( $obj['q_names'] as $k => $q)
			{
				if (!empty($q))
					$res_alt[] = rvac_get_elem($obj, $k);
			}
		}
	});

	return array(
			'total' => count($res) + count($res_alt),
			'altLangIndex' => count($res),
			'src' => array_merge($res, $res_alt),
			'roots' => $roots
		);
}

function rvac_on_qsearch_expression_parsed($expr)
{
	global $conf;
	$file=PHPWG_ROOT_PATH.$conf['data_location'].'tmp/autocomplete_variants.dat';
	$data = @unserialize( file_get_contents($file));
	if ($data === false)
		return;

	$rmap = $data['replace'];
	$amap = $data['add'];
	foreach ($expr->stokens as $token)
	{
		if (isset($token->scope) && !$token->scope->is_text)
			continue;
		if ($token->modifier & (QST_QUOTED|QST_WILDCARD))
			continue;

		$all = array_merge( array($token->term), $token->variants);

		$in = $all[0];
		$in_t = pwg_transliterate($in);
		if (isset($rmap[$in_t]))
		{
			$all = $rmap[$in_t];
			foreach ($all as &$one)
			{
				if ('$i' == $one)
					$one = $in;
			}
			unset($one);
		}

		$processed = array();
		for ($i=0; $i<count($all); $i++)
		{
			$in = $all[$i];
			$in_t = pwg_transliterate($in);

			if (isset($processed[$in_t]))
			{
				array_splice($all, $i, 1);
				$i--;
				continue;
			}
			$processed[$in_t] = 1;

			if (isset($amap[$in_t]))
				$all = array_merge($all, $amap[$in_t]);
		}

		$token->term = array_shift($all);
		$token->variants = $all;
	}
}
?>