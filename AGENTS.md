<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>

<!-- backend-ai-factory:start — блок ставит install.sh, правки внутри перезапишутся -->
## Правила команды (бэкенд)

Обязательны для агента и приоритетнее дефолтного поведения. Перед любой задачей прочитай полные правила:
[process.md](.ai-factory/extensions/backend/rules/process.md),
[code-style.md](.ai-factory/extensions/backend/rules/code-style.md),
[graphql-rules.md](.ai-factory/extensions/backend/rules/graphql-rules.md).

- Задача с изменением кода начинается с `/aif-plan`. Покажи план и остановись: код — только после того,
  как разработчик подтвердил план.
- В плане и коде — только критерии задачи. Перед новой колонкой, полем схемы, флагом или защитой проверь:
  есть ли это в задаче, воспроизводится ли проблема в основной ветке, нет ли готового решения в Laravel, Lighthouse или проекте.
- Схема GraphQL — контракт с клиентами: существующие поля не удаляются и не меняют тип без `@deprecated`.
- Резолвер тонкий, логика — в сервисе или модуле, ошибки клиенту — `GraphQLException` с `ErrorCode`.
- `php artisan` и `composer` — так, как указано в «Настройках проекта». `migrate:fresh`, `rollback`, `db:wipe`, `composer update` — только по команде.
- Отладочные логи и `dd`/`dump` не добавляй.
- Коммит и push — только по команде разработчика.

## Шпаргалка: работа с ИИ-агентом (бэкенд, Laravel + Lighthouse)

### Цикл задачи

1. `/aif-plan <номер задачи> <что сделать>` → план в `.ai-factory/plans/`, агент остановился.
2. Читаешь план: поля схемы с директивами, миграции, коды ошибок, тесты, только критерии задачи. Правишь или подтверждаешь.
3. `/aif-implement` или код вручную по плану. Баг — `/aif-fix`, падающий тест и патч в `.ai-factory/patches/`.
4. `/aif-review` → исправить всё из "Блокирует МР".
5. Читаешь дифф целиком. Коммит одной строкой в формате проекта, push и МР — сам.

### Вопросы к своему коду перед МР

- Могу объяснить каждую колонку, поле схемы, директиву, ветку `catch` и проверку?
- Это есть в задаче? Проблема воспроизводится в основной ветке?
- Это уже умеет Laravel, Lighthouse или код проекта?
- Не сломает ли изменение схемы старых клиентов?

### Чек-лист перед МР

- [ ] `php artisan test` зелёный, `./vendor/bin/pint --test` без замечаний.
- [ ] `php artisan lighthouse:validate-schema` проходит, миграции применяются на чистой базе.
- [ ] Новые коды ошибок — в `ErrorCode` и документации кодов, новые ключи — в `.env.example`.
- [ ] Ветка домержена с основной веткой, конфликты разрешены.
- [ ] МР меньше 80 файлов, только критерии задачи, нет мёртвого кода.
- [ ] `/aif-review` пройден.

### Блокирует МР

- Автор не может объяснить колонку, директиву, ветку или проверку.
- Логика, запросы или внешние вызовы в резолвере; резолвер на то, что делает директива.
- Ошибка для клиента без `GraphQLException` + `ErrorCode`; тихий `catch`.
- Поле с данными пользователя без `@guard`; вход и коды без `@throttle`.
- Удаление или смена типа поля схемы без `@deprecated`; новый обязательный аргумент у старого поля.
- `$guarded = []`; коды и токены в открытом виде; `env()` вне `config/`.
- Запрос к базе в цикле, растущий список без `@paginate`; правка старой миграции.
- `dd`/`dump`; ПДн, коды или токены в логах.
- Новое поле схемы без feature-теста.

Полные правила: `.ai-factory/extensions/backend/rules/`.
<!-- backend-ai-factory:end -->
