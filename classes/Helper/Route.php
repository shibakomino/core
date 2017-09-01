<?php namespace Kohana\Helper;
/**
 * Created by PhpStorm.
 * User: Digi3
 * Date: 26/1/2015
 * Time: 13:38
 */
class Route
{
	public static $routes = [];

	public static function add_route($name, $uri, $regex, $defaults, $weight){
		Route::$routes[$name] = [
		  'uri' => $uri,
      'regex' => $regex,
      'defaults' => $defaults,
      'weight' => $weight,
    ];
	}

	public static function make_routes(){
		$routes_to_sort = self::$routes;
		usort($routes_to_sort, "\Kohana\Helper\sort_by_weight");

		foreach ($routes_to_sort as $key=>$value) {
			\Route::set($key,$value['uri'],$value['regex'])
				->defaults($value['defaults']);
		}
	}
}

function sort_by_weight($a, $b)
{
	return $b['weight'] - $a['weight'];
}