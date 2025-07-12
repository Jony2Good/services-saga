<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Prometheus\CollectorRegistry;
use Throwable;

class PrometheusMiddleware
{
    private $registry;
    private $counter;
    private $gauge;
    private $memoryGauge;
    private $histogram;
    private $errorCounter;

    public function __construct(CollectorRegistry $registry)
    {
        $this->registry = $registry;

        $this->counter = $registry->getOrRegisterCounter(
            'app',
            'http_requests_total',
            'Total number of HTTP requests',
            ['status', 'path', 'method']
        );

        $this->errorCounter = $registry->getOrRegisterCounter(
            'app',
            'http_5xx_errors_total',
            'Total number of 5xx HTTP errors',
            ['path', 'method']
        );

        $this->gauge = $this->registry->getOrRegisterGauge(
            'app',
            'http_active_requests',
            'Number of active HTTP requests',
        );

        $this->memoryGauge = $this->registry->getOrRegisterGauge(
            'app',
            'memory_usage_bytes',
            'Current memory usage in bytes',
            ['type']
        );

        $this->histogram = $registry->getOrRegisterHistogram(
            'app',
            'http_request_duration_seconds',
            'HTTP request duration in seconds',
            ['status', 'path', 'method'],
            [0.1, 0.25, 0.5, 1, 2.5, 5]
        );
    }

    public function handle(Request $request, Closure $next)
    {
        $this->gauge->inc();

        $start = microtime(true);

        try {
            $response = $next($request);
        } catch (\Throwable $e) {

            \Log::info($e->getMessage());

            $this->errorCounter->inc([
                'path' => $request->path(),
                'method' => $request->method()
            ]);

            $this->gauge->dec();

            throw $e;
        }

        $duration = microtime(true) - $start;

        $status = $response->getStatusCode();

        $this->counter->inc([
            'status' => $status,
            'path' => $request->path(),
            'method' => $request->method()
        ]);

        if ($status >= 500 && $status < 600) {
            $this->errorCounter->inc([
                'path' => $request->path(),
                'method' => $request->method()
            ]);
        }

        $this->memoryGauge->set(memory_get_usage(true), ['real']);
        $this->memoryGauge->set(memory_get_usage(false), ['emalloc']);

        $this->histogram->observe(
            $duration,
            [
                'status' => $response->getStatusCode(),
                'path' => $request->path(),
                'method' => $request->method()
            ]
        );

        $this->gauge->dec();

        return $response;
    }
}
