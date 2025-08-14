<?php
/**
 * Session Fallback Handler
 * 
 * Provides fallback session handling when memcached is unavailable
 */

class SessionFallback {
    private static $initialized = false;
    private static $useFileSessions = false;
    
    /**
     * Initialize session with fallback support
     */
    public static function init() {
        if (self::$initialized) {
            return;
        }
        
        // Check if memcached is available
        if (self::isMemcachedAvailable()) {
            // Use memcached sessions
            ini_set('session.save_handler', 'memcached');
            self::$useFileSessions = false;
        } else {
            // Fallback to file sessions
            ini_set('session.save_handler', 'files');
            ini_set('session.save_path', '/tmp');
            self::$useFileSessions = true;
            
            // Log the fallback
            error_log("SessionFallback: Memcached unavailable, falling back to file sessions");
        }
        
        self::$initialized = true;
    }
    
    /**
     * Check if memcached is available
     */
    private static function isMemcachedAvailable(): bool {
        if (!extension_loaded('memcached')) {
            return false;
        }
        
        try {
            $memcached = new Memcached();
            $servers = explode(',', ini_get('session.save_path'));
            
            foreach ($servers as $server) {
                $parts = explode(':', trim($server));
                $host = $parts[0];
                $port = isset($parts[1]) ? $parts[1] : 11211;
                
                $memcached->addServer($host, $port);
            }
            
            // Test connection with a simple operation
            $testKey = 'connection_test_' . time();
            $result = $memcached->set($testKey, 'test', 10);
            $memcached->delete($testKey);
            
            return $result !== false;
        } catch (Exception $e) {
            error_log("SessionFallback: Memcached connection test failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Start session with error handling
     */
    public static function start(): bool {
        self::init();
        
        try {
            $result = session_start();
            if (!$result) {
                error_log("SessionFallback: session_start() returned false");
                return false;
            }
            return true;
        } catch (Exception $e) {
            error_log("SessionFallback: session_start() exception: " . $e->getMessage());
            
            // If memcached failed, try file sessions as last resort
            if (!self::$useFileSessions) {
                error_log("SessionFallback: Trying file sessions as last resort");
                ini_set('session.save_handler', 'files');
                ini_set('session.save_path', '/tmp');
                
                try {
                    return session_start();
                } catch (Exception $e2) {
                    error_log("SessionFallback: File sessions also failed: " . $e2->getMessage());
                    return false;
                }
            }
            
            return false;
        }
    }
    
    /**
     * Get current session handler type
     */
    public static function getHandlerType(): string {
        return self::$useFileSessions ? 'files' : 'memcached';
    }
}
?>
