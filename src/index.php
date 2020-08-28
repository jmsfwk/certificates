<?php

header('Content-Type: text/plain');

var_dump(array_filter($_SERVER, static function (string $key) {
    return strpos($key, 'SSL_') === 0;
}, ARRAY_FILTER_USE_KEY));
