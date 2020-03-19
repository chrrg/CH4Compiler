# CH4Compiler
PHP写的 Win32 exe文件 编译器 Compiler  
真正的从机器码进行生成  
通过php语法进行exe的编译  
没有语法解析器，仅仅是通过机器码生成  
想要写编译器的可以学习  

# 使用方法
运行index.php即可得到new.exe

# 文件结构
--compiler.class.php  
编译器类  
--index.php  
调用compiler.class.php进行生成windows下可执行程序x86 EXE后缀的文件  

# 注释
代码加了很多注释，有命令行的输出字符的方法，Win API调用的方法等，理论可以编译生成win下所有类型的可执行文件

# 关于项目
本项目借鉴了VB制作的Visia编译器  
本项目遵循The MIT License  
