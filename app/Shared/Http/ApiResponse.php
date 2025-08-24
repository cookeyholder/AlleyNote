<?php

declare(strict_types=1);

namespace App\Shared\Http;

class ApiResponse
{
    public static function success($data = null, string $message = 'Success'): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c'),
        ];
    }

    public static function error(string $message, int $code = 400, $errors = null): array
    {
        return [
            'success' => false,
            'message' => $message,
            'error_code' => $code,
            'errors' => $errors,
            'timestamp' => date('c'),
        ];
    }

    public static function paginated(array $data, int $total, int $page, int $perPage): array
    {
        return [
            'success' => true,
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage),
            ],
            'timestamp' => date('c'),
        ];
    }
}
