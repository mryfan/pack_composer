<?php

namespace Pack\services;

use Pack\system\PackYii;
use Exception;

class FilesParse
{
    private static $allPsr4AndFilesMap = [
        'psr-4'=>[],
        'files'=>[],
    ];

    public static function parsePsr4AndFiles($workDir)
    {
        static::scanDIRAndParsePsr4AndFiles($workDir);

        return static::$allPsr4AndFilesMap;
    }

    public static function getScandirArray($dirName)
    {
        $dirContentArray = scandir($dirName);
        return array_filter($dirContentArray, function ($v) {
            return !in_array($v, PackYii::$config['common_exclude_file']);
        });
    }

    public static function scanDIRAndParsePsr4AndFiles($dirName)
    {
        $dirContentArray=static::getScandirArray($dirName);
        if (in_array('composer.json', $dirContentArray)) {
            [$psr4MapArray,$filesArray]=NamespaceAndPathMap::handle($dirName);
            if(!empty($psr4MapArray)){
                static::$allPsr4AndFilesMap['psr-4'][]=$psr4MapArray;
            }

            if(!empty($filesArray)){
                static::$allPsr4AndFilesMap['files'][]=$filesArray;
            }

        } else {
            foreach ($dirContentArray as $k => $item) {
                $tmpName = $dirName . DIRECTORY_SEPARATOR . $item;
                if (is_dir($tmpName)) {
                    static::scanDIRAndParsePsr4AndFiles($tmpName);
                }
            }
        }
    }
}