<?php
namespace infrajs\cache;
use infrajs\once\Once;
use infrajs\mem\Mem;
/*
Cache::exec(true,'somefn',array($arg1,$arg2)); - выполняется всегда
Cache::exec(true,'somefn',array($arg1,$arg2),$data); - Установка нового значения в кэше 
*/
class Cache {
	public static $conf=array();
	public static function fullrmdir($delfile, $ischild = true)
	{
		$conf=static::$conf;
		$delfile = $conf['theme']($delfile);
		if (file_exists($delfile)) {		
			if (is_dir($delfile)) {
				$handle = opendir($delfile);
				while ($filename = readdir($handle)) {
					if ($filename != '.' && $filename != '..') {
						$src = $delfile.$filename;
						if (is_dir($src)) $src .= '/';
						$r=static::fullrmdir($src, true);
						if(!$r)return false;
					}
				}
				closedir($handle);
				if ($ischild) {
					return rmdir($delfile);
				}

				return true;
			} else {
				return unlink($delfile);
			}
		}
		return true;
	}
	public static function isTime($time, $call){
		return $call($time);
	}
	public static function exec($conds, $name, $fn, $args = array(), $re = false)
	{
		$name = 'Cache::exec'.$name;
		return Once::exec($name, function ($args, $r, $hash) use ($name, $fn, $conds, $re) {
			$data = Mem::get($hash);

			if (!$data) {
				$data = array('time' => 0);
			}
			$execute = Cache::isTime($data['time'], function ($cache_time) use ($conds) {
				if (!sizeof($conds)) {
					return false;//Если нет conds кэш навсегда и develop не поможет
				}
				$conf=Cache::$conf;
				$max_time = 1;
				for ($i = 0, $l = sizeof($conds); $i < $l; ++$i) {
					$mark = $conds[$i];
					$mark = $conf['theme']($mark);
					if (!$mark) {
						continue;
					}
					$m = filemtime($mark);
					if ($m > $max_time) {
						$max_time = $m;
					}
					if (!is_dir($mark)) {
						continue;
					}
					foreach (glob($mark.'*.*') as $filename) {
						$m = filemtime($filename);
						if ($m > $max_time) {
							$max_time = $m;
						}
					}
				}

				return $max_time > $cache_time;
			}, $re);

			if ($execute) {
				$cache = static::check(function () use (&$data, $fn, $args, $re) {
					$data['result'] = call_user_func_array($fn, array_merge($args, array($re)));
				});
				if ($cache) {
					$data['time'] = time();
					Mem::set($hash, $data);
				} else {
					Mem::delete($hash);
				}
			}

			return $data['result'];
		}, array($args));
	}
	public static function clear($name, $args = array())
	{
		$name = 'Cache::clear::'.$name;
		$hash = Once::clear($name, $args);
		Mem::delete($hash);

		return $hash;
	}
/**
	 * Возможны только значения no-store и no-cache
	 * no-store - вообще не сохранять кэш.
	 * no-cache - кэш сохранять но каждый раз спрашивать не поменялось ли чего.
	 */
	public static function is()
	{
		$list = headers_list();
		foreach ($list as $name) {
			$r = explode(':', $name, 2);
			if ($r[0] == 'Cache-Control') {
				return (strpos($r[1], 'no-store') === false);
			}
		}

		return true;
	}
	public static function check($call)
	{
		$cache = static::is();
		if (!$cache) {
			//По умолчанию готовы кэшировать
			header('Cache-Control: no-cache');
		}

		$call();

		//Смотрим есть ли возражения
		$cache_after = static::is();

		if (!$cache && $cache_after) {
			//Возражений нет и функция вернёт это в $cache2..
			//но уже была установка что кэш не делать... возвращем эту установку для вообще скрипта
			header('Cache-Control: no-store');
		}
		return $cache_after;
	}
}
Cache::$conf=array(
	'theme'=>function($src){
		$s=__DIR__.'/../../../'.$src;
		if (!file_exists($s)) return false;
		$s=realpath($s);
		$root=realpath(__DIR__.'/../../../');

		if(strpos($s, $root) !== 0) return false;

		return $s;
	}
);
