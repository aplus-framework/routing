<?php declare(strict_types=1);
/*
 * This file is part of Aplus Framework Routing Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\Routing;

use Framework\HTTP\RequestInterface;
use Framework\HTTP\ResponseInterface;

/**
 * Interface ResourceInterface.
 *
 * The interface for data management via RESTful APIs
 * using all correct HTTP methods to manage a resource.
 *
 * Note: If a resource needs more than one parameter to get URL path information
 * provided by placeholders, in addition to $id, do not implement this interface.
 * But this interface can be a reference because its method names are used in
 * {@see RouteCollection::resource()}.
 *
 * @see https://developer.mozilla.org/en-US/docs/Glossary/REST
 *
 * @package routing
 */
interface ResourceInterface
{
    /**
     * Handles a GET request for /.
     *
     * Common usage: Show a list of paginated items.
     *
     * @see RequestInterface::METHOD_GET
     *
     * @return mixed
     */
    public function index() : mixed;

    /**
     * Handles a POST request for /.
     *
     * Common usage: Try to create an item. On success, set the Location header to
     * the 'show' method URL and return a 201 (Created) status code. On fail, return
     * a 400 (Bad Request) status code and list the error messages in the body.
     *
     * @see RequestInterface::METHOD_POST
     * @see ResourceInterface::show()
     * @see ResponseInterface::CODE_BAD_REQUEST
     * @see ResponseInterface::CODE_CREATED
     * @see ResponseInterface::HEADER_LOCATION
     *
     * @return mixed
     */
    public function create() : mixed;

    /**
     * Handles a GET request for /$id.
     *
     * Common usage: Show a specific item, based on the $id, in the body. If the item
     * does not exist, return an 404 (Not Found) status code.
     *
     * @param string $id
     *
     * @see RequestInterface::METHOD_GET
     * @see ResponseInterface::CODE_NOT_FOUND
     * @see ResponseInterface::CODE_OK
     *
     * @return mixed
     */
    public function show(string $id) : mixed;

    /**
     * Handles a PATCH request for /$id.
     *
     * Common usage: Try to update an item based on the $id. On success return a 200
     * (OK) status code and set the Location header to the 'show' method URL. On
     * fail, return a 400 (Bad Request) with the validation errors in the body.
     *
     * Note: The HTTP PATCH method allow items to be updated by parts. E.g.
     * it is possible to update only one, or more, fields in a database table
     * row.
     *
     * @param string $id
     *
     * @see RequestInterface::METHOD_PATCH
     * @see ResourceInterface::show()
     * @see ResponseInterface::CODE_BAD_REQUEST
     * @see ResponseInterface::CODE_OK
     * @see ResponseInterface::HEADER_LOCATION
     *
     * @return mixed
     */
    public function update(string $id) : mixed;

    /**
     * Handles a PUT request for /$id.
     *
     * Common usage: Try to replace an item based on the $id. On success return a 200
     * (OK) status code and set the Location header to the 'show' method URL. On
     * fail, return a 400 (Bad Request) with the validation errors in the body.
     *
     * Note: The HTTP PUT method requires an entire resource to be updated. E.g.
     * all fields in a database table row should be updated/replaced.
     *
     * @param string $id
     *
     * @see RequestInterface::METHOD_PUT
     * @see ResourceInterface::show()
     * @see ResponseInterface::CODE_BAD_REQUEST
     * @see ResponseInterface::CODE_OK
     * @see ResponseInterface::HEADER_LOCATION
     *
     * @return mixed
     */
    public function replace(string $id) : mixed;

    /**
     * Handles a DELETE request for /$id.
     *
     * Common usage: Delete an item based on the $id. On success, return a 204
     * (No Content) status code.
     *
     * @param string $id
     *
     * @see RequestInterface::METHOD_DELETE
     * @see ResponseInterface::CODE_NO_CONTENT
     *
     * @return mixed
     */
    public function delete(string $id) : mixed;
}
