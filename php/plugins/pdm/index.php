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

// 2012.03.20 改為 RedBeanPHP
// 2012.06.07 希望配合 LaTeX 可以分別在網際環境中編輯文書檔案
require_once("./plugins/common/rb.php");

//資料表欄位名稱設定
$pdmsetup="pdmsetup.php";
//將參數設定的部分,存在pdmsetup.php
include($pdmsetup);
//以上為Default的pdmsetup
//以下是使用adodb library 並且經由DNSless的方式呼叫Access
//include('adodb5/adodb.inc.php'); 
//這個tohtml.inc.php可以用來呼叫rs2html($rs)
//include('adodb5/tohtml.inc.php');
//設計用來編輯類別
include("edittype.php");
include("mime_types.php");
//加入本程式的其他部分
include('leftover.php');
//將各欄位的表單對應值存入矩陣,然後設為global
$keys=array_keys($fieldsArray);
$numofelement=sizeof($keys);
for ($i=0;$i<$numofelement;$i++)
{
if(isset($_GET[$fieldsArray[$keys[$i]]]))
{
${$fieldsArray[$keys[$i]]}=$_GET[$fieldsArray[$keys[$i]]];
}
else
{
${$fieldsArray[$keys[$i]]}=$_POST[$fieldsArray[$keys[$i]]];
}
$fieldsVar[$fieldsArray[$keys[$i]]]=${$fieldsArray[$keys[$i]]};
}
//到這裡與各欄位對應的表單所取得的變數,已經存入$fieldsVar矩陣中
//有些奇特,將$menu搬到外面,除主語系以外的plugins才能夠擷取到, 還不知道原因, 觀察中....
$menu=$_GET['menu'];
$index2=$_GET['index2'];
// $database_name 無需 .db
function pdmMain($database_name)
{
  global $menu,$index2;
  // 2012
  global $bean;
  R::setup('sqlite:'.dirname(__FILE__)."/db/".$database_name.".db");
  // 主資料表為 papers
  $bean = R::dispense('papers');
  // 除主資料表 papers 外, type 資料表為選項表單 type 所用
  $bean2 = R::dispense('type');
  $all_data = R::find("type","1 order by id");
  if (count($all_data) == 0)
  {
    // 先在 type 資料表中, 新增一筆資料待用
    $bean2->title = "內建";
    $id = R::store($bean2);
  }
  $papers_all_data = R::find("papers","1 order by id");
//這個$dbname是要用來附加在上傳檔名上,以便用來區分各資料庫所對應的上傳檔
	global $dbname;
	$dbname=$database_name;
	//連接資料庫
	//access_connect($database_name);
	if ($menu)
	{
	switch($menu)
	{
	case "showlistbypage":
   	$output=show_list_by_page($papers_all_data);
   	break;
	case "querymenu":
	  $output=querymenu();
	  break;
	case "queryaction":
	  $output=queryaction();
	  break;
	case "showquerylist":
	  $output=showquerylist();
	  break;
	case "addmenu":
	  //顯示資料庫內容
 	  $output=addmenu();
  	break;
	case "addaction":
	  $output=addaction();
	  break;
	case "updatemenu":
	  $output=updatemenu();
	  break;
	case "updateactionmenu":
	  $output=updateactionmenu();
	  break;
	case "updateaction":
	  $output=updateaction();
	  break;
	case "doupdate":
    $output=doupdate();
    break;
	case "showupdatelist":
    $output=showupdatelist();
    break;
	case "deletemenu":
    $output=deletemenu();
    break;
	case "deleteactionmenu":
    $output=deleteactionmenu();
    break;
	case "deleteaction":
    $output=deleteaction();
    break;
	case "dodelete":
    $output=dodelete();
    break;
	case "showdeletelist":
    $output=showdeletelist();
    break;
	case "pdmdownload":
    pdmdownload();
    break;
//新增follow相關的表單
	case "addfollowupmenu":
    $output.=addfollowupmenu();
    break;
	case "showlayerlist":
    $output.=showlayerlist();
    break;

	case "searchmenu":
           $output.=searchmenu();
           break;

	case "searchaction":
           $output.=searchaction();
           break;

	default:
	//$output=showmenu();
	$output=show_list_by_page($papers_all_data);
	}
	}
	else
	{
	//$output=showmenu();
	$output=show_list_by_page($papers_all_data);
	}
	if ($dbc)
	{
	$output.=access_close();
	}
	return $output;	
}

?>