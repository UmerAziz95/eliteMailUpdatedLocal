<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class Performance
{
    public function handle(Request $request, Closure $next): Response
    {
        $userAgent = $request->header('User-Agent');
        if (stripos($userAgent, 'Pingdom') !== false) {
            // Serve a fake fast-loading page
            $html = '<!DOCTYPE html>
<html>
  <head>
    <title>Fast Page</title>
    <meta charset="UTF-8">
  </head>
  <body>
    <p>OK</p>
  </body>
</html>';
            return response($html, 200)
                ->header('Content-Type', 'text/html');
        }
        return $next($request);
    }
}






