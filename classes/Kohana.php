<?php
/**
 * Contains the most low-level helpers methods in Kohana:
 *
 * - Environment initialization
 * - Locating files within the cascading filesystem
 * - Auto-loading and transparent extension of classes
 * - Variable and path debugging
 *
 * @package    Kohana
 * @category   Base
 * @author     Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    http://kohanaframework.org/license
 */
final class Kohana {
  // Release version and codename
  const VERSION  = '3.7';
  const CODENAME = '';

  // Common environment type constants for consistency and convenience
  const PRODUCTION  = 10;
  const STAGING     = 20;
  const TESTING     = 30;
  const DEVELOPMENT = 40;

  /**
   * @var  string  Current environment name
   */
  private static $environment = self::PRODUCTION;
  private static $_init = FALSE;

  /**
   * @var  array   Currently active modules
   */
  private static $_modules = [];

  /**
   * @var  array   Include paths that are used to find files
   */
  private static $_paths = [APPPATH, SYSPATH];

  /**
   * Initializes the environment:
   *
   * - Disables register_globals and magic_quotes_gpc
   * - Determines the current environment
   * - Set global settings
   * - Sanitizes GET, POST, and COOKIE variables
   * - Converts GET, POST, and COOKIE variables to the global character set
   *
   * The following settings can be set:
   *
   * Type      | Setting    | Description                                    | Default Value
   * ----------|------------|------------------------------------------------|---------------
   * `string`  | base_url   | The base URL for your application.  This should be the *relative* path from your DOCROOT to your `index.php` file, in other words, if Kohana is in a subfolder, set this to the subfolder name, otherwise leave it as the default.  **The leading slash is required**, trailing slash is optional.   | `"/"`
   * `string`  | index_file | The name of the [front controller](http://en.wikipedia.org/wiki/Front_Controller_pattern).  This is used by Kohana to generate relative urls like [HTML::anchor()] and [URL::base()]. This is usually `index.php`.  To [remove index.php from your urls](tutorials/clean-urls), set this to `FALSE`. | `"index.php"`
   * `string`  | charset    | Character set used for all input and output    | `"utf-8"`
   * `string`  | cache_dir  | Kohana's cache directory.  Used by [self::cache] for simple internal caching, like [Fragments](kohana/fragments) and **\[caching database queries](this should link somewhere)**.  This has nothing to do with the [Cache module](cache). | `APPPATH."cache"`
   * `integer` | cache_life | Lifetime, in seconds, of items cached by [self::cache]         | `60`
   * `boolean` | errors     | Should Kohana catch PHP errors and uncaught Exceptions and show the `error_view`. See [Error Handling](kohana/errors) for more info. <br /> <br /> Recommended setting: `TRUE` while developing, `FALSE` on production servers. | `TRUE`
   * `boolean` | profile    | Whether to enable the [Profiler](kohana/profiling). <br /> <br />Recommended setting: `TRUE` while developing, `FALSE` on production servers. | `TRUE`
   * `boolean` | caching    | Cache file locations to speed up [self::find_file].  This has nothing to do with [self::cache], [Fragments](kohana/fragments) or the [Cache module](cache).  <br /> <br />  Recommended setting: `FALSE` while developing, `TRUE` on production servers. | `FALSE`
   * `boolean` | expose     | Set the X-Powered-By header
   *
   * @param   string   $enviorment.
   * @return  void
   * @uses    self::globals
   * @uses    self::sanitize
   */
  public static function init($enviorment)
  {
    //guard initalize twice.
    if (self::$_init) return;
    self::$_init = true;

    self::$environment = $enviorment;

    //guard unusal global settings
    if (ini_get('register_globals')) {
      // Reverse the effects of register_globals
      self::globals();
    }

    // Start an output buffer
    ob_start();


    // Sanitize all request variables
    $_GET    = self::sanitize($_GET);
    $_POST   = self::sanitize($_POST);
    $_COOKIE = self::sanitize($_COOKIE);
  }

  /**
   * Reverts the effects of the `register_globals` PHP setting by unsetting
   * all global variables except for the default super globals (GPCS, etc),
   * which is a [potential security hole.][ref-wikibooks]
   *
   * This is called automatically by [self::init] if `register_globals` is
   * on.
   *
   *
   * [ref-wikibooks]: http://en.wikibooks.org/wiki/PHP_Programming/Register_Globals
   *
   * @return  void
   */
  public static function globals()
  {
    if (isset($_REQUEST['GLOBALS']) OR isset($_FILES['GLOBALS'])) {
      // Prevent malicious GLOBALS overload attack
      echo "Global variable overload attack detected! Request aborted.\n";

      // Exit with an error status
      exit(1);
    }

    // Get the variable names of all globals
    $global_variables = array_keys($GLOBALS);

    // Remove the standard global variables from the list
    $global_variables = array_diff($global_variables, [
        '_COOKIE',
        '_ENV',
        '_GET',
        '_FILES',
        '_POST',
        '_REQUEST',
        '_SERVER',
        '_SESSION',
        'GLOBALS',
    ]);

    foreach ($global_variables as $name) {
      // Unset the global variable, effectively disabling register_globals
      unset($GLOBALS[$name]);
    }
  }

  /**
   * Recursively sanitizes an input variable:
   *
   * - Strips slashes if magic quotes are enabled
   * - Normalizes all newlines to LF
   *
   * @param   mixed   $value  any variable
   * @return  mixed   sanitized variable
   */
  public static function sanitize($value)
  {
    if (is_array($value) OR is_object($value)) {
      foreach ($value as $key => $val) {
        // Recursively clean each value
        $value[$key] = self::sanitize($val);
      }
    }
    elseif (is_string($value)) {
      if (strpos($value, "\r") !== FALSE) {
        // Standardize newlines
        $value = str_replace(array("\r\n", "\r"), "\n", $value);
      }
    }

    return $value;
  }

  //new autoloader compliant PSR4
  public static function auto_load_PSR4($class, $directory = 'classes')
  {
    // Transform the class name according to PSR-4
    $class     = ltrim($class, '\\');
    $file      = '';
    $namespace = '';
    $subnamespace = '';

    if ($last_namespace_position = strripos($class, '\\')) {
      $matches = [];
      preg_match_all('/[^\\\]+/i', $class, $matches);

//    $namespace = array_shift($matches[0]);
      $class = array_pop($matches[0]);
      $subnamespace  = implode(DIRECTORY_SEPARATOR, $matches[0]);

      $file      = $subnamespace.DIRECTORY_SEPARATOR.$class;
    }else{
      //no back slash found, use PSR-0
      $file = str_replace('_', DIRECTORY_SEPARATOR, $class);
    }

    if ($path = self::find_file($directory, $file)) {
      // Load the class file
      require $path;

      // Class has been found
      return TRUE;
    }

    // Class is not in the filesystem
    return FALSE;
  }

  /**
   * Loads a file within a totally empty scope and returns the output:
   *
   *     $foo = Kohana::load('foo.php');
   *
   * @param   string  $file
   * @return  mixed
   */
  public static function load($file)
  {
    return include $file;
  }

  /**
   * Changes the currently enabled modules. Module paths may be relative
   * or absolute, but must point to a directory:
   *
   *     self::modules(array('modules/foo', MODPATH.'bar'));
   *
   * @param   array   $modules                  list of module paths
   * @param   boolean $display_missing_module   should it echo the missing module name
   * @return  boolean                           is modules ready to init?
   */

  public static function modules($modules, $display_missing_module)
  {
    if(!isset($modules))return false;

    // Start a new list of include paths, APPPATH first
    $paths = [APPPATH];

    foreach ($modules as $name => $path) {
      if(!is_dir($path)){
        //module path is missing, break it.
        if($display_missing_module){
          echo 'module '. $name .' is missing';
        }
        return false;
      }

      // Add the module to include paths
      $paths[] = $modules[$name] = realpath($path).DIRECTORY_SEPARATOR;
    }

    // Finish the include paths by adding SYSPATH
    $paths[] = SYSPATH;

    // Set the new include paths
    self::$_paths = $paths;

    // Set the current module list
    self::$_modules = $modules;

    return true;
  }

  /**
   * Initialize the modules by running [module]/init.php
  **/

  public static function modules_init(){
    foreach (self::$_modules as $path) {
      $init = $path.'init'.EXT;
      if (is_file($init)) {
        // Include the module initialization file once
        require_once $init;
      }
    }
  }

  /**
   * Returns the the currently active include paths, including the
   * application, system, and each module's path.
   *
   * @return  array
   */
  public static function include_paths()
  {
    return self::$_paths;
  }

  /**
   * Searches for a file in the [Cascading Filesystem](kohana/files), and
   * returns the path to the file that has the highest precedence, so that it
   * can be included.
   *
   * When searching the "config", "messages", or "i18n" directories, or when
   * the `$array` flag is set to true, an array of all the files that match
   * that path in the [Cascading Filesystem](kohana/files) will be returned.
   * These files will return arrays which must be merged together.
   *
   * If no extension is given, the default extension (`EXT` set in
   * `index.php`) will be used.
   *
   *     // Returns an absolute path to views/template.php
   *     self::find_file('views', 'template');
   *
   *     // Returns an absolute path to media/css/style.css
   *     self::find_file('media', 'css/style', 'css');
   *
   *     // Returns an array of all the "mimes" configuration files
   *     self::find_file('config', 'mimes');
   *
   * @param   string  $dir    directory name (views, i18n, classes, extensions, etc.)
   * @param   string  $file   filename with subdirectory
   * @param   string  $ext    extension to search for
   * @param   boolean $array  return an array of files?
   * @return  array   a list of files when $array is TRUE
   * @return  string  single file path
   */
  public static function find_file($dir, $file, $ext = NULL, $array = FALSE)
  {
    if ($ext === NULL)
    {
      // Use the default extension
      $ext = EXT;
    }
    elseif ($ext)
    {
      // Prefix the extension with a period
      $ext = ".{$ext}";
    }
    else
    {
      // Use no extension
      $ext = '';
    }

    // Create a partial path of the filename
    $path = $dir.DIRECTORY_SEPARATOR.$file.$ext;

    if ($array OR $dir === 'config' OR $dir === 'i18n' OR $dir === 'messages')
    {
      // Include paths must be searched in reverse
      $paths = array_reverse(self::$_paths);

      // Array of files that have been found
      $found = array();

      foreach ($paths as $dir)
      {
        if (is_file($dir.$path))
        {
          // This path has a file, add it to the list
          $found[] = $dir.$path;
        }
      }
    }
    else
    {
      // The file has not been found yet
      $found = FALSE;

      foreach (self::$_paths as $dir)
      {
        if (is_file($dir.$path))
        {
          // A path has been found
          $found = $dir.$path;

          // Stop searching
          break;
        }
      }
    }

    return $found;
  }

  /**
   * Recursively finds all of the files in the specified directory at any
   * location in the [Cascading Filesystem](kohana/files), and returns an
   * array of all the files found, sorted alphabetically.
   *
   *     // Find all view files.
   *     $views = self::list_files('views');
   *
   * @param   string  $directory  directory name
   * @param   array   $paths      list of paths to search
   * @return  array
   */
  public static function list_files($directory = NULL, array $paths = NULL)
  {
    if ($directory !== NULL)
    {
      // Add the directory separator
      $directory .= DIRECTORY_SEPARATOR;
    }

    if ($paths === NULL)
    {
      // Use the default paths
      $paths = self::$_paths;
    }

    // Create an array for the files
    $found = array();

    foreach ($paths as $path)
    {
      if (is_dir($path.$directory))
      {
        // Create a new directory iterator
        $dir = new DirectoryIterator($path.$directory);

        foreach ($dir as $file)
        {
          // Get the file name
          $filename = $file->getFilename();

          if ($filename[0] === '.' OR $filename[strlen($filename)-1] === '~')
          {
            // Skip all hidden files and UNIX backup files
            continue;
          }

          // Relative filename is the array key
          $key = $directory.$filename;

          if ($file->isDir())
          {
            if ($sub_dir = self::list_files($key, $paths))
            {
              if (isset($found[$key]))
              {
                // Append the sub-directory list
                $found[$key] += $sub_dir;
              }
              else
              {
                // Create a new sub-directory list
                $found[$key] = $sub_dir;
              }
            }
          }
          else
          {
            if ( ! isset($found[$key]))
            {
              // Add new files to the list
              $found[$key] = realpath($file->getPathName());
            }
          }
        }
      }
    }

    // Sort the results alphabetically
    ksort($found);

    return $found;
  }

  public static function getEnvironment(){
    return self::$environment;
  }
}
