<?php

declare(strict_types=1);

return [
    'openapi' => '3.0.0',
    'info' => [
        'title' => 'AlleyNote API',
        'description' => 'AlleyNote 公布欄系統 API 文件',
        'version' => '1.0.0',
        'contact' => [
            'name' => 'AlleyNote Team',
            'email' => 'contact@alleynote.example.com'
        ],
        'license' => [
            'name' => 'MIT',
            'url' => 'https://opensource.org/licenses/MIT'
        ]
    ],
    'servers' => [
        [
            'url' => '{protocol}://{host}/api',
            'description' => 'AlleyNote API Server',
            'variables' => [
                'protocol' => [
                    'enum' => ['http', 'https'],
                    'default' => 'http'
                ],
                'host' => [
                    'default' => 'localhost:8080'
                ]
            ]
        ]
    ],
    'components' => [
        'securitySchemes' => [
            'bearerAuth' => [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'JWT',
                'description' => 'JWT Authorization header using the Bearer scheme'
            ],
            'sessionAuth' => [
                'type' => 'apiKey',
                'in' => 'cookie',
                'name' => 'PHPSESSID',
                'description' => 'Session-based authentication'
            ],
            'csrfToken' => [
                'type' => 'apiKey',
                'in' => 'header',
                'name' => 'X-CSRF-TOKEN',
                'description' => 'CSRF protection token'
            ]
        ],
        'schemas' => [
            'ErrorResponse' => [
                'type' => 'object',
                'properties' => [
                    'success' => [
                        'type' => 'boolean',
                        'example' => false
                    ],
                    'error' => [
                        'type' => 'string',
                        'example' => 'Error message'
                    ],
                    'code' => [
                        'type' => 'integer',
                        'example' => 400
                    ],
                    'timestamp' => [
                        'type' => 'string',
                        'format' => 'date-time',
                        'example' => '2025-01-15T10:30:00Z'
                    ]
                ]
            ],
            'SuccessResponse' => [
                'type' => 'object',
                'properties' => [
                    'success' => [
                        'type' => 'boolean',
                        'example' => true
                    ],
                    'message' => [
                        'type' => 'string',
                        'example' => 'Operation successful'
                    ],
                    'data' => [
                        'type' => 'object',
                        'description' => 'Response data'
                    ]
                ]
            ]
        ],
        'responses' => [
            'ValidationError' => [
                'description' => '輸入驗證錯誤',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'success' => [
                                    'type' => 'boolean',
                                    'example' => false
                                ],
                                'error' => [
                                    'type' => 'string',
                                    'example' => 'Validation failed'
                                ],
                                'errors' => [
                                    'type' => 'object',
                                    'additionalProperties' => [
                                        'type' => 'array<mixed>',
                                        'items' => ['type' => 'string']
                                    ],
                                    'example' => [
                                        'title' => ['標題不能為空'],
                                        'content' => ['內容長度不能超過 10000 字元']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'NotFound' => [
                'description' => '資源不存在',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'success' => [
                                    'type' => 'boolean',
                                    'example' => false
                                ],
                                'error' => [
                                    'type' => 'string',
                                    'example' => 'Resource not found'
                                ],
                                'code' => [
                                    'type' => 'integer',
                                    'example' => 404
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'Unauthorized' => [
                'description' => '未授權存取',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'success' => [
                                    'type' => 'boolean',
                                    'example' => false
                                ],
                                'error' => [
                                    'type' => 'string',
                                    'example' => 'Unauthorized access'
                                ],
                                'code' => [
                                    'type' => 'integer',
                                    'example' => 401
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'Forbidden' => [
                'description' => '禁止存取',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'success' => [
                                    'type' => 'boolean',
                                    'example' => false
                                ],
                                'error' => [
                                    'type' => 'string',
                                    'example' => 'Access forbidden'
                                ],
                                'code' => [
                                    'type' => 'integer',
                                    'example' => 403
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'BadRequest' => [
                'description' => '請求格式錯誤',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'success' => [
                                    'type' => 'boolean',
                                    'example' => false
                                ],
                                'error' => [
                                    'type' => 'string',
                                    'example' => 'Bad request'
                                ],
                                'code' => [
                                    'type' => 'integer',
                                    'example' => 400
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'InternalServerError' => [
                'description' => '伺服器內部錯誤',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'success' => [
                                    'type' => 'boolean',
                                    'example' => false
                                ],
                                'error' => [
                                    'type' => 'string',
                                    'example' => 'Internal server error'
                                ],
                                'code' => [
                                    'type' => 'integer',
                                    'example' => 500
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'TooManyRequests' => [
                'description' => '請求次數過多',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'success' => [
                                    'type' => 'boolean',
                                    'example' => false
                                ],
                                'error' => [
                                    'type' => 'string',
                                    'example' => 'Too many requests'
                                ],
                                'code' => [
                                    'type' => 'integer',
                                    'example' => 429
                                ],
                                'retry_after' => [
                                    'type' => 'integer',
                                    'description' => '重試等待秒數',
                                    'example' => 60
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'ServiceUnavailable' => [
                'description' => '服務暫時無法使用',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'success' => [
                                    'type' => 'boolean',
                                    'example' => false
                                ],
                                'error' => [
                                    'type' => 'string',
                                    'example' => 'Service temporarily unavailable'
                                ],
                                'code' => [
                                    'type' => 'integer',
                                    'example' => 503
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        'parameters' => [
            'PageParam' => [
                'name' => 'page',
                'in' => 'query',
                'description' => '頁碼',
                'required' => false,
                'schema' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'default' => 1
                ]
            ],
            'LimitParam' => [
                'name' => 'limit',
                'in' => 'query',
                'description' => '每頁筆數',
                'required' => false,
                'schema' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 100,
                    'default' => 10
                ]
            ],
            'SearchParam' => [
                'name' => 'search',
                'in' => 'query',
                'description' => '搜尋關鍵字',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'minLength' => 1,
                    'maxLength' => 255
                ]
            ]
        ],
        'requestBodies' => [
            'JsonRequest' => [
                'description' => '標準 JSON 請求',
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object'
                        ]
                    ]
                ]
            ],
            'FileUpload' => [
                'description' => '檔案上傳請求',
                'required' => true,
                'content' => [
                    'multipart/form-data' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'file' => [
                                    'type' => 'string',
                                    'format' => 'binary',
                                    'description' => '要上傳的檔案'
                                ]
                            ],
                            'required' => ['file']
                        ]
                    ]
                ]
            ]
        ],
        'headers' => [
            'RateLimitRemaining' => [
                'description' => '剩餘請求次數',
                'schema' => [
                    'type' => 'integer',
                    'example' => 99
                ]
            ],
            'RateLimitReset' => [
                'description' => '限制重置時間（Unix 時間戳）',
                'schema' => [
                    'type' => 'integer',
                    'example' => 1642249200
                ]
            ]
        ],
        'examples' => [
            'PostExample' => [
                'summary' => '貼文範例',
                'description' => '一個典型的貼文資料',
                'value' => [
                    'id' => 1,
                    'title' => '重要公告',
                    'content' => '這是一則重要公告內容',
                    'category' => 'announcement',
                    'status' => 'published',
                    'priority' => 'high',
                    'author_id' => 1,
                    'created_at' => '2025-01-15T10:30:00Z',
                    'updated_at' => '2025-01-15T11:00:00Z'
                ]
            ],
            'UserExample' => [
                'summary' => '使用者範例',
                'description' => '一個典型的使用者資料',
                'value' => [
                    'id' => 1,
                    'username' => 'admin',
                    'email' => 'admin@example.com',
                    'role' => 'admin',
                    'created_at' => '2025-01-01T00:00:00Z'
                ]
            ],
            'ValidationErrorExample' => [
                'summary' => '驗證錯誤範例',
                'description' => '表單驗證失敗時的錯誤回應',
                'value' => [
                    'success' => false,
                    'error' => '資料驗證失敗',
                    'errors' => [
                        'title' => ['標題不能為空', '標題長度不能超過 255 字元'],
                        'email' => ['電子郵件格式不正確'],
                        'password' => ['密碼長度不能少於 8 字元']
                    ]
                ]
            ]
        ]
    ]
];
