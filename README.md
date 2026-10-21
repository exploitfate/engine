# Install and setup project

Don't forget install RabbitMQ
```
sudo apt-get install -y rabbitmq-server
docker compose pull
docker compose run --rm php composer install
```

The Docker runtime includes bcmath and mbstring, the PHP 7.4 security backport
for CVE-2026-109618/CVE-2026-109619, and disables upload progress. Applications
embedding this component must update their own PHP interpreter as well.

Usage example
```
$engine = new Engine(new EngineQueue($queueConnectionConfig), $request, $engineParams);
$engine
    ->setIsShowReferral(true)
    ->notify();
```
