<?php

namespace Pack\services\AutoloadFilesParse;

use Pack\services\FilesParse;
use Pack\services\NamespaceAndPathMap;
use Pack\system\PackYii;

class CalledThisFunctionHandle
{
    public static function handle($filesInfoMap)
    {
        //每个文件处理
        static::filesHandle($filesInfoMap);

    }


    private static function filesHandle($filesInfoMap)
    {
        foreach ($filesInfoMap as $item) {
            static::functionHandle($item);

        }
    }

    private static function functionHandle($filesInfo)
    {


        foreach ($filesInfo['file_useful_info']['function_block_array'] as $k=>$v){
            //扫描除了自己之外的文件里面调用了这个方法的位置，并替换成相关的命名空间\类名::方法名称的方式
            static::scanDIRAndParsePsr4AndFiles(PackYii::$config['dist_dir'],$v['function_name'],$filesInfo['file_info']);
        }
    }


    private static function scanDIRAndParsePsr4AndFiles($dirName,$functionName,$fileInfo)
    {
        $dirContentArray=FilesParse::getScandirArray($dirName);
        foreach ($dirContentArray as $k => $item) {
            $tmpName = $dirName . DIRECTORY_SEPARATOR . $item;
            if (is_dir($tmpName)) {
                static::scanDIRAndParsePsr4AndFiles($tmpName,$functionName,$fileInfo);
            }else{
                if(in_array(realpath($tmpName),ShengChengNewFiles::$allExcludeFiles)){
                    continue;
                }else{
                    //处理逻辑
                    static::tiHuan($tmpName,$functionName,$fileInfo);
                }
            }
        }
    }

    private static function tiHuan($filePath,$functionName,$fileInfo)
    {
        $contents=file_get_contents($filePath);
        //替换当前文件中的当前函数的调用方式

        $namespaceAndClassNameStr='\\'.PackYii::$config['namespace'].'\\'.$fileInfo['current_namespace'].ShengChengNewFiles::getNewFileSuffixName($fileInfo['class_name']).'::';

        $replace_contents=preg_replace('/(?<!function)(\s+)('.$functionName.')(\()/','${1}'.$namespaceAndClassNameStr.'${2}${3}${4}',$contents,-1,$count);
        if ($count > 0) {
            echo('当前函数  ' . $functionName . '  在当前文件  ' . $filePath . ' 替换了 ' . $count . ' 次'."\n\n");
        }
        file_put_contents($filePath,$replace_contents);
    }


}