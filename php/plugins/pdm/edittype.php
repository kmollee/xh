<?php
//plugin的名稱
//$plugin_name="type";
//--------------------------------------------------------------請注意!這個mainfunction2在plugins中必須有獨一的名稱(1/4)
function edittype($database_name)
{
	global $fieldsArray,$fieldsNum,$fieldsType,$fieldsData,$tablename;
	$fieldsArray = array("序號"=>index2,"類別"=> type);
	$fieldsNum=array("類別"=>15);
	$fieldsType=array("序號"=>hidden,"類別"=>text);
	$fieldsData=array("序號"=>auto,"類別"=>must);
	$tablename="type";
	global $menu,$index2;
	global $dbname;
	$dbname=$database_name;
	//連接資料庫--------------------------------------------請注意!這個access_connect2在plugins中必須有獨一的名稱(2/4)
	access_connect_edittype($database_name);
	if ($menu)
	{
	switch($menu)
	{
	case "showlistbypage":
   	$output=show_list_by_page();
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
	default:
	$output=showmenu();
	}
	}
	else
	{
	$output=showmenu();
	}
	if ($dbc)
	{
	$output.=access_close();
	}
	return $output;	
}
//--------------------------------------------------------------請注意!這個access_connect2在plugins中必須有獨一的名稱(3/3)
//擁有獨立的access_connect2(),可以讓資料庫各自存在plugin的子目錄內
function access_connect_edittype($database_name)
{
//$dbc與$connstr為連接資料庫與執行資料庫查詢所需的廣域變數
//本程式的資料庫呼叫只有一次,最後離開時必須將此一連接刪除
global $dbc,$connstr;
/*
$dbc = new COM("ADODB.Connection"); 
$connstr = "DRIVER={Microsoft Access Driver (*.mdb)};"; 
$connstr .= "DBQ=".dirname(__FILE__)."\\db1.mdb;uid=;pwd=;"; 
$dbc->Open($connstr);
*/
//ADOLoadCode("ado_access");
//$dbc = ADONewConnection("ado_access");
//若ADONewConnection前有&則$dbc無法成為global
//$dbc =&ADONewConnection("ado_access");
//$connstr="DRIVER={Microsoft Access Driver (*.mdb)};";
//$connstr.="DBQ=".dirname(__FILE__)."\\db1.mdb;uid=;pwd=;";
//$dbc->charPage=CP_UTF8;
//$dbc->Connect($connstr);
// for SQLite 2.0
/*
$dbc=NewADOConnection('sqlite');
$dbc->SetFetchMode(2);
$connstr=dirname(__FILE__)."/".$database_name;
*/
// for SQLite 3.0
$dbc=NewADOConnection('pdo');
$dbc->SetFetchMode(2);
$connstr="sqlite:".dirname(__FILE__)."/".$database_name;
$dbc->charPage=CP_UTF8;
$dbc->Connect($connstr);
    if ($dbc->Connect($connstr)==NULL)
    {
	    $output="無法連接到資料庫";
	    return $output;
    	    exit;
    }
}
?>
