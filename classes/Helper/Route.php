<?php
/**
 * Created by PhpStorm.
 * User: Digi3
 * Date: 26/1/2015
 * Time: 13:38
 */
class Helper_Route
{
	public static $routes = array();
	public static function add_route($name,$uri,$regex,$defaults,$weight){
		Helper_Route::$routes[$name] = [
		  'uri' => $uri,
      'regex' => $regex,
      'defaults' => $defaults,
      'weight' => $weight,
    ];
	}

	public static function make_routes(){
		$routes_to_sort = Helper_Route::$routes;
		usort($routes_to_sort, "sort_by_weight");

		foreach ($routes_to_sort as $key=>$value) {
			Route::set($key,$value['uri'],$value['regex'])
				->defaults($value['defaults']);
		}
	}
}

function sort_by_weight($a, $b)
{
	return $b['weight'] - $a['weight'];
}