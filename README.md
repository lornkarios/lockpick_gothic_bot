# 🔓 Lockpick Gothic Bot

[![Tests](https://github.com/lornkarios/lockpick_gothic_bot/actions/workflows/tests.yml/badge.svg)](https://github.com/lornkarios/lockpick_gothic_bot/actions/workflows/tests.yml)
![PHP](https://img.shields.io/badge/PHP-^8.2-777BB4?logo=php&logoColor=white)

> **Взламывай замки в Gothic 1 Remake — не ломай голову.**

Telegram-бот, который решает головоломки со взломом замков за тебя. Ввёл конфигурацию замка и текущее положение рычагов — получил пошаговую инструкцию.

---

## 🤔 Как это работает

В Gothic 1 Remake каждый замок — это механическая головоломка:

- У замка есть **N рычагов** («частей»)
- Каждый рычаг может быть в положении от **1 (верх)** до **7 (низ)**
- Замок открыт, когда **все рычаги стоят на 4** (середина)
- Рычаги **связаны**: двигая один, ты двигаешь другие — в ту же сторону (`+`) или в противоположную (`-`)

Бот строит граф состояний и находит кратчайший путь к решению.

### Пример

```
Конфигурация: 3:[2+,3+,1+]
Текущее состояние: 3:[1,3,4]

0 - -      ← положение 1 (верх)
- 0 -      ← положение 2
- - -      ← положение 3
- - 0      ← положение 4 (цель!)
- - -      ← положение 5
- - -      ← положение 6
- - -      ← положение 7
 1 2 3     ← рычаги

Передвинь 1 часть вниз → замок открыт!
```

---

## 🎮 Команды

| Команда | Что делает |
|---------|-----------|
| `/start` | Сброс сессии, начало новой головоломки |

### Режимы вывода решения

| Кнопка | Описание |
|--------|---------|
| **📋 Полная инструкция** | Весь список шагов одним сообщением |
| **👣 Пошагово** | Интерактивный режим — жмёшь «Следующий шаг» и видишь, что меняется |
| **➡️ Следующий шаг** | Переход к следующему действию в пошаговом режиме |

> **Совет**: Пошаговый режим удобнее для сложных замков — меньше шанса ошибиться.

---

## 🧠 Архитектура

```
User → Telegram → Polling → PollHandler
                                │
                    ┌───────────┼──────────────┐
                    ▼           ▼              ▼
           NeedConfig    NeedState       CallbackHandler
                    └───────┬──┘              │
                            ▼                  │
                      UnlockHandler             │
                     (BFS Solver)               │
                            │                   │
                            ▼                   ▼
                     full_instruction    step_by_step
```

- **Polling**: Бот работает через `getUpdates` (не webhook). Пинг API каждую секунду.
- **Поиск решения**: BFS (поиск в ширину) по графу состояний. Кодирует состояния в base-7 для быстрых сравнений.
- **Очередь**: Тяжёлые вычисления уходят в очередь (database queue), чтобы не блокировать polling.
- **Машина состояний**: Сессия пользователя проходит через цепочку `START → CONFIGURATION → UNLOCKING → UNLOCKED → STEP_BY_STEP_UNLOCKING`.

---

## 🛠 Стек

| Компонент | Технология |
|-----------|-----------|
| Backend | PHP 8.2, Laravel 12 |
| Telegram | defstudio/telegraph |
| База | MySQL |
| Очередь | Database queue |
| Инфра | Docker (php-nginx + mysql) |
| CI/CD | GitHub Actions |

---

## 🚀 Запуск

```bash
# Клонировать
git clone ...
cd lockpick_gothic_bot

# Поднять инфраструктуру
docker compose up -d

# Установить зависимости
docker compose exec app composer install

# Конфигурация
docker compose exec app cp .env.example .env
docker compose exec app php artisan key:generate

# Миграции
docker compose exec app php artisan migrate

# Запустить polling
docker compose exec app php artisan bot:work
```

### Переменные окружения

```env
TELEGRAM_BOT_TOKEN=       # Токен бота от @BotFather
TELEGRAM_BOT_NAME=        # Имя бота
QUEUE_CONNECTION=database # Очередь
```

---

## 🧪 Тесты

```bash
docker compose exec app php artisan test
```

Покрыты:
- Механика движения рычагов
- Валидация ввода
- BFS-решатель
- Генерация инструкций
- Пошаговый режим

---

## 🎯 Мотивация

Потому что в Gothic 1 Remake замки — это больно, когда у тебя 30 рычагов и каждый двигает ещё 5. А боту всё равно, он посчитает.

---

## 📄 Лицензия

MIT
