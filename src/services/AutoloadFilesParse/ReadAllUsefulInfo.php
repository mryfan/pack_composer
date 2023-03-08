<?php

namespace Pack\services\AutoloadFilesParse;

use Exception;
use Pack\system\PackYii;
use ReflectionFunction;

class ReadAllUsefulInfo
{
    private static $tmpSuffixWithNamespaceFile = 'tmp';

    private static function getNameWithNamespaceFile($name)
    {
        if (!empty(static::$tmpSuffixWithNamespaceFile)) {
            return $name . '_' . static::$tmpSuffixWithNamespaceFile;
        }
        return $name;
    }

    private static function getPHPUseBlock($contents)
    {
        preg_match_all('/use\s+.*;$/m', $contents, $matches);

        return empty($matches[0]) ? [] : $matches[0];
    }

    private static function getPHPNamespaceBlock($contents)
    {
        preg_match_all('/namespace\s+.*;$/m', $contents, $matches);

        return empty($matches[0][0]) ? '' : $matches[0][0];
    }

    private static function getFunctionNameArray($contents)
    {
        $tmp = [];
        $resArr = token_get_all($contents);
        foreach ($resArr as $k => &$v) {
            if (is_numeric($v[0])) {
                $v['token_name'] = token_name($v[0]);
                if ($v['token_name'] == 'T_FUNCTION') {
                    if (!empty($resArr[$k + 2]['1'])) {
                        $tmp[] = $resArr[$k + 2]['1'];
                    }
                }
            }
        }
        return $tmp;
    }

    private static function getFunctionBlock($fileInfo, $contents)
    {
        //如果内容里面存在命名空间，那么就生成一个不带命名空间的临时文件，获取到相关的方法名和函数体之后需要删除
        $namespaceBlock = static::getPHPNamespaceBlock($contents);
        if (!empty($namespaceBlock)) {
            $tmp = static::getHaveNameSpaceOfFunctionInfo($fileInfo, $contents, $namespaceBlock);
        } else {
            $tmp = static::getDontHaveNameSpaceOfFunctionInfo($fileInfo, $contents);
        }
        return $tmp;
    }

    private static function getFunctionInfo($filePath, $contents)
    {
        $scriptPath = PackYii::$config['command_path'] . DIRECTORY_SEPARATOR . 'GetFuncInfo.php';
        $command = "php $scriptPath -f $filePath";
        exec($command, $output, $return_var);
        if ($return_var != 0) {
            throw new \Exception('执行命令：' . $command . '的返回状态错误');
        }
        if (empty($output)) {
            throw new \Exception('执行命令：' . $command . '的返回结果所填充的数组为空');
        }
        if (empty($output[0])) {
            throw new \Exception('执行命令：' . $command . '的返回结果所填充的数组第一行为空');
        }
        $resultArray = json_decode($output[0], true);
        if (empty($resultArray)) {
            throw new \Exception('命令结果的第一行数据转换成数组格式为空！命令结果的第一行数据为:' . $output[0]);
        }
        if ($resultArray['status'] == false) {
            throw new \Exception($resultArray['msg']);
        }
        if (empty($resultArray['data'])) {
            throw new \Exception('当前命令返回的函数信息数组为空');
        }

        return $resultArray['data'];
    }

    private static function getDontHaveNameSpaceOfFunctionInfo($fileInfo, $contents)
    {
        return static::getFunctionInfo($fileInfo['full_path'], $contents);
    }

    private static function getHaveNameSpaceOfFunctionInfo($fileInfo, $contents, $namespaceBlock)
    {
        //生成一个不带命名空间的临时文件，获取到相关的方法名和函数体之后需要删除
        [$tmpFilePath, $tmpContents] = static::shengChengTmpFile($fileInfo, $contents, $namespaceBlock);
        $data = static::getFunctionInfo($tmpFilePath, $tmpContents);
        unlink($tmpFilePath);
        return $data;
    }

    private static function shengChengTmpFile($fileInfo, $contents, $namespaceBlock)
    {
        $tmpContents = str_replace($namespaceBlock, '', $contents);
        $realPath = realpath($fileInfo['full_path']);
        $pathName = pathinfo($realPath);
        if (empty($pathName['filename'])) {
            throw new \Exception('当前文件 获取文件名称失败:当前文件为:' . $fileInfo['full_path']);
        }


        $tmpFilePath = $pathName['dirname'] . DIRECTORY_SEPARATOR . static::getNameWithNamespaceFile($pathName['filename']) . '.' . $pathName['extension'];

        $writeBytes = file_put_contents($tmpFilePath, $tmpContents);
        if (!$writeBytes) {
            throw new Exception('生成临时的文件失败:当前文件为:' . $fileInfo['full_path']);
        }
        return [$tmpFilePath, $tmpContents];
    }

    public static function handle($fileInfo)
    {
        $contents = file_get_contents($fileInfo['full_path']);
        $useBlockArray = static::getPHPUseBlock($contents);
        $functionBlockArray = static::getFunctionBlock($fileInfo, $contents);


        return [
            'use_block_array' => $useBlockArray,
            'function_block_array' => $functionBlockArray,
        ];
    }
}