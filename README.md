# Laufen — Backend

![CI](https://github.com/steelbsteel/LaufenBackend/actions/workflows/ci.yml/badge.svg?branch=develop)

GraphQL API для трекера пробежек Laufen: Laravel, Lighthouse, Sanctum, PostgreSQL.

## Коды ошибок

Ошибки бизнес-логики приходят в `errors[].extensions`:

```json
{ "message": "Пробежка не найдена", "extensions": { "code": 2001, "name": "RUN_NOT_FOUND" } }
```

Ошибки валидации приходят отдельно, в `extensions.validation` — тексты по полям.

| Код | Имя | Где | Что делать клиенту |
|---|---|---|---|
| 1001 | `INVALID_CREDENTIALS` | `login` | Показать «Неверный email или пароль» |
| 1002 | `REFRESH_TOKEN_INVALID` | `authTokenRefresh` | Очистить токены, открыть экран входа |
| 2001 | `RUN_NOT_FOUND` | `run`, `runDelete` | Пробежки нет или она чужая: вернуться к списку и обновить его |
| 2002 | `RUN_TRACK_INVALID` | `runCreate` | Ошибка в данных трека — баг клиента, отправить в логи |