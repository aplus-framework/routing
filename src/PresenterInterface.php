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

use Framework\HTTP\Method;
use Framework\HTTP\Response;
use Framework\HTTP\Status;

/**
 * Interface PresenterInterface.
 *
 * The interface for data management via a Web Browser UI
 * using the HTTP GET and POST methods.
 *
 * Note: If a presenter needs more than one parameter to get URL path information
 * provided by placeholders, in addition to $id, do not implement this interface.
 * But this interface can be a reference because its method names are used in
 * {@see RouteCollection::presenter()}.
 *
 * @see https://developer.mozilla.org/en-US/docs/Glossary/UI
 *
 * @package routing
 */
interface PresenterInterface
{
    /**
     * Handles a GET request for /.
     *
     * Common usage: Show a list of paginated items.
     *
     * @see Method::GET
     *
     * @return mixed
     */
    public function index() : mixed;

    /**
     * Handles a GET request for /new.
     *
     * Common usage: Show a form with inputs to create a new item.
     * The POST action must go to the 'create' method URL.
     *
     * @see PresenterInterface::create()
     * @see Method::GET
     *
     * @return mixed
     */
    public function new() : mixed;

    /**
     * Handles a POST request for /.
     *
     * Common usage: Try to create a new item. On success, redirect to the 'show' or
     * 'edit' method URL. On fail, back to the 'new' method URL.
     *
     * @see PresenterInterface::edit()
     * @see PresenterInterface::new()
     * @see PresenterInterface::show()
     * @see Method::POST
     * @see Response::redirect()
     *
     * @return mixed
     */
    public function create() : mixed;

    /**
     * Handles a GET request for /$id.
     *
     * Common usage: Show a specific item based on the $id.
     *
     * @param string $id
     *
     * @see Method::GET
     * @see Status::NOT_FOUND
     *
     * @return mixed
     */
    public function show(string $id) : mixed;

    /**
     * Handles a GET request for /$id/edit.
     *
     * Common usage: Show a form to edit a specific item based on the $id.
     * The POST action must go to the 'update' method URL.
     *
     * @param string $id
     *
     * @see PresenterInterface::update()
     * @see Method::GET
     *
     * @return mixed
     */
    public function edit(string $id) : mixed;

    /**
     * Handles a POST request for /$id/update.
     *
     * Common usage: Try to update an item based on the $id. After the process, back
     * to the 'edit' method URL and show a message.
     *
     * @param string $id
     *
     * @see PresenterInterface::edit()
     * @see Method::POST
     * @see Response::redirect()
     *
     * @return mixed
     */
    public function update(string $id) : mixed;

    /**
     * Handles a GET request for /$id/remove.
     *
     * Common usage: Show an alert message about the item to be deleted based on the
     * $id. The confirmation action must call a POST request to the 'delete'
     * method URL.
     *
     * @param string $id
     *
     * @see PresenterInterface::delete()
     * @see Method::GET
     *
     * @return mixed
     */
    public function remove(string $id) : mixed;

    /**
     * Handles a POST request for /$id/delete.
     *
     * Common usage: Try to delete an item based on the $id. On success, go to the
     * 'index' method URL and show a success message. On fail, back to the 'remove'
     * method URL and show the error message.
     *
     * @param string $id
     *
     * @see PresenterInterface::index()
     * @see PresenterInterface::remove()
     * @see Method::POST
     * @see Response::redirect()
     *
     * @return mixed
     */
    public function delete(string $id) : mixed;
}
