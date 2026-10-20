<?php

namespace App\Http\Middleware;

use App\Services\Kiosk\KioskOptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureKioskOptionAvailable
{
    public function __construct(private readonly KioskOptionService $options) {}

    public function handle(Request $request, Closure $next, string $option): Response
    {
        $status = $this->options->status($option);

        if ($status === KioskOptionService::AVAILABLE) {
            return $next($request);
        }

        $message = KioskOptionService::OPTIONS[$option].': '.KioskOptionService::STATUSES[$status].'.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 503);
        }

        return redirect()->route('kiosk.dashboard')->with('kiosk_unavailable', $message);
    }
}
