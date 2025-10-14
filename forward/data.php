<?php

if (!defined('IN_LR')) {
    die('Access denied');
}

class SurfRecordsModule {
    
    private $settings;
    private $conn;
    private $cache_dir;
    private $General;
    private $Translate;
    
    public function __construct($General = null, $Translate = null) {
        $this->General = $General;
        $this->Translate = $Translate;
        $this->loadSettings();
        $this->initDatabase();
        $this->initCache();
    }
    
    private function loadSettings() {
        $db_file = STORAGE . 'cache/sessions/db.php';
        
        if (!file_exists($db_file)) {
            throw new Exception('Database configuration file not found: ' . $db_file);
        }
        
        $db_config = require $db_file;
        
        if (empty($db_config['surf']) || !is_array($db_config['surf'])) {
            throw new Exception('Surf server configuration not found in db.php');
        }
        
        $surf_config = $db_config['surf'][0];
        
        if (empty($surf_config['HOST'])) {
            throw new Exception('Database host is required in db.php');
        }
        
        if (empty($surf_config['USER'])) {
            throw new Exception('Database username is required in db.php');
        }
        
        if (empty($surf_config['PASS'])) {
            throw new Exception('Database password is required in db.php');
        }
        
        if (empty($surf_config['DB']) || !is_array($surf_config['DB'])) {
            throw new Exception('Database configuration is required in db.php');
        }
        
        $db_config_item = $surf_config['DB'][0];
        
        if (empty($db_config_item['DB'])) {
            throw new Exception('Database name is required in db.php');
        }
        
        $settings_file = __DIR__ . '/../settings.php';
        
        if (!file_exists($settings_file)) {
            throw new Exception('Settings file not found: ' . $settings_file);
        }
        
        $module_settings = require $settings_file;
        
        $this->settings = [
            'database' => [
                'host' => $surf_config['HOST'],
                'port' => $surf_config['PORT'] ?? 3306,
                'username' => $surf_config['USER'],
                'password' => $surf_config['PASS'],
                'database' => $db_config_item['DB'],
                'charset' => 'utf8mb4'
            ],
            'display' => $module_settings['display'] ?? [
                'default_map' => 'surf_whiteout',
                'records_per_page' => 50,
                'map_division' => true,
                'default_tab' => 'surf'
            ],
            'cache' => $module_settings['cache'] ?? [
                'enabled' => true,
                'time' => 1800,
                'maps_cache_time' => 3600,
                'stats_cache_time' => 900,
                'records_cache_time' => 600
            ]
        ];
    }
    
    private function getTablePrefix() {
        $db_file = STORAGE . 'cache/sessions/db.php';
        
        if (!file_exists($db_file)) {
            return '';
        }
        
        $db_config = require $db_file;
        
        if (empty($db_config['surf'][0]['DB'][0]['Prefix'][0]['table'])) {
            return ''; 
        }
        
        return $db_config['surf'][0]['DB'][0]['Prefix'][0]['table'];
    }
    
    private function initDatabase() {
        try {
        $db = $this->settings['database'];
        
        $host = $db['host'];
            if (!empty($db['port']) && $db['port'] != 3306) {
                $host .= ':' . $db['port'];
            }
            
            $this->conn = new mysqli(
                $host,
                $db['username'],
                $db['password'],
                $db['database']
            );
            
            if ($this->conn->connect_error) {
                throw new Exception('Database connection failed: ' . $this->conn->connect_error);
            }
            
            if (isset($db['charset'])) {
                $this->conn->set_charset($db['charset']);
            }
            
        } catch (Exception $e) {
            error_log('SurfRecordsModule Database Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    private function initCache() {
        $this->cache_dir = STORAGE . 'modules_cache/surf_records/';
        
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0777, true);
        }
    }
    
    public function isConnected() {
        return $this->conn && !$this->conn->connect_error;
    }
    
    
    public function getConfig() {
        return [
            'db' => $this->settings['database'],
            'display' => [
                'default_map' => $this->settings['display']['default_map'] ?? 'surf_whiteout',
                'limit' => $this->settings['display']['records_per_page'] ?? 100,
                'map_division' => $this->settings['display']['map_division'] ?? true,
                'tab_opened' => $this->settings['display']['default_tab'] ?? 'surf'
            ]
        ];
    }
    
    
    
    
    
    
    
    
    
    
    
    private function validateMapName($map_name) {
        if (empty($map_name) || !is_string($map_name)) {
            return false;
        }
        
        if (strlen($map_name) > 64 || strlen($map_name) < 1) {
            return false;
        }
        
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $map_name)) {
            return false;
        }
        
        $dangerous_patterns = [
            '/union/i', '/select/i', '/insert/i', '/update/i', '/delete/i',
            '/drop/i', '/create/i', '/alter/i', '/exec/i', '/script/i',
            '/<script/i', '/javascript:/i', '/vbscript:/i', '/onload/i',
            '/onerror/i', '/onclick/i'
        ];
        
        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $map_name)) {
                return false;
            }
        }
        
        return true;
    }
    
    private function getCachedData($key, $callback, $custom_cache_time = null) {
        $cache_enabled = $this->settings['cache']['enabled'] ?? true;
        if (!$cache_enabled) {
            return $callback();
        }
        
        $cache_file = $this->cache_dir . $key . '.cache';
        
        if ($custom_cache_time !== null) {
            $cache_time = $custom_cache_time;
        } else {
            if (strpos($key, 'maps') === 0) {
                $cache_time = $this->settings['cache']['maps_cache_time'] ?? 3600;
            } elseif (strpos($key, 'statistics') === 0) {
                $cache_time = $this->settings['cache']['stats_cache_time'] ?? 900;
            } elseif (strpos($key, 'records_') === 0) {
                $cache_time = $this->settings['cache']['records_cache_time'] ?? 600;
            } else {
                $cache_time = $this->settings['cache']['time'] ?? 1800;
            }
        }
        
        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_time) {
            $cached_data = file_get_contents($cache_file);
            return json_decode($cached_data, true);
        }
        
        $data = $callback();
        file_put_contents($cache_file, json_encode($data));
        
        return $data;
    }
    
    public function clearCache() {
        $cache_dir = MODULESCACHE . '/module_page_surf_records';
        if (is_dir($cache_dir)) {
            $files = glob($cache_dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }
    
    public function getMaps($category = null) {
        $cache_key = $category ? "maps_{$category}" : 'maps';
        
        return $this->getCachedData($cache_key, function() use ($category) {
            $maps = [
                'surf' => [],
                'kz' => [],
                'bhop' => [],
                'other' => []
            ];
            
            $table_prefix = $this->getTablePrefix();
            $table_name = $table_prefix . 'PlayerRecords';
            
            if ($category && in_array($category, ['surf', 'kz', 'bhop', 'other'])) {
                $prefix = $category . '_';
                $query = "SELECT DISTINCT MapName FROM {$table_name} 
                          WHERE Style = 0 
                          AND MapName LIKE ? 
                          ORDER BY MapName ASC 
                          LIMIT 500";
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param('s', $prefix);
            } else {
                $query = "SELECT DISTINCT MapName FROM {$table_name} 
                          WHERE Style = 0 
                          ORDER BY MapName ASC 
                          LIMIT 1000";
                $stmt = $this->conn->prepare($query);
            }
            
            if (!$stmt) {
                error_log('SurfRecordsModule: Failed to prepare maps statement - ' . $this->conn->error);
                return $maps;
            }
            
            if (!$stmt->execute()) {
                error_log('SurfRecordsModule: Failed to execute maps statement - ' . $stmt->error);
                $stmt->close();
                return $maps;
            }
            
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $map = $row['MapName'];
                    
                    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $map) || strlen($map) > 64) {
                        continue;
                    }
                    
                    if (strpos($map, 'surf_') === 0) {
                        $maps['surf'][] = $map;
                    } elseif (strpos($map, 'kz_') === 0) {
                        $maps['kz'][] = $map;
                    } elseif (strpos($map, 'bhop_') === 0) {
                        $maps['bhop'][] = $map;
                    } else {
                        $maps['other'][] = $map;
                    }
                }
            }
            
            $stmt->close();
            return $maps;
        });
    }
    
    public function getMapRecords($map_name) {
        if (!$this->validateMapName($map_name)) {
            error_log('SurfRecordsModule: Potential SQL injection attempt - ' . $map_name . ' from IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            return [];
        }
        
        return $this->getCachedData('records_' . md5($map_name), function() use ($map_name) {
            $records = [];
            $limit = $this->settings['display']['records_per_page'] ?? 100;
            
            $table_prefix = $this->getTablePrefix();
            $table_name = $table_prefix . 'PlayerRecords';
            
            $query = "SELECT 
                        p.SteamID,
                        p.PlayerName,
                        p.TimerTicks,
                        p.FormattedTime,
                        p.UnixStamp as Date,
                        ROW_NUMBER() OVER (ORDER BY p.TimerTicks ASC) as place
                      FROM {$table_name} p 
                      WHERE p.MapName = ? AND p.Style = 0
                      ORDER BY p.TimerTicks ASC 
                      LIMIT ?";
            
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                error_log('SurfRecordsModule: Failed to prepare statement - ' . $this->conn->error);
                return [];
            }
            
            $stmt->bind_param('si', $map_name, $limit);
            
            if (!$stmt->execute()) {
                error_log('SurfRecordsModule: Failed to execute statement - ' . $stmt->error);
                $stmt->close();
                return [];
            }
            
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $formattedTime = !empty($row['FormattedTime']) ? $row['FormattedTime'] : $this->formatTime($row['TimerTicks']);
                    
                    $records[] = [
                        'SteamID' => $row['SteamID'],
                        'PlayerName' => $row['PlayerName'],
                        'Time' => $row['TimerTicks'],
                        'FormattedTime' => $formattedTime,
                        'Date' => $row['Date'],
                        'place' => (int)$row['place']
                    ];
                }
            }
            
            $stmt->close();
            return $records;
        });
    }
    
    public function getStatistics() {
        return $this->getCachedData('statistics', function() {
            $stats = [
                'total_records' => 0,
                'total_players' => 0,
                'total_maps' => 0
            ];
            
            $table_prefix = $this->getTablePrefix();
            $table_name = $table_prefix . 'PlayerRecords';
            
            $query = "SELECT 
                        COUNT(*) as total_records,
                        COUNT(DISTINCT SteamID) as total_players,
                        COUNT(DISTINCT MapName) as total_maps
                      FROM {$table_name} 
                      WHERE Style = 0";
            
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                error_log('SurfRecordsModule: Failed to prepare statistics statement - ' . $this->conn->error);
                return $stats;
            }
            
            if (!$stmt->execute()) {
                error_log('SurfRecordsModule: Failed to execute statistics statement - ' . $stmt->error);
                $stmt->close();
                return $stats;
            }
            
            $result = $stmt->get_result();
            if ($result && $row = $result->fetch_assoc()) {
                $stats['total_records'] = (int)$row['total_records'];
                $stats['total_players'] = (int)$row['total_players'];
                $stats['total_maps'] = (int)$row['total_maps'];
            }
            
            $stmt->close();
            return $stats;
        });
    }
    
    private function formatTime($ticks) {
        if (empty($ticks) || $ticks == 0) {
            return '0:00.000';
        }
        
        $seconds = $ticks / 66.67;
        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;
        return sprintf('%d:%06.3f', $minutes, $seconds);
    }
    
    
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

if (class_exists('SurfRecordsModule') && !isset($SurfRecords)) {
    $SurfRecords = new SurfRecordsModule();
}