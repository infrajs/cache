<?php
namespace infrajs\cache;
use infrajs\once\Once;
use infrajs\mem\Mem;
use infrajs\access\Access;
use infrajs\path\Path;
/*
Cache::exec(true,'somefn',array($arg1,$arg2)); - выполняется всегда
Cache::exec(true,'somefn',array($arg1,$arg2),$data); - Установка нового значения в кэше 
*/
class Cache {
	public static function fullrmdir($delfile, $ischild = false)
	{
		return Path::fullrmdir($delfile, $ischild);
	}
	
	public static function exec($conds, $name, $fn, $args = array(), $re = false)
	{
		$name = 'Cache::exec'.$name;
		return Once::exec($name, function ($args, $r, $hash) use ($name, $fn, $conds, $re) {
			$data = Mem::get($hash);
			if (!$data) {
				$data = array('time' => 0);
			}
			$execute = Access::adminIsTime($data['time'], function ($cache_time) use ($conds) {

				if (!sizeof($conds)) {
					return false;//Если нет conds кэш навсегда и develop не поможет
				}
				$max_time = 1;
				for ($i = 0, $l = sizeof($conds); $i < $l; $i++) {
					$mark = $conds[$i];
					$mark = Path::theme($mark);
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
		}, array($args), $re);
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