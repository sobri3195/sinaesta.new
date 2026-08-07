<?php

declare(strict_types=1);

namespace Sinaesta\Shared\Http;

final class Router
{
    /** @var list<array{method:string,path:string,handler:callable,middleware:list<callable>}> */
    private array $routes = [];
    private string $prefix = '';
    /** @var list<callable> */ private array $groupMiddleware = [];

    public function get(string $path, callable $handler, array $middleware = []): void { $this->add('GET', $path, $handler, $middleware); }
    public function post(string $path, callable $handler, array $middleware = []): void { $this->add('POST', $path, $handler, $middleware); }
    public function put(string $path, callable $handler, array $middleware = []): void { $this->add('PUT', $path, $handler, $middleware); }
    public function patch(string $path, callable $handler, array $middleware = []): void { $this->add('PATCH', $path, $handler, $middleware); }
    public function delete(string $path, callable $handler, array $middleware = []): void { $this->add('DELETE', $path, $handler, $middleware); }

    public function group(string $prefix, callable $routes, array $middleware = []): void
    {
        $previousPrefix = $this->prefix; $previousMiddleware = $this->groupMiddleware;
        $this->prefix .= '/' . trim($prefix, '/');
        $this->groupMiddleware = [...$this->groupMiddleware, ...$middleware];
        $routes($this);
        $this->prefix = $previousPrefix; $this->groupMiddleware = $previousMiddleware;
    }

    private function add(string $method, string $path, callable $handler, array $middleware): void
    {
        $fullPath = '/' . trim($this->prefix . '/' . trim($path, '/'), '/');
        $this->routes[] = ['method' => $method, 'path' => $fullPath, 'handler' => $handler, 'middleware' => [...$this->groupMiddleware, ...$middleware]];
    }

    public function dispatch(Request $request): Response
    {
        $allowed = [];
        foreach ($this->routes as $route) {
            $pattern = preg_replace_callback('/\{([A-Za-z_][A-Za-z0-9_]*)\}/', static fn(array $m): string => '(?P<' . $m[1] . '>[^/]+)', $route['path']);
            if (!preg_match('#^' . $pattern . '$#', $request->path, $matches)) { continue; }
            if ($route['method'] !== $request->method) { $allowed[] = $route['method']; continue; }
            foreach ($matches as $key => $value) { if (is_string($key)) { $request = $request->withAttribute($key, rawurldecode($value)); } }
            $next = static fn(Request $req): Response => ($route['handler'])($req);
            foreach (array_reverse($route['middleware']) as $middleware) {
                $downstream = $next;
                $next = static fn(Request $req): Response => $middleware($req, $downstream);
            }
            return $next($request);
        }
        if ($allowed !== []) { return new Response(405, ['success'=>false,'message'=>'Metode tidak diizinkan','data'=>null,'meta'=>null,'errors'=>null], ['Allow' => implode(', ', array_unique($allowed))]); }
        return Response::error('Endpoint tidak ditemukan', 404);
    }
}
