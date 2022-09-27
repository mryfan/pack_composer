#概述

    这是一个打包第三方composer开源包的工具，
    目的是为了避免composer内冲突


本工具需要用到 composer  等 请自行安装


#应用场景

```
当你知道一个优秀的composer包，并且要使用安装的时候，
发现这个包依赖着一个  B 包 的8.5版本，
但是你的项目又依赖4.5版本，你不敢贸然升级B包，
因为有可能不稳定。

所以有了这个小工具，暂时的使用场景仅仅用在了 illuminate/validation 这个包里面 
```



#使用方法
    

```
1.git clone https://github.com/mryfan/pack_composer.git
2.php ./index.php
3.cd dist
4.git init 
5.git add .
6.git commit -m '创世提交'
7.git remote add github https://github.com/mryfan/pack_composer.git //注意换成你的仓库地址并与packagist.org 联动。
8.找个新的目录 执行  composer require 你的包名称
