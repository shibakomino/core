<?php
namespace Kohana\HTTP;

use \HTTP_Message as HTTP_Message;

/**
 * A HTTP Response specific interface that adds the methods required
 * by HTTP responses. Over and above [Kohana_HTTP_Interaction], this
 * interface provides status.
 *
 * @package    Kohana
 * @category   HTTP
 * @author     Kohana Team
 * @since      3.1.0
 * @copyright  (c) 2008-2014 Kohana Team
 * @license    http://kohanaframework.org/license
 */
interface Response extends HTTP_Message {

	/**
	 * Sets or gets the HTTP status from this response.
	 *
	 *      // Set the HTTP status to 404 Not Found
	 *      $response = Response::factory()
	 *              ->status(404);
	 *
	 *      // Get the current status
	 *      $status = $response->status();
	 *
	 * @param   integer  $code  Status to set to this response
	 * @return  mixed
	 */
	public function status($code = NULL);

}
