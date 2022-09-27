<?php

namespace Pack\services\PackageNameEQValidationHandle;

class Handle
{
    public static function index()
    {
        //下载翻译文件，并转换成数组，同时生成数组的文件形式
        GetTranslatableFile::Handle();
        //生成调用的文件
        GenerateValidationFile::Handle();
        //最后的处理，追加自定义的composer psr4 相关属性到composer.json文件里面
        PSR4::Handle();
    }
}