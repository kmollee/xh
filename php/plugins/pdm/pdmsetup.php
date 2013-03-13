<?php
	/**************************************************************************\
	* Pdm CMSimple plugin 0.01
	* http://cmsimple.cycu.org
	* Copyright (C) 2013 by Chiaming Yen
	* ------------------------------------------------------------------------
	*  This program is free software; you can redistribute it and/or 
	*  modify it under the terms of the GNU General Public License Version 2
	*  as published by the Free Software Foundation; only version 2
	*  of the License, no later version. 
	* 
	*  This program is distributed in the hope that it will be useful,
	*  but WITHOUT ANY WARRANTY; without even the implied warranty of
	*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	*  GNU General Public License for more details.
	* 
	*  You should have received a copy of the GNU General Public License
	*  Version 2 along with this program; if not, write to the Free Software
	*  Foundation, Inc., 59 Temple Place - Suite 330, Boston,
	*  MA  02111-1307, USA. 
	\**************************************************************************/

// 2012.10.11 起進行大幅修改, 並且放入 CMSimple XH 1.5.4 版本中
// 去去欄位 1/4
$fieldsArray = array("序號"=>id,"組別"=> title,"討論標題"  => partno,"去去"=>go,"討論類別"=>type,"備註"=>memo,"更新時間"=>mod_date,"版次"=>version);

// 去去欄位長度 2/4
$fieldsNum=array("組別"=>15,"討論標題"=>20,"去去"=>10,"討論類別"=>10,"備註"=>30);

// 去去欄位類別 3/4
$fieldsType=array("序號"=>hidden,"組別"=>text,"討論標題"=>text,"去去"=>text,"討論類別"=>option,"備註"=>text,"更新時間"=>hidden,"版次"=>hidden);

//$fieldsData=array("序號"=>auto,"組別"=>must,"討論標題"=>must,"討論類別"=>must,"備註"=>option,"更新時間"=>auto,"版次"=>auto);

// 去去欄位資料格式 4/4
$fieldsData=array("序號"=>auto,"組別"=>must,"討論標題"=>must,"討論類別"=>option,"備註"=>option,"更新時間"=>auto,"版次"=>auto);

$tablename="pdm";
//$hiddenArray=array("var1"=>value1,"var2"=>value2,"var3"=>value3);

//連接資料庫
//$database_name="951_vault.db";
?>