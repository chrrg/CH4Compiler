# CH4Compiler
PHP写的 Win32 exe文件 编译器 Compiler  
真正的从机器码进行生成  
通过php语法进行exe的编译  
没有语法解析器等，仅仅是通过PHP写的代码进行生成  
生成的EXE文件通常只有2KB  
想要写编译器的可以学习  

# 使用方法
运行index.php即可得到new.exe

# 文件结构
## compiler.class.php  
编译器类  
## index.php  
调用compiler.class.php进行生成windows下可执行程序x86 EXE后缀的文件  

# 注释
代码加了很多注释，有命令行的输出字符的方法，Win API调用的方法等，理论可以编译生成win下所有类型的可执行文件

# 输出一个Hello World的例子：
```PHP
<?php
include "compiler.class.php";

//
$c=new compiler();

$c->performance(function(&$c){//性能监测
	$c->compile(function(&$c){//开始编译
		
		$c->addSymbol("a",1,1);
		$c->addSymbol("b",1,1);
		$c->addSymbol("c",1,1);
		$c->word(0x15FF,2);$c->addFix("AllocConsole",1,2,0x400000);//invoke调用函数 
		$c->word(0x15FF,2);$c->addFix("GetStdHandle",1,2,0x400000);//invoke调用函数 

		$c->byte(0xA3,2);$c->addFix("a",1,2,0x400000);//mov [a],eax

		$c->byte(0x68,2);$c->dword(0,2);//push 0x20 第一个参数
		$c->byte(0x68,2);$c->addFix("c",1,2,0x400000);//push 0x20 第一个参数
		$c->byte(0x68,2);$c->dword(9,2);//push 0x20 第一个参数
		$c->addString("Hello World 你好 世界！\0");//这里是Hello，World 支持中文
		$c->byte(0xA1,2);$c->addFix("a",1,2,0x400000);
		$c->byte(0x50,2);
		$c->word(0x15FF,2);$c->addFix("WriteConsole",1,2,0x400000);//invoke调用函数 
    
    //下面是为了弹出一个messagebox对话框
		$c->byte(0x68,2);$c->dword(0,2);//push 0x20 第一个参数
		$c->addString("结束结束结束结束结束结束");//第二个参数 对话框 标题
		$c->addString("结束");//第三个参数 对话框 内容
		$c->byte(0x68,2);$c->dword(0,2);//push 0//第四个参数 0
		$c->word(0x15FF,2);$c->addFix("MessageBox",1,2,0x400000);//invoke调用函数 
    
		//exit start
		$c->byte(0x68,2);$c->dword(0,2);//push value
		$c->word(0x15FF,2);$c->addFix("ExitProcess",1,2,0x400000);//invoke
		//exit end
    
	});
	$c->outputFile("new.exe");//将编译好的字节数组转成文件
});
```

# 关于项目
本项目借鉴了VB制作的Visia编译器  
本项目遵循The MIT License  
