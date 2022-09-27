<?php

namespace Pack\services;

use Exception;
use Pack\system\PackYii;

class CommandExec
{
    /**
     * @return void
     * @throws Exception
     */
    public static function downloadVendor()
    {
        if (empty(PackYii::$config['tmp_dir'])) {
            throw new Exception('请填写临时目录');
        }

        //检查是否存在 composer.json 的模板文件
        if(empty(PackYii::$config['tpl_path'])){
            throw new Exception('配置 tpl_path 项为空');
        }

        //清空目录
        if (is_dir(PackYii::$config['tmp_dir'])) {
            static::delDir(PackYii::$config['tmp_dir']);
        }

        //创建目录
        if (!mkdir(PackYii::$config['tmp_dir'], 0777, true)) {
            throw new Exception('创建临时目录失败');
        }

        if (!chdir(PackYii::$config['tmp_dir'])) {
            throw new Exception('更改目录失败');
        }

        //复制composer的模板文件到临时目录里面
        $tmpContents=file_get_contents(PackYii::getTPLPath());
        file_put_contents(PackYii::$config['tmp_dir'].DIRECTORY_SEPARATOR.'composer.json',$tmpContents);

        if (empty(PackYii::$config['composer_cli']['package_name'])) {
            throw new Exception('composer_cli package_name 参数为空');
        }
        if(empty(PackYii::$config['composer_cli']['version'])){
            $command=PackYii::$config['composer_cli']['package_name'];
        }else{
            $command=PackYii::$config['composer_cli']['package_name'].':'.PackYii::$config['composer_cli']['version'];
        }
        system('composer require '.$command, $returnVar);
    }

    private static function delDir($directory)
    { // 自定义函数递归的函数整个目录
        if (file_exists($directory)) { // 判断目录是否存在，如果不存在rmdir()函数会出错
            if ($dir_handle = @opendir($directory)) { // 打开目录返回目录资源，并判断是否成功
                while ($filename = readdir($dir_handle)) { // 遍历目录，读出目录中的文件或文件夹
                    if ($filename != '.' && $filename != '..') { // 一定要排除两个特殊的目录
                        $subFile = $directory . "/" . $filename; //将目录下的文件与当前目录相连
                        // echo $subFile . "<br>";
                        if (is_dir($subFile)) { // 如果是目录条件则成了
                            static::delDir($subFile); //递归调用自己删除子目录
                        }
                        if (is_file($subFile)) { // 如果是文件条件则成立
                            unlink($subFile); //直接删除这个文件
                        }
                    }
                }
                closedir($dir_handle); //关闭目录资源
                rmdir($directory); //删除空目录
            }
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public static function copyVendor()
    {
        if (empty(PackYii::$config['dist_dir'])) {
            throw new Exception('请填写目标的仓储目录');
        }
        //清空目录
        if (is_dir(PackYii::$config['dist_dir'])) {
            static::delDir(PackYii::$config['dist_dir']);
        }

        //创建目录
        if (!mkdir(PackYii::$config['dist_dir'], 0777, true)) {
            throw new Exception('创建临时目录失败');
        }

        //开始复制数据到目标目录中
        PackYii::copydir(PackYii::$config['tmp_dir'],PackYii::$config['dist_dir']);


        //去除composer.lock文件、composer.json文件、autoload.php
        $willDelFilesArray = [
            PackYii::$config['dist_dir'] . DIRECTORY_SEPARATOR . 'composer.lock',
            PackYii::$config['dist_dir'] . DIRECTORY_SEPARATOR . 'composer.json',
            PackYii::$config['dist_dir'] . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php'
        ];
        static::delFile($willDelFilesArray);


        //删除composer目录
        static::delDir(PackYii::$config['dist_dir'] . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'composer');

        //将vendor目录改名为src
        rename(PackYii::$config['dist_dir'] . DIRECTORY_SEPARATOR . 'vendor', PackYii::$config['dist_dir'] . DIRECTORY_SEPARATOR . 'src');
    }

    /**
     * @param $filesArray
     * @return void
     * @throws Exception
     */
    private static function delFile($filesArray)
    {
        foreach ($filesArray as $k => $filePath) {
            if (!unlink($filePath)) {
                throw new Exception('删除文件:' . $filePath . ' 失败');
            }
        }
    }
}