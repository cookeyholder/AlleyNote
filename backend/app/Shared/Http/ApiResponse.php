<?php

declare(strict_types=1);

namespace App\Shared\Http;

class ApiResponse
{
    /**
     * @return array<string, mixed>
     */
    public static function success(mixed $data = null, string $message = 'Success'): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function error(string $message, int $code = 400, mixed $errors = null): array
    {
        return [
            'success' => false,
            'message' => $message,
            'error_code' => $code,
            'errors' => $errors,
            'timestamp' => date('c'),
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
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
