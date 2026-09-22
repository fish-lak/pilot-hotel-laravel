<?php

use Illuminate\Http\Request;
use Illuminate\Foundation\Application;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->bind('path.public', fn () => __DIR__ . '/../public');

$app->handleRequest(Request::capture());
