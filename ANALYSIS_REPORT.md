# CasterREP 4.0 — Подробен анализ на проекта

**Дата на анализ:** 16 април 2026  
**Версия:** 4.0.220610 (v3.0.20201010 вътрешна)  
**Лиценз:** GPL-3.0  
**Автори:** Juan Morillo, Javier Guerrero, Ruben Molina, Domingo Solomando (Red Extremeña de Posicionamiento — REP)  
**Контакт:** jmorillo@unex.es

---

## 1. Обобщение

CasterREP е **NTRIP Caster сървър с отворен код** за разпространение на GNSS корекции в реално време (RTCM 3.x) към полеви устройства (ровери). Системата се състои от три основни компонента:

| Компонент | Технология | Описание |
|-----------|-----------|----------|
| RTCM3 Capture Server | Python (multiprocessing) | Приема RTCM данни от базови станции |
| NTRIP Caster Server | Python (threading, HTTP) | Обслужва ровери по NTRIP v1.0 протокол |
| Административен панел | PHP + Bootstrap + OpenLayers | Уеб интерфейс за управление |
| База данни | MongoDB | Съхранение на потоци, потребители, сурови данни |

---

## 2. Архитектура на системата

```
┌──────────────────┐     TCP:2103     ┌──────────────────────┐
│  Базови станции  │ ──────────────►  │ REP_rtcm3_capture.py │
│  (CORS / GNSS)   │   SOURCE cmd     │  (multiprocessing)   │
└──────────────────┘                  └─────────┬────────────┘
                                                │ upsert
                                                ▼
                                      ┌──────────────────┐
┌──────────────────┐     HTTP:2101    │    MongoDB        │
│  Ровери / GNSS   │ ◄──────────────  │  (casterrep DB)   │
│  клиенти         │   RTCM data      │                   │
└────────┬─────────┘                  │  Колекции:        │
         │  GET /mountpoint            │  - streams        │
         ▼                            │  - users          │
┌──────────────────────┐              │  - rtcm_raw       │
│ REP_caster_server.py │──────────────│  - rover_connections│
│  (ThreadingMixIn)    │   read/write │  - comms          │
└──────────────────────┘              └────────┬──────────┘
                                               │
                                               ▼
┌──────────────────────┐              ┌──────────────────┐
│ REP_ntrip_client.py  │──────────────│  Външен NTRIP    │
│ (Опционален клиент)  │  Fetch RTCM  │  Caster          │
└──────────────────────┘              └──────────────────┘

┌──────────────────────┐     HTTP:80/443
│  PHP Админ панел     │──────────────► MongoDB
│  (casterrep/)        │   Web UI
└──────────────────────┘
```

---

## 3. Детайлен анализ на файловете

### 3.1. Python Backend

#### `REP_caster_server.py` — Главен NTRIP Caster сървър
- **Роля:** HTTP сървър, обслужващ NTRIP клиенти (ровери)
- **Порт:** 2101 (конфигурируем)
- **Клас `CasterRequestHandler`** — наследява `BaseHTTPRequestHandler`
  - `do_GET()` → `handle_data()` — обработва всяка заявка
  - `do_SOURCETABLE()` — връща списък на наличните потоци (mountpoints)
  - `do_UNAUTHORIZED()` — отговаря с HTTP 401
- **Клас `CasterServer`** — `ThreadingMixIn + HTTPServer` за многонишково обслужване
- **Функции:**
  - `checkMountpointInDatabase()` — валидира mountpoint в MongoDB
  - `checkAuth()` — проверява Base64 токен срещу `users` колекция
  - `patch_broken_pipe_error()` — заглушава broken pipe грешки
- **Поток на данни:**
  1. Клиент прави GET на `/` → получава Sourcetable
  2. GET на `/MOUNTPOINT` → проверка за валидност → проверка за auth → `ICY 200 OK`
  3. Използва `select()` за async I/O — чете NMEA от клиента, пише RTCM данни
  4. За `NEAREST` mountpoint — изисква GPGGA от клиента, изчислява най-близка станция

#### `REP_rtcm3_capture.py` — RTCM3 приемник
- **Роля:** TCP сървър, който приема RTCM данни от базови станции
- **Порт:** 2103 (конфигурируем)
- **Архитектура:** `multiprocessing` — нов процес за всяка входяща връзка
- **Клас `Server`** — слуша за TCP връзки, spawn-ва `handle()` процеси
- **`handle()` функция:**
  1. Очаква `SOURCE <password> /<mountpoint>` пакет
  2. Валидира mountpoint и encoder password в MongoDB
  3. Отговаря с `ICY 200 OK`
  4. Декодира входящите RTCM3 пакети чрез `decodeRTCM3Packet()`
  5. Записва данните в `rtcm_raw` колекция (upsert по mountpoint)

#### `REP_ntrip_client.py` — NTRIP клиент за външни кастери
- **Роля:** Свързва се с външен NTRIP caster и fetch-ва RTCM данни
- **Аргументи от командния ред:**
  - `ip`, `port` — адрес на отдалечен caster
  - `-c / --caster` — режим на NTRIP caster клиент
  - `-s / --station` — режим за директна връзка към станция
  - `-m / --mountpoint`, `-u / --user`, `-p / --password`
- **Забележка:** Този файл е по-стар и импортира от `config_file` и `_p3` версии на модулите

#### `REP_RTCM3_Decode.py` — Декодер на RTCM 3.x пакети
- **Функция `decodeRTCM3Packet(raw)`**
- Парсира hex данни с preamble `D3`
- Разпознава съобщения:
  - **GPS:** 1001-1004, 1071-1077
  - **GLONASS:** 1009-1012, 1081-1087
  - **Galileo:** 1091-1097
  - **BeiDou:** 1121-1127
- Извлича: ID на станция, брой сателити по система (GPS, GLO, GAL, BEI)
- Връща: `(id_station, n_gps, n_glo, n_gal, n_bei)`

#### `REP_GPGGADecoder.py` — NMEA GGA декодер + геопространствени функции
- **`GPGGADecodeAndUpdateRover(nmea_gga, rover)`**
  - Парсира `$GPGGA` / `$GNGGA` / `$GLGGA` съобщения
  - Извлича: координати, качество на позицията, HDOP, използвани сателити, латентност
  - Обновява информацията за ровера в MongoDB
- **`haversine(lon1, lat1, lon2, lat2)`** — изчислява разстояние между две точки (в км)
- **`getNearestMountpoint(user_lat, user_lon)`**
  - Обхожда всички активни потоци
  - Изчислява разстояние чрез Haversine
  - Връща най-близкия mountpoint

#### `REP_RoverUserClass.py` — Клас за ровер потребител
- **Клас `RoverUser`** — модел на свързан ровер
  - Атрибути: `_id`, `conn_ip`, `conn_useragent`, `conn_path`, `username`, `login_time`, координати, качество, `ref_station` и др.
  - `newUser()` — вмъква запис в `rover_connections`
  - `disconnectUser()` — маркира `conn_status: false`
  - `printer()` — отпечатва debug информация

#### `REP_init_mongodb.py` — Инициализация на базата
- Създава начален admin потребител: `admin` / `casterrep` (base64: `YWRtaW46Y2FzdGVycmVw`)
- Създава default `NEAREST` mountpoint stream
- **Важно:** Изпълнява се еднократно при инсталация

#### `REP_header_printer.py` — ASCII арт заглавие
- Чисто козметична функция за терминален банер

#### `general_defs.py` — Общи дефиниции
- `createMongoClient()` — създава MongoDB клиент с auth (SCRAM-SHA-1)
- `format_time()` — форматира timestamp

#### `config_load.py` — Зареждане на конфигурацията
- Чете `config_var.json` → взема път до `config_file.ini`
- Използва `ConfigObj` за парсване на INI файла
- Връща конфигурационен речник

#### `config_load_db.py` — Алтернативно зареждане от MongoDB
- Чете конфигурацията директно от `conf` колекция в MongoDB
- **Не се използва активно** в текущата версия

#### `REP_config_file.py` — Хардкодирана конфигурация (legacy)
- Съдържа директно записани стойности:
  - `RTCM_CAPTURE_SERVER_HOST = "158.49.61.19"`
  - `CASTER_SERVER_PORT = 2101`
  - `MONGODB_AUTH_USER = 'c_rep'`
  - `MONGODB_AUTH_PASSWD = '1qazxsw2'`
- **Проблем:** Дублира конфигурацията от INI файла

#### `gps_time.py` — GPS време утилити
- Конверсии между GPS week/TOW, datetime и UTC
- Клас `GPSTime` — GPS времеви обект
- Поддръжка на leap seconds (до 2017+)

### 3.2. Конфигурационни файлове

#### `config_file.ini`
```ini
[SETTINGS]
  [[ENVIRONMENT]]
    STR_PYTHON_VERSION = 'python'
    DIR_OUTPUT = 'DATA'
  [[MONGODB]]
    HOST_MONGODB = '127.0.0.1'
    PORT_MONGODB = 27017
  [[PREFERENCES]]
    TIME_OUT_RAWDATA = 30          # секунди timeout за "стари" данни
    STR_NEAREST_MOUNPOINT = 'NEAREST'

[PROFILE]
  [[IO]]
    RTCM_CAPTURE_SERVER_HOST = '158.49.61.19'
    RTCM_CAPTURE_SERVER_PORT = 2103
    CASTER_SERVER_HOST = '158.49.61.19'
    CASTER_SERVER_PORT = 2101
  [[DATABASE]]
    STR_MONGODB_AUTH_USER = 'c_rep'
    STR_MONGODB_AUTH_PASSWD = '1qazxsw2'
    str_db_Name = 'casterrep'
    str_db_UsersTable = 'users'
    str_db_RTCMTable = 'rtcm_raw'
    str_db_StreamsTable = 'streams'
    str_db_RoverConnections = 'rover_connections'
```

#### `config_var.json` — Индирекция за INI файла
```json
[{"FILE_CONFIG_INI" : "config_file.ini"}]
```

#### `REP_logging_config.json` — Logging конфигурация
- Rotating файлови हандлери: `caster_server.log`, `rtcm3_capture.log`
- 10MB максимум, 20 бекъпа

### 3.3. PHP Уеб интерфейс (`casterrep/`)

#### `conf.php` — Конфигурация за MongoDB връзка
```php
$ip = 'localhost'; $port = '27017';
$user = 'username'; $pasw = 'password';
$dashboard_rate = 15; // секунди
```

#### `login.php` — Страница за вход
- POST форма за username/password
- Base64 кодиране на токена: `base64_encode(username:password)`
- Проверява срещу `users` колекция + проверява `type == 0` (admin)
- Записва `$_SESSION['username']` при успех

#### `index.php` — Dashboard (начална страница)
- Показва статистики: broi streams, потребители, онлайн потоци, онлайн ровери
- Таблица с активни ровер връзки (IP, потребител, mountpoint, координати, качество, сателити)
- Auto-refresh на всеки `$dashboard_rate` секунди

#### `editStreams.php` — Управление на потоци
- DataTables таблица с всички mountpoints
- Бутони за Edit и Delete за всеки запис
- Показва: mountpoint, identifier, формат, навигационна система, координати, статус

#### `editStreamPage.php` — Редактиране на конкретен поток
- Форма с всички полета (mountpoint, identifier, format detail, carrier, nav system, network, country, coordinates, generator, bitrate, encoder password, active, solution тип)
- Поддържа GET (зареждане) и POST (запис)

#### `newStream.php` — Създаване на нов поток
- Форма за въвеждане на нов mountpoint с всички параметри

#### `editUsers.php` — Управление на потребители
- DataTables таблица: тип, username, име, организация, email, телефон, дата, статус
- Edit / Delete бутони

#### `editUserPage.php` — Редактиране на потребител
- Подробна форма с всички потребителски полета

#### `newUser.php` — Създаване на нов потребител
- Форма с задължителни полета: username, password, email, first name

#### `deleteStream.php` — Изтриване на поток
- AJAX GET: `?idstream=<ObjectID>` → `streams.deleteOne()`

#### `deleteUser.php` — Изтриване на потребител
- AJAX GET: `?iduser=<ObjectID>` → `users.deleteOne()`

#### `generateJSON.php` — GeoJSON генератор за картата
- Формира FeatureCollection с:
  - Потоци (триъгълници) — координати, статус, GPS/GLO сателити
  - Ровери (маркери) — координати, качество, user agent, най-близка станция
- Използва се от `map.js` чрез AJAX

#### `map.php` — Карта в реално време
- OpenLayers 3 карта с базови слоеве (OSM + ESRI)
- Refresh на всеки 30 секунди чрез JavaScript

#### `navigation.php` — Навигационно меню
- Sidebar с линкове: Dashboard, Map, Edit Streams, New Stream, Edit Users, New User, Logout

### 3.4. JavaScript файлове

#### `js/map.js`
- OpenLayers 3 карта с GeoJSON слоеве
- Стилове за: online/offline потоци (зелен/червен триъгълник), ровери по качество (RTK кръст, Float RTK, DGPS, GPS Fix)
- Popup при клик с детайлна информация
- AJAX зареждане от `generateJSON.php` на всеки 30 сек

#### `js/editstreams.js` / `js/editusers.js`
- DataTables инициализация
- Click хендлъри за Edit (навигация) и Delete (AJAX + потвърждение)
- Noty.js нотификации

---

## 4. MongoDB схема на базата данни

### Колекция `streams`
```json
{
  "_id": ObjectId,
  "mountpoint": "CACE",
  "identifier": "CACE",
  "data_format": "RTCM 3.1",
  "format_detail": "1004(1), 1012(1), ...",
  "carrier": 2,
  "nav_system": "GPS+GLONASS",
  "network": "CasterREP",
  "country": "ESP",
  "latitude": 39.474,
  "longitude": -6.372,
  "nmea": 0,
  "solution": false,          // false = Single Base, true = Network
  "generator": "Leica GR25",
  "compr_encryp": "none",
  "authentication": "B",
  "fee": "N",
  "bitrate": 9600,
  "misc": "...",
  "encoder_pwd": "gnss",      // парола за SOURCE командата
  "id_station": 1234,
  "active": true
}
```

### Колекция `users`
```json
{
  "_id": ObjectId,
  "username": "admin",
  "token_auth": "YWRtaW46Y2FzdGVycmVw",  // base64(username:password)
  "first_name": "Red",
  "last_name": "Extremeña de Posicionamiento",
  "organisation": "REP",
  "email": "rep@unex.es",
  "phone": "+34600959393",
  "city": "Badajoz",
  "country": "ESP",
  "zip_code": "06006",
  "description": "Default Admin",
  "type": 0,                   // 0 = Admin, 1 = User
  "valid_from": 1587045600,
  "active": true
}
```

### Колекция `rtcm_raw`
```json
{
  "_id": ObjectId,
  "mountpoint": "CACE",
  "data": Binary(<RTCM bytes>),
  "timestamp": 1587045600.123,
  "id_station": 1234,
  "n_gps": "8",
  "n_glo": "6",
  "n_gal": "5",
  "n_bei": "0"
}
```

### Колекция `rover_connections`
```json
{
  "_id": ObjectId,
  "conn_status": true,
  "conn_ip": ["192.168.1.100", 45678],
  "conn_useragent": "NTRIP Client/1.0",
  "conn_path": "CACE",
  "username": "user1",
  "login_time": 1587045600,
  "timestamp_last_msg": 1587045630,
  "distance_near": 12.345,
  "coordinates": [39.474, -6.372],
  "latency": "1.2",
  "quality": "RTK",
  "ref_station": "CACE",
  "sat_used": "14",
  "nmea_msg": "$GPGGA,...",
  "last_update": 1587045630
}
```

### Колекция `comms` (дефинирана, но слабо използвана)
- Предназначена за host/port и потребителски данни за всеки mountpoint

---

## 5. Протоколен поток (NTRIP v1.0)

### 5.1. Базова станция → Capture сървър
```
Станция: SOURCE <password> /<mountpoint>\r\n
Сървър:  ICY 200 OK\r\n
Станция: <RTCM3 binary data>...
```

### 5.2. Ровер → Caster сървър
```
Ровер:   GET / HTTP/1.0                    → Получава Sourcetable
Ровер:   GET /MOUNTPOINT HTTP/1.0
         Authorization: Basic <base64>      → Получава RTCM данни
Сървър:  ICY 200 OK
         <RTCM3 binary data>...
```

### 5.3. NEAREST функционалност
1. Ровер заявява mountpoint `NEAREST`
2. Сървърът чака GPGGA съобщение (до 5 сек)
3. Декодира позицията → изчислява Haversine разстояние до всички активни станции
4. Изпраща данните от най-близкия mountpoint

---

## 6. Проблеми със сигурността (КРИТИЧНИ)

### 6.1. Хардкодирани пароли в plain text
**Файлове:** `REP_config_file.py`, `config_file.ini`
```python
MONGODB_AUTH_USER = 'c_rep'
MONGODB_AUTH_PASSWD = '1qazxsw2'
```
**Риск:** Паролите за базата данни са в source code и могат да се откраснат чрез git история.
**Препоръка:** Използване на environment variables или secrets manager.

### 6.2. Base64 вместо хеширане на пароли
**Файлове:** `REP_caster_server.py`, `login.php`, `newUser.php`
```python
token_auth = base64.b64encode("username:password")
```
**Риск:** Base64 е **обратимо кодиране**, НЕ криптиране. Всеки с достъп до DB може да декодира всички пароли за секунди.
**Препоръка:** Използване на `bcrypt` или `argon2` за хеширане на пароли.

### 6.3. Липса на input validation в PHP
**Файл:** `deleteStream.php`, `deleteUser.php`
```php
$deleted = $streams->deleteOne(['_id' => new MongoDB\BSON\ObjectID($_GET['idstream'])]);
```
**Риск:** Директно подаване на потребителски вход към MongoDB заявка. Потенциална NoSQL инжекция.
**Препоръка:** Валидиране на ObjectID формат преди използване. Използване на POST вместо GET за деструктивни операции.

### 6.4. DELETE чрез GET заявка
**Файлове:** `deleteStream.php`, `deleteUser.php`
**Риск:** GET заявките трябва да бъдат idempotent. Изтриващите операции трябва да са POST/DELETE. GET заявки могат да бъдат изпълнени от ботове, cache-ове или prefetch.
**Препоръка:** Промяна на AJAX метода от GET на POST.

### 6.5. Липса на CSRF защита
**Файлове:** Всички PHP форми
**Риск:** Няма CSRF token в нито една форма. Атакуващ може да подмами admin да изтрие потоци/потребители.
**Препоръка:** Добавяне на CSRF токен в сесията и проверка при всяка POST заявка.

### 6.6. XSS уязвимости
**Файлове:** `editStreams.php`, `editUsers.php`, `index.php`
```php
echo "<td>".$doc['mountpoint']."</td>";
```
**Риск:** Данните от базата се рендират директно без `htmlspecialchars()`. Ако потребител запише злонамерен JavaScript в mountpoint/username, той ще се изпълни в браузъра на admin-а.
**Препоръка:** Винаги escape-ване с `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')`.

### 6.7. Session management слабости
- Липса на `session_regenerate_id()` след login
- Липса на `HttpOnly` / `Secure` cookie флагове
- Няма session timeout

### 6.8. Публичен достъп до `generateJSON.php`
**Файл:** `generateJSON.php`
**Риск:** Няма session проверка. Всеки може да достъпи GeoJSON данните с координатите на всички ровери.
**Препоръка:** Добавяне на session проверка.

---

## 7. Проблеми с качеството на кода

### 7.1. Несъвместимост Python 2/3
Множество файлове съдържат:
```python
try:
    import BaseHTTPServer as HTTPServer  # Python 2
except ImportError:
    import http.server as HTTPServer     # Python 3
```
**Проблем:** Python 2 е End of Life от 01.01.2020. Тези guards могат да бъдат премахнати.

### 7.2. Три различни начина за конфигурация
| Метод | Файл | Използва се от |
|-------|------|---------------|
| Хардкодирани стойности | `REP_config_file.py` | `REP_ntrip_client.py`, `REP_rtcm3_capture.py` |
| INI файл чрез ConfigObj | `config_load.py` | `REP_caster_server.py`, `general_defs.py` |
| MongoDB колекция | `config_load_db.py` | Не се използва активно |

**Препоръка:** Консолидиране в един метод.

### 7.3. Дублиране на `createMongoClient()`
Функцията е дефинирана на 4 различни места:
1. `general_defs.py` — ред 9 (използва config_load)
2. `REP_rtcm3_capture.py` — ред 25 (използва REP_config_file)
3. `REP_ntrip_client.py` — ред 22 (използва REP_config_file)
4. `REP_caster_server.py` — импортира от `general_defs`

**Препоръка:** Единична дефиниция в `general_defs.py`.

### 7.4. Bare except клаузи
```python
except:
    print(" EXCEPTION: The packet has not enough bits")
```
**Файлове:** `REP_RTCM3_Decode.py`, `REP_rtcm3_capture.py`
**Проблем:** Хваща абсолютно всички изключения включително `KeyboardInterrupt` и `SystemExit`.

### 7.5. Липса на unit тестове
Проектът не съдържа нито един тест файл.

### 7.6. Debug `print()` в production код
Множество `print()` оператори из целия код, които трябва да бъдат заменени с logging:
```python
print(mountp)
print(stream)
print('lanza el constructor')
print('no vale el dato')
```

### 7.7. Некоректен индент в `REP_rtcm3_capture.py`
```python
if bandera == True:
    dataString = data.decode("ascii")
if dataString.find('SOURCE') > -1:    # ← Това НЕ е вмъкнато в if блока!
```
Очевиден бъг: вторият `if` винаги се изпълнява, а не само когато `bandera == True`.

### 7.8. Resource leaks в MongoDB връзки
В `REP_GPGGADecoder.py` → `getNearestMountpoint()`:
```python
dbClient.close()  # Затваря само при успех
# Ако exception → dbClient не се затваря
```
**Препоръка:** Използване на `try/finally` или context manager.

---

## 8. Проблеми с производителността

### 8.1. Polling модел за данни
`REP_caster_server.py` използва busy-wait `select()` loop:
```python
while 1:
    readable, writable, exceptional = select.select(...)
    # ...чете от DB за нов RTCM пакет на всяка итерация
```
Всяко изпращане на данни към ровер изисква MongoDB заявка. При 100 ровера = 100 заявки в секунда минимум.

**Препоръка:** Pub/Sub модел (Redis, MongoDB Change Streams, или direct memory sharing).

### 8.2. Нова MongoDB връзка при всяка RoverUser операция
`RoverUser.newUser()` и `disconnectUser()` всеки път създават нова връзка.
**Препоръка:** Connection pooling или singleton pattern.

### 8.3. Multiprocessing vs. async I/O
`REP_rtcm3_capture.py` създава нов процес за всяка базова станция. При 50+ станции = 50+ процеса.
**Препоръка:** Преминаване към `asyncio` или thread pool.

---

## 9. Зависимости

### Python
| Пакет | Роля |
|-------|------|
| `pymongo` | MongoDB драйвер |
| `configobj` | INI файл парсер |
| `bson` | Binary данни за MongoDB |

### PHP
| Пакет | Роля |
|-------|------|
| `mongodb/mongodb` (composer) | PHP MongoDB библиотека |
| Bootstrap 3 | CSS framework |
| jQuery 3.3.1 | JavaScript библиотека |
| DataTables | Табличен плъгин |
| OpenLayers 3 | Картографска библиотека |
| MetisMenu | Sidebar меню |
| Noty.js | Нотификации |
| Font Awesome | Икони |
| Chart.js | (Включен, но не се ползва активно) |
| Morris.js + Raphael | (Включени, но не се ползват) |

---

## 10. Силни страни

1. **Чиста модулна архитектура** — ясно разделение между capture, caster и web панел
2. **NTRIP v1.0 съвместимост** — коректна имплементация на протокола
3. **NEAREST функционалност** — автоматично насочване към най-близка станция чрез Haversine
4. **Подробни sourcetable записи** — пълна NTRIP sourcetable спецификация
5. **Logging с rotation** — добре структурирано логване с JSON конфигурация
6. **Мулти-GNSS поддръжка** — GPS, GLONASS, Galileo, BeiDou декодиране
7. **Администраторски панел** — пълнофункционален с карта, CRUD и мониторинг
8. **OpenLayers карта** — визуализация на станции и ровери в реално време
9. **GPL-3.0 лиценз** — отворен код

---

## 11. Препоръки за подобрения

### Критични (Сигурност)
| # | Действие | Приоритет |
|---|----------|-----------|
| 1 | Замяна на base64 с bcrypt/argon2 за пароли | 🔴 Критичен |
| 2 | Преместване на DB credentials в env vars | 🔴 Критичен |
| 3 | Добавяне на `htmlspecialchars()` за XSS защита | 🔴 Критичен |
| 4 | CSRF токени за всички форми | 🔴 Критичен |
| 5 | Валидация на ObjectID в deleteStream/deleteUser | 🔴 Критичен |
| 6 | Session проверка в `generateJSON.php` | 🟠 Висок |
| 7 | HTTPS за уеб панела | 🟠 Висок |
| 8 | POST вместо GET за DELETE операции | 🟠 Висок |

### Архитектурни
| # | Действие | Приоритет |
|---|----------|-----------|
| 9 | Премахване на Python 2 съвместимост | 🟡 Среден |
| 10 | Единна конфигурационна система | 🟡 Среден |
| 11 | Единен `createMongoClient()` с connection pool | 🟡 Среден |
| 12 | Замяна на `print()` с `logging` | 🟡 Среден |
| 13 | Поправка на indentation бъг в capture.py | 🔴 Критичен |
| 14 | Добавяне на unit тестове | 🟡 Среден |
| 15 | Async I/O (asyncio) вместо multiprocessing | 🟢 Нисък |

---

## 12. Структура на файловете (пълна)

```
CasterREPPy3v4Subido/
├── REP_caster_server.py          # Главен NTRIP Caster сървър (HTTP, Threading)
├── REP_rtcm3_capture.py          # RTCM3 приемник от базови станции (TCP, Multiprocessing)
├── REP_rtcm3_capture_WIN.py      # Windows версия на capture
├── REP_ntrip_client.py           # NTRIP клиент за външни кастери
├── REP_RTCM3_Decode.py           # RTCM 3.x декодер
├── REP_GPGGADecoder.py           # NMEA GGA декодер + Haversine
├── REP_RoverUserClass.py         # Ровер потребител модел
├── REP_init_mongodb.py           # Инициализация на MongoDB
├── REP_header_printer.py         # ASCII арт банер
├── REP_config_file.py            # Legacy хардкодирана конфигурация
├── REP_logging_config.json       # Logging конфигурация
├── config_file.ini               # Основен INI конфигурационен файл
├── config_load.py                # INI loader чрез ConfigObj
├── config_load_db.py             # Алтернативен loader от MongoDB
├── config_var.json               # Индирекция за INI файл
├── general_defs.py               # Общи дефиниции (MongoDB client, formatters)
├── gps_time.py                   # GPS време утилити
├── configspec                    # ConfigObj спецификация
├── *.log.*                       # Лог файлове
│
└── casterrep/                    # PHP Уеб интерфейс
    ├── conf.php                  # DB конфигурация
    ├── login.php                 # Login страница
    ├── logout.php                # Logout
    ├── index.php                 # Dashboard
    ├── map.php                   # OpenLayers карта
    ├── generateJSON.php          # GeoJSON API за картата
    ├── navigation.php            # Sidebar навигация
    ├── footer.php                # Footer
    ├── editStreams.php            # Списък потоци
    ├── editStreamPage.php        # Редакция поток
    ├── newStream.php             # Нов поток
    ├── deleteStream.php          # Изтриване поток
    ├── editUsers.php             # Списък потребители
    ├── editUserPage.php          # Редакция потребител
    ├── newUser.php               # Нов потребител
    ├── deleteUser.php            # Изтриване потребител
    ├── iso3166-1-a3.php          # Кодове за държави
    ├── composer.json             # PHP зависимости
    ├── css/                      # Стилове (Bootstrap, OL, custom)
    ├── js/                       # JavaScript (map, editstreams, editusers)
    ├── img/                      # Изображения
    └── vendor/                   # PHP/JS библиотеки (Composer)
```

---

## 13. Заключение

CasterREP е функционална NTRIP caster система, подходяща за малки до средни GNSS мрежи. Архитектурата е добре модуларизирана с ясно разделение между capture, serving и администрация. Основните рискове са свързани със **сигурността** — base64 пароли, липса на input валидация, XSS и CSRF уязвимости. За production deployment е задължително адресиране на критичните проблеми, описани в раздел 11.

**Общ рейтинг по области:**
- Функционалност: ⭐⭐⭐⭐ (4/5)
- Архитектура: ⭐⭐⭐ (3/5)
- Сигурност: ⭐⭐ (2/5)
- Качество на код: ⭐⭐⭐ (3/5)
- Документация: ⭐⭐ (2/5)
- Тестове: ⭐ (1/5)
