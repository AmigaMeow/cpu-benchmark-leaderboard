<?php
/**
 * API 响应工具类
 * 开源版 API 响应工具
 */

class Response {
    /**
     * 成功响应
     *
     * @param mixed $data 数据
     * @param string $message 消息
     * @param int $code 业务状态码
     */
    public static function success($data = null, $message = '操作成功', $code = 0) {
        self::json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ], 200);
    }

    /**
     * 失败响应
     *
     * @param string $message 错误消息
     * @param int $code 业务错误码
     * @param int $httpCode HTTP状态码
     */
    public static function error($message = '操作失败', $code = 1, $httpCode = 400) {
        // 兼容旧调用方：第二参数常传 HTTP 状态码，避免业务码与实际状态码脱节。
        if ($httpCode === 400 && in_array($code, [401, 403, 404, 405, 409, 413, 415, 419, 422, 429, 500, 502, 503, 504], true)) {
            $httpCode = $code;
        }
        self::json([
            'code' => $code,
            'message' => $message,
            'data' => null,
            'timestamp' => time()
        ], $httpCode);
    }

    /**
     * 未授权响应
     */
    public static function unauthorized($message = '未授权访问') {
        self::json([
            'code' => 401,
            'message' => $message,
            'data' => null,
            'timestamp' => time()
        ], 401);
    }

    /**
     * 禁止访问响应
     */
    public static function forbidden($message = '禁止访问') {
        self::json([
            'code' => 403,
            'message' => $message,
            'data' => null,
            'timestamp' => time()
        ], 403);
    }

    /**
     * 未找到响应
     */
    public static function notFound($message = '资源不存在') {
        self::json([
            'code' => 404,
            'message' => $message,
            'data' => null,
            'timestamp' => time()
        ], 404);
    }

    /**
     * 验证失败响应
     *
     * @param array $errors 验证错误信息
     */
    public static function validationError($errors = [], $message = '参数验证失败') {
        self::json([
            'code' => 422,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => time()
        ], 422);
    }

    /**
     * 服务器错误响应
     */
    public static function serverError($message = '服务器内部错误') {
        self::json([
            'code' => 500,
            'message' => $message,
            'data' => null,
            'timestamp' => time()
        ], 500);
    }

    /**
     * 分页数据响应
     */
    public static function paginate($data, $total, $page, $pageSize, $message = '查询成功') {
        self::json([
            'code' => 0,
            'message' => $message,
            'data' => [
                'list' => $data,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'page_size' => $pageSize,
                    'total_pages' => ceil($total / $pageSize)
                ]
            ],
            'timestamp' => time()
        ], 200);
    }

    /**
     * 输出JSON响应
     */
    private static function json($data, $httpCode = 200) {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');

        // 允许跨域 (CORS)
        $appConfigFile = __DIR__ . '/../config/app.php';
        $config = is_file($appConfigFile) ? require $appConfigFile : [];
        if (!empty($config['cors_origins'])) {
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
            if (in_array($origin, $config['cors_origins'])) {
                header("Access-Control-Allow-Origin: {$origin}");
                header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
                header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
                header('Access-Control-Allow-Credentials: true');
            }
        }

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
