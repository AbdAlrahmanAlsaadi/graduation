<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class Response
{
    public static function success($message, $data = [], $status = 200): JsonResponse
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    public static function error($message, $code = 500): JsonResponse
    {
        return response()->json([
            'status' => $code,
            'message' => $message,
            'data' => [],
        ], $code);
    }

    public static function Validation($message, $errors = [], $code = 422): JsonResponse
    {
        return response()->json([
            'status' => $code,
            'message' => $message,
            'data' => [
                'errors' => $errors,
            ],
        ], $code);
    }
}
