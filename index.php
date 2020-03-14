<h1>编译开始</h1>
<hr />
<?php
include "compiler.class.php";
$c=new compiler();
$c->performance(function($c){//性能监测
	$c->compile(function($c){//开始编译
		//编译源码：
		
		$c->addSymbol('BW',0,1);$c->dword(0,1);//添加一个变量名为BW 类型为dword
		$c->addSymbol('hConsoleOut',0,1);$c->dword(0,1);//添加一个变量名为hConsoleOut 类型为dword
		
		//定义sOut="" 长度为256;
		$c->addSymbol("sOut",0,1);
		$str="";//默认内容
		$l=strlen($str);
		for($i=0;$i<$l;$i++)$c->byte(ord($str[$i]),1);
		for($i=$l;$i<256;$i++)$c->byte(0,1);//填充256长度
		$c->byte(0,1);//字符串结束
		//定义结束
		
///字符串赋值开始sOut="hahahahahahaa"
		$s=$c->unique("string");//获取唯一字符串名
		$c->addSymbol($s,0,1);//type=0 id=data
		$str=iconv("UTF-8", "gb2312" , "哈哈哈输出成功！\n换行成功\n缩进\t成功");//设置内容
		$l=strlen($str);
		for($i=0;$i<$l;$i++)$c->byte(ord($str[$i]),1);//将字符串存入data
		$c->byte(0,1);//data写入0
		$c->byte(0,1);//data写入0
		
		////////////////////////字符串复制开始
			$c->byte(0x68,2);/*AddRelocation OffsetOf(".code");*/$c->addFix($s,1,2,0x400000);//push
			$c->byte(0x68,2);$c->addFix("sOut",1,2,0x400000);//push
			$c->word(0x15FF,2);$c->addFix("lstrcpy",1,2,0x400000);//invoke lstrcpy 字符串复制 
			//$c->word(0x35FF,2);$c->addFix("sOut",1,2,0x400000);//PushContent这行加不加好像没区别
		////////////////////////字符串复制结束
///字符串赋值结束
		
		$c->word(0x15FF,2);$c->addFix("AllocConsole",1,2,0x400000);//invoke AllocConsole 初始化命令行环境
		
		//hConsoleOut = GetStdHandle(STD_OUTPUT_HANDLE);开始
		$c->byte(0x68,2);$c->dword(-11,2);//push -11 STD_OUTPUT_HANDLE=-11
		$c->word(0x15FF,2);$c->addFix("GetStdHandle",1,2,0x400000);//调用函数 GetStdHandle(STD_OUTPUT_HANDLE)
		$c->byte(0x50,2);//PushEAX 获取返回值
		$c->byte(0xA3,2);//赋值给：
		$c->addFix("hConsoleOut",1,2,0x400000);//hConsoleOut变量
		//hConsoleOut = GetStdHandle(STD_OUTPUT_HANDLE);结束
		
		
		$c->byte(0x68,2);$c->dword(0,2);//push 0 第一个参数
		$c->byte(0xA1,2);$c->addFix("BW",1,2,0x400000);$c->byte(0x50,2);//pusheax BW  第二个参数 BW变量 变量类型是DWORD
		////////第三个参数开始
		$c->byte(0x68,2);/*AddRelocation OffsetOf(".code")*/$c->addFix("sOut",1,2,0x400000);
		$c->word(0x15FF,2);$c->addFix("lstrlen",1,2,0x400000);//invoke lstrlen
		$c->byte(0x50,2);//PushEAX 获取函数返回值
		////////第三个参数结束
		//$c->addString(iconv("UTF-8", "gb2312" , "哈哈123"));//第四个参数
		$c->byte(0x68,2);/*AddRelocation OffsetOf(".code")*/$c->addFix("sOut",1,2,0x400000);//第四个参数 sOut变量类型是string
		
		$c->byte(0xA1,2);$c->addFix("hConsoleOut",1,2,0x400000);$c->byte(0x50,2);//pusheax BW  第五个参数 变量类型是DWORD
		$c->word(0x15FF,2);$c->addFix("WriteConsole",1,2,0x400000);//调用函数 WriteConsole //WriteConsole(hConsoleOut,sOut,lstrlen(sOut),BW,0);输出sOut内容到命令行窗口
		
//对话框开始
		$c->byte(0x68,2);$c->dword(32,2);//push 0x20 第一个参数
		$c->addString(iconv("UTF-8", "gb2312" , "标题1.."));//第二个参数 对话框 标题
		$c->addString(iconv("UTF-8", "gb2312" , "哈\t哈\n123"));//第三个参数 对话框 内容
		$c->byte(0x68,2);$c->dword(0,2);//push 0//第四个参数 0
		$c->word(0x15FF,2);$c->addFix("MessageBox",1,2,0x400000);//invoke调用函数 MessageBox
//对话框结束
		//$c->byte(0x50,2);$c->byte(0xA3,2);$c->addFix("BW",1,2,0x400000);////PushEAX将函数返回值赋值给BW变量
//对话框开始
		$c->byte(0x68,2);$c->dword(32,2);//push 0x20 第一个参数
		$c->addString(iconv("UTF-8", "gb2312" , "标题2.."));//第二个参数 对话框 标题
		$c->addString(iconv("UTF-8", "gb2312" , "哈哈123456"));//第三个参数 对话框 内容
		$c->byte(0x68,2);$c->dword(0,2);//push 0//第四个参数 0
		$c->word(0x15FF,2);$c->addFix("MessageBox",1,2,0x400000);//invoke MessageBox
//对话框结束
		
	});//源码
	$c->outputFile();
});
//echo '<pre>';$c->output(0);echo '</pre>';//输出0区块代码
?>
<hr />
<h1>编译结束</h1>