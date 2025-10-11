## Основные возможности

- **Статистика** - общее количество рекордов, игроков и карт
- **Фильтрация карт** - разделение по категориям (surf, kz, bhop, other)
- **Таблица лидеров** - топ игроков по времени прохождения
- **Профили игроков** - ссылки на профили и Steam
- **Поиск** - быстрый поиск карт
- **Интеграция с темой** - использует цвета и шрифты сайта

## Требования
- **PHP 7.4+**
- **MySQL 5.7+**
- [NEO 3.0](https://stellarteam.store/resource/template-neo-v3)

## База данных

### Подключение
Настройки хранятся в `settings.php`:
```php
'database' => [
    'host' => 'localhost',
    'username' => 'your_username',
    'password' => 'your_password',
    'database' => 'your_database',
    'charset' => 'utf8mb4'
]
```

### Требования к базе данных
Модуль работает с таблицей `PlayerRecords` со следующей структурой:

```sql
CREATE TABLE `PlayerRecords` (
  `SteamID` varchar(17) NOT NULL,
  `PlayerName` varchar(32) NOT NULL,
  `MapName` varchar(32) NOT NULL,
  `TimerTicks` int(11) NOT NULL,
  `FormattedTime` varchar(12) DEFAULT NULL,
  `UnixStamp` int(11) NOT NULL,
  PRIMARY KEY (`SteamID`, `MapName`),
  KEY `MapName` (`MapName`),
  KEY `TimerTicks` (`TimerTicks`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Поля таблицы
- `SteamID` - Steam ID игрока (varchar(17))
- `PlayerName` - имя игрока (varchar(32))
- `MapName` - название карты (varchar(32))
- `TimerTicks` - время в тиках (int(11))
- `FormattedTime` - отформатированное время (varchar(12))
- `UnixStamp` - дата рекорда (int(11))

## Установка

1. Скопируйте модуль в `/app/modules/`
2. **Настройте базу данных** в `settings.php`:
   ```php
   'database' => [
       'host' => 'your_database_host',
       'username' => 'your_database_username', 
       'password' => 'your_database_password',
       'database' => 'your_database_name',
       'charset' => 'utf8mb4'
   ]
   ```
3. Откройте страницу: `https://your-domain.com/surf/`

## Функциональность

### Статистика
Отображает общую информацию:
- Всего рекордов
- Всего игроков
- Всего карт

### Фильтрация карт
Категории:
- **SURF** - surf_* карты
- **KZ** - kz_* карты  
- **BHOP** - bhop_* карты
- **Other** - остальные карты

### Настройка отображения
В `settings.php`:
```php
'display' => [
    'default_map' => 'surf_whiteout',     // Карта по умолчанию
    'records_per_page' => 100,            // Лимит записей
    'map_division' => true,               // Разделение по категориям
    'default_tab' => 'surf'               // Вкладка по умолчанию
]
```

## Решение проблем

### Переводы не появляются
Очистите кеш в Админ панели
