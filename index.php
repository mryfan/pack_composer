<?php
use Pack\controllers\PackIndex;


require_once __DIR__ . DIRECTORY_SEPARATOR . '/vendor/autoload.php';

$config= require_once __DIR__ . DIRECTORY_SEPARATOR . 'src/config/params.php';

try{
    (new PackIndex($config))->run();
}catch(\Exception $e){
    var_dump($e->getMessage().' in '.$e->getFile().'  at '.$e->getLine());
}

