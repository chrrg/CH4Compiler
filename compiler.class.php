<?php
class compiler{
	public $sec=[];
	public $sys=[];//symbol
	public $fix=[];
	public $uniqid=[];//uniqid
	public $IsDLL=false;
	public $SizeOfHeader;
	public $SizeOfAllSectionsBeforeRaw;
	public $AppType=3;//SubSystem = 2:GUI; 3:CUI
	public $func;
	protected function init(){//初始化
		//初始化sec变量
		$this->sec=[];//初始化sec
		$this->sys=[];//标记
		$this->uniqid=[];
		$this->initSection("codebyte");//源代码
		$this->initSection(".data");
		$this->initSection(".code");
		$this->initSection(".idata");
		$this->initSection(".edata");
		$this->initSection(".rsrc");
		$this->initSection(".reloc");
		//var_dump($this->sec);//输出sec
		//var_dump($this->sys);//输出sys
		/*
		.data
		.code
		.idata
		.edata
		.rsrc
		.reloc
		*/
		$this->DOSHeader();
		$this->OutputDOSStub();
		$this->parse();
		
		$this->GenerateImportTable();
		$this->PEHeader();
		$this->sectionHeader();
		//$this->sections();
		$this->body();
		
		$this->fix();
	}
	public function NumberOfSections(){
		$count=0;
		for($i=1;$i<count($this->sec);$i++)
			if(count($this->sec[$i]["byte"])>0)$count++;
		return $count;
	}
	public function PEHeader(){//输出PE头
		//输出这里的时候section应该已经生成了
    $this->dword(0x4550);                     //Signature = "PE"
    $this->word(0x14C);                       //Machine 0x014C;i386
    $this->word($this->NumberOfSections());            //NumberOfSections = 4
    $this->dword(0x0);                        //TimeDateStamp
    $this->dword(0x0);                        //PointerToSymbolTable = 0
    $this->dword(0x0);                        //NumberOfSymbols = 0
    $this->word(0xE0);                        //SizeOfOptionalHeader
    if($this->IsDLL)
      $this->word(0x210E);                      //Characteristics
    else
      $this->word(0x818F);                      //Characteristics
    
    $this->word(0x10B);                       //Magic
    $this->byte(0x5);                         //MajorLinkerVersion
    $this->byte(0x0);                         //MinerLinkerVersion
    $this->addSymbol("SizeOfCode",1);          //SizeOfCode
    $this->addSymbol("SizeOfInitializedData",1);    //SizeOfInitializedData
    $this->addSymbol("SizeOfUnInitializedData",1);  //SizeOfUnInitializedData
    $this->addSymbol("AddressOfEntryPoint",1); //AddressOfEntryPoint
    $this->addSymbol("BaseOfCode",1);          //BaseOfCode
    $this->addSymbol("BaseOfData",1);          //BaseOfData
    $this->dword(0x400000);                   //ImageBase
    $this->dword(0x1000);                     //SectionAlignment
    $this->dword(0x200);                      //FileAlignment
    $this->word(0x1);                         //MajorOSVersion
    $this->word(0x0);                         //MinorOSVersion
    $this->word(0x0);                         //MajorImageVersion
    $this->word(0x0);                         //MinorImageVersion
    $this->word(0x4);                         //MajorSubSystemVerion
    $this->word(0x0);                         //MinorSubSystemVerion
    $this->dword(0x0);                        //Win32VersionValue
    $this->addSymbol("SizeOfImage",1);         //SizeOfImage
    $this->addSymbol("SizeOfHeaders",1);       //SizeOfHeaders
    $this->dword(0x0);                        //CheckSum
    $this->word($this->AppType);//AppType               //SubSystem = 2:GUI; 3:CUI
    $this->word(0x0);                         //DllCharacteristics
    $this->dword(0x10000);                    //SizeOfStackReserve
    $this->dword(0x10000);                    //SizeOfStackCommit
    $this->dword(0x10000);                    //SizeOfHeapReserve
    $this->dword(0x0);                        //SizeOfHeapRCommit
    $this->dword(0x0);                        //LoaderFlags
    $this->dword(0x10);                       //NumberOfDataDirectories
    
    $this->addSymbol("ExportTable.Entry",1);
    $this->addSymbol("ExportTable.Size",1);
    
    $this->addSymbol("ImportTable.Entry",1);
    $this->addSymbol("ImportTable.Size",1);
    
    $this->addSymbol("ResourceTable.Entry",1);
    $this->addSymbol("ResourceTable.Size",1);
   
    $this->dword(0x0); $this->dword(0x0);      //Exception_Table
    $this->dword(0x0); $this->dword(0x0);      //Certificate_Table
		//$this->addSymbol("ExceptionTable.Entry",1);
    //$this->addSymbol("ExceptionTable.Size",1);
    //$this->addSymbol("CertificateTable.Entry",1);
    //$this->addSymbol("CertificateTable.Size",1);
		
    $this->addSymbol("RelocationTable.Entry",1);
    $this->addSymbol("RelocationTable.Size",1);
    
    $this->dword(0x0); $this->dword(0x0);      //Debug_Data
    $this->dword(0x0); $this->dword(0x0);      //Architecture
    $this->dword(0x0); $this->dword(0x0);      //Global_PTR
    $this->dword(0x0); $this->dword(0x0);      //TLS_Table
    $this->dword(0x0); $this->dword(0x0);      //Load_Config_Table
    $this->dword(0x0); $this->dword(0x0);      //BoundImportTable
    $this->dword(0x0); $this->dword(0x0);      //ImportAddressTable
    $this->dword(0x0); $this->dword(0x0);      //DelayImportDescriptor
    $this->dword(0x0); $this->dword(0x0);      //COMplusRuntimeHeader
    $this->dword(0x0); $this->dword(0x0);      //Reserved
	}
	public function addSymbol($name,$type=0,$id=0,$data=0){//增加一个标记
		$this->sys[]=[
			"name"=>$name,
			"id"=>$id,
			"offset"=>count($this->sec[$id]["byte"]),
			"type"=>$type,
			"data"=>$data
		];
		if($type===1)//对id写入Dword值为data
			$this->dword($data,$id);
	}
	public function addFix($name,$type=0,$id=0,$data=0){//增加一个标记
		$this->fix[]=[
			"name"=>$name,
			"id"=>$id,
			"offset"=>count($this->sec[$id]["byte"]),
			"type"=>$type,
			"data"=>$data
		];
		if($type===1)//对id写入Dword值为data
			$this->dword($data,$id);
	}
	public function fixOffset($id,$i,$value){
		$this->sec[$id]["byte"][$i]=$value & 0x000000FF;
		$this->sec[$id]["byte"][$i+1]=($value & 0x0000FF00)>>8;
		$this->sec[$id]["byte"][$i+2]=($value & 0x00FF0000)>>16;
		$this->sec[$id]["byte"][$i+3]=($value & 0xFF000000)>>24;
	}
	public function fixSymbol($name,$value){
		foreach($this->sys as $v){
			if($v["name"]==$name){
				
				$i=$v["id"];
				$o=$v["offset"];
				//var_dump("fix".$o."|".$name."|".$value);
				$this->sec[$i]["byte"][$o]=$value & 0x000000FF;
				$this->sec[$i]["byte"][$o+1]=($value & 0x0000FF00)>>8;
				$this->sec[$i]["byte"][$o+2]=($value & 0x00FF0000)>>16;
				$this->sec[$i]["byte"][$o+3]=($value & 0xFF000000)>>24;
				return;
			}
		}
		die("Symbol:".$name." is not found!");
	}
	public function initSection($name){
		$this->sec[]=[
			"index"=>count($this->sec),
			"byte"=>[],//字节
			"name"=>$name,
		];
	}
	public function byte($i,$id=0){//字节 1个字节
		$this->sec[$id]["byte"][]=$i & 0xFF;
	}
	public function word($i,$id=0){//字 2个字节
		$this->sec[$id]["byte"][]=$i & 0x00FF;
		$this->sec[$id]["byte"][]=($i & 0xFF00)>>8;
	}
	public function dword($i,$id=0){//双字 4个字节
		$this->sec[$id]["byte"][]= $i & 0x000000FF;
		$this->sec[$id]["byte"][]=($i & 0x0000FF00)>>8;
		$this->sec[$id]["byte"][]=($i & 0x00FF0000)>>16;
		$this->sec[$id]["byte"][]=($i & 0xFF000000)>>24;
	}
	public function DOSHeader(){
		$this->dword(0x805A4D);
    $this->dword(0x1);
    $this->dword(0x100004);
    $this->dword(0xFFFF);//这里$this->dword(0xFFFF);
    $this->dword(0x140);
    $this->dword(0x0);
    $this->dword(0x40);
    $this->dword(0x0);
    $this->dword(0x0);
    $this->dword(0x0);
    $this->dword(0x0);
    $this->dword(0x0);
    $this->dword(0x0);
    $this->dword(0x0);
    $this->dword(0x0);
    $this->dword(0x80);
	}
	public function OutputDOSStub(){
		$this->dword(0xEBA1F0E);
    $this->dword(0xCD09B400);
    $this->dword(0x4C01B821);
    $this->dword(0x687421CD);
    $this->dword(0x70207369);
    $this->dword(0x72676F72);
    $this->dword(0x63206D61);
    $this->dword(0x6F6E6E61);
    $this->dword(0x65622074);
    $this->dword(0x6E757220);
    $this->dword(0x206E6920);
    $this->dword(0x20534F44);
    $this->dword(0x65646F6D);
    $this->dword(0x240A0D2E);
    $this->dword(0x0);
    $this->dword(0x0);
	}
	public static function fix512($code){//对齐512
		$size = count($code);
		if($size%512!=0){//需要对齐
			$size=512*(intval($size/512)+1)-$size;//需要对齐多少
			for($i=$size;$i>0;$i--)$code[]=0;//ord(".");
		}
		return $code;
	}
	public static function tryFix($size,$length=512){//对齐512
		if($size%$length!=0){//需要对齐
			$size=$length*(intval($size/$length)+1);//需要对齐多少
			//for($i=$size;$i>0;$i--)$code[]=ord("_");
		}
		return $size;
	}
	public function unique($str){
		if(!isset($this->uniqid[$str]))$this->uniqid[$str]=0;
		return $str.".".($this->uniqid[$str]++);
	}
	public function addString($str){
		$l=strlen($str);
		$s=$this->unique("string");
		$this->addSymbol($s,0,1);//type=0 id=data
		for($i=0;$i<$l;$i++)
			$this->byte(ord($str[$i]),1);//将字符串存入data
		$this->byte(0,1);//data写入0
		$this->byte(0,1);//data写入0
		$this->byte(0x68,2);//push;//PushAddress
		$this->addFix($s,0,2,0x400000);//fix.string.0
		$this->dword(0,2);//需要被修改，写入code
		return $s;
	}
	
	public function compile($func){
		$this->func=$func;
		$this->init();
	}
	public function output($id=0){
		//print(self::toStr($this->sec[0]->byte));
		//print("<hr />\n\n\n\n");
		foreach($this->sec[$id]["byte"] as $ch)
			print(chr($ch));
		//var_dump($this->code);
	}
	public function outputFile($file){
		if(is_file($file)&&!is_writable($file)){
			print("文件写入被拒绝！");
			return false;
		}
		$file = @fopen($file,"w+");
		if(!$file)return false;
		foreach($this->sec[0]["byte"] as $c=>$v)
			fwrite($file,pack("C",$v));
		fclose($file);
		return true;
	}
	function performance($func){
		$start = microtime(true);
		define('DD_MEMORY_LIMIT_ON',function_exists('memory_get_usage'));// 记录内存初始使用
		if(DD_MEMORY_LIMIT_ON) $GLOBALS['_startUseMems'] = memory_get_usage();
		$func($this);
		$end = microtime(true);
		$use_time = (number_format($end-$start, 8))*1000;
		echo "\n<h2>耗时：<span style='color:red;'>".$use_time."ms</span></h2>";
		echo "\n内存：";
		echo DD_MEMORY_LIMIT_ON ? number_format((memory_get_usage() - $GLOBALS['_startUseMems'])/1024,2).' KB':'不支持';
		echo "\n内存峰值：".number_format(memory_get_peak_usage()/1024,2).' KB';
	}
	public function parse(){//解析
		/*
		.data  1
		.code  2
		.idata 3
		.edata 4
		.rsrc  5
		.reloc 6
		*/
		$this->addSymbol('Instance',1,1);
		$this->addSymbol('$Intern.Property',1,1);
		$this->addSymbol('$Intern.Compare.One',1,1);
		$this->addSymbol('$Intern.Compare.Two',1,1);
		$this->addSymbol('$Intern.Float',1,1);
		$this->addSymbol('$Intern.Array',1,1);
		$this->addSymbol('$Intern.Loop',1,1);
		$this->addSymbol('$Intern.Count',1,1);
		$this->addSymbol('$Intern.Return',1,1);
		
		
		$this->word(0x6A,2);
		
		$this->word(0x15FF,2);//invoke
		$this->addFix("GetModuleHandle",1,2,0x400000);
		$this->byte(0xA3,2);
		$this->addFix("Instance",1,2,0x400000);
		
		$this->byte(0xE8,2);//ExprCall "$entry"
		$this->addFix('$entry',0,2);//linkerfix修复方式2
		$this->dword(0xFFFFFFFF,2);
		//--------------------------------------------
		$this->addSymbol('$entry',2,2);
		
		////--------------------------------------------主程序入口开始主程序入口开始主程序入口开始主程序入口开始主程序入口开始主程序入口开始主程序入口开始
		////--------------------------------------------主程序入口开始主程序入口开始主程序入口开始主程序入口开始主程序入口开始主程序入口开始主程序入口开始
		////--------------------------------------------主程序入口开始主程序入口开始主程序入口开始主程序入口开始主程序入口开始主程序入口开始主程序入口开始
		////--------------------------------------------主程序入口开始主程序入口开始主程序入口开始主程序入口开始主程序入口开始主程序入口开始主程序入口开始
		////--------------------------------------------主程序入口开始主程序入口开始主程序入口开始主程序入口开始主程序入口开始主程序入口开始主程序入口开始
		////--------------------------------------------主程序入口开始主程序入口开始主程序入口开始主程序入口开始主程序入口开始主程序入口开始主程序入口开始
		////--------------------------------------------主程序入口开始主程序入口开始主程序入口开始主程序入口开始主程序入口开始主程序入口开始主程序入口开始
		////--------------------------------------------主程序入口开始主程序入口开始主程序入口开始主程序入口开始主程序入口开始主程序入口开始主程序入口开始
		
		$func=$this->func;
		//var_dump($func);
		$func($this);
		
		////--------------------------------------------主程序入口结束主程序入口结束主程序入口结束主程序入口结束主程序入口结束主程序入口结束
		////--------------------------------------------主程序入口结束主程序入口结束主程序入口结束主程序入口结束主程序入口结束主程序入口结束
		////--------------------------------------------主程序入口结束主程序入口结束主程序入口结束主程序入口结束主程序入口结束主程序入口结束
		////--------------------------------------------主程序入口结束主程序入口结束主程序入口结束主程序入口结束主程序入口结束主程序入口结束
		////--------------------------------------------主程序入口结束主程序入口结束主程序入口结束主程序入口结束主程序入口结束主程序入口结束
		////--------------------------------------------主程序入口结束主程序入口结束主程序入口结束主程序入口结束主程序入口结束主程序入口结束
		////--------------------------------------------主程序入口结束主程序入口结束主程序入口结束主程序入口结束主程序入口结束主程序入口结束
		////--------------------------------------------主程序入口结束主程序入口结束主程序入口结束主程序入口结束主程序入口结束主程序入口结束
		
		$this->byte(0x68,2);//push;
		$this->dword(0,2);//push value
			
			
		$this->word(0x15FF,2);//invoke
		$this->addFix("ExitProcess",1,2,0x400000);//add dword and wait fix
		
		
		
		//var_dump("parse");
	}
	
	public function addLibrary($name){
		$this->dword(0,3);//
		$this->dword(0,3);//
		$this->dword(0,3);//
		$this->addFix($name."_NAME",0,3);//AddFixup Libraries(i) & "_NAME", OffsetOf(".idata"), Import
		$this->dword(0,3);//
		$this->addFix($name."_TABLE",0,3);//AddFixup Libraries(i) & "_TABLE", OffsetOf(".idata"), Import
		$this->dword(0,3);//
	}
	public function GenerateImportTable(){
		//导入表 循环开始
		$Libraries=["KERNEL32.DLL","USER32.DLL"];
		$import=[];
		$import[]=[
			"name"=>"ExitProcess",
			"alias"=>"ExitProcess",
			"lib"=>"KERNEL32.DLL",
		];
		$import[]=[
			"name"=>"GetModuleHandleA",
			"alias"=>"GetModuleHandle",
			"lib"=>"KERNEL32.DLL",
		];
		$import[]=[
			"name"=>"AllocConsole",
			"alias"=>"AllocConsole",
			"lib"=>"KERNEL32.DLL",
		];
		$import[]=[
			"name"=>"GetStdHandle",
			"alias"=>"GetStdHandle",
			"lib"=>"KERNEL32.DLL",
		];
		$import[]=[
			"name"=>"lstrlenA",
			"alias"=>"lstrlen",
			"lib"=>"KERNEL32.DLL",
		];
		$import[]=[
			"name"=>"WriteConsoleA",
			"alias"=>"WriteConsole",
			"lib"=>"KERNEL32.DLL",
		];
		$import[]=[
			"name"=>"lstrcpyA",
			"alias"=>"lstrcpy",
			"lib"=>"KERNEL32.DLL",
		];
		$import[]=[
			"name"=>"MessageBoxA",
			"alias"=>"MessageBox",
			"lib"=>"USER32.DLL",
		];

		
		
		foreach($Libraries as $i){
			$this->addLibrary($i);
		}
		
		//导入表 循环结束
		
		if(count($Libraries)>0){
			//空白开始
			$this->dword(0,3);//
			$this->dword(0,3);//
			$this->dword(0,3);//
			$this->dword(0,3);//
			$this->dword(0,3);//
			//空白结束
		}
		//var_dump($this->sec[3]["byte"]);
		foreach($Libraries as $i){
			$this->addSymbol($i."_TABLE",0,3);
			foreach($import as $i2){
				if($i==$i2["lib"]){
					$this->addSymbol($i2["alias"],0,3);
					$this->addFix($i2["name"]."_ENTRY",0,3);
					$this->dword(0,3);
				}
			}
			$this->dword(0,3);
		}
		foreach($Libraries as $i){
			$this->addSymbol($i."_NAME",0,3);
			$l=strlen($i);
			for($ii=0;$ii<$l;$ii++)
				$this->byte(ord($i[$ii]),3);
			$this->byte(0,3);
		}
		foreach($import as $i){
			$this->addSymbol($i["name"]."_ENTRY",0,3);
			$this->word(0,3);
			$l=strlen($i["name"]);
			for($ii=0;$ii<$l;$ii++)
				$this->byte(ord($i["name"][$ii]),3);
			$this->byte(0,3);
		}
	}
	public function sectionHeader(){//区段头
		
		
		
		define("CH_CODE",0x20);
    define("CH_INITIALIZED_DATA", 0x40);
    define("CH_UNINITIALIZED_DATA", 0x80);
    define("CH_MEM_DISCARDABLE", 0x2000000);
    define("CH_MEM_NOT_CHACHED", 0x4000000);
    define("CH_MEM_NOT_PAGED", 0x8000000);
    define("CH_MEM_SHARED", 0x10000000);
    define("CH_MEM_EXECUTE", 0x20000000);
    define("CH_MEM_READ", 0x40000000);
    define("CH_MEM_WRITE", 0x80000000);
		if(count($this->sec[1]["byte"])>0)$this->createSection(".data",CH_INITIALIZED_DATA + CH_MEM_READ + CH_MEM_WRITE);
		if(count($this->sec[2]["byte"])>0)$this->createSection(".code",CH_CODE + CH_MEM_READ + CH_MEM_EXECUTE);
		if(count($this->sec[3]["byte"])>0)$this->createSection(".idata", CH_INITIALIZED_DATA + CH_MEM_READ + CH_MEM_WRITE);
		if(count($this->sec[4]["byte"])>0)$this->createSection(".edata", CH_INITIALIZED_DATA + CH_MEM_READ);
		if(count($this->sec[5]["byte"])>0)$this->createSection(".rsrc", CH_INITIALIZED_DATA + CH_MEM_READ);
		if(count($this->sec[6]["byte"])>0)$this->createSection(".reloc", CH_MEM_DISCARDABLE + CH_INITIALIZED_DATA);
		//var_dump(count($this->sec[0]["byte"]));
		$this->sec[0]["byte"]=self::fix512($this->sec[0]["byte"]);
		$this->SizeOfHeader = count($this->sec[0]["byte"]);
		$this->fixSymbol("SizeOfHeaders",$this->SizeOfHeader);
		$this->SizeOfAllSectionsBeforeRaw=$this->SizeOfHeader;
		
		
		
	}
	/*public function sections(){//区段头
		
		for($i=0;$i<=6;$i++){
			if(count($this->sec[$i]["byte"])==0)continue;//没有就跳过
			FixAttribute Section(i).Name & ".VirtualSize", UBound(Section(i).Bytes)
		}
		
		
		
	}*/
	public function createSection($name,$Characteristics){
		$l=strlen($name);
		for($i=0;$i<$l;$i++)
			$this->byte(ord($name[$i]));
		for($i=$l;$i<8;$i++)
			$this->byte(0);
		$this->addSymbol($name.".VirtualSize",1);
		$this->addSymbol($name.".VirtualAddress",1);
		$this->addSymbol($name.".SizeOfRawData",1);
		$this->addSymbol($name.".PointerToRawData",1);
		$this->addSymbol($name.".PointerToRelocations",1);
		$this->dword(0x0);                        //PointerToLinenumbers
    $this->word(0x0);                         //NumberOfRelocations
    $this->word(0x0);                         //NumberOfLinenumbers
    $this->dword($Characteristics);
	}
	public function PhysicalSizeOf($num,$sub=0){
		if($num==0)return 0;
		$result=0;
		for($i=0;$i<=$num+512-$sub;$i+=512)
			$result=$i;
		return $result;
	}
	public function VirtualSizeOf($num,$sub=0){
		if($num==0)return 0;
		for($i=4096;$i<=268431360;$i+=4096)
			if($i>$num-$sub)return $i;
		return $i;
	}
	public function body(){
		$SizeOfAllSectionsBefore=0x1000;
		for($i=1;$i<=6;$i++){//1 2 3 4 5 6
			if(count($this->sec[$i]["byte"])==0)continue;//没有就跳过
			$this->fixSymbol($this->sec[$i]["name"].".VirtualSize",count($this->sec[$i]["byte"]));
			
			if($i==3){
				$this->fixSymbol("ImportTable.Size", count($this->sec[$i]["byte"]));
			}else if($i==4){
				$this->fixSymbol("ExportTable.Size", count($this->sec[$i]["byte"]));
			}else if($i==5){
				$this->fixSymbol("ResourceTable.Size", count($this->sec[$i]["byte"]));
			}else if($i==6){
				$this->fixSymbol("RelocationTable.Size", count($this->sec[$i]["byte"]));
			}
			
			//FixTableSize i, UBound(Section(i).Bytes)
      $PhysicalSize = $this->PhysicalSizeOf(count($this->sec[$i]["byte"]));
			$this->sec[$i]["byte"]=self::fix512($this->sec[$i]["byte"]);
			
			if($i==2){
				$this->fixSymbol("AddressOfEntryPoint", $SizeOfAllSectionsBefore);
			}else if($i==3){
				$this->fixSymbol("ImportTable.Entry", $SizeOfAllSectionsBefore);
			}else if($i==4){
				$this->fixSymbol("ExportTable.Entry", $SizeOfAllSectionsBefore);
			}else if($i==5){
				$this->fixSymbol("ResourceTable.Entry", $SizeOfAllSectionsBefore);
			}else if($i==7){
				$this->fixSymbol("RelocationTable.Entry", $SizeOfAllSectionsBefore);
			}
			if($i==6)$this->fixSymbol(".code.PointerToRelocations", $SizeOfAllSectionsBefore);
			
			$this->fixSymbol($this->sec[$i]["name"].".VirtualAddress", $SizeOfAllSectionsBefore);
			$SizeOfAllSectionsBefore += $this->VirtualSizeOf(count($this->sec[$i]["byte"]));
			$this->fixSymbol($this->sec[$i]["name"].".PointerToRawData", $this->SizeOfAllSectionsBeforeRaw);
			$this->SizeOfAllSectionsBeforeRaw += $PhysicalSize;
      $this->fixSymbol($this->sec[$i]["name"].".SizeOfRawData", $PhysicalSize);
				
			foreach($this->sec[$i]["byte"] as $c)
				$this->sec[0]["byte"][]=$c;
			
			if($PhysicalSize%4096==0)$SizeOfAllSectionsBefore-=4096;
			/*for($ii=0;$ii<=268431360;$ii+=4096){
				if($PhysicalSize==$ii)$SizeOfAllSectionsBefore-=4096;
			}
			For ii = 0 To &H1000& * &HFFFF& Step &H1000
				If PhysicalSize = ii Then SizeOfAllSectionsBefore = SizeOfAllSectionsBefore - &H1000
			Next ii*/
		}
		$this->fixSymbol("SizeOfImage", $SizeOfAllSectionsBefore);
	}
	public function PhysicalAddressOf($sid){
		$addr=$this->SizeOfHeader;
		for($i=1;$i<=6;$i++){
			if($i==$sid)return $addr;
			$addr+=count($this->sec[$i]["byte"]);
		}
	}
	public function VitualAddressOf($sid){
		$addr=4096;
		for($i=1;$i<=6;$i++){
			if($i==$sid)return $addr;
			
			
			$addr+=$this->tryFix(count($this->sec[$i]["byte"]),4096);
		}
	}
	public function LinkerFix($offset,$value){
		$this->fixOffset(0,$offset,$value);
	}
	public function fix(){
		//var_dump($this->fix);
		foreach($this->fix as $f){
			foreach($this->sys as $s){
				if($f["name"]==$s["name"]){
					//var_dump("fix");
					if($s["type"]==2){
						//var_dump($this->PhysicalAddressOf($f["id"])+$f["offset"]);
						//print("\n");
						//var_dump($s["offset"]-$f["offset"]-4+$f["data"]);
						//die;
						//LinkerFix PhysicalAddressOf(Fixups(i).Section) + Fixups(i).Offset, Symbols(ii).Offset - Fixups(i).Offset - 4 + Fixups(i).ExtraAdd
						$this->LinkerFix($this->PhysicalAddressOf($f["id"])+$f["offset"],$s["offset"]-$f["offset"]-4+$f["data"]);
					}else{
						//var_dump($this->PhysicalAddressOf($f["id"])+$f["offset"]);
						//var_dump($this->VitualAddressOf($s["id"])+$s["offset"]+$f["data"]);
						//print("\n");
						//var_dump($s,$f);
						$this->LinkerFix($this->PhysicalAddressOf($f["id"])+$f["offset"],$this->VitualAddressOf($s["id"])+$s["offset"]+$f["data"]);
					}
					//LinkerFix PhysicalAddressOf(Fixups(i).Section) + Fixups(i).Offset, VitualAddressOf(Symbols(ii).Section) + Symbols(ii).Offset + Fixups(i).ExtraAdd
				}
			}
		}
		
	}
}