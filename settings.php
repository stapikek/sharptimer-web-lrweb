<?php
/**
 * Файл конфигурации модуля
 * 
 * Настройки базы данных загружаются из storage/cache/sessions/db.php
 * Здесь только настройки отображения и кеширования
 */

if (!defined('IN_LR')) {
    die('Доступ запрещен');
}

return [
<<<<<<< HEAD
    'display' => [
        'default_map' => 'surf_whiteout',
        'records_per_page' => 50,
=======
    // Настройки отображения
    'display' => [
        'default_map' => 'surf_whiteout',
        'records_per_page' => 50,  // Уменьшено с 100 до 50
>>>>>>> 0c32d38afa72eb973481df02bfd15ac2784578a2
        'map_division' => true,
        'default_tab' => 'surf'
    ],
    
<<<<<<< HEAD
    // Кеширование
=======
    // Настройки кеширования
>>>>>>> 0c32d38afa72eb973481df02bfd15ac2784578a2
    'cache' => [
        'enabled' => true,
        'time' => 1800,  // Увеличено до 30 минут
        'maps_cache_time' => 3600,  // Карты кешируются на 1 час
        'stats_cache_time' => 900,  // Статистика кешируется на 15 минут
        'records_cache_time' => 600  // Рекорды кешируются на 10 минут
    ]
];

