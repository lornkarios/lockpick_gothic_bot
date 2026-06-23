<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequestLogger
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        //here you can check the request to be logged
        Log::info($request->getUri(), [
            'uri' => $request->getUri(),
            'method' => $request->getMethod(),
            'request_body' => $request->all(),
            'response' => $response->getContent(),
            'request_headers' => $request->headers->all(),
        ]);
        return $response;
    }
}
