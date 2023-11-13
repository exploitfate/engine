# Install and setup project

Don't forget install RabbitMQ
```
sudo apt-get install -y rabbitmq-server php-bcmath php-mbstring
```

Usage example
```
$engine = new Engine(new EngineQueue($queueConnectionConfig), $request, $engineParams);
$engine
    ->setIsShowReferral(true)
    ->notify();
```
