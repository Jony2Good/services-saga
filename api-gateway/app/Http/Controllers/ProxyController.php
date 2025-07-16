<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProxyController extends Controller
{
    public function index(string $service, string $any = '', Request $request)
    {
        $serviceMap = [
            'auth'    => 'auth-service',
            'billing' => 'billing-service',
            'orders'   => 'order-service',
            'notifications' => 'notification-service',
            'stock' => 'stock-service',
            'delivery' => 'delivery-service'
        ];

        if (!isset($serviceMap[$service])) {
            return response()->json(['error' => 'Указанный в запросе сервис в системе не найден'], 404);
        }

        $namespace = 'k8s-basics';
        $serviceHost = $serviceMap[$service] . ".{$namespace}.svc.cluster.local";
        $url = "http://$serviceHost/api/{$any}";

        if (!preg_match('#/(login|register|refresh)#', $any)) {
            $token = $request->bearerToken();

            if (!$token) {
                return response()->json(['error' => 'В запросе отсутствует токен авторизации'], 401);
            }

            try {
                $authResponse = Http::withToken($token)->get("http://auth-service.k8s-basics.svc.cluster.local/api/v1/check");

                if ($authResponse->status() !== 200) {
                    return response()->json(['error' => 'Ошибка в токене'], 401);
                }
            } catch (\Exception $e) {
                return response()->json(['error' => 'Сервис аутентификации недоступен'], 503);
            }
        }


        $response = Http::withHeaders($request->headers->all())
            ->send($request->method(), $url, [
                'query' => $request->query(),
                'body'  => $request->getContent(),
            ]);


        return response($response->body(), $response->status())
            ->withHeaders($response->headers());
    }


    //     public function index(string $service, string $any = '', Request $request)
    //     {
    //         // Для локальной разработки
    //         // $serviceMap = [
    //         //     'order' => 'http://127.0.0.1:8082', 
    //         //     'auth'  => 'http://127.0.0.1:8081', 
    //         // ];

    // \Log::info($service);
    //         // Для docker
    //         $serviceMap = [
    //             'auth' => 'http://auth-service-nginx', 
    //             'order'  => 'http://order-service-nginx', 
    //         ];
    // \Log::error($any);
    //         if (!isset($serviceMap[$service])) {
    //             return response()->json(['error' => 'Указанный в запросе сервис в системе не найден'], 404);
    //         }

    //         $serviceBaseUrl = $serviceMap[$service];
    //         $url = "$serviceBaseUrl/api/{$any}";   
    // \Log::warning($url);
    //         if (!preg_match('#/(login|register|refresh)#', $any)) {
    //             $token = $request->bearerToken();

    //             if (!$token) {
    //                 return response()->json(['error' => 'В запросе отсутствует токен авторизации'], 401);
    //             }

    //             try {

    //                 // локаль                
    //                 // $authValidationUrl = 'http://127.0.0.1:8081/api/v1/check';

    //                 // Докер
    //                  $authValidationUrl = 'http://auth-service-nginx/api/v1/check';

    //                 $authResponse = Http::withToken($token)->get($authValidationUrl);

    //                 \Log::info( $authResponse);

    //                 if ($authResponse->status() !== 200) {
    //                     return response()->json(['error' => 'Ошибка в токене'], 401);
    //                 }

    //             } catch (\Exception $e) {
    //                 return response()->json(['error' => 'Сервис аутентификации недоступен'], 503);
    //             }
    //         }       
    //         $response = Http::withHeaders($request->headers->all())
    //             ->send($request->method(), $url, [
    //                 'query' => $request->query(),
    //                 'body'  => $request->getContent(),
    //             ]);

    //         return response($response->body(), $response->status())
    //             ->withHeaders($response->headers());
    //     }
}
