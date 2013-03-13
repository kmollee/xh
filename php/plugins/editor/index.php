<?php
	/**************************************************************************\
	* Editor CMSimple plugin 0.01
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

// editor plugin
// 新增功能: 假如所希望編輯的 plugin 尚未建立, 則此編輯程式將自行建立 plugin 目錄與檔案

$editor_pass = $cf['myeditor']['pass'];
session_start();
function editorMain($plugin)
{
global $menu;
 
$menu=$_GET["menu"];
$file=$_GET["file"];
// 若 plugin 目錄下根本就沒有與 file 對應的目錄, 則自行建立對應目錄與空的 index.php
// 若 $plugin 為空, 則回覆錯誤訊息
if ($plugin == "")
{
    $output = "Error! Please contact the system administrator!";
    return $output;
}
if (file_exists("./plugins/".$plugin)) {
    // 繼續執行
} else {
    // 建立該對應目錄
    mkdir("./plugins/".$plugin, 0700);
    // 建立空程式檔案
    $ourFileName = "index.php";
    $ourFileHandle = fopen("./plugins/".$plugin."/".$ourFileName, 'w') or die("can't open file");
    fclose($ourFileHandle);
}
$output="正在編輯 ".$plugin." 延伸程式<br /><br />";
  switch($menu)
  {
    case "editorform":
        if($_SESSION["editortoken"])
            $output.=editorForm($plugin);
            else
            $output=editorLogin();
    break;
 
    case "editorsave":
        if($_SESSION["editortoken"])
            $output.=editorSave($plugin);
            else
            $output=login2();
    break;
 
    case "editorcheck":
            $output=editorCheck($plugin);
    break;
 
    case "editorlogout":
            $output=editorLogout();
    break;
 
    default:
            $output=editorLogin();
  }
 
return $output;
}
 
function editorLogin()
{
    global $sn,$su;
 
    $output= "請輸入登入密碼";
    $output.="<form method=POST action=".$sn."?".$su."&menu=editorcheck><br>";
    $output.="密碼:<input type=password name=editorpass>";
    $output.="<input type=submit value=send>";
    $output.="</form>";
    return $output;
}
 
function editorLogout()
{
    session_destroy();
    $output="已經登出<br>";
    $output.=editorLogin();
    return $output;
}
 
function editorCheck($plugin)
{
    global $editor_pass;
    
    $password = $_POST["editorpass"];
    $output = $password;
 
    if($password==$editor_pass)
    {
        $_SESSION["editortoken"]=true;
        $output=editorPrintmenu($plugin);
    }
    else
    {
        $_SESSION["editortoken"]=false;
        $output=editorLogin();
    }
    return $output;
}
 
function editorPrintmenu($plugin)
{
global $sn,$su;
 
$output.="<a href=".$sn."?".$su."&menu=editorform>編輯 ".$plugin." 程式</a>|";
$output.="<a href=".$sn."?".$su."&menu=editorlogout>logout</a>|";
 
return $output;
}
 
function editorForm($plugin)
{
    global $sn,$su;
 
    $output="<form method=post action=".$sn."?".$su."&menu=editorsave>";
    //$output.=dirname(__FILE__);
 
    $fp = fopen (dirname(__FILE__)."/./../".$plugin."/index.php", "r");
    $contents = fread($fp, filesize(dirname(__FILE__)."/./../".$plugin."/index.php"));
    fclose($fp);
    $output.="<textarea cols=50 rows=20 name=\"content\">";
    //這裡為了在html區域展示程式碼,若要轉回來,則使用htmlspecialchars_decode()
    $output.=htmlspecialchars($contents);
    $output.="</textarea>";
    $output.="<br><input type=submit value=send>";
    $output.="</form>";
    $output.=editorPrintmenu($plugin);
 
return $output;
}
 
function editorSave($plugin)
{
    global $sn,$su;
 
if(ini_get('magic_quotes_gpc')=="1")
{
    $content = stripslashes(htmlspecialchars_decode($_POST["content"]));
}
else
{
    $content = htmlspecialchars_decode($_POST["content"]);
}
    $fp = fopen (dirname(__FILE__)."/./../".$plugin."/index.php", "w");
    fwrite($fp,$content);
    fclose($fp);
    $output .= date("H:i:s").":已經存檔,請在以下編輯區,繼續編輯";
// 以下回到編輯區
    $output.="<form method=post action=".$sn."?".$su."&menu=editorsave>";
    //$output.=dirname(__FILE__);
 
    $fp = fopen (dirname(__FILE__)."/./../".$plugin."/index.php", "r");
    $contents = fread($fp, filesize(dirname(__FILE__)."/./../".$plugin."/index.php"));
    fclose($fp);
    $output.="<textarea cols=50 rows=20 name=\"content\">";
    //這裡為了在html區域展示程式碼,若要轉回來,則使用htmlspecialchars_decode()
    $output.=htmlspecialchars($contents);
    $output.="</textarea>";
    $output.="<br><input type=submit value=send>";
    $output.="</form>";
    $output.=editorPrintmenu($plugin);
 
return $output;
}