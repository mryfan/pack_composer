
#概述

    这是一个打包第三方composer开源包的工具，
    目的是为了避免composer内冲突


本工具需要用到 composer  等 请自行安装


#应用场景

    当你知道一个优秀的composer包，并且要使用安装的时候，
    发现这个包依赖着一个  B 包 的8.5版本，
    但是你的项目又依赖4.5版本，你不敢贸然升级B包，
    因为有可能不稳定。

    为了不留遗憾，所以我此时开发了这个小工具，
    可以帮大家解决这个问题。

#原理

    将你发现的优秀的composer包及其所有依赖全部打包成一个composer包，
    并且再发布，这样你就可以完整的使用这个优秀的composer包了
    
#使用方法
    
    参考example.php 以及validation.php 的样例
    
    输入生成的目录，以及你想要的打包的composer 包名称
    
    (1).以validation.php为例，直接控制台输入 php validation.php
    (2).运行之后会生成ext目录，该目录下就是你打包好的composer包的所有文件了
    (3).修改里面的composer.json 的信息，提交到https://packagist.org/
     网站上。 接下来享受你的成果吧
    
