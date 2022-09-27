<?php

namespace Pack\services;

use Pack\services\AutoloadFilesParse\FilesHandle;
use Pack\services\AutoloadFilesParse\ShengChengNewFiles;
use Pack\system\PackYii;

class Psr4Parse
{
    public static function handle($psr4Array)
    {
        $psr4DataArray=FilesHandle::moreToOneArray($psr4Array);

        //处理每个命名空间
        static::namespaceHandle($psr4DataArray);
        return $psr4DataArray;
    }

    private static function namespaceHandle($psr4DataArray)
    {
        foreach ($psr4DataArray as $k=>$v){
            //搜索所有的文件以老的命名空间开头的全部替换为新的
            static::scanDIRAndParsePsr4(realpath(PackYii::$config['dist_dir']),$v);
        }
    }

    private static function scanDIRAndParsePsr4($dirName,$currentPSR4Info)
    {
        $dirContentArray=FilesParse::getScandirArray($dirName);
        foreach ($dirContentArray as $k => $item) {
            $tmpName = $dirName . DIRECTORY_SEPARATOR . $item;
            if (is_dir($tmpName)) {
                static::scanDIRAndParsePsr4($tmpName,$currentPSR4Info);
            }else{
                if(in_array(realpath($tmpName),ShengChengNewFiles::$allExcludeFiles)){
                    continue;
                }else{
                    //处理逻辑
                    static::tiHuan($tmpName,$currentPSR4Info);
                }
            }
        }
    }

    private static function tiHuan($tmpName,$currentPSR4Info)
    {
        $contents=file_get_contents($tmpName);

        $namespaceOldStr=str_replace('\\','\\\\',rtrim($currentPSR4Info['oldNamespace'],'\\'));

        $pattern='/((\s+|\')|((?<!\S)\\\))('.$namespaceOldStr.')/';
        $namespaceNewStr=rtrim($currentPSR4Info['newNamespace'],'\\');


        $replaceContents=preg_replace($pattern,'${1}'.$namespaceNewStr,$contents,-1,$count);
        if($count>0){
            echo '当前文件 '.$tmpName.' 存在命名空间:'.$namespaceOldStr."\n\n";
        }
        file_put_contents($tmpName,$replaceContents);
    }
}