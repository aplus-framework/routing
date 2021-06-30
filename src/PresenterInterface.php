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
 * Interface PresenterInterface.
 */
interface PresenterInterface
{
	public function index();

	public function new();

	public function create();

	public function show($id);

	public function edit($id);

	public function update($id);

	public function remove($id);

	public function delete($id);
}
