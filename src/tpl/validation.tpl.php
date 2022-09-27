namespace {{namespace}}\Provide\Validation;

use {{namespace}}\Illuminate\Filesystem\Filesystem;
use {{namespace}}\Illuminate\Translation\FileLoader;
use {{namespace}}\Illuminate\Translation\Translator;
use {{namespace}}\Illuminate\Validation\Factory;

class Validation
{
    private static $uniqueClassName = [];

    private static function getFilesPathArray(string $dirPath)
    {
        $tmp = [];
        $currentFilesArray = scandir($dirPath);
        foreach ($currentFilesArray as $k => $v) {
            if (in_array($v, ['.', '..'])) {
                continue;
            }

            $item = $dirPath . DIRECTORY_SEPARATOR . $v;
            if (is_dir($item)) {
                $tmp = array_merge($tmp, static::getFilesPathArray($item));
            } else {
                $tmp[] = $item;
            }
        }
        return $tmp;
    }

    private static function getClassNameArray(string $dirPath)
    {
        if (empty($dirPath)) {
            return [];
        }

        if (!is_dir($dirPath)) {
            throw new \Exception('传递进入的 $dirPath 参数 不是目录');
        }
        $filesPathArray = static::getFilesPathArray($dirPath);

        $tmp = [];
        foreach ($filesPathArray as $k => $ruleFilePath) {
            //解析类名,区分有没有命名空间，如果要是有命名空间，需要保证被自动加载
            //实例化后的对象
            $contents = file_get_contents($ruleFilePath);
            $namespacePatternCounts = preg_match('/^namespace\s+(.*);/m', $contents, $namespaceMatches);

            $classNamePatternCounts = preg_match('/class\s+([\d|\w_]*)\s+/m', $contents, $classNameMatches);

            if ($classNamePatternCounts == 0) {
                throw new \Exception('没有匹配到当前文件：' . $ruleFilePath . '的类名');
            }

            $tmp[] = [
                'namespace_name' => empty($namespaceMatches[1]) ? '' : $namespaceMatches[1],
                'class_name' => empty($classNameMatches[1]) ? '' : $classNameMatches[1],
                'rule_file_path' => $ruleFilePath
            ];
        }

        return $tmp;
    }

    private static function getRuleObj($classMap)
    {
        //防止 规则类名冲突
        if (isset(static::$uniqueClassName[$classMap['class_name']])) {
            throw new \Exception('规则类存在着类名重复，请检查');
        } else {
            static::$uniqueClassName[$classMap['class_name']] = $classMap['class_name'];
        }


        if (empty($classMap['namespace_name'])) {
            require_once $classMap['rule_file_path'];
            return new $classMap['class_name'];
        }
        $classNameWithNamespace = $classMap['namespace_name'] . '\\' . $classMap['class_name'];
        return new $classNameWithNamespace;
    }

    private static function addExtend(Factory $factory, string $ruleFilePath)
    {
        $classMapArray = static::getClassNameArray($ruleFilePath);

        foreach ($classMapArray as $item) {
            $rulesObj = static::getRuleObj($item);
            $factory->replacer($item['class_name'], function ($message, $attribute, $rule, $parameters) use ($rulesObj) {
                return $rulesObj->message();
            });

            $factory->extend($item['class_name'], function ($attribute, $value, $parameters, $validator) use ($rulesObj) {
                return $rulesObj->passes($attribute, $value);
            });
        }
    }

    public static function getInstance(string $ruleFilePath = '')
    {
        static $factory = NULL;

        if ($factory === NULL) {
            $translationFileLoader = new FileLoader(new Filesystem, {{testTranslationPath}});
            $translator = new Translator($translationFileLoader, '{{testTranslationLocale}}');
            $factory = new Factory($translator);
            static::addExtend($factory, $ruleFilePath);
        }

        return $factory;

    }
}
