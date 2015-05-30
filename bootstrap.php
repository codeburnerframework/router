<?php

include __DIR__.'/src/dispatcher.php';

spl_autoload_register(function ($class) {
	if (strpos($class, 'Codeburner\\Routing\\') === 0) {
        $name = substr($class, strlen('Codeburner\\Routing'));
        require __DIR__ . '/src/' . strtr($name, '\\', DIRECTORY_SEPARATOR) . '.php';
    }
});
