<?php
/**
 * CSRF 防护辅助类
 * @author AI Assistant
 */
declare(strict_types=1);

namespace App\Core;

use Psr\Container\ContainerInterface;

class Csrf
{
    protected static $container;
    
    public static function setContainer(ContainerInterface $container): void
    {
        self::$container = $container;
    }
    
    /**
     * 获取 CSRF token
     */
    public static function getToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * 验证 CSRF token
     */
    public static function validateToken(string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        $tokenTime = $_SESSION['csrf_token_time'] ?? 0;
        
        // 检查 token 是否存在
        if (empty($sessionToken) || empty($token)) {
            return false;
        }
        
        // 安全比较 token
        if (!hash_equals($sessionToken, $token)) {
            return false;
        }
        
        // 检查 token 是否过期（1小时）
        if (time() - $tokenTime > 3600) {
            return false;
        }
        
        return true;
    }
    
    /**
     * 刷新 CSRF token
     */
    public static function refreshToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        
        return self::getToken();
    }
    
    /**
     * 生成 CSRF 隐藏字段 HTML
     */
    public static function getHiddenField(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * 生成 CSRF meta 标签（用于 AJAX 请求）
     */
    public static function getMetaTag(): string
    {
        $token = self::getToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}