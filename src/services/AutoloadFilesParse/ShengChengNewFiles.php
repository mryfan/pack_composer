<?php

namespace Pack\services\AutoloadFilesParse;

use Pack\system\PackYii;
use Exception;

class ShengChengNewFiles
{
    public static $allExcludeFiles = [];

    private static $newFileSuffixName = 'new';

    public static function getNewFileSuffixName($name)
    {
        if (!empty(static::$newFileSuffixName)) {
            return $name . '_' . static::$newFileSuffixName;
        }
        return $name;
    }

    private static function getNewFilePath($fileData)
    {
        $pathinfo = pathinfo(realpath($fileData['full_path']));

        $newPath = $pathinfo['dirname'] . DIRECTORY_SEPARATOR . static::getNewFileSuffixName($fileData['class_name']) . '.' . $pathinfo['extension'];

        static::$allExcludeFiles[] = realpath($fileData['full_path']);
        return $newPath;
    }

    public static function handle($fileUsefulInfo, $fileData)
    {
        if (empty($fileUsefulInfo['function_block_array'])) {
            return ['', ''];
        }
        $newFilePath = static::getNewFilePath($fileData);

        //获取函数体里面是否有使用内部类
        $arr = CheckFuncBlockIsHaveInternalClass::getInternalClassNameArray($fileUsefulInfo);
        if (!empty($arr)) {
            $fileUsefulInfo['use_block_array'] = array_merge($fileUsefulInfo['use_block_array'], $arr);
        }

        $contents = '<?php' . "\n";

        $tmpNamespace = PackYii::$config['namespace'] . '\\' . rtrim($fileData['current_namespace'], '\\') ;

        $contents .= 'namespace ' . $tmpNamespace. ';' . "\n\n";

        foreach ($fileUsefulInfo['use_block_array'] as $k => $v) {
            $contents .= $v . "\n";
        }
        $contents .= "\n\n\n\n";

        //class头
        $contents .= 'class ' . static::getNewFileSuffixName($fileData['class_name']) . '{' . "\n\n";

        //函数体
        foreach ($fileUsefulInfo['function_block_array'] as $k => $v) {
            $contents .= 'static ' . $v['function_content'];
        }
        $contents .= "}\n";

        $writeBytes = file_put_contents($newFilePath, $contents);
        if (!$writeBytes) {
            throw new Exception('生成新的文件出现错误：当前文件为' . $fileData['full_path']);
        }

        return [$fileUsefulInfo, $tmpNamespace];
    }
}