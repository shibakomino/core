<?php
namespace Kohana\HTTP;

/**
 * A HTTP Request specific interface that adds the methods required
 * by HTTP requests. Over and above [Kohana_HTTP_Interaction], this
 * interface provides method, uri, get and post methods.
 *
 * @package    Kohana
 * @category   HTTP
 * @author     Kohana Team
 * @since      3.1.0
 * @copyright  (c) 2008-2014 Kohana Team
 * @license    http://kohanaframework.org/license
 */
interface IRequest extends IMessage
{
    /**
     * Gets or sets the HTTP method. Usually GET, POST, PUT or DELETE in
     * traditional CRUD applications.
     *
     * @param   string $method Method to use for this request
     * @return  mixed
     */
    public function method($method = null);

    /**
     * Gets the URI of this request, optionally allows setting
     * of [Route] specific parameters during the URI generation.
     * If no parameters are passed, the request will use the
     * default values defined in the Route.
     *
     * @return  string
     */
    public function uri();

    /**
     * Gets or sets HTTP query string.
     *
     * @param   mixed $key Key or key value pairs to set
     * @param   string $value Value to set to a key
     * @return  mixed
     */
    public function query($key = null, $value = null);

    /**
     * Gets or sets HTTP POST parameters to the request.
     *
     * @param   mixed $key Key or key value pairs to set
     * @param   string $value Value to set to a key
     * @return  mixed
     */
    public function post($key = null, $value = null);

}
