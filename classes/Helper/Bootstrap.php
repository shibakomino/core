<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Created by PhpStorm.
 * User: colinleung
 * Date: 1/9/2017
 * Time: 11:39 AM
 */

class Helper_Bootstrap{
  public static $sub_request_handlers = array();

  public static function get_protocol(){


    if(!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])){
      return $_SERVER['HTTP_X_FORWARDED_PROTO'];
    }

    if(!empty($_SERVER['HTTPS']) || ($_SERVER['SERVER_PORT']==443)){
      return 'https';
    }

    return 'http';
  }

  private static function redirect_insecure(){
    if(!empty($_POST))return FALSE;
    if(!isset(Kohana::$config))return FALSE;
    $ssl_enable = Kohana::$config->load('site.ssl_enable');
    $current_protocol = self::get_protocol();

    if($ssl_enable === TRUE && $current_protocol == 'http'){
      if(preg_match('/^local./i', $_SERVER['HTTP_HOST'])==1)return FALSE;

      $url = "https://". $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

      //CHECK: why trim the query string.
//      if(!empty($_SERVER['QUERY_STRING'])){
//        $url = str_replace('?'.$_SERVER['QUERY_STRING'], '', $url);
//      }

      header('Location: '.$url);
      return TRUE;
    }

    return FALSE;
  }

  private static function redirect_upper_url(){
    //fix keep post data.
    if(!empty($_POST))return FALSE;

    $url = self::get_protocol() ."://". $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    if(!empty($_SERVER['QUERY_STRING'])){
      //do not alter query_string variables
      $url = str_replace('?'.$_SERVER['QUERY_STRING'], '', $url);
    }
    $r = preg_match_all('/[A-Z]/', $url, $matches);
    if($r > 0){
      $url = strtolower($url);
      if(!empty($_SERVER['QUERY_STRING'])){
        $url = $url.'?'.$_SERVER['QUERY_STRING'];
      }
      header('Location: '.$url);
      return TRUE;
    }
    return FALSE;
  }

  public static function executeRequest(){
    if(Helper_Bootstrap::redirect_insecure() == TRUE)return '<!-- redirect SSL -->';
    if(Helper_Bootstrap::redirect_upper_url() == TRUE)return '<!-- redirect upper url -->';

    $request    = Request::factory(TRUE,array(),FALSE);
    $response   = $request->execute();//param need to parse after execute.

    //default city name;
    //TODO: default redirection
    //Helper_Server::redirect_domain_by_city($params['city']);


    //the status code will generate after execute;
    //if status = 404, run the sub request handlers
    //sub-request
    if($response->status() == 404){
      foreach(self::$sub_request_handlers as $handler){
        $response = $handler($request);
        if($response->status()<400)break;// success, no need to handle by next handler
      }

    };

    $result = $response
      ->send_headers(TRUE)
      ->body();

    if(Kohana::getEnvironment() == Kohana::DEVELOPMENT){
      $result .= self::append_debug_message($request, $response);
    }
    return $result;
  }

  private static function append_debug_message($request, $response){
    if($request->param('format')=='php'){
      $debug_msg = $response->headers('X-D3CMS');
      if(!empty($debug_msg)){
        return PHP_EOL.'<!-- '.$debug_msg.' -->';
      }
    }
  }
}
