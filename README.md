# Invoke PHP

```php
<?php

require_once __DIR__.'/vendor/autoload.php';

use function Kdubuc\Invocator\invoke;
use My\Custom\RequestHandler;

$action = new RequestHandler();

invoke($action, STDIN, STDOUT);
```

Invoke's job is to marshal a raw HTTP request incomming into its input resource to build a PSR-7 ServerRequest object, invoke your chosen PSR-15 RequestHandler with that, and to respond with a raw HTTP Response message issued by the RequestHandler.

Invoke uses stdio as an interface between the API gateway and your RequestHandler.

Can be useful in a Serverless context (API Gateway -> Tiny HTTP server like kdubuc/watchdog -> Invoke).