<?php

namespace Pack\services\PackageNameEQValidationHandle;

use Pack\system\PackYii;

class GenerateValidationFile
{
    private static function getValidationFileDIRPath()
    {
        $tmp=PackYii::$config['dist_dir'].DIRECTORY_SEPARATOR.PackYii::$config['provide_path'].DIRECTORY_SEPARATOR.'validation';
        if(!is_dir($tmp)){
            mkdir($tmp,0777,true);
        }
        return $tmp;
    }

    private static function writeToDisk($contents)
    {
        $DIRName=static::getValidationFileDIRPath();
        $fileName=$DIRName.DIRECTORY_SEPARATOR.'Validation.php';
        file_put_contents($fileName,$contents);
    }


    private static function getValidationFile()
    {
        $tplFilePath = PackYii::getTPLPath('validation.tpl.php');
        $fileContents = file_get_contents($tplFilePath);
        $fileContentsReplace = str_replace(
            ['{{namespace}}', '{{testTranslationPath}}','{{testTranslationLocale}}'],
            [PackYii::$config['namespace'], 'dirname(__DIR__).DIRECTORY_SEPARATOR.\'lang\'',GetTranslatableFile::$localization],
            $fileContents);
        return '<?php'."\n".$fileContentsReplace;
    }

    public static function Handle()
    {
        $fileContentsReplace=static::getValidationFile();
        static::writeToDisk($fileContentsReplace);
    }
}