<?php
namespace infrajs\cache;
use infrajs\once\Once;
use infrajs\mem\Mem;
use infrajs\access\Access;
use infrajs\path\Path;
use infrajs\nostore\Nostore;
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
			$data = Mem::get($name.$hash);
			if (!$data) $data = array('time' => 0);
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
				$is = Nostore::check(function () use (&$data, $fn, $args, $re) { //Проверка был ли запрет кэша
					$data['result'] = call_user_func_array($fn, array_merge($args, array($re)));
				});
				if (!$is && !$re) { //При $re кэш не сохраняется. Это позволяет запустит Cache::exec до установи расширений в Search
					$data['time'] = time();
					Mem::set($name.$hash, $data);
				} else {
					Mem::delete($name.$hash);
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

}
