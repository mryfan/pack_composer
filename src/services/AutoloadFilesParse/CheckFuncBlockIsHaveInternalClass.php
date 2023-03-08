<?php

namespace Pack\services\AutoloadFilesParse;

class CheckFuncBlockIsHaveInternalClass
{
    private static function parse($functionContent)
    {
        $tmp = [];
        //获取所有内部类名称
        $allDeclaredClassNameArray = get_declared_classes();
        $tokenArray = token_get_all('<?php ' . $functionContent . ' ?>');
        foreach ($tokenArray as $k => $item) {
            if (is_array($item) && $item[0] == 283 && $item[1] == 'instanceof') {
                //获取当前数组key+2 的数组，这个数组是 类名称
                $className = empty($tokenArray[$k + 2][1]) ? '' : trim($tokenArray[$k + 2][1]);
                if (!empty($className) && in_array($className, $allDeclaredClassNameArray)) {
                    $tmp[] = $className;
                }
            }
        }
        return $tmp;
    }

    private static function getSuspectedUseInternalClassName($functionBlockArray)
    {
        $tmp = [];
        foreach ($functionBlockArray as $blockItem) {
            $tmp = array_merge($tmp, static::parse($blockItem['function_content']));
        }
        return $tmp;
    }

    private static function getUseClassNameArray(array $useBlockArray)
    {
        $tmp = [];
        foreach ($useBlockArray as $item) {
            $tokenArray = token_get_all('<?php ' . $item . ' ?>');
            $getClassName = function ($tokenArray) {
                $tmp265 = $tmp262 = '';
                foreach ($tokenArray as $item) {
                    if (is_array($item)) {
                        if ($item[0] == 265) {
                            $tmp265 = $item[1];
                        } elseif ($item[0] == 262) {
                            $tmp262 = $item[1];
                        }
                    }
                }
                if (!empty($tmp262)) {
                    return $tmp262;
                }
                return $tmp265;
            };
            $tmp[] = $getClassName($tokenArray);
        }
        return $tmp;
    }

    private static function findUseBlockIsHaveInternalClass($useBlockArray, $suspectedUseInternalClassNameArray)
    {
        $tmp = [];
        $useClassNameArray = static::getUseClassNameArray($useBlockArray);
        foreach ($suspectedUseInternalClassNameArray as $item) {
            if (!in_array($item, $useClassNameArray)) {
                $tmp[] = $item;
            }
        }
        return $tmp;
    }

    public static function getInternalClassNameArray($fileUsefulInfo)
    {
        //如果函数体里面存在疑似使用内部类的地方,那么纪录下来
        $suspectedUseInternalClassNameArray = static::getSuspectedUseInternalClassName($fileUsefulInfo['function_block_array']);
        //在命名空间里面查找是否是确认使用了内部类
        $array=static::findUseBlockIsHaveInternalClass($fileUsefulInfo['use_block_array'], $suspectedUseInternalClassNameArray);
        //最后返回 确实使用了内部类的数组，拼接生成要使用的use块
        $tmp=[];
        foreach ($array as $item){
            $tmp[]='use '. $item.';';
        }
        return $tmp;
    }
}