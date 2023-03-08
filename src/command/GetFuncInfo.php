<?php

namespace Pack\command;

use Exception;
use ReflectionFunction;

class GetFuncInfo
{
    private static $log = [];

    private static function getFilePath(array $inputDataArray)
    {
        $tmpFilePath = $inputDataArray['f'] ?? '';
        if (empty($tmpFilePath)) {
            throw new Exception('文件路径参数为空');
        }
        return $tmpFilePath;
    }

    private static function checkEnvAndGetInputDataArray()
    {
        if (PHP_SAPI !== 'cli' && !empty($_SERVER['REMOTE_ADDR'])) {
            throw new Exception('此脚本只能运行于CLI模式下');
        }
        $inputDataArray = getopt('f:');

        return [
            'filePath' => static::getFilePath($inputDataArray),//解析文件路径
        ];
    }

    private static function errorHandle(Exception $e)
    {
        $tmp = [
            'status' => false,
            'msg' => $e->getMessage(),
            'data' => [],
            'log' => static::$log,
        ];
        echo json_encode($tmp, 256 + 64);
    }

    private static function successHandle(array $data)
    {
        $tmp = [
            'status' => true,
            'msg' => 'success',
            'data' => $data,
            'log' => static::$log,
        ];
        echo json_encode($tmp, 256 + 64);
    }

    private static function handle(string $filePath)
    {
        $tmp = [];
        if (!file_exists($filePath)) {
            throw new Exception('当前文件:' . $filePath . '不存在，请检查');
        }
        require_once $filePath;
        $definedFunctionsArray = get_defined_functions();
        foreach ($definedFunctionsArray['user'] as $k => $functionName) {
            try {
                $func = new ReflectionFunction($functionName);
            } catch (Exception $e) {
                throw new \Exception('反射当前函数名称：' . $functionName . '出现错误,当前文件为' . $filePath . '  异常原因:' . $e->getMessage());
            }
            //排除内部函数
            if ($func->isInternal()) {
                continue;
            }
            $start = $func->getStartLine() - 1;
            $end = $func->getEndLine() - 1;
            $filename = $func->getFileName();
            $functionContent = implode("", array_slice(file($filename), $start, $end - $start + 1));
            $tmp[] = [
                'function_name' => $functionName,
                'function_content' => $functionContent,
            ];
        }
        return $tmp;
    }

    public static function run()
    {
        try {
            $inputDataArray = static::checkEnvAndGetInputDataArray();
            $funcInfoArray = static::handle($inputDataArray['filePath']);
            static::successHandle($funcInfoArray);
        } catch (Exception $e) {
            static::errorHandle($e);
        }
    }
}

//   C:/Users/qianyi/Desktop/pack_composer/src/config/../../dist/src/illuminate/collections/helpers.php
GetFuncInfo::run();