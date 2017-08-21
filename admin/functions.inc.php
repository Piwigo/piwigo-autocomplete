<?php

function rvac_invalidate_cache()
{
	global $conf;
	conf_update_param('rvac_version', ++$conf['rvac_version'] );
	if (rand()%10==0)
	{
		foreach (glob(PHPWG_ROOT_PATH.PWG_COMBINED_DIR.'acds-*.js') as $file)
			@unlink($file);
	}
}

function rvac_custom_link(&$suggestion, $roots)
{
	$url = $suggestion['url'];
	if (empty($url))
	{
		$q = $suggestion['name'];
		if ( ($pos=strpos($q,'\\')) !== false )
		{
			$q = substr($q, 0, $pos);
		}
		$invalid = '&()".:;,';
		$q = strtr($q, $invalid, str_repeat(' ', strlen($invalid)));
		for($i=0; $i<3; $i++)
			$q = str_replace('  ', ' ', $q);
	}
	elseif (strncmp($url,'q=',2)==0)
	{
		$q = substr($url,2);
	}
	else
	{
		foreach( $roots as $k => $root)
			$url = str_replace('$'.$k.'/', $root, $url);
	}

	if (isset($q))
	{
		$url = get_root_url().'qsearch.php?q='.rawurlencode($q);
	}

	$suggestion['U_LINK'] = $url;
	return $suggestion;
}

function rvac_ws_add_methods($srv_arr)
{
	global $conf;
	$srv = $srv_arr[0];
	include_once( dirname(__FILE__).'/../functions.inc.php' );
	$srv->addMethod('rvac.addCustom', 'rv_ac_ws_add_custom_suggestion',
		array(
			'name' => array(),
			'counter' => array('default'=>0),
			'url' => array('default'=>''),
			'level' => array('default'=>min($conf['available_permission_levels']), 'maxValue'=>max($conf['available_permission_levels']),'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
		),
		'','', array('admin_only'=>true, 'post_only'=>true));

	$srv->addMethod('rvac.modCustom', 'rv_ac_ws_mod_custom_suggestion',
		array(
			'id' => array(),
			'name' => array('flags' => WS_PARAM_OPTIONAL),
			'counter' => array('flags' => WS_PARAM_OPTIONAL),
			'url' => array('flags' => WS_PARAM_OPTIONAL),
			'level' => array('flags' => WS_PARAM_OPTIONAL, 'maxValue'=>max($conf['available_permission_levels']),'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
		),
		'','', array('admin_only'=>true, 'post_only'=>true));

	$srv->addMethod('rvac.delCustom', 'rv_ac_ws_del_custom_suggestion',
		array('id'),
		'','', array('admin_only'=>true, 'post_only'=>true));


	$srv->addMethod('rvac.addVariant', 'rv_ac_ws_add_variant',
		array(
			'in' => array(),
			'type' => array(),
			'out' => array(),
			'comment' => array('flags' => WS_PARAM_OPTIONAL),
		),
		'','', array('admin_only'=>true, 'post_only'=>true));

	$srv->addMethod('rvac.modVariant', 'rv_ac_ws_mod_variant',
		array(
			'key' => array(),
			'in' => array(),
			'type' => array(),
			'out' => array(),
			'comment' => array('flags' => WS_PARAM_OPTIONAL),
		),
		'','', array('admin_only'=>true, 'post_only'=>true));

	$srv->addMethod('rvac.delVariant', 'rv_ac_ws_del_variant',
		array(
			'key' => array(),
		),
		'','', array('admin_only'=>true, 'post_only'=>true));

}

function rv_ac_ws_add_custom_suggestion($params, $service)
{
	$name = trim($params['name']);
	if (empty($name))
		return new PwgError(400, 'Bad name');

	$insert = array('name'=>$name);

	if (isset($params['counter']))
		$insert['counter'] = intval($params['counter']);
	if ( !empty($params['url']) )
		$insert['url'] = $params['url'];
	if ( isset($params['level']) )
		$insert['level'] = $params['level'];

	mass_inserts(RVAC_SUGGESTIONS, array_keys($insert), array($insert));
	$id =  pwg_db_insert_id(RVAC_SUGGESTIONS);

	rvac_invalidate_cache();
	$row = pwg_db_fetch_assoc( pwg_query('SELECT * FROM '.RVAC_SUGGESTIONS.' WHERE id='.$id) );
	rvac_custom_link($row, rvac_get_url_roots());
	return $row;
}

function rv_ac_ws_mod_custom_suggestion($params, $service)
{
	$id = intval($params['id']);
	$update = array();

	if (!empty($params['name']))
		$update['name'] = $params['name'];
	if (isset($params['counter']))
		$update['counter'] = intval($params['counter']);
	if (isset($params['url']))
		$update['url'] = $params['url'];
	if ( isset($params['level']) )
		$update['level'] = $params['level'];

	single_update(RVAC_SUGGESTIONS,
		$update,
		array('id' => $id)
		);
	$changes = pwg_db_changes();

	if ($changes)
		rvac_invalidate_cache();

	$row = pwg_db_fetch_assoc( pwg_query('SELECT * FROM '.RVAC_SUGGESTIONS.' WHERE id='.$id) );
	rvac_custom_link($row, rvac_get_url_roots());
	return $row;
}

function rv_ac_ws_del_custom_suggestion($params, $service)
{
	$id = intval($params['id']);
	$q = 'DELETE FROM '.RVAC_SUGGESTIONS.' WHERE id='.$id;
	pwg_query($q);
	$changes = pwg_db_changes();
	if ($changes)
		rvac_invalidate_cache();
	return $changes;
}




function rvac_load_variant_rules()
{
	global $conf;
	$file=PHPWG_ROOT_PATH.$conf['data_location'].'plugins/autocomplete_variants.dat';

	$data = @unserialize( file_get_contents($file));
	if ($data === false)
		return array();
	return $data;
}

function rvac_save_variant_rules($rules, $callback = null)
{
	global $conf, $persistent_cache;
	$file=PHPWG_ROOT_PATH.$conf['data_location'].'plugins/autocomplete_variants.dat';
	$save = serialize($rules);
	if (@file_put_contents($file, $save)===false)
	{
		mkgetdir(dirname($file));
		file_put_contents($file, $save);
	}

	$rmap = $amap = array();
	foreach($rules as $rule)
	{
		foreach($rule['in'] as $in_word)
		{
			$in_word_t = pwg_transliterate($in_word);
			$results = $rule['out'];
			$processed = array();
			if ('a' == $rule['type'])
				$processed[$in_word_t] = 1;

			for ($i=0; $i<count($results); $i++)
			{
				$outkey = $results[$i] == '$i' ? $in_word_t : pwg_transliterate($results[$i]);
				if (isset($processed[$outkey]))
				{
					array_splice($results, $i, 1);
					$i--;
					continue;
				}
				$processed[$outkey] = 1;

				if ('$a' == $results[$i])
				{
					array_splice($results, $i, 1, $rule['in']);
					$i--;
				}
			}

			if ('r' == $rule['type'])
			{
				if (isset($rmap[$in_word_t]))
				{
					if ($callback) $callback( array(
						'word' => $in_word,
						'wordt' => $in_word_t,
						'msg' => ' defined as input several times in replace rules'
					));
				}
				else
					$rmap[$in_word_t] = $results;
			}
			else
			{
				$results = array_diff($results, array($in_word,$in_word_t, '$i'));
				if (isset($amap[$in_word_t]))
					$results = array_unique( array_merge($amap[$in_word_t], $results));
				$amap[$in_word_t] = $results;
			}
		}
	}

	$file=PHPWG_ROOT_PATH.$conf['data_location'].'tmp/autocomplete_variants.dat';
	$res = array('replace' => $rmap, 'add' => $amap );
	$save = serialize($res);
	if (@file_put_contents($file, $save)===false)
	{
		mkgetdir(dirname($file));
		file_put_contents($file, $save);
	}
	$persistent_cache->purge(true);
	$file=PHPWG_ROOT_PATH.$conf['data_location'].'tmp/autocomplete_variants.txt';
	if ($fh = @fopen($file, 'w'))
	{
		fputcsv($fh, array("Type", "In", "Out"), "\t");
		foreach($rmap as $in => $words)
			fputcsv($fh, array("r", $in, implode(',', $words)), "\t");
		foreach($amap as $in => $words)
			fputcsv($fh, array("a", $in, implode(',', $words)), "\t");
		fclose($fh);
	}
}

function rv_ac_ws_add_variant($params, $service)
{
	return rvac_ws_add_or_mod_variant($params, true);
}

function rv_ac_ws_mod_variant($params, $service)
{
	return rvac_ws_add_or_mod_variant($params, false);
}

function rvac_ws_add_or_mod_variant($params, $is_add)
{
	$rules = rvac_load_variant_rules();

	foreach( array('in', 'out') as $i => $name)
	{
		$arr = preg_split("/[\n,]+/", stripslashes($params[$name]) );
		$arr = array_map('trim', $arr);
		$arr = array_values( array_filter($arr, function($word) {
			return strlen($word)>0;
		}) );
		$arr = array_unique($arr);
		$$name = $arr;
	}
	if (empty($in))
		return new PwgError(WS_ERR_INVALID_PARAM, 'input word list empty');
	if (empty($out))
		return new PwgError(WS_ERR_INVALID_PARAM, 'output word list empty');

	$in_trans = array_map('pwg_transliterate', $in);
	sort($in_trans);
	$key = implode(',', $in_trans);
	if (strlen($key)>24)
		$key = md5($key);

	$type = $params['type'];
	if ($type!='r' && $type!='a')
		return new PwgError(WS_ERR_INVALID_PARAM, 'type');

	if ($is_add)
	{
		if (isset($rules[$key]))
			return new PwgError(WS_ERR_INVALID_PARAM, 'list already defined');
	}
	else
	{
		$okey = stripslashes($params['key']);
		if (!isset($rules[$okey]))
			return new PwgError(WS_ERR_INVALID_PARAM, 'no rule to update');
		unset($rules[$okey]);
	}

	$rule = array(
		'in' => $in,
		'type' => $type,
		'out' => $out,
	);
	if (!empty($params['comment']))
		$rule['comment'] = stripslashes($params['comment']);
	$rules[$key] = $rule;

	$messages = array();
	$callback = function($params) use($in_trans, &$messages) {
		if (isset($params['wordt']))
		{
			if (in_array($params['wordt'], $in_trans))
				$messages[] = $params['word'].' '.$params['msg'];
		}
		else
			$messages[] = $params['msg'];
	};

	rvac_save_variant_rules($rules, $callback);
	$rule['key'] = $key;
	return array(
		'messages' => $messages,
		'rule' => $rule,
	);
}

function rv_ac_ws_del_variant($params, $service)
{
	$rules = rvac_load_variant_rules();

	$key = stripslashes($params['key']);
	if (empty($key))
		return new PwgError(WS_ERR_INVALID_PARAM, 'empty key');

	if (!isset($rules[$key]))
		return true;

	unset($rules[$key]);

	rvac_save_variant_rules($rules);
}
?>