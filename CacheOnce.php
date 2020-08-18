<?php

namespace infrajs\cache;

trait CacheOnce
{
    public static $once = array();
	public static function oncekey($name, $args)
	{
		if (!isset(self::$once[$name])) self::$once[$name] = [];
		return is_array($args) ? json_encode($args, JSON_UNESCAPED_UNICODE) : $args;
	}
	public static function once($name, $args, $fn)
	{
		$key = self::oncekey($name, $args);
		if (isset(self::$once[$name][$key])) return self::$once[$name][$key];
		return self::$once[$name][$key] = call_user_func_array($fn, is_array($args) ? $args : [$args]);
	}
}
