<?php

namespace Pack\controllers;

use Exception;
use Pack\services\AutoloadFilesParse\FilesHandle;
use Pack\services\CommandExec;
use Pack\services\ComposerFile;
use Pack\services\FilesParse;
use Pack\services\PackageNameEQValidationHandle\Handle;
use Pack\services\Psr4Parse;
use Pack\system\PackYii;

class PackIndex
{
    public function __construct(array $config)
    {
        PackYii::$config=$config;
    }


    /**
     * @return void
     * @throws Exception
     */
    public function run()
    {
        //执行下载第三方包命令,并存储到临时文件夹里面
        CommandExec::downloadVendor();
//        //复制处理好的数据到新的处理工作目录中
        CommandExec::copyVendor();

        //程序的工作目录
        $workDir=PackYii::$config['dist_dir'].DIRECTORY_SEPARATOR.'src';

        //生成psr4以及Files的关系映射
        $psr4AndFilesMap=FilesParse::parsePsr4AndFiles($workDir);

        //处理Files的数据
        $filesDataArray=FilesHandle::handle($psr4AndFilesMap['files']);

        //处理psr4的数据
        $psr4DataArray=Psr4Parse::handle($psr4AndFilesMap['psr-4']);

        //生成composer的文件
        ComposerFile::handle($psr4DataArray);

        //针对illuminate/validation 包的特殊处理
        if(PackYii::$config['composer_cli']['package_name']==='illuminate/validation'){
            Handle::index();
        }
    }
}