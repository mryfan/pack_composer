<?php

namespace Pack\services;

use Exception;
use Pack\system\PackYii;

class NamespaceAndPathMap
{
    private static $namespaceMap = [];

    private static function getClassName($namespace, $className)
    {
        $willShengChengName = $namespace . $className;
        if (!empty(static::$namespaceMap[$willShengChengName])) {

            $realClassName = $className . count(static::$namespaceMap[$willShengChengName]['real_class_name_map']);

            static::$namespaceMap[$willShengChengName]['real_class_name_map'][] = $realClassName;

            return $realClassName;
        } else {
            static::$namespaceMap[$willShengChengName] = [
                'full_name' => $willShengChengName,
                'namespace' => $namespace,
                'real_class_name_map' => [$className],
            ];
            return $className;
        }
    }


    public static function handle($dirName)
    {
        $composerJsonPath = $dirName . DIRECTORY_SEPARATOR . 'composer.json';
        //解析composer.json 文件的各项有用数据
        $content = file_get_contents($composerJsonPath);
        if (empty($content)) {
            throw new Exception('获取composer.json的数据失败:文件路径为：' . $composerJsonPath);
        }
        $composerJsonArray = json_decode($content, true);
        if (empty($composerJsonArray)) {
            throw new Exception('composer.json数据json_decode为array失败:文件路径为：' . $composerJsonPath);
        }

        //获取composer.json 里面的autoload项的数组
        $autoloadArray = $composerJsonArray['autoload'];
        if (empty($autoloadArray)) {
            throw new Exception('当前composer.json文件 [\'autoload\'] 项数据为空:文件路径为：' . $composerJsonPath);
        }

        $diffPathStr = PackYii::comparePathDiff($dirName, PackYii::$config['dist_dir']);


        $filesNameAndNameSpaceMap = static::getFilesNameAndNameSpaceMap($autoloadArray, $dirName);
        //生成以老的命名空间为key,老的命名空间与路径的映射  与  新的命名空间与路径的映射

        $psr4MapArray = $filesArray = [];
        foreach ($autoloadArray as $key => $item) {
            [$psr4Map, $files] = static::keyOfAutoloadHandle($key, $item, $dirName, $diffPathStr, $filesNameAndNameSpaceMap);

            if (!empty($psr4Map)) {
                $psr4MapArray[] = $psr4Map;
            }
            if (!empty($files)) {
                $filesArray[] = $files;
            }
        }
        return [$psr4MapArray, $filesArray];
    }

    private static function keyOfAutoloadHandle($keyName, $keyValue, $dirName, $diffPathStr, $filesNameAndNameSpaceMap)
    {
        $diffPathStr = rtrim(rtrim($diffPathStr, '/'), '\\');

        $psr4Map = $files = [];
        foreach ($keyValue as $k => $v) {
            if ($keyName == 'psr-4') {
                $psr4Map[] = [
                    'oldNamespace' => $k,
                    'oldPath' => $v,
                    'newNamespace' => PackYii::getNamespace() . '\\' . $k,
                    'newPath' => empty($v) ? $diffPathStr : rtrim(rtrim($diffPathStr . DIRECTORY_SEPARATOR . $v, '/'), '\\'),
                ];
            } else if ($keyName == 'files') {
                $tmpNameSpace = $filesNameAndNameSpaceMap[$v]['namespace'];
                if (empty($tmpNameSpace)) {
                    throw new Exception('没有匹配到的命名空间：' . $dirName);
                }

                $tmpFullPath = $filesNameAndNameSpaceMap[$v]['full_path'];
                if (empty($tmpFullPath)) {
                    throw new Exception('没有匹配到当前file文件的路径' . $dirName);
                }

                $class_name = static::getClassName($tmpNameSpace, pathinfo(realpath($tmpFullPath))['filename']);

                if (!in_array(realpath($tmpFullPath), PackYii::$config['exclude_files_path'])) {
                    $files[] = [
                        'oldPath' => $v,
                        'newPath' => $diffPathStr . DIRECTORY_SEPARATOR . $class_name . '.' . pathinfo(realpath($tmpFullPath))['extension'],
                        'current_namespace' => $tmpNameSpace,
                        'full_path' => $tmpFullPath,
                        'class_name' => $class_name,
                    ];
                }
            }
        }
        return [$psr4Map, $files];
    }

    private static function getFilesNameAndNameSpaceMap($autoloadArray, $dirName)
    {
        $tmp = [];
        if (!empty($autoloadArray['files'])) {
            foreach ($autoloadArray['files'] as $k => $fileName) {
                $fullPath = rtrim(rtrim($dirName, '\\'), '/') . DIRECTORY_SEPARATOR . rtrim(ltrim($fileName, '\\'), '/');
                $psr4Array = $autoloadArray['psr-4'] ?? '';
                $namespace = static::getNamespaceByPath($fileName, $psr4Array, $dirName);
                $tmp[$fileName] = [
                    'namespace' => $namespace,
                    'full_path' => $fullPath,
                ];
            }
        }
        return $tmp;
    }

    private static function getNamespaceByPath($fileName, $psr4Array, $dirName)
    {
        $namespaceStr = '';
        $tmpFileParentPath = dirname($fileName);
        $fileParentPath = $tmpFileParentPath == '.' ? '' : $tmpFileParentPath;

        if (!empty($psr4Array)) {
            foreach ($psr4Array as $k => $v) {
                $namespaceStr = $k . $fileParentPath;
            }
        } else {
            $diffPathStr = PackYii::comparePathDiff($dirName, PackYii::$config['dist_dir']);
            //去掉第一段
            $diffPathArray = explode('/', $diffPathStr);
            $excludeFirstItemArray = array_slice($diffPathArray, 2);
            $excludeFirstItemStr = implode('\\', $excludeFirstItemArray);

            $namespaceStr = trim($excludeFirstItemStr, '\\/');
        }
        $newNamespaceStr=str_replace('-','_',$namespaceStr);

        if (empty($newNamespaceStr)) {
            throw new Exception('获取父级的path字符串为空,目录为：' . $dirName);
        }
        return $newNamespaceStr;
    }
}