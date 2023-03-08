<?php

$basePath = dirname(__DIR__, 2);
return [
    'namespace' => 'Fy97Validation',//定义要生成包的命名空间前缀，建议以 （供应商+包名）  的方式,以避免冲突。如 ： Fy97Validation
    'composer_cli' => [
        'package_name' => 'illuminate/validation',//你想要打包的原始包名称，也就是以哪个包为基础
        'version' => '',//包的版本，一般都是以git上的tag为版本号，如果不填就是最新适配当前环境的包版本，也可以填写dev-main等参数，具体请看composer 命令的文档
    ],
    'common_exclude_file' => ['.', '..'], //默认排除的文件，建议不要更改
    'dist_dir' =>$basePath. DIRECTORY_SEPARATOR . 'dist',//定义最终生成的目录
    'tmp_dir' => $basePath. DIRECTORY_SEPARATOR . 'tmp',  //定义执行完命令后生成的临时目录
    'exclude_files_path' => [
    ],//暂时不需要
    'tpl_path' => $basePath . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'tpl',//模板的目录
    'provide_path' => 'provide',//自动生成的文件或者是代码目录名称，相对于dist_dir目录的位置
    'base_path' => $basePath,//基础目录
    'command_path' => $basePath.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'command',//基础目录
];
