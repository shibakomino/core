###Arr
- no diff
 
###Controller
- no diff

###HTTP_Header
- expose
  - ` Fatal error: Access to undeclared static property: Kohana::$expose in /Users/colinleung/www/local.kohana.com/web/system/classes/Kohana/HTTP/Header.php on line 886 `
- content type
  - `Fatal error: Access to undeclared static property: Kohana::$content_type in /Users/colinleung/www/local.kohana.com/web/system/classes/Kohana/HTTP/Header.php on line 883`
 
###HTTP_Message
- no diff
 
###HTTP_Request
- no diff
 
###HTTP_Response
- no diff

###HTTP
- no diff

###Request_Client_Internal
- profiling
  - `Fatal error: Access to undeclared static property: Kohana::$profiling in /Users/colinleung/www/local.kohana.com/web/system/classes/Kohana/Request/Client/Internal.php on line 48`
- exception
  - `Fatal error: Class 'HTTP_Exception' not found in /Users/colinleung/www/local.kohana.com/web/system/classes/Kohana/Request/Client/Internal.php on line 76`

###Request_Client
- no diff

###Request
- kohana::$base_url
  - `Fatal error: Cannot access private property Kohana::$base_url in /Users/colinleung/www/local.kohana.com/web/system/classes/Kohana/Request.php on line 265`
- //remove the base URL from URI

###Response
- no diff

###Route
- no diff

###URL
- add static $base_url = '/';

###View
- View_Exception

---
##Handle Request
index.php
  - include bootstrap

    - Kohana::init()
	   - start auto loader
	   - clear register_globals
	   - ob_start
	   - sanitize $_GET, _$POST, $_COOKIE, $_REQUEST
	   - handle module list
	   - store system enviorment [DEVELOPMENT | TESTING | PRODUCTION]

     - /Kohana/URL.php
	   - set URL base

	 - **Kohana::module_init();**
	   - initialize modules

	 - /Kohana/Route.php
	   - compile the Route

/Helper/Bootstrap.php
  - Helper_Bootstrap::executeRequest()
    - redirect upper case
    - redirect by domain

      /Kohana/Request.php  imp /Kohana/HTTP/Request.php
      -- make Request
	      /Kohana/HTTP/Message.php
		  /Kohana/HTTP.php
		  /Kohana/HTTP/Header.php
		  /Kohana/Request/Client/Internal.php
		  /Kohana/Request/Client.php

      -- get Response from Request->excute()
			/Kohana/Arr.php
			/Kohana/Response.php
			/Kohana/HTTP/Response.php
			-- concrete controller 
			/Kohana/Controller.php
          --if response status == 404, do sub-request;

      -- response send header.
      -- echo body