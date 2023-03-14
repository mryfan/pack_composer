<?php

namespace Pack\services;

use Pack\system\PackYii;

class ComposerFile
{
    private static function shengChengPSR4Map($psr4DataArray)
    {
        $tmp=[];
        foreach ($psr4DataArray as $item) {
            $newNamespace=$item['newNamespace'];
            $newPath=str_replace('\\','/',$item['newPath']);

            if(empty($tmp[$newNamespace])){
                $tmp[$newNamespace]=$newPath;
            }else{
                if(is_string($tmp[$newNamespace])){
                    $yuanShiTmp=$tmp[$newNamespace];
                    unset($tmp[$newNamespace]);
                    $tmp[$newNamespace][]=$yuanShiTmp;
                    $tmp[$newNamespace][]=$newPath;
                }else{
                    if(!in_array($newPath,$tmp[$newNamespace])){
                        $tmp[$newNamespace][]=$newPath;
                    }
                }
            }
        }

        return $tmp;
    }

    public static function handle($psr4DataArray)
    {
        $psr4Array=static::shengChengPSR4Map($psr4DataArray);
        $tpmComposerJSON=file_get_contents(PackYii::getTPLPath());
        $tplComposerArray=json_decode($tpmComposerJSON,true);
        $tplComposerArray['autoload']['psr-4']=$psr4Array;

        $targetComposerPath=PackYii::$config['dist_dir'].DIRECTORY_SEPARATOR.'composer.json';

        file_put_contents($targetComposerPath,json_encode($tplComposerArray,256+64+JSON_PRETTY_PRINT));

    }
}