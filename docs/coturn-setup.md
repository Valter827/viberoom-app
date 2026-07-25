# Свой TURN-сервер (coturn) для голосового чата

Заменяет бесплатный публичный `openrelay.metered.ca`, у которого нет
гарантий аптайма и есть общий лимит трафика на всех пользователей сразу —
именно это давало "Восстановление связи…" и пропадающий звук.

Выполнять на том же VPS, где крутится сайт (или на отдельном — тогда
`TURN_HOST` ниже указывает на него).

## 1. Установка

```bash
sudo apt update
sudo apt install -y coturn

# включаем демон (по умолчанию в Debian/Ubuntu он выключен)
sudo sed -i 's/#TURNSERVER_ENABLED=1/TURNSERVER_ENABLED=1/' /etc/default/coturn
```

## 2. Секрет для временных кредов

```bash
openssl rand -hex 32
```

Скопируй результат — он пойдёт и в `turnserver.conf`, и в `.env` сайта
(`TURN_SECRET`), значения должны совпадать.

## 3. Конфиг `/etc/turnserver.conf`

Замени плейсхолдеры на свои значения и допиши/раскомментируй эти строки
(остальной файл можно не трогать):

```conf
listening-port=3478
tls-listening-port=5349

# внешний (публичный) IP сервера — узнать: curl -4 ifconfig.me
external-ip=ТВОЙ_ПУБЛИЧНЫЙ_IP

realm=valter.pp.ua
server-name=valter.pp.ua

# временные креды по HMAC вместо статичного логина/пароля
use-auth-secret
static-auth-secret=ТОТ_ЖЕ_СЕКРЕТ_ЧТО_В_ENV

# диапазон портов для релея медиа
min-port=49152
max-port=65535

# без TLS-сертификата TURNS не поднимется — сначала можно закомментировать
# эти две строки и включить позже, когда будет сертификат (см. шаг 5)
cert=/etc/letsencrypt/live/valter.pp.ua/fullchain.pem
pkey=/etc/letsencrypt/live/valter.pp.ua/privkey.pem

no-tcp-relay
fingerprint
no-multicast-peers
no-cli
```

## 4. Файрвол / security group

Открой у провайдера (DigitalOcean/Timeweb/Selectel и т.п. — где бы ни
был VPS) и локально через `ufw`:

```bash
sudo ufw allow 3478/udp
sudo ufw allow 3478/tcp
sudo ufw allow 5349/tcp
sudo ufw allow 49152:65535/udp
```

Если сервер за NAT облачного провайдера (внутренний IP ≠ публичный) —
дополнительно пропиши в `turnserver.conf`:

```conf
external-ip=ВНУТРЕННИЙ_IP/ПУБЛИЧНЫЙ_IP
```

## 5. TLS-сертификат (можно пропустить на старте)

Если уже используешь certbot для самого сайта — тот же сертификат
подойдёт для coturn, просто дай процессу coturn права на чтение:

```bash
sudo usermod -a -G ssl-cert turnserver
```

Без TLS TURN всё равно будет работать (`turn:`), просто без `turns:`
(зашифрованный вариант) — для старта этого достаточно, можно добавить
TLS позже.

## 6. Запуск

```bash
sudo systemctl enable --now coturn
sudo systemctl status coturn
```

## 7. Проверка

```bash
# базовая проверка, что порт слушается
sudo ss -ulnp | grep 3478

# полноценная проверка ICE через встроенный клиент Google
# (открыть в браузере, вписать turn:ТВОЙ_ХОСТ:3478, временные креды —
# по одной сгенерировать вручную по формуле из VoiceController::turnCredentials)
```
https://webrtc.github.io/samples/src/content/peerconnection/trickle-ice/

## 8. На стороне Laravel

В `.env` сайта:

```env
TURN_HOST=valter.pp.ua
TURN_SECRET=тот_же_секрет_что_в_turnserver.conf
```

Дальше — просто `php artisan config:cache`, если он используется.
Код (`VoiceController::turnCredentials`, `app.js`) уже подготовлен.
