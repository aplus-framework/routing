<?php declare(strict_types=1);
/*
 * This file is part of Aplus Framework Routing Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\Routing\Debug;

use Framework\Debug\Collector;
use Framework\Routing\RouteCollection;
use Framework\Routing\Router;

/**
 * Class RouterCollector.
 *
 * @package routing
 */
class RouterCollector extends Collector
{
    protected Router $router;

    public function setRouter(Router $router) : static
    {
        $this->router = $router;
        return $this;
    }

    public function getContents() : string
    {
        \ob_start(); ?>
        <h2>Matched Route</h2>
        <table>
            <thead>
            <tr>
                <th>Method</th>
                <th>Origin</th>
                <th>Path</th>
                <th>Action</th>
                <th>Name</th>
                <th>Has Options</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <?php
                $route = $this->router->getMatchedRoute(); ?>
                <td><strong><?= $this->router->getResponse()->getRequest()->getMethod() ?></strong>
                </td>
                <td><?= $this->router->getMatchedOrigin() ?></td>
                <td><?= $this->router->getMatchedPath() ?></td>
                <td><?= $route->getAction() instanceof \Closure
                        ? 'Closure'
                        : $route->getAction() ?></td>
                <td><?= $route->getName() ?></td>
                <td><?= $route->getOptions() ? 'Yes' : 'No' ?></td>
            </tr>
            </tbody>
        </table>
        <h2>Route Collections</h2>
        <?php foreach ($this->router->getCollections() as $collection): ?>
        <p><strong>Origin:</strong> <?= $this->toCodeBrackets($collection->origin) ?></p>
        <p><strong>Name:</strong> <?= $collection->name ?></p>
        <?php
        $notFound = $collection->getRouteNotFound(); // @phpstan-ignore-line
        if ($notFound) {
            $notFound = $notFound->getAction() instanceof \Closure
                ? 'Closure'
                : $notFound->getAction();
        } ?>
        <p><strong>Route Not Found:</strong> <?= $notFound ?></p>
        <p><strong>Routes Count:</strong> <?= \count($collection) ?></p>
        <table>
            <thead>
            <tr>
                <th>Method</th>
                <th>Path</th>
                <th>Action</th>
                <th>Name</th>
                <th>Has Options</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($this->getRoutes($collection) as $route): ?>
                <tr<?= $route['matched'] ? ' class="active" title="Matched Route"' : '' ?>>
                    <td><strong><?= $route['method'] ?></strong></td>
                    <td><?= $route['path'] ?></td>
                    <td><?= $route['action'] ?></td>
                    <td><?= $route['name'] ?></td>
                    <td><?= $route['hasOptions'] ?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
        <hr>
    <?php endforeach ?>
        <h2>Router Infos</h2>
        <p><strong>Auto Methods:</strong> <?= $this->router->isAutoMethods() ? 'On' : 'Off' ?></p>
        <p><strong>Auto Options:</strong> <?= $this->router->isAutoOptions() ? 'On' : 'Off' ?></p>
        <h3>Placeholders</h3>
        <table>
            <thead>
            <tr>
                <th>Placeholder</th>
                <th>Pattern</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($this->router->getPlaceholders() as $placeholder => $pattern): ?>
                <tr>
                    <td><code><?= $placeholder ?></code></td>
                    <td><code><?= $pattern ?></code></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
        <?php
        return \ob_get_clean(); // @phpstan-ignore-line
    }

    /**
     * @param RouteCollection $collection
     *
     * @return array<array<string,mixed>>
     */
    protected function getRoutes(RouteCollection $collection) : array
    {
        $result = [];
        foreach ($collection->routes as $method => $routes) {
            foreach ($routes as $route) {
                $result[] = [
                    'method' => $method,
                    'path' => $this->toCodeBrackets($route->getPath()),
                    'action' => \is_string($route->getAction()) ? $route->getAction() : '{closure}',
                    'name' => $route->getName(),
                    'hasOptions' => $route->getOptions() ? 'Yes' : 'No',
                    'matched' => $route === $this->router->getMatchedRoute(),
                ];
            }
        }
        \usort($result, static function ($str1, $str2) {
            $cmp = \strcmp($str1['path'], $str2['path']);
            if ($cmp === 0) {
                $cmp = \strcmp($str1['method'], $str2['method']);
            }
            return $cmp;
        });
        return $result;
    }

    protected function toCodeBrackets(string $str) : string
    {
        return \strtr($str, [
            '{' => '<code>{',
            '}' => '}</code>',
        ]);
    }
}
