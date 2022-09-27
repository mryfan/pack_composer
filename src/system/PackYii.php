<?php

namespace Pack\system;

class PackYii
{
    public static $config = [];

    public static function comparePathDiff($currentPath, $targetPath)
    {
        $tmp = ltrim(str_replace($targetPath, '', $currentPath), '\\');
        return str_replace('\\', '/', $tmp);
    }

    public static function getNamespace()
    {
        return static::$config['namespace'];
    }

    public static function copydir($dirsrc, $dirto)
    {
        if (file_exists($dirto)) {
            if (!is_dir($dirto)) {
                throw new \Exception($dirto . '不是目录，不能复制！');
            }
        } else {
            //如果原目标目录不存在则创建
            mkdir($dirto);
        }
        $files = opendir($dirsrc);
        while ($filename = readdir($files)) {
            if ($filename != '.' && $filename != '..') {
                $srcfile = $dirsrc . '/' . $filename;//原文件
                $tofile = $dirto . '/' . $filename;//目标文件
                if (is_file($srcfile)) {
                    copy($srcfile, $tofile);
                }
                if (is_dir($srcfile)) {
                    static::copydir($srcfile, $tofile);
                }
            }
        }
        closedir($files);
    }

    public static function getTPLPath($fileName='composer.tpl.json')
    {
        $tmp=static::$config['tpl_path'].DIRECTORY_SEPARATOR.$fileName;
        if(!is_file($tmp)){
            throw new \Exception($tmp . '不是一个文件，请检查');
        }
        return $tmp;
    }
}