<?php
/**
 * Created by PhpStorm.
 * User: 杨帆
 * Date: 2021/1/25
 * Time: 13:56
 */

class constant
{
    public static $namespacePrefix = 'Fy';

    public static $oldNameSpaceAndNewRelation = [];

    public static $oldUniqueNameSpaceArray=[];
    public static $newUniqueNameSpaceArray=[];
}

class fileSystem
{
    public static $bathDirPath;

    public static $vendorPath = 'vendor';

    public static $autoloadArray;

    private static $cachePathAndComposerJsonInfo;

    public static function getAllStructInCurrentDir($path = '', $getOneLevel = false)
    {
        static::$bathDirPath = dirname(__FILE__).DIRECTORY_SEPARATOR;
        $path                = empty($path) ? static::$bathDirPath.static::$vendorPath : $path;
        $fileAndDirNameAll   = scandir($path);
        $fileAndDirNameArr   = [];

        $removeArray = ['.', '..', 'composer', 'autoload.php'];
        array_map(function ($item) use (&$fileAndDirNameArr, $removeArray, $path, $getOneLevel) {
            if (!in_array($item, $removeArray, true)) {
                //如果是只获取一级
                $fileName = $path.DIRECTORY_SEPARATOR.$item;
                if ($getOneLevel === true) {
                    $fileAndDirNameArr[] = $fileName;
                } else {
                    if (is_dir($fileName)) {
                        $fileAndDirNameArr = array_merge($fileAndDirNameArr,
                            static::getAllStructInCurrentDir($fileName));
                    } elseif (is_file($fileName)) {
                        $fileAndDirNameArr[] = $fileName;
                    } else {
                        throw new \Exception('当前$fileName类型无效:'.$fileName);
                    }

                }

            }
        }, $fileAndDirNameAll);
        if (empty($fileAndDirNameArr)) {
            throw new \Exception('当前目录'.$path.'下的结构为空');
        }

        return $fileAndDirNameArr;
    }

    public static function getTargetDirAndCreate($fileName, $newPath)
    {
        $fileNamePathInfo = pathinfo($fileName);
        $baseAfterPath    = str_replace(static::$bathDirPath.static::$vendorPath, '', $fileNamePathInfo['dirname']);

        $targetDirStr = static::$bathDirPath.$newPath.$baseAfterPath.DIRECTORY_SEPARATOR;

        if (!is_dir($targetDirStr)) {
            mkdir($targetDirStr, 0777, true);
        }
        if (!copy($fileName, $targetDirStr.$fileNamePathInfo['basename'])) {
            throw new \Exception('复制文件失败:'.$fileName);
        }

        return $targetDirStr.$fileNamePathInfo['basename'];
    }

    public static function isCustomFile($filePath, $ext)
    {
        if (isset(pathinfo($filePath)['extension']) && strtolower(pathinfo($filePath)['extension']) === $ext) {
            return true;
        }

        return false;
    }

    public static function parseComposerJsonFile($filePath, $newPath)
    {
        $filePathInfo = pathinfo($filePath);
        if (!empty(static::$cachePathAndComposerJsonInfo[$filePathInfo['dirname']])) {
            return;
        }


        if (is_file($composerJsonFile = $filePathInfo['dirname'].DIRECTORY_SEPARATOR.'composer.json')) {
            $composerData = json_decode(file_get_contents($composerJsonFile), true);
            if (empty($composerData) || !isset($composerData['autoload'])) {
                echo '当前composer文件没有autoload选项:'.$composerJsonFile.'   跳过  '.PHP_EOL;

                return;
            }
            //解析autoload    json串
            foreach ($composerData['autoload'] as $k => $v) {
                $path = str_replace([static::$bathDirPath.static::$vendorPath, '\\'], ['', '/'],
                    $filePathInfo['dirname']);
                if (!isset(static::$autoloadArray[$k])) {
                    static::$autoloadArray[$k] = [];
                }

                if ($k === 'psr-4') {
                    foreach ($v as $kk => $vv) {
                        static::$autoloadArray['psr-4'] = array_merge_recursive(static::$autoloadArray['psr-4'], [
                            constant::$namespacePrefix.'\\'.$kk => empty($vv)?$newPath.$path:$newPath.$path.'/'.$vv,
                        ]);

                        constant::$oldNameSpaceAndNewRelation['all'][] = [$kk => constant::$namespacePrefix.'\\'.$kk];
                        constant::$oldNameSpaceAndNewRelation['old'][] = rtrim($kk, '\\');
                        constant::$oldNameSpaceAndNewRelation['new'][] = rtrim(constant::$namespacePrefix.'\\'.$kk,
                            '\\');

                    }
                }
                if ($k === 'files') {
                    foreach ($v as $kk => $vv) {
                        static::$autoloadArray['files'] = array_merge(static::$autoloadArray['files'], [
                            $newPath.$path.'/'.$vv,
                        ]);
                    }
                }


            }
        }
        static::$cachePathAndComposerJsonInfo[$filePathInfo['dirname']] = 1;
    }

    public static function deleteDir($dir)
    {
        echo '正在删除目录：'.$dir.PHP_EOL;
        if(strpos(php_uname('s'),'Windows NT')!==false){
            echo '当前是windows环境'.PHP_EOL;
            $command='rd /s/q '.$dir;
        }else{
            $command='rm -rf '. $dir;
        }
        system($command);
    }

    public static function deleteFile($path)
    {
        if(is_file($path)){
            echo '正在删除:'.$path.PHP_EOL;
            unlink($path);
        }
    }

    public static function dirMove($newPath)
    {
        if(mkdir(fileSystem::$bathDirPath.'ext',0777)===false){
            throw new \Exception('创建基础的生成目录失败');
        }
        //移动composer.json
        if(rename(fileSystem::$bathDirPath.$newPath.DIRECTORY_SEPARATOR.'composer.json', fileSystem::$bathDirPath.'ext'.DIRECTORY_SEPARATOR.'composer.json')===false){
            throw new \Exception('移动composer.json文件失败');
        }

        if(static::move(fileSystem::$bathDirPath.$newPath, fileSystem::$bathDirPath.'ext'.DIRECTORY_SEPARATOR)===false){
            throw new \Exception('移动到ext目录失败');
        }
    }

    public static function move($source,$dest){
        $file = basename($source);
        $desct = $dest.DIRECTORY_SEPARATOR.$file;
        return rename($source,$desct);
    }
}

class base
{
    public static $composerJsonData='';
    private static function generateOldNameSpaceAndNewRelation($newPath)
    {
        $content = json_encode(constant::$oldNameSpaceAndNewRelation, 256 + 64 + JSON_PRETTY_PRINT);

        if (file_put_contents(fileSystem::$bathDirPath.$newPath.DIRECTORY_SEPARATOR.'oldNameSpaceAndNewRelation.json',
                $content) === false) {
            throw new \Exception('写入composer.json文件失败');
        }
    }

    private static function generateComposerJson($newPath)
    {
        $array   = [
            'name'=>'供应商名称/包名称',
            'description'=>'描述这个包',
            'keywords'=>[
                '关键词1',
                '关键词2',
                '关键词3',
            ],
            'authors'=>[
                [
                    'name'=>'Fy',
                    'email'=>'mryfan@163.com',
                ],
            ],
            'require'=>[
                'php'=>'^7.3',
            ],
            'license'=>'MIT',
            'autoload' => fileSystem::$autoloadArray,
        ];
        $content = json_encode($array, 256 + 64 + JSON_PRETTY_PRINT);

        if (file_put_contents(fileSystem::$bathDirPath.$newPath.DIRECTORY_SEPARATOR.'composer.json',
                $content) === false) {
            throw new \Exception('写入composer.json文件失败');
        }
    }

    private static function copy($newPath,$composerName)
    {
        $command = 'composer require '.$composerName;
        system($command,$returnVar);
        echo '执行结果'.$returnVar.PHP_EOL;

        echo '执行命令'.$command.'成功,正在进行复制处理操作'.PHP_EOL;

        $targetFilePath = [];

        $firstLevelStructArray = fileSystem::getAllStructInCurrentDir('', true);
        echo '获取所有的一级目录'.json_encode($firstLevelStructArray, 256 + 64).PHP_EOL;
        foreach ($firstLevelStructArray as $firstLocate => $firstLevel) {
            echo '开始处理第'.($firstLocate + 1).'个目录'.$firstLevel.PHP_EOL;
            $allFileArray = fileSystem::getAllStructInCurrentDir($firstLevel);
            if (empty($allFileArray)) {
                throw new \Exception('当前目录下的结构为空:'.json_encode($allFileArray, 256 + 64));
            }

            foreach ($allFileArray as $value) {
                //判断当前文件夹下有没有composer.json文件  并且解析出autoload字段

                fileSystem::parseComposerJsonFile($value, $newPath);

                $targetFilePath[] = fileSystem::getTargetDirAndCreate($value, $newPath);

            }
        }

        return $targetFilePath;
    }

    private static function replace($targetFilePath)
    {
        constant::$oldUniqueNameSpaceArray=$old = array_values(array_unique(constant::$oldNameSpaceAndNewRelation['old']));
        constant::$newUniqueNameSpaceArray=$new = array_values(array_unique(constant::$oldNameSpaceAndNewRelation['new']));
        foreach ($targetFilePath as $k => $path) {
            if (fileSystem::isCustomFile($path, 'php')) {
                //替换里面的命名空间
                $contents = file_get_contents($path);

                foreach ($old as $key => $value) {
                    $contents = str_replace($value, $new[$key], $contents);
                }

                if (file_put_contents($path, $contents) === false) {
                    throw new \Exception('写入文件失败:'.$path);
                }
            }
        }
    }

    private static function endHandle($newPath)
    {
//        fileSystem::deleteFile(fileSystem::$bathDirPath.'composer.json');
//        fileSystem::deleteFile(fileSystem::$bathDirPath.'composer.lock');
        fileSystem::dirMove($newPath);

        fileSystem::deleteDir(fileSystem::$bathDirPath.fileSystem::$vendorPath);

        $baseComposerJsonData=dirname(__FILE__).DIRECTORY_SEPARATOR.'composer.json';
        file_put_contents($baseComposerJsonData, static::$composerJsonData);


    }

    private static function preprocessing()
    {
        $baseComposerJsonData=dirname(__FILE__).DIRECTORY_SEPARATOR.'composer.json';
        static::$composerJsonData=file_get_contents($baseComposerJsonData);
    }

    public static function start($newPath = 'src',$composerName='illuminate/validation v8.25')
    {
        static::preprocessing();
        $targetFilePath = static::copy($newPath,$composerName);
        static::generateComposerJson($newPath);
        static::generateOldNameSpaceAndNewRelation($newPath);
        static::replace($targetFilePath);
        static::endHandle($newPath);
    }
}

