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

use Closure;
use Framework\Debug\Collector;
use Framework\Routing\RouteCollection;
use Framework\Routing\Router;

/**
 * Class RoutingCollector.
 *
 * @package routing
 */
class RoutingCollector extends Collector
{
    protected Router $router;

    public function setRouter(Router $router) : static
    {
        $this->router = $router;
        return $this;
    }

    public function getActivities() : array
    {
        $activities = [];
        $serveCount = 0;
        foreach ($this->getData() as $data) {
            if ($data['type'] === 'serve') {
                $serveCount++;
                $activities[] = [
                    'collector' => $this->getName(),
                    'class' => static::class,
                    'description' => 'Serve route collection ' . $serveCount,
                    'start' => $data['start'],
                    'end' => $data['end'],
                ];
            } elseif ($data['type'] === 'match') {
                $activities[] = [
                    'collector' => $this->getName(),
                    'class' => static::class,
                    'description' => 'Match route',
                    'start' => $data['start'],
                    'end' => $data['end'],
                ];
            } elseif ($data['type'] === 'run') {
                $activities[] = [
                    'collector' => $this->getName(),
                    'class' => static::class,
                    'description' => 'Run matched route',
                    'start' => $data['start'],
                    'end' => $data['end'],
                ];
            }
        }
        return $activities;
    }

    public function getContents() : string
    {
        if ( ! isset($this->router)) {
            return '<p>A Router instance has not been set on this collector.</p>';
        }
        \ob_start(); ?>
        <h1>Matched Route</h1>
        <?= $this->renderMatchedRoute() ?>
        <h1>Route Collections</h1>
        <?= $this->renderRouteCollections() ?>
        <h1>Router Infos</h1>
        <p><strong>Auto Methods:</strong> <?= $this->router->isAutoMethods() ? 'On' : 'Off' ?></p>
        <p><strong>Auto Options:</strong> <?= $this->router->isAutoOptions() ? 'On' : 'Off' ?></p>
        <p>
            <strong>Default Route Action Method:</strong> <?= \htmlentities($this->router->getDefaultRouteActionMethod()) ?>
        </p>
        <?php
        $notFound = $this->router->defaultRouteNotFound; // @phpstan-ignore-line
        if ($notFound): ?>
            <p><strong>Default Route Not Found:</strong> <?=
                $notFound instanceof Closure ? 'Closure' : \htmlentities($notFound)
            ?></p>
        <?php
        endif ?>
        <h2>Placeholders</h2>
        <?php
        $placeholders = [];
        foreach ($this->router->getPlaceholders() as $placeholder => $pattern) {
            $placeholders[\trim($placeholder, '{}')] = $pattern;
        }
        \ksort($placeholders); ?>
        <p>Total of <?= \count($placeholders) ?> placeholders.</p>
        <table>
            <thead>
            <tr>
                <th>Placeholder</th>
                <th>Pattern</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($placeholders as $placeholder => $pattern): ?>
                <tr>
                    <td><code>{<?= \htmlentities($placeholder) ?>}</code></td>
                    <td>
                        <pre><code class="language-regex"><?= \htmlentities($pattern) ?></code></pre>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
        <?php
        return \ob_get_clean(); // @phpstan-ignore-line
    }

    protected function renderMatchedRoute() : string
    {
        $route = $this->router->getMatchedRoute();
        if ($route === null) {
            return '<p>No matching route on this Router instance.</p>';
        }
        \ob_start(); ?>
        <table>
            <thead>
            <tr>
                <th title="Route Collection">RC</th>
                <th>Method</th>
                <th>Origin</th>
                <th>Path</th>
                <th>Action</th>
                <th>Name</th>
                <th>Has Options</th>
                <th title="Seconds">Time to Match</th>
                <th title="Seconds">Runtime</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><?php
                    foreach ($this->router->getCollections() as $index => $collection) {
                        if ($collection === $this->router->getMatchedCollection()) {
                            echo $index + 1;
                        }
                    } ?></td>
                <td><?= $this->router->getResponse()->getRequest()->getMethod() ?></td>

                <td><?= \htmlentities($this->router->getMatchedOrigin()) ?></td>
                <td><?= \htmlentities($this->router->getMatchedPath()) ?></td>
                <td><?= $route->getAction() instanceof Closure
                        ? 'Closure'
                        : \htmlentities($route->getAction()) ?></td>
                <td><?= \htmlentities((string) $route->getName()) ?></td>
                <td><?= $route->getOptions() ? 'Yes' : 'No' ?></td>
                <td><?php
                    foreach ($this->getData() as $data) {
                        if ($data['type'] === 'match') {
                            echo \round($data['end'] - $data['start'], 6);
                        }
                    } ?></td>
                <td><?php
                    foreach ($this->getData() as $data) {
                        if ($data['type'] === 'run') {
                            echo \round($data['end'] - $data['start'], 6);
                        }
                    } ?></td>
            </tr>
            </tbody>
        </table>
        <?php
        return \ob_get_clean(); // @phpstan-ignore-line
    }

    protected function renderRouteCollections() : string
    {
        $countCollections = \count($this->router->getCollections());
        if ($countCollections === 0) {
            return '<p>No route collection has been set.</p>';
        }
        $plural = $countCollections > 1;
        \ob_start(); ?>
        <p>There <?= $plural ? 'are' : 'is' ?> <?= $countCollections ?> route collection<?=
            $plural ? 's' : '' ?> set.
        </p>
        <?php
        foreach ($this->router->getCollections() as $index => $collection): ?>
            <h2>Route Collection <?= $index + 1 ?></h2>
            <p><strong>Origin:</strong> <?= $this->toCodeBrackets($collection->origin) ?></p>
            <?php
            if ($collection->name !== null): ?>
                <p><strong>Name:</strong> <?= $collection->name ?></p>
            <?php
            endif;
            $notFound = $collection->notFoundAction ?? null;
            if ($notFound !== null):
                ?>
                <p><strong>Route Not Found:</strong> <?= $notFound instanceof Closure
                        ? 'Closure'
                        : \htmlentities($notFound) ?></p>
            <?php
            endif;
            echo $this->renderRouteCollectionsTable($collection);
        endforeach;
        return \ob_get_clean(); // @phpstan-ignore-line
    }

    protected function renderRouteCollectionTime(RouteCollection $collection) : string
    {
        $contents = '';
        foreach ($this->getData() as $data) {
            if ($data['type'] === 'serve' && $data['collectionId'] === \spl_object_id($collection)) {
                $contents = '<p title="Seconds"><strong>Time to Serve:</strong> '
                    . \round($data['end'] - $data['start'], 6)
                    . '</p>';
                break;
            }
        }
        return $contents;
    }

    protected function renderRouteCollectionsTable(RouteCollection $collection) : string
    {
        $routesCount = \count($collection);
        \ob_start();
        echo '<p><strong>Routes Count:</strong> ' . $routesCount . '</p>';
        echo $this->renderRouteCollectionTime($collection);
        if ($routesCount === 0) {
            echo '<p>No route has been set in this collection.</p>';
            return \ob_get_clean(); // @phpstan-ignore-line
        }
        // @phpstan-ignore-next-line
        if ($routesCount === 1 && $collection->router->getMatchedOrigin() && $collection->getRouteNotFound()) {
            echo '<p>Only Route Not Found has been set in this collection.</p>';
            return \ob_get_clean(); // @phpstan-ignore-line
        } ?>
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>Method</th>
                <th>Path</th>
                <th>Action</th>
                <th>Name</th>
                <th>Has Options</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($this->getRoutes($collection) as $index => $route): ?>
                <tr<?= $route['matched'] ? ' class="active" title="Matched Route"' : '' ?>>
                    <td><?= ++$index ?></td>
                    <td><?= \htmlentities($route['method']) ?></td>
                    <td><?= $this->toCodeBrackets(\htmlentities($route['path'])) ?></td>
                    <td><?= \htmlentities($route['action']) ?></td>
                    <td><?= \htmlentities((string) $route['name']) ?></td>
                    <td><?= \htmlentities($route['hasOptions']) ?></td>
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
        $collectionRoutes = $collection->routes;
        \ksort($collectionRoutes);
        foreach ($collectionRoutes as $method => $routes) {
            foreach ($routes as $route) {
                $result[] = [
                    'method' => $method,
                    'path' => $route->getPath(),
                    'action' => \is_string($route->getAction()) ? $route->getAction() : 'Closure',
                    'name' => $route->getName(),
                    'hasOptions' => $route->getOptions() ? 'Yes' : 'No',
                    'matched' => $route === $this->router->getMatchedRoute(),
                ];
            }
        }
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
