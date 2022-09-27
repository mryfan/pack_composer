<?php

namespace Pack\services\PackageNameEQValidationHandle;

use DragonCode\PrettyArray\Services\Formatter;
use Illuminate\Support\Arr;
use Pack\system\PackYii;

class GetTranslatableFile
{
    public static $localization='zh';

    private static $translationFileComposerPkgArray=[
        'laravel-lang/attributes',
        'laravel-lang/lang',
    ];

    private static function getValidationFilePath()
    {
        if(empty(PackYii::$config['provide_path'])){
            throw new \Exception('自动生成的文件目录为空');
        }

        $languagePath=static::getLanguagePath();
        $localizationPath=$languagePath.DIRECTORY_SEPARATOR.static::$localization;

        return $localizationPath.DIRECTORY_SEPARATOR.'validation.php';
    }

    public static function getLanguagePath()
    {
        return PackYii::$config['dist_dir'].DIRECTORY_SEPARATOR.PackYii::$config['provide_path'].DIRECTORY_SEPARATOR.'lang';
    }

    private static function getTranslationFileComposerPkgCLI()
    {
        if(empty(static::$translationFileComposerPkgArray)){
            throw new \Exception('翻译文件composer包的数组为空');
        }
        $tmp='composer require ';
        foreach (static::$translationFileComposerPkgArray as $item) {
            $tmp.=$item.' ';
        }
        return $tmp;
    }


    private static function formatArray($array)
    {
        return (new Formatter)->raw($array);
    }


    private static function getTranslationFileArrayFormat()
    {
        $tmp=[];
        foreach (static::$translationFileComposerPkgArray as $k=>$composerPkgName) {
            $composerPkgPath=PackYii::$config['tmp_dir'].DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.$composerPkgName;
            $translationFilePath=$composerPkgPath.DIRECTORY_SEPARATOR.'locales'.DIRECTORY_SEPARATOR.'zh_CN'.DIRECTORY_SEPARATOR.'php.json';
            $content=file_get_contents($translationFilePath);
            if(empty($content)){
                throw new \Exception('当前翻译文件内容为空,路径为:'.$translationFilePath);
            }
            $translationFileContentOfArray=Arr::undot(json_decode($content,true));
            $tmp=array_merge($tmp,$translationFileContentOfArray);
        }
        return static::formatArray($tmp);
    }

    public static function Handle()
    {
        if(!chdir(PackYii::$config['tmp_dir'])){
            throw new \Exception('更改目录失败：操作目标：下载翻译文件');
        }
        system(static::getTranslationFileComposerPkgCLI(),$return_var);

        //获取翻译文件的数组格式化之后形式
        $translationFileContent=static::getTranslationFileArrayFormat();

        //写入文件到磁盘
        static::writeToDisk($translationFileContent);
    }

    private static function writeToDisk($translationFileContent)
    {
        $validationFilePath=static::getValidationFilePath();

        if(!is_dir(dirname($validationFilePath))){
            mkdir(dirname($validationFilePath),'0777',true);
        }
        $tmp='<?php'."\n".'return '.$translationFileContent.';';
        file_put_contents($validationFilePath,$tmp);
    }
}