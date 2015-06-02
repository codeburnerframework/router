<?php

include __DIR__.'/dispatcher.php';

spl_autoload_register(function ($class) {
	if (strpos($class, 'Codeburner\\Routing\\') === 0) {
        $name = substr($class, strlen('Codeburner\\Routing'));
        require __DIR__  . strtr($name, '\\', DIRECTORY_SEPARATOR) . '.php';
    }
});
