<?php

namespace Pack\services\AutoloadFilesParse;

class FilesHandle
{
    public static function moreToOneArray($array)
    {
        $tmp=[];
        foreach ($array as $k=>$v){
            foreach ($v as $kk=>$vv){
                $tmp=array_merge($tmp,$vv);
            }
        }
        return $tmp;
    }

    private static function generateNewFileByFilesArray($filesDataArray)
    {
        $filesInfoMap=[];
        foreach ($filesDataArray as $k=>$item){
            //读取当前文件有用的信息
            $fileUsefulInfo=ReadAllUsefulInfo::handle($item);
            //拼接要生成的文件内容，并生成副本文件
            $newFileUsefulInfo=ShengChengNewFiles::handle($fileUsefulInfo,$item);
            if(!empty($newFileUsefulInfo)){
                $filesInfoMap[]=[
                    'file_info'=>$item,
                    'file_useful_info'=>$fileUsefulInfo,
                ];
            }
        }
        return $filesInfoMap;
    }
    
    public static function handle($filesArray)
    {
        $filesDataArray=static::moreToOneArray($filesArray);
        //转换相关的文件， 变成静态类名形式,并生成相关的命名空间与路径的映射
        $filesInfoMap=static::generateNewFileByFilesArray($filesDataArray);
        //循环开始，处理调用此函数文件，并替换为新的调用的方式
        CalledThisFunctionHandle::handle($filesInfoMap);
    }
}