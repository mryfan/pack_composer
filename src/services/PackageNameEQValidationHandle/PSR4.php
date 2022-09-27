<?php

namespace Pack\services\PackageNameEQValidationHandle;

use Pack\system\PackYii;

class PSR4
{
    public static function Handle()
    {
        $composerJSONPath=PackYii::$config['dist_dir'].DIRECTORY_SEPARATOR.'composer.json';
        $contents=file_get_contents($composerJSONPath);
        $contentsArray=json_decode($contents,true);
        $contentsArray['autoload']['psr-4']=array_merge($contentsArray['autoload']['psr-4'],[
            PackYii::$config['namespace'].'\\Provide\\'=>PackYii::$config['provide_path']
        ]);

        $prettyJson=json_encode($contentsArray,256+64+JSON_PRETTY_PRINT);
        file_put_contents($composerJSONPath,$prettyJson);
    }
}