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

/**
 * Interface PresenterInterface.
 *
 * The interface for data management via a Web Browser UI
 * using the HTTP GET and POST methods.
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
     * Usage: Show a list of paginated items.
     *
     * @return mixed
     */
    public function index() : mixed;

    /**
     * Handles a GET request for /new.
     *
     * Usage: Show a form with inputs to create a new item.
     * The POST action must go to the 'create' method.
     *
     * @return mixed
     */
    public function new() : mixed;

    /**
     * Handles a POST request for /.
     *
     * Usage: Try to create a new item. On success, redirect to the 'show' or
     * 'edit' method. On fail, back to the 'new' method.
     *
     * @return mixed
     */
    public function create() : mixed;

    /**
     * Handles a GET request for /$id.
     *
     * Usage: Show a specific item based on the $id.
     *
     * @param string $id
     *
     * @return mixed
     */
    public function show(string $id) : mixed;

    /**
     * Handles a GET request for /$id/edit.
     *
     * Usage: Show a form to edit a specific item based on the $id.
     * The POST action must go to the 'update' method.
     *
     * @param string $id
     *
     * @return mixed
     */
    public function edit(string $id) : mixed;

    /**
     * Handles a POST request for /$id/update.
     *
     * Usage: Try to update an item based on the $id. After the process, back
     * to the 'edit' method and show a message.
     *
     * @param string $id
     *
     * @return mixed
     */
    public function update(string $id) : mixed;

    /**
     * Handles a GET request for /$id/remove.
     *
     * Usage: Show an alert message about the item to be deleted based on the
     * $id. The confirmation action must call a POST request to the 'delete'
     * method.
     *
     * @param string $id
     *
     * @return mixed
     */
    public function remove(string $id) : mixed;

    /**
     * Handles a POST request for /$id/delete.
     *
     * Usage: Try to delete an item based on the $id. On success, go to the
     * 'index' method and show a success message. On fail, back to the 'remove'
     * method and show the error message.
     *
     * @param string $id
     *
     * @return mixed
     */
    public function delete(string $id) : mixed;
}
