<?php declare(strict_types=1);
/*
 * This file is part of The Framework Routing Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\Routing;

/**
 * Interface ResourceInterface.
 *
 * The interface for data management via RESTful APIs
 * using all correct HTTP methods to manage a resource.
 *
 * @see https://developer.mozilla.org/en-US/docs/Glossary/REST
 */
interface ResourceInterface
{
	/**
	 * Handles a GET request for /.
	 *
	 * Usage: Show a list of paginated items.
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/GET
	 *
	 * @return mixed
	 */
	public function index();

	/**
	 * Handles a POST request for /.
	 *
	 * Usage: Try to create an item. On success, set the Location header to
	 * the 'show' method and return a 201 (Created) status code. On fail, return
	 * a 400 (Bad Request) status code and list the error messages in the body.
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/POST
	 *
	 * @return mixed
	 */
	public function create();

	/**
	 * Handles a GET request for /$id.
	 *
	 * Usage: Show a specific item, based on the $id, in the body. If the item
	 * does not exists, return an 404 (Not Found) status code.
	 *
	 * @param $id
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/GET
	 *
	 * @return mixed
	 */
	public function show($id);

	/**
	 * Handles a PATCH request for /$id.
	 *
	 * Usage: Try to update an item based on the $id. On success return a 200
	 * (OK) status code and set the Location header to the 'show' method. On
	 * fail, return a 400 (Bad Request) with the validation errors in the body.
	 *
	 * NOTE: The HTTP PATCH method allow items to be updated by parts. E.g.
	 * it is possible to update only one, or more, fields in a database table.
	 *
	 * @param $id
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/PATCH
	 *
	 * @return mixed
	 */
	public function update($id);

	/**
	 * Handles a PUT request for /$id.
	 *
	 * Usage: Try to replace an item based on the $id. On success return a 200
	 * (OK) status code and set the Location header to the 'show' method. On
	 * fail, return a 400 (Bad Request) with the validation errors in the body.
	 *
	 * NOTE: The HTTP PUT method requires an entire resource to be updated. E.g.
	 * all fields in a database table must be updated/replaced.
	 *
	 * @param $id
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/PUT
	 *
	 * @return mixed
	 */
	public function replace($id);

	/**
	 * Handles a DELETE request for /$id.
	 *
	 * Usage: Delete an item based on the $id. On success, must return a 204
	 * (No Content) status code.
	 *
	 * @param $id
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/DELETE
	 *
	 * @return mixed
	 */
	public function delete($id);
}
