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
// 2012.10.11 將系統搬遷到 CMSimple XH, 並且寫為可以處理多人協同 rst 文件
// 並且與 portable_latex 結合, 成為協同 Latex 與 restructuredTEXT 的編輯套件
//設計用來編輯類別
//有這兩行,會讓download 最前頭出現兩行空白資料,問題出在程式結束之後還有兩行空白,因此造成問題
//將這兩行移到 index.php
//include("edittype.php");
//include("mime_types.php");
//20061008請注意!因為在上傳的檔案名稱中附加$dbname_append,並且是由$dbname,也就是在#CMSimple程式段中所呼叫的資料庫檔案名稱
//由於整個程式使用$dbname_append=trim($dbname,".db");的緣故, 目前只能用在SQLite .db的檔案中
//或許應該將$dbname_append改為將$dbname中的副檔名去除的方式, 就可以適用於.mdb使用Access資料庫的情況
//請注意,在本程式中透過&menu=,來派送執行的函式,就download的menu要使用pdmdownload,$sn,$su,$tablename,$dbname都必須global

// 以下處理 $su 目的在配合 CMSimple XH 版本的使用
$su = $su."&normal";

function access_close()
{
global $dbc;
if ($dbc)
{
$dbc->close(); 
//關閉COM物件時不能有輸入字串
$dbc=null;
}
else
{
$output= "<br>請注意!資料庫無法關閉!<br>";
}
return $output;
}

function show_list_by_page($all_data)
{
    // 2012 將 $all_data 重組為 index 為 0,1,2....
    // http://php.net/manual/en/function.array-values.php
   $all_data=array_values($all_data);
    // 2012 改為 RedBeanPHP
    //global $output;
    global $sn,$su;
    global $dbc,$connstr;
    global $page,$item_per_page;
    global $tablename;
    // 2012, asc or desc
    //$all_data = R::find("papers","1 order by id");
    //$sql="SELECT * FROM ".$tablename." order by index2";
    //$rs=access_query($sql,$connstr);
    $page=$_GET['page'];
    $item_per_page=$_GET['item_per_page'];
    if(!$page)
    {
        $page=1;
    }
    if (!$item_per_page)
    {
        $item_per_page=10;
    }
    $total_rows=count($all_data);
    //$total_rows=$rs->RecordCount();
    //沒有資料時送出錯誤訊息
        if ($total_rows==0)
        {
            $output="沒有資料";
            $output.="<br><br>";
            $output.= showmenu();
            return $output;
            exit();
        }
    // 當資料庫內沒有資料的情況在 $rs=access_query($sql,$connstr)的函式內就已經處理
    if (($total_rows % $item_per_page)==0)
    $totalpage=$total_rows/$item_per_page;
    else
    $totalpage=(int)($total_rows/$item_per_page)+1;
    $starti = $item_per_page * ($page - 1) + 1;
    $endi = $starti + $item_per_page - 1;
    //$rs->MoveFirst();
    If ((int)($page * $item_per_page) < $total_rows)
    {
    $notlast = true;
    //$output.=access_list($rs,$starti,$endi);
      $output.=access_list($all_data,$starti,$endi);
    }
    else
    {
      //$output.=access_list($rs,$starti,$total_rows);
        $output.=access_list($all_data,$starti,$total_rows);
    }
    If ($page > 1)
    {
        $page_num=$page-1;
        $output.= "<a href=";
        $output.= $sn."?".$su."&menu=showlistbypage&page=".$page_num."&item_per_page=".$item_per_page;
        $output.= ">上一頁</a>< ";
    }
    //list all page link
    //echo "Page:";
    for ($j=0;$j<$totalpage;$j++)
    {
        $page_now=$j+1;
        if($page_now==$page)
        {
        $output.="<font size=+1 color=red>".$page." </font>";
        }
        else
        {
            $output.= "<a href=".$PHP_SELF;
            $output.=$sn."?".$su."&menu=showlistbypage&page=".$page_now."&item_per_page=".$item_per_page;
            $output.= ">".$page_now."</a> ";
        }
    }
    If ($notlast == true)
    {
    $nextpage=$page+1;
    $output.= "<a href=".$PHP_SELF;
    $output.= $sn."?".$su."&menu=showlistbypage&page=".$nextpage."&item_per_page=".$item_per_page;
    $output.= ">>下一頁</a>";
    }
    $output.= "<br><br>";
    $output.= showmenu();
    return $output;
}

function access_query($sql,$conn)
{
//執行SQL運作
global $dbc;
//SetFetchMode為2表示利用欄位名稱取值
//若SetFetchMode為1則為利用欄位次序取值
$dbc->SetFetchMode(2);
$rs=$dbc->Execute($sql);
return $rs;
}

function access_list($result,$from,$to)
{
    // 2012
    //$sn與$su為CMSimple用來擷取連結頁面的參數
    global $sn,$su;
    global $fieldsArray;
    global $tablename;
    global $dbname;
    $keys_of_fields=array_keys($fieldsArray);
    // 2012
    //$keys=array_keys($result->fields);
    //利用sizeof()取得向量對應值的個數
    //$numofelement=sizeof($keys);
    // 資料表中的欄位個數
    $numofelement2=sizeof($keys_of_fields);
    //global $dbc;
    // 2012
    //$result->MoveFirst();
    $output.= "<table border=1>";
    $output.= "<tr bgcolor=lightgreen>";
    //必須要由$result得知資料的各欄位名稱
    for ($i=0;$i<$numofelement2;$i++)
    {
      $output.= "<td>$keys_of_fields[$i]</td>";
    }
    //############################################################## 連結欄位
    $output.= "<td>連結</td>";
    //############################################################## 取檔欄位
    $output.= "<td>檔案</td>";
    //############################################################## 串流
    $output.= "<td>串流</td>";
    /*
    //############################################################## checkout欄位
    echo "<td>簽進出</td>";
    //############################################################## gettree欄位
    echo "<td>關聯</td>";
    */
    $output.= "</tr>";
    //echo "<td>序號</td><td>姓名</td><td>號碼</td></tr>";
    //將資料的$from到$to比的資料列出來
    // 2012 請注意, 因為改為 RedBeanPHP 後 find 的陣列指標由 1 開始, 而非 ADODB 的 0
    // 但是採用 array_values 重新將 array 設為 index 0,1,2,3... 則 index 又從 0 開始算
    for ($i=$from-1;$i<$to;$i++)
    //for ($i=$from;$i<$to+1;$i++)
    {
      $output.= "<tr>";
    // 2012
    //$result->Move($i);
    //若fields用大寫在Access印不出資料
    //echo stripslashes($result->fields['index2'] );
     // 請注意,這裡應該只列出 $result 中的第 $i 筆而非全部
    // foreach($result as $result_data)
    //{
        // 處理由 setup 讀進的變動欄位資料, 其個數為 numofelement2
        for ($j=0;$j<$numofelement2;$j++)
        {
            if(fmod($i,2)==0)
            {
                $output.= "<td bgcolor='#F0F0F0'>";
                $data=access_qresult($result,$i,$fieldsArray[$keys_of_fields[$j]]);
                    if ($data)
                    {
                        $output.= $data;
                    }
                    else
                    {
                        $output.= "";
                    }
                $output.= "</td>";
            }
            else
            {
                $output.= "<td bgcolor='#E0E0E0'>";
                    $data=access_qresult($result,$i,$fieldsArray[$keys_of_fields[$j]]);
                    if ($data)
                    {
                    $output.= $data;
                    }
                    else
                    {
                    $output.= " ";
                    }
                $output.= "</td>";
            }
        }
        //這裡新增一個連結的欄
        //############################################################## 連結欄位
        $url=access_qresult($result,$i,'url');
                if ($url)
                {
            $output.= "<td><a href=\"".$url."\" target=\"_blank\"><center><img src=\"graphics/link.gif\" border=0></center></a></td>";
                }
                else
                {
                $output.= "<td> </td>";
                }
        //這裡新增一個取檔的欄
        //############################################################## 取檔欄位
        $filename=access_qresult($result,$i,'upload');
        $indexno=access_qresult($result,$i,'index2');
        $version=access_qresult($result,$i,'version');
        $file_size=access_qresult($result,$i,'upload_size');
        $dbname_append=trim($dbname,".db");
        $ext = ereg_replace("^.+\\.([^.]+)$", "\\1", $filename);
        //以下的取檔是直接經由web link取檔,這樣會造成安全上的問題
        //照理說, 應該要經由menu=pdmdownload&index2=number取檔,然後將web link在upload_files設定為不可取檔
        //echo "<td><a href=\"upload_files/".$filename."\" target=\"_blank\">".$filename."</a></td>";
                if ($filename)
                {
                        //只有圖形檔有preview功能,接受.jpg,.png與.gif檔
                        if (eregi('(\.jpg|\.png|\.gif)',$filename))
                        {
        //請注意,這裡以原圖大小preview,若要限制preview大小,則要設定width=640
        //	    $output.= "<td><a href=\"".$sn."?".$su."&menu=pdmdownload&index2=".$indexno."\" target=\"_blank\" onmouseout=hide() onmouseover=\"show(event,'<img src=plugins/pdm/upload_files/".$version."_".$dbname_append."_".$tablename."_".$indexno.".".$ext." >')\">".$filename."</a>(".$file_size.")</td>";
                    $output.= "<td><a href=\"".$sn."?".$su."&menu=pdmdownload&index2=".$indexno."\" target=\"_blank\" onmouseout=hide() onmouseover=\"show(event,'<img src=plugins/pdm/upload_files/".$version."_".$dbname_append."_".$tablename."_".$indexno.".".$ext." >')\">".$filename."</a>(".$file_size.")</td>";
                        }
                        elseif(eregi('(\.htm|\.swf)',$filename))
                        {
                        // the $filename is the uploaded file name but not the real file name
                        $ext = ereg_replace("^.+\\.([^.]+)$", "\\1", $filename);
                        // 以下是由 function access_list()中搬來作為參考用
                        //$finalfilename=$folder."/upload_files/".$version."_".$tablename."_".$maxnow.".".$ext;
                        $output.= "<td><a href=upload_files/".$version."_".$dbname_append."_".$tablename."_".$indexno.".".$ext.">".$filename."</a>(".$file_size.")</td>";
                        }
                        else
                        {
                        $output.= "<td><a href=\"".$sn."?".$su."&menu=pdmdownload&index2=".$indexno."\" target=\"_blank\">".$filename."</a>(".$file_size.")</td>";
                        }
                }
                else
                {
                $output.= "<td> </td>";
                }
        //############################################################## 串流欄位
        $ext = ereg_replace("^.+\\.([^.]+)$", "\\1", $filename);
        $realfilename=$version."_".$dbname_append."_".$tablename."_".$indexno.".".$ext;
            if (isset($filename))
            {
            if (eregi('(\.mp3|\.wav|\.MP3|\.WAV|\.wma|\.WMA|\.mpg|\.MPG|\.mpeg|\.MPEG|\.wmv|\.WMV)',$filename))
            {
            //echo "<td><a href=\"/audiostreaming.php?filename=".$filename."><center><img src=graphics/earphone.jpg border=\"0\" alt=\"線上串流\"></center></a></td>";
            $output.= "<td><a href=\"m3u/audiostreaming.php?filename=".$realfilename."\"><img src=graphics/earphone.gif></a></td>";
            }
            else
            {
            $output.= "<td> </td>";
            }
            }
        /*
        //############################################################## checkout欄位
        //這裡必須要檢視資料欄位,若status為locked則show出-簽入,若status欄位值為open或空白,則show出-簽出
        $status=access_qresult($result,$i,'status');
        if ($status=='locked')
        {
        //這裡若直接將簽入導到updateactionmenu的選單,目前checkin函式並沒有用到
        //請注意,這裡讓updateaction直接接受serialno,會造成資料的安全問題,請注意!最後要有安全機制的考量
        //echo "<td><a href=\"?menu=updateaction&serialno=".$indexno."\" target=\"_blank\"><center><img src=graphics/locked.gif border=\"0\" alt=\"簽入\"></center></a></td>";
        echo "<td><a href=\"?menu=checkin&index2=".$indexno."\"><center><img src=graphics/locked.gif border=\"0\" alt=\"簽入\"></center></a></td>";
        }
        else
        {
        echo "<td><a href=\"?menu=checkout&index2=".$indexno."\"><center><img src=graphics/unlocked.gif border=\"0\" alt=\"簽出\"></center></a></td>";
        }
        //############################################################## gettree欄位
        echo "<td><a href=\"?menu=gettree&index2=".$indexno."\" target=\"_blank\"><center><img src=graphics/gettree.gif border=\"0\" alt=\"查詢關聯資料\"></center></a></td>";
        */
      $output.= "</tr>";
      }
   // } // ends foreach
    $output.= "</table><br>";
    return $output;
}

function access_update_list($result,$from,$to)
{
global $sn,$su;
global $dbname;
global $fieldsArray;
$keys_of_fields=array_keys($fieldsArray);
$numofelement2=sizeof($keys_of_fields);
$result->MoveFirst();
$output.= "<form method=post action=".$sn."?".$su."&menu=updateaction>";
$output.= "<table border=1>";
$output.= "<tr bgcolor=lightgreen>";
//echo "<td>選項</td><td>序號</td><td>姓名</td><td>號碼</td></tr>";
//因為在各列的第一欄是radio選項,所以titlt要在這裡加入
$output.= "<td>選項</td>";
for ($i=0;$i<$numofelement2;$i++)
{
$output.= "<td>$keys_of_fields[$i]</td>";
}
//############################################################## 取檔欄位
$output.= "<td>檔案</td>";
/*
//############################################################## gettree欄位
$output.= "<td>關聯</td>";
*/
$output.= "</tr>";
for ($i=$from-1;$i<$to;$i++)
{
$output.= "<tr>";
$result->Move($i);
$serialno=access_qresult($result,$i,"index2");
$status=access_qresult($result,$i,"status");
$output.= "<td>";
//請注意,這個更新資料的按鈕,當該筆資料status為locked時將不允許更新,也就是該檔案只能唯讀
if ($status=='locked')
{
//什麼都不作,因為不允許更新資料,這裡將要show出一個資料locked的圖像
$output.= "<center><img src=graphics/locked.gif></center>";
}
else
{
$output.= "<input type=radio name=serialno value=".$serialno.">";
}
$output.= "</td>";
for ($j=0;$j<$numofelement2;$j++)
{
$output.= "<td>";
	$data=access_qresult($result,$i,$fieldsArray[$keys_of_fields[$j]]);
	if ($data)
	{
	$output.= $data;
	}
	else
	{
	$output.= "";
	}
$output.= "</td>";
}
//這裡新增一個取檔的欄
//############################################################## 取檔欄位
$filename=access_qresult($result,$i,'upload');
$indexno=access_qresult($result,$i,'index2');
$version=access_qresult($result,$i,'version');
$dbname_append=trim($dbname,".db");
$ext = ereg_replace("^.+\\.([^.]+)$", "\\1", $filename);
//$output.= "<td><a href=\"?menu=download&index2=".$indexno."\" target=\"_blank\">".$filename."</a></td>";
//$output.= "<td><a href=\"?menu=download&index2=".$indexno."\" target=\"_blank\" onmouseout=hide() onmouseover=\"show(event,'<img src=upload_files/".$version."_".$filename." width=300>')\">".$filename."</a></td>";
	if ($filename)
	{
		//只有圖形檔有preview功能,接受.jpg,.png與.gif檔
		if (eregi('(\.jpg|\.png|\.gif)',$filename))
		{
		$output.= "<td><a href=\"".$sn."?".$su."&menu=pdmdownload&index2=".$indexno."\" target=\"_blank\" onmouseout=hide() onmouseover=\"show(event,'<img src=plugins/pdm/upload_files/".$version."_".$dbname_append."_".$tablename."_".$indexno.".".$ext." >')\">".$filename."</a></td>";
		}
		else
		{
		$output.= "<td><a href=\"".$sn."?".$su."&menu=pdmdownload&index2=".$indexno."\" target=\"_blank\">".$filename."</a></td>";
		}
	}
	else
	{
	//無資料的欄位補上空白
	$output.= "<td> </td>";
	}
/*
//############################################################## gettree欄位
$output.= "<td><a href=\"?menu=gettree&index2=".$indexno."\" target=\"_blank\"><center><img src=graphics/gettree.gif border=\"0\" alt=\"查詢關聯資料\"></center></a></td>";
*/
$output.= "</tr>";
/*
$output.= "<input type=radio name=serialno value=".$serialno.">";
$output.= "</td>";
$output.= "<td>";
access_result($result,$i,"index2");
$output.= "</td>";
$output.= "<td>";
access_result($result,$i,"name");
$output.= "</td>";
//echo "|";
$output.= "<td>";
access_result($result,$i,"no");
$output.= "</td>";
$output.= "</tr>";
*/
}
$output.= "</table>";
//$output.= "<input type=hidden  name=index2 value=".$index2.">";
$output.= "<input type=submit value=送出>";
$output.= "<input type=reset value=重寫>";
$output.= "</form>";
return $output;
}
//ends access_update_list
function access_delete_list($result,$from,$to)
{
global $sn,$su;
global $dbname;
global $fieldsArray;
$keys_of_fields=array_keys($fieldsArray);
$numofelement2=sizeof($keys_of_fields);
$result->MoveFirst();
$output.= "<form method=post action=".$sn."?".$su."&menu=deleteaction>";
$output.= "<table border=1>";
$output.= "<tr bgcolor=lightgreen>";
//$output.= "<td>選項</td><td>序號</td><td>姓名</td><td>號碼</td></tr>";
//因為在各列的第一欄是radio選項,所以titlt要在這裡加入
$output.= "<td>選項</td>";
for ($i=0;$i<$numofelement2;$i++)
{
$output.= "<td>$keys_of_fields[$i]</td>";
}
//############################################################## 取檔欄位
$output.= "<td>檔案</td>";
/*
//############################################################## gettree欄位
$output.= "<td>關聯</td>";
*/
$output.= "</tr>";
for ($i=$from-1;$i<$to;$i++)
{
$output.= "<tr>";
$result->Move($i);
$serialno=access_qresult($result,$i,"index2");
$output.= "<td>";
$output.= "<input type=radio name=serialno value=".$serialno.">";
$output.= "</td>";
for ($j=0;$j<$numofelement2;$j++)
{
$output.= "<td>";
	$data=access_qresult($result,$i,$fieldsArray[$keys_of_fields[$j]]);
	if ($data)
	{
	$output.= $data;
	}
	else
	{
	$output.= " ";
	}
$output.= "</td>";
}
//這裡新增一個取檔的欄
//############################################################## 取檔欄位
$filename=access_qresult($result,$i,'upload');
$indexno=access_qresult($result,$i,'index2');
$version=access_qresult($result,$i,'version');
$dbname_append=trim($dbname,".db");
$ext = ereg_replace("^.+\\.([^.]+)$", "\\1", $filename);
//$output.= "<td><a href=\"?menu=download&index2=".$indexno."\" target=\"_blank\">".$filename."</a></td>";
//$output.= "<td><a href=\"?menu=download&index2=".$indexno."\" target=\"_blank\" onmouseout=hide() onmouseover=\"show(event,'<img src=upload_files/".$version."_".$filename." width=300>')\">".$filename."</a></td>";
	//只有圖形檔有preview功能,接受.jpg,.png與.gif檔
	if (eregi('(\.jpg|\.png|\.gif)',$filename))
	{
$output.= "<td><a href=\"".$sn."?".$su."&menu=pdmdownload&index2=".$indexno."\" target=\"_blank\" onmouseout=hide() onmouseover=\"show(event,'<img src=plugins/pdm/upload_files/".$version."_".$dbname_append."_".$tablename."_".$indexno.".".$ext." >')\">".$filename."</a></td>";
	}
	else
	{
	$output.= "<td><a href=\"".$sn."?".$su."&menu=pdmdownload&index2=".$indexno."\" target=\"_blank\">".$filename."</a></td>";
	}
/*
//############################################################## gettree欄位
echo "<td><a href=\"?menu=gettree&index2=".$indexno."\" target=\"_blank\"><center><img src=graphics/gettree.gif border=\"0\" alt=\"查詢關聯資料\"></center></a></td>";
*/
$output.= "</tr>";
/*
echo "<td>";
access_result($result,$i,"index2");
echo "</td>";
echo "<td>";
access_result($result,$i,"name");
echo "</td>";
//echo "|";
echo "<td>";
access_result($result,$i,"no");
echo "</td>";
echo "</tr>";
*/
}
$output.= "</table>";
//echo "<input type=hidden  name=index2 value=".$index2.">";
$output.= "<input type=submit value=送出>";
$output.= "<input type=reset value=重寫>";
$output.= "</form>";
return $output;
}
function dodelete()
{
global $dbname;
global $dbc,$connstr;
//global $index2;
global $tablename;
$index2=$_POST['index2'];
//$connstr=access_connect();
//先刪除資料所對應的檔案(在upload_files內)
//1.先由$index取得所對應的$uploadfilename
$sql="SELECT upload,version from ".$tablename." where index2=$index2";
$rs=access_query($sql,$connstr);
$uploadfilename=access_qresult($rs,0,'upload');
$version=access_qresult($rs,0,'version');
$dbname_append=trim($dbname,".db");
$ext = ereg_replace("^.+\\.([^.]+)$", "\\1", $uploadfilename);
//利用dirname(__FILE__)取得目前所在的目錄位置
$filedir=dirname(__FILE__);
//必須處理刪除檔案
$command="del \"".$filedir."\\upload_files\\".$version."_".$dbname_append."_".$tablename."_".$index2.".".$ext."\" /F/Q";
system($command,$result);
//以下為debug用
//$output.=$filedir;
//return$output;
//exit;
//echo $command;
//echo "<br>";
//echo $uploadfilename;
//echo "<br>";
//再刪除資料庫所對應的資料
$sql="DELETE from ".$tablename." where index2=$index2";
$rs=access_query($sql,$connstr);
$output.= "所選資料已經刪除";
$output.= "<br>";
$output.=showmenu();
return $output;
}
function access_result($result,$count,$id)
{
//直接利用Move到相對的筆數, 然後印出來
//將$result的Move放在這裡,不會正確,因此將Move至於呼叫的主函式,就會正確!!find out what 's wrong
//$result->Move($count);
//若fields用大寫在Access印不出資料
return stripslashes($result->fields[$id] );
}

function access_qresult($result,$count,$id)
{
//這個函式已經改為先在上層函式先移到要列出的index然後再列出資料,所以無法在子函式移動index??
//return stripslashes($result->fields[$id]) ;
    // 2012
    return stripslashes($result[$count]->$id) ;
}

function showmenu()
{
	global $sn,$su,$adm;
	$output.="<br><a href=".$sn."?".$su."&menu=addmenu>新增資料</a>";
	$output.="|";
	$output.="<a href=".$sn."?".$su."&menu=querymenu>查詢資料</a>";
	$output.="|";
       $output.="<a href=".$sn."?".$su."&menu=searchmenu>關鍵字查詢</a>";
	$output.="|";
	$output.="<a href=".$sn."?".$su."&menu=showlistbypage>分頁顯示</a>";
	//請注意!這個程式還沒有進入非管理員的執行程式內加已設定$adm權限
	//後續一定要加以修改
	if ($adm)
	{
	$output.="|";
	$output.="<a href=".$sn."?".$su."&menu=showlayerlist>多緒顯示</a>";
	$output.="|";
	$output.="<a href=".$sn."?".$su."&menu=updatemenu>更新資料</a>";
	$output.="|";
	$output.="<a href=".$sn."?".$su."&menu=deletemenu>刪除資料</a><br>";
	}
	return $output;
}

function querymenu()
{
    //global $output;
    global $fieldsArray,$fieldsNum,$fieldsType;
    $keys=array_keys($fieldsArray);
    $numofelement=sizeof($keys);
    $actionScript="menu=queryaction";
    //$hiddenArray=array("var1"=>value1,"var2"=>value2,"var3"=>value3);
    $hiddenArray=null;
    $descriptionString="查詢表單";
    $output.=generate_form_header($descriptionString,$actionScript,$hiddenArray,$fieldsArray,$fieldsNum,$fieldsType);
    $output.=generate_form_footer();
    $output.= "<br>";
    $output.=showmenu();
    return $output;
}

function searchmenu()
{
    global $sn,$su;
    $output ="
    請利用下列表單進行搜尋:<br />
    <form method=\"post\" action=\"".$sn."?".$su."&menu=searchaction\">
    關鍵字:<input type=\"text\" name=\"keyword\" size=\"10\">
    <input type=\"submit\" value=\"send\">
    </form>
    ";
    $output.=showmenu();
    return $output;
}

function searchaction()
{
    global $fieldsArray;
    $keys=array_keys($fieldsArray);
    $numofelement=sizeof($keys);
    $order = 1;
    $keyword = $_POST["keyword"];
    for($i=0;$i<$numofelement;$i++)
    {
        if($order == 1)
        {
            $where .= "(".$fieldsArray[$keys[$i]]." LIKE '%".$keyword."%') ";
        }
        else
        {
            $where .= "OR (".$fieldsArray[$keys[$i]]." LIKE '%".$keyword."%') ";
        }
        $order++;
    }
    $where .= " order by id";
    //$search_data = R::getAll("SELECT * FROM papers where var1 like ? or var2 like ? or var3 like ?",array("%".$keyword."%","%".$keyword."%","%".$keyword."%")); 
    //$search_data = R::getAll("SELECT * FROM papers ".$where,array($array_content));
    //$where = "title like '%1%' or memo like '%1%'";
    $search_data = R::find("papers",$where);
    // 使用 array_values reset index to 0,1,2,3...
    $output .= show_list_by_page($search_data);
    return $output;
}

function addmenu()
{
    //global $output;
    //目前要針對新增來更改
    global $fieldsArray,$fieldsNum,$fieldsType;
    global $hiddenArray,$follow;
    $keys=array_keys($fieldsArray);
    //$numofelement=sizeof($keys);
    //由於資料新增,有掛檔的需求,因此將ENCTYPE的格式直接掛在$actionScript的後面
    $actionScript="menu=addaction ENCTYPE=\"multipart/form-data\"";
    $hiddenArray=array("follow"=>"0");
    //將$hiddenArray["follow"]設為0,然後宣告為global
    $follow=$hiddenArray["follow"];
    //$hiddenArray=null;
    $descriptionString="新增表單";
    $output.=generate_form_header($descriptionString,$actionScript,$hiddenArray,$fieldsArray,$fieldsNum,$fieldsType);
    //中間這裡讓使用者增加一些相關的額外表單內容,但是所增加的內容要如何與SQL的部分配合??,要再修改??
    //以下是新增資料的上載格式設定變數
    $output.= "連結: <input type=text name=\"url\" size=40><br>\n";
    $output.= "附加檔案: <input type=\"file\" name=\"userfile\" size=30><br>\n";
    $output.="<script language=\"JavaScript\">\n";
    $output.="function validate() {\n";
    $output.="var Ary = document.uploadf.userfile.value.split('\\\\');\n";
    $output.="document.uploadf.fname.value=Ary[Ary.length-1];\n";
    $output.="return true;\n";
    $output.="}\n";
    $output.="</script>\n";
    $output.="<input type=\"hidden\" name=\"fname\">\n";
    //$output.="<input type=\"hidden\" name=\"follow\" value=\"".$follow."\">\n";
    $output.= "<input type=\"submit\" name=\"upload\" value=\"新增\">\n";
    $output.= "<input type=\"reset\" nmae=\"reset\" value=\"重寫\">\n";
    $output.= "</form>";
    //generate_form_footer();
    $output.= "<br>";
    $output.= showmenu();
    return $output;
}

function updatemenu()
{
global $sn,$su;
//目前要針對更新表單來更改
global $fieldsArray,$fieldsNum,$fieldsType;
$keys=array_keys($fieldsArray);
$numofelement=sizeof($keys);
$actionScript="menu=updateactionmenu";
$hiddenArray=array("follow"=>0);
//$hiddenArray=null;
$descriptionString="更新表單";
$output.=generate_form_header($descriptionString,$actionScript,$hiddenArray,$fieldsArray,$fieldsNum,$fieldsType);
//中間這裡讓使用者增加一些相關的額外表單內容
$output.=generate_form_footer();
$output.= "<br>";
$output.=showmenu();
return $output;
}
function deletemenu()
{
//目前要針對刪除表單來更改
global $fieldsArray,$fieldsNum,$fieldsType;
$keys=array_keys($fieldsArray);
$numofelement=sizeof($keys);
$actionScript="menu=deleteactionmenu";
$hiddenArray=array("follow"=>0);
//$hiddenArray=null;
$descriptionString="刪除表單";
$output.=generate_form_header($descriptionString,$actionScript,$hiddenArray,$fieldsArray,$fieldsNum,$fieldsType);
//中間這裡讓使用者增加一些相關的額外表單內容
$output.=generate_form_footer();
$output.= "<br>";
$output.=showmenu();
return $output;
}
function queryaction()
{
global $sn,$su;
//應該要針對表單內容就所取得的欄位資料進入查詢的準備
//這裡也必須要將各變數所對應的表單值設為global
global $fieldsArray,$fieldsNum,$fieldsType,$fieldsVar;
global $tablename;
// after we get the query strings here we need to keep track of them during the whole query process
global $dbc,$connstr;
global $page,$item_per_page;
global $query_string;
//必須根據$fieldsVar實質對應的值來拼湊出對應的$query_string
$keys=array_keys($fieldsArray);
$numofelement=sizeof($keys);
$count=0;
for($i=0;$i<$numofelement;$i++)
{
	if($fieldsVar[$fieldsArray[$keys[$i]]])
	{
	$count++;
		if($count==1)
		{
//第一個變數就查詢字串不需要有&
//就sql查詢的where後面字串則不需要有and(各變數的查詢交集)
		$query_string.=$fieldsArray[$keys[$i]]."=".$fieldsVar[$fieldsArray[$keys[$i]]];
		$after_where.=$fieldsArray[$keys[$i]]." like '%".$fieldsVar[$fieldsArray[$keys[$i]]]."%' ";
		}
		else
		{
//針對第一個有值的變數以後
		$after_where.=" and ".$fieldsArray[$keys[$i]]." like '%".$fieldsVar[$fieldsArray[$keys[$i]]]."%' ";
		$query_string.="&".$fieldsArray[$keys[$i]]."=".$fieldsVar[$fieldsArray[$keys[$i]]];
		}
	}
	else
	{
	//當沒有輸入查詢關鍵字時,該變數對應的字串使用空白
	$after_where.="";
	$query_string.="";
	}
}
//到這裡若$after_where仍為空白字串,則會安排列出全部的資料,以供使用者瀏覽
if($after_where=="" and $query_string=="")
{
		$after_where=$fieldsArray[$keys[0]]." like '%".$fieldsVar[$fieldsArray[$keys[0]]]."%' ";
		$query_string=$fieldsArray[$keys[0]]."=".$fieldsVar[$fieldsArray[$keys[0]]];
}
$sql="SELECT * FROM ".$tablename." where ".$after_where;
$rs=access_query($sql,$connstr);
$page=$_GET['page'];
$item_per_page=$_GET['item_per_page'];
  if(!$page)
   {
    $page=1;
   }
  if (!$item_per_page)
   {
    $item_per_page=10;
   }
if ($rs->EOF)
{
$output.= "<br>";
$output.= "查不到你要的資料";
$output.= "<br><br>";
$output.=showmenu();
return $output;
}
$total_rows=$rs->RecordCount();
if (($total_rows % $item_per_page)==0)
$totalpage=$total_rows/$item_per_page;
else
$totalpage=(int)($total_rows/$item_per_page)+1;
$starti = $item_per_page * ($page - 1) + 1;
$endi = $starti + $item_per_page - 1;
//$rs->MoveFirst();
$rs->Move($starti - 1);
If ($page > 1)
{
 $page_num=$page-1;
    $output.= "<a href=".$PHP_SELF;
    $output.= $sn."?".$su."&menu=showquerylist&page=".$page_num."&item_per_page=".$item_per_page."&".$query_string;
    $output.= ">上一頁</a><br><br>";
}
If ((int)($page * $item_per_page) < $total_rows)
{
$notlast = true;
$output.= access_list($rs,$starti,$endi);
}
else
{
$output.= access_list($rs,$starti,$total_rows);
}
If ($notlast == true)
{
   $nextpage=$page+1;
   $output.= "<br><a href=".$PHP_SELF;
   $output.= $sn."?".$su."&menu=showquerylist&page=".$nextpage."&item_per_page=".$item_per_page."&".$query_string;
   $output.= ">下一頁</a><br><br>";
}
$output.=showmenu();
return $output;
}
//ends queryaction
function generate_form_header($descriptionString,$actionScript,$hiddenArray,$fieldsArray,$fieldsNum,$fieldsType)
{
    //global $output;
    global $sn,$su;
    global $fieldsData;
    $output.= "<h2>".$descriptionString."</h2>";
    $output.= "<form method=post action=".$sn."?".$su."&".$actionScript.">";
    //echo "<br>";
    //根據$fieldsArray,$fieldsNum and $fieldsType產生表單
    $keys=array_keys($fieldsArray);
    $numofelement=sizeof($keys);
    for ($i=0;$i<$numofelement;$i++)
    {
    //若$fieldsType為hidden的欄位表示要在輸出時show出,但是輸入時不顯示輸入欄位與相關標題
            if ($fieldsType[$keys[$i]]=='hidden')
            {
                    //do nothing
            }
            elseif ($fieldsType[$keys[$i]]=='option')
            {
            //由資料表中取出資料並產生選項表單,例如欄位英文名稱為type,對應的欄位格式為option
            //因此必須到資料表type取出其中欄位title,並且列出option的選單,這裡要決定type的值
            if($descriptionString=="新增表單" && $fieldsData[$keys[$i]]=='must'){$output.="<font color=red>*</font>";}
            // 2012 暫時關閉選項選單
            $output.= generate_option_menu($keys[$i],$fieldsArray[$keys[$i]]);
            }
            else
            {
            if($descriptionString=="新增表單" && $fieldsData[$keys[$i]]=='must'){$output.="<font color=red>*</font>";}
            $output.= $keys[$i].":<input type=".$fieldsType[$keys[$i]]." name=".$fieldsArray[$keys[$i]]." size=".$fieldsNum[$keys[$i]].">";
            $output.= "<br>";
            }
    }
    if ($hiddenArray)
    {
    $hiddenkeys=array_keys($hiddenArray);
    $numofhiddenfields=sizeof($hiddenkeys);
    }
    if($numofhiddenfields==0)
    {
    //什麼都不做
    }
    else
    {
    for ($i=0;$i<$numofhiddenfields;$i++)
    {
    //這裡還要根據不同的type列出不同的欄位,這裡僅先處理text,password與textarea等三種
    $output.= "<input type=hidden  name=".$hiddenkeys[$i]." value=".$hiddenArray[$hiddenkeys[$i]].">";
    }
    }
    //ends if
    return $output;
}

function updateactionmenu()
{
global $sn,$su;
global $fieldsArray,$fieldsNum,$fieldsType,$fieldsVar;
global $dbc,$connstr;
global $page,$item_per_page;
global $tablename;
//global $name,$no;
//global $query_string;
//必須根據$fieldsVar實質對應的值來拼湊出對應的$query_string
$keys=array_keys($fieldsArray);
$numofelement=sizeof($keys);
$count=0;
for($i=0;$i<$numofelement;$i++)
{
	if($fieldsVar[$fieldsArray[$keys[$i]]])
	{
	$count++;
		if($count==1)
		{
//第一個變數就查詢字串不需要有&
//就sql查詢的where後面字串則不需要有and(各變數的查詢交集)
		$query_string.=$fieldsArray[$keys[$i]]."=".$fieldsVar[$fieldsArray[$keys[$i]]];
		$after_where.=$fieldsArray[$keys[$i]]." like '%".$fieldsVar[$fieldsArray[$keys[$i]]]."%' ";
		}
		else
		{
//針對第一個有值的變數以後
		$after_where.=" and ".$fieldsArray[$keys[$i]]." like '%".$fieldsVar[$fieldsArray[$keys[$i]]]."%' ";
		$query_string.="&".$fieldsArray[$keys[$i]]."=".$fieldsVar[$fieldsArray[$keys[$i]]];
		}
	}
	else
	{
	//當沒有輸入查詢關鍵字時,該變數對應的字串使用空白
	$after_where.="";
	$query_string.="";
	}
}
//到這裡若$after_where仍為空白字串,則會安排列出全部的資料,以供使用者瀏覽
if($after_where=="" and $query_string=="")
{
		$after_where=$fieldsArray[$keys[0]]." like '%".$fieldsVar[$fieldsArray[$keys[0]]]."%' ";
		$query_string=$fieldsArray[$keys[0]]."=".$fieldsVar[$fieldsArray[$keys[0]]];
}
//$update_string="name=".$name;
//$connstr=access_connect();
//$sql="SELECT * FROM user where name like '%".$name. "%'";
$sql="SELECT * FROM ".$tablename." where ".$after_where;
$rs=access_query($sql,$connstr);
if (!isset($page))
{
$page=$_GET['page'];
}
else
{
$page=$_POST['page'];
}
if(!isset($item_per_page))
{
$item_per_page=$_GET['item_per_page'];
}
else
{
$item_per_page=$_POST['item_per_page'];
}
  if(!$page)
   {
    $page=1;
   }
  if (!$item_per_page)
   {
    $item_per_page=10;
   }
if ($rs->EOF)
{
$output.=showmenu();
$output.= "<br>";
$output.="查不到你要的資料";
return $output;
exit;
}
$total_rows=$rs->RecordCount();
if (($total_rows % $item_per_page)==0)
$totalpage=$total_rows/$item_per_page;
else
$totalpage=(int)($total_rows/$item_per_page)+1;
$starti = $item_per_page * ($page - 1) + 1;
$endi = $starti + $item_per_page - 1;
//$rs->MoveFirst();
$rs->Move($starti - 1);
If ($page > 1)
{
 $page_num=$page-1;
    $output.= "<a href=".$PHP_SELF;
    $output.= $sn."?".$su."&menu=showupdatelist&page=".$page_num."&item_per_page=".$item_per_page."&".$qyery_string;
    $output.= ">上一頁</a><br><br>";
}
If ((int)($page * $item_per_page) < $total_rows)
{
$notlast = true;
$output.=access_update_list($rs,$starti,$endi);
}
else
{
$output.=access_update_list($rs,$starti,$total_rows);
}
If ($notlast == true)
{
   $nextpage=$page+1;
   $output.= "<br><a href=".$PHP_SELF;
   $output.= $sn."?".$su."&menu=showupdatelist&page=".$nextpage."&item_per_page=".$item_per_page."&".$query_string;
   $output.= ">下一頁</a><br><br>";
}
$output.=showmenu();
return $output;
}
function updateaction()
{
global $sn,$su;
global $fieldsArray,$fieldsType;
global $tablename;
global $dbc,$connstr;
global $serialno;
//配合upload檔案的更新,將$folder,$upload,$superdat,$suuperdat_name與$superdat_size設為global
global $folder,$upload;
//global $superdat,$superdat_name,$superdat_size;
//$oldfilename是要更新資料前所對應的舊檔案名稱
global $oldfilename;
$keys_of_fields=array_keys($fieldsArray);
$numofelement2=sizeof($keys_of_fields);
if(!isset($serialno))
{
$serialno=$_POST['serialno'];
}
if(!$serialno)
{
$output.= "請選擇所要更新的項目";
return $output;
exit;
}
//echo "你選擇要更新的資料是第 ".$serialno."筆";
//$connstr=access_connect();
$sql="SELECT * FROM ".$tablename." where index2 = ".$serialno;
$rs=access_query($sql,$connstr);
//因為選擇要更新的資料只有一筆,所以由0算起
$rs->MoveFirst();
//開始組成要做更新的表單,這裡要加入更新附掛檔的表單,隨後的作用將要刪除舊檔並將新檔掛入
  $output.= "請利用以下表單完成資料更新<br>";
  $output.= "<form method=\"POST\" action=\"".$sn."?".$su."&menu=doupdate\" ENCTYPE=\"multipart/form-data\">";
  $output.= "<p>";
$mydata=array();
for ($j=0;$j<$numofelement2;$j++)
{
//若$fieldsType為hidden的欄位表示要在輸出時show出,但是輸入時不顯示輸入欄位與相關標題
	if ($fieldsType[$keys_of_fields[$j]]=='hidden')
	{
	$mydata[$j]=access_qresult($rs,0,$fieldsArray[$keys_of_fields[$j]]);
	//當type為hidden,只列出input不要列出欄位title
	$output.= "<input type=\"".$fieldsType[$keys_of_fields[$j]]."\" name=\"".$fieldsArray[$keys_of_fields[$j]]."\" value=\"".stripslashes(addslashes($mydata[$j]))."\"></p>";
	}
	else
	{
	$mydata[$j]=access_qresult($rs,0,$fieldsArray[$keys_of_fields[$j]]);
	$output.= $keys_of_fields[$j].":<input type=\"".$fieldsType[$keys_of_fields[$j]]."\" name=\"".$fieldsArray[$keys_of_fields[$j]]."\" value=\"".stripslashes(addslashes($mydata[$j]))."\"></p><BR>";
	}
}
//請注意,這裡由資料庫取出的許功蓋等字必須要加上slash再strip掉,所顯示的字才會正確??
//以下的表單是要更新資料所附掛的檔案
$output.= "更新檔案: <input type=\"file\" name=\"userfile\" size=20><br>\n";
//這裡要讓使用者選擇資料更新模式,請注意,資料更新模式只有在有更新檔案的情形下才會作用
//因此也許可以在此一選單後,針對有更新檔案的情形增加一級選單
$output.= "<br>";
$output.= "更新模式: <select name=rep_mod>\n";
//echo "<option selected>\n";
$output.= "<option selected value=\"replace\">取代\n";
$output.= "<option value=\"minor\">小改版\n";
$output.= "<option value=\"major\">大改版\n";
$output.= "</select>\n";
$output.= "<br>";
//$filedir=dirname(__FILE__);
//where we put the upload files
  //echo "<input type=\"hidden\" name=\"folder\" value=\"$filedir\">\n";
//為了要將舊的附掛檔刪除,這裡取得舊檔名,以hidden方式送到doupdate處理
$oldfilename=access_qresult($rs,0,'upload');
  $output.= "<input type=\"hidden\" name=\"oldfilename\" value=\"$oldfilename\">\n";
  $output.= "<p><input type=\"submit\" name=\"upload\" value=\"更新\">";
  $output.= "<input type=\"reset\" value=\"重新設定\" name=\"reset\"></p>";
  $output.= "</form>";
//$total_rows=access_num_rows($rs);
//echo $total_rows;
$output.=showmenu();
return $output;
}
function doupdate()
{
global $fieldsArray,$fieldsNum,$fieldsType,$fieldsVar;
global $tablename;
global $dbname;
global $dbc,$connstr;
//配合upload檔案的更新,將$folder,$upload,$superdat,$suuperdat_name與$superdat_size設為global
global $folder,$upload;
//global $superdat,$superdat_name,$superdat_size;
//$oldfilename是要更新資料前所對應的舊附掛檔案名稱,要掛新檔前,希望能將舊檔刪除,$folder為對應的系統目錄
//所以舊的檔案為$filedir\upload_files\$oldfilename
global $oldfilename;
global $rep_mod;
//$oldfilename=$_POST['oldfilename'];
//$rep_mod=$_POST['rep_mod'];
//global $name,$no,$index2;
//先判別有無superdat,先試試均會保留原舊資料的情形
//以所取的的欄位資料新增一筆
//####################################################################### start to add an additional data
//####################################################################### end to add an additional data
//先刪除舊的附掛檔,注意,當使用者只更新其它欄位資料時,原先所掛的檔會被莫名的刪掉,因此也許使用蓋檔而不先刪檔
$filedir=dirname(__FILE__);
//$command="del \"".$filedir."\\upload_files\\".$oldfilename."\" /F/Q";
//system($command,$result);
$keys=array_keys($fieldsArray);
$numofelement=sizeof($keys);
$seq=0;
for($i=0;$i<$numofelement;$i++)
{
${$fieldsArray[$keys[$i]]}=$fieldsVar[$fieldsArray[$keys[$i]]];
	if($fieldsType[$keys[$i]]!='hidden')
	{
	$seq++;
	//應該是組成資料的第一順位不要有逗號,因為第一欄位type有可能為hidden
	if($seq==1)
	{
	//這是根據取得的欄位,新增另一筆資料的部分
	$myfield.=$fieldsArray[$keys[$i]];
	//進行值的編碼,也就是處理#,與特殊的CMSimpl斷頁符號{[
	$inside_text=preg_replace("/(\#)/","#",$fieldsVar[$fieldsArray[$keys[$i]]]);
	$new_text1=preg_replace("/(\[)/","[",$inside_text);
	$new_text2=preg_replace("/(\])/","]",$new_text1);
	$new_text3=preg_replace("/(\{)/","{",$new_text2);
	$do_pound_encode=preg_replace("/(\})/","}",$new_text3);
	//以下為原先未編碼的程式
	//$myvalue.="'".$fieldsVar[$fieldsArray[$keys[$i]]]."'";
	$myvalue.="'".$do_pound_encode."'";
	//這是更新舊資料的部分
	//以下為原先未編碼的程式
	//$mystring.=$fieldsArray[$keys[$i]]."="."'".$fieldsVar[$fieldsArray[$keys[$i]]]."'";
	$mystring.=$fieldsArray[$keys[$i]]."="."'".$do_pound_encode."'";
	}
	else
	{
	//第二個起
	//這是根據取得的欄位,新增另一筆資料的部分
	$myfield.=",".$fieldsArray[$keys[$i]];
	//進行值的編碼,也就是處理#,與特殊的CMSimpl斷頁符號{[
	$inside_text=preg_replace("/(\#)/","#",$fieldsVar[$fieldsArray[$keys[$i]]]);
	$new_text1=preg_replace("/(\[)/","[",$inside_text);
	$new_text2=preg_replace("/(\])/","]",$new_text1);
	$new_text3=preg_replace("/(\{)/","{",$new_text2);
	$do_pound_encode=preg_replace("/(\})/","}",$new_text3);
	//以下為原先未編碼的程式
	//$myvalue.=",'".$fieldsVar[$fieldsArray[$keys[$i]]]."'";
	$myvalue.=",'".$do_pound_encode."'";
	//這是更新舊資料的部分
	//以下為原先未編碼的程式
	//$mystring.=",".$fieldsArray[$keys[$i]]."="."'".$fieldsVar[$fieldsArray[$keys[$i]]]."'";
	$mystring.=",".$fieldsArray[$keys[$i]]."="."'".$do_pound_encode."'";
	}
	}
//新增值的部分只針對欄位type不是hidden的部分,加以處理,也就是對於type為hidden的欄位不處理
}
//#########################################################################################################
//額外新增部分
$num_must=$numofelement;
$add_field_value=TRUE;
//由最前的變數算起,個數為$num_must,任一欄位為null就不被接受,印出欄位不可空白的警告
for($i=0;$i<$num_must;$i++)
{
//對於非hidden的欄位必須要有值否則$add_field_value將會設為FALSE,也就是會印出欄位不可空白的警告
//對於$fieldsData值為must且該欄位取值($fieldsArray)為null而且該欄位的類別($fieldsType不是hidden,就會列出"欄位不可空白的警告"
if(${$fieldsArray[$keys[$i]]}==null && $fieldsType[$keys[$i]]!='hidden'&&$fieldsData[$keys[$i]]=='must')
{
$add_field_value=FALSE;
}
}
$date=date("m-d-Y H:i:s");
// we need to get the version and follow
$sql="select version,follow from ".$tablename." where index2=$index2";
$rs=access_query($sql,$connstr);
$rs->MoveFirst();
$version=access_qresult($rs,0,"version");
$dbname_append=trim($dbname,".db");
$follow=access_qresult($rs,0,"follow");
if(!isset($rep_mod))
{
$rep_mod=$_POST['rep_mod'];
}
if ($rep_mod=='minor')
{
$version+=0.1;
}
elseif($rep_mod=='major')
{
$version=ceil($version);
}
if($rep_mod!='replace')
{
	//進入這裡表示為新增資料,且將就資料鎖定
	if($add_field_value)
	{
	//準備要運作的SQL字串
	//upload的附掛檔是option,因此若有$superdat_name表示有附檔,否則只是一般資料新增
	//將$date也視為系統自動取得並且新增至資料庫的特殊欄位,因此在pobs內要與$follow的處理方式一樣,不能用編碼取代
	//get the maxindex
	$sql="select index2 from ".$tablename." order by index2 DESC";
	//執行SQL
	$rs=access_query($sql,$connstr);
	if ($rs->EOF)
	{
	$maxnow=1;
	}
	else
	{
	$rs->MoveFirst();
	$maxnow=$rs->fields['index2'];
	//若真正資料新增,目前最大的序號+1後,就是此一新增資料的序號
	$maxnow=$maxnow+1;
	}
	//有上傳檔案
	if (isset($_FILES['userfile']))
	{
	//這裡針對有掛檔的輸入,將版次掛在上傳的檔名前面,無論minor或major更版,均是新增資料所以要copy $follow值
	$uploadfilename=$_FILES['userfile']['name'];
	$newfilename=$version."_".$dbname_append."_".$tablename."_".$maxnow."_".$uploadfilename;
	$uploadfilesize=$_FILES['userfile']['size'];
	//$sql="insert into user(name,pubno,follow,upload,upload_size) values ('$name','$pubno','$follow','$superdat_name','$superdat_size')";
	$sql="insert into ".$tablename."(".$myfield.",follow,upload,upload_size,mod_date,version) values (".$myvalue.",'$follow','$uploadfilename','$uploadfilesize','$date','$version')";
	}
	else
	{
	//以下為沒有掛檔的資料更新
	//$sql="insert into user(name,pubno,follow) values ('$name','$pubno','$follow')";
	$sql="insert into ".$tablename."(".$myfield.",follow,mod_date,version) values (".$myvalue.",'$follow','$date','$version')";
	}
	//執行SQL
	$rs=access_query($sql,$connstr);
	//處理資料上傳的部分
	$folder=dirname(__FILE__);
	$ext = ereg_replace("^.+\\.([^.]+)$", "\\1", $uploadfilename);
	//$finalfilename=$folder."/upload_files/".$version."_".$tablename."_".$maxnow."_".$uploadfilename;
	$finalfilename=$folder."/upload_files/".$version."_".$dbname_append."_".$tablename."_".$maxnow.".".$ext;
	$date=$_POST['date'];
	$msg = "";
	/*
	echo "file ext 為$ext<br>";
	echo "fname 為$fname<br>";
	echo "upload 的值為$upload<br>";
	echo "uploadfile為$uploadfilename<br>";
	echo "folder 的值為$folder<br>";
	echo "新增資料的時間為$date<br>";
    */
	if(isset($_FILES['userfile']))
	{
	if (@move_uploaded_file($_FILES['userfile']['tmp_name'],$finalfilename)) 
               {
               $msg = "檔案上傳已完成.";
               }
               else
               {
                $msg = "所指定的檔案無法上傳."; 
               }
	}
	else
	{ 
	$msg = "沒有指定上傳的檔案."; 
	}
	//結束處理資料上傳的部分
	//因為資料的更新過程中,將舊版的資料留在資料庫內($index),所以要將此一筆舊資料的status設為locked
		$sql="UPDATE ".$tablename." set status='locked' where index2=$index2";
		$rs=access_query($sql,$connstr);
	$output.= "舊版次的資料已經被鎖定,只能唯讀!<br>";
	$output.= "已新增一筆資料";
	$output.= "<br>";
	$output.=showmenu();
}
else
{
$output.= "欄位不可空白";
}
//以上完成非取代(not replace)模式,也就是改版模式(either minor or major)
//################################################################################################
}
//這裡是判斷$rep_mod,以上是非replace,以下為replace,也就是更改原資料中的欄位,並沒有新增一筆資料
else
{
$new_version=$version+0.1;
//upload的附掛檔更新是option,因此若有$superdat_name表示有附檔更新的需求,否則只是一般資料更新
//if ($superdat_name),upload為$superdat_name,upload_size為$superdat_size
//因為即使欄位更新時被改為空白,前面的欄位仍會有值,因此將upload與upload_size的資料庫update放在SQL字串後面
	if ($_FILES['userfile']['name']!="")
	{
	$uploadfilename=$_FILES['userfile']['name'];
	$uploadfilesize=$_FILES['userfile']['size'];
	$mystring.=",upload='".$uploadfilename."',upload_size='".$uploadfilesize."'";
	}
//$connstr=access_connect();
//這裡要根據set後面的字串,以泛用陣列拼出
//$sql="UPDATE user set name='$name',no='$no' where index2=$index2";
$sql="UPDATE ".$tablename." set ".$mystring.",mod_date='$date',version='$new_version' where index2=$index2";
$rs=access_query($sql,$connstr);
//處理資料上傳的部分,這裡是否要將舊的資料刪除(若是同一檔名則可蓋過,但若不同檔名則必須要刪去舊檔)
$msg = "";
//echo "upload file name為$uploadfilename<br>";
$upload=$_POST['upload'];
if(isset($upload) && ($_FILES['userfile']['name']!=""))
{
	$uploadfilename=$_FILES['userfile']['name'];
	$folder=dirname(__FILE__);
	$ext = ereg_replace("^.+\\.([^.]+)$", "\\1", $uploadfilename);
	$oldfiletitle=$version."_".$dbname_append."_".$tablename."_".$index2.".".$ext;
	$newfilename=$folder."/upload_files/".$new_version."_".$dbname_append."_".$tablename."_".$index2.".".$ext;
	if (@move_uploaded_file($_FILES['userfile']['tmp_name'],$newfilename)) 
        {
		//確定新檔案上傳完成後,刪除舊檔案
		$command="del \"".$filedir."\\upload_files\\".$oldfiletitle."\"";
		system($command,$result);
		$msg = "舊檔案已經刪除,<br>檔案上傳已完成.";
	}
              else
             { $msg = "所指定的檔案無法上傳."; }
}
else
{
	//get the oldfile extension
	$sql="select upload from ".$tablename." where index2=".$index2;
	//執行SQL
	$rs=access_query($sql,$connstr);
	$oldfilename=$rs->fields['upload'];
	//$version=$rs->fields['version'];
	$ext = ereg_replace("^.+\\.([^.]+)$", "\\1", $oldfilename);
	//當更新時沒有指定上傳檔案則將原有檔案改名為新的版次檔名,也就是版次+檔名 $version.$superdat_name
	$filedir=dirname(__FILE__);
	$oldfiletitle=$version."_".$dbname_append."_".$tablename."_".$index2.".".$ext;
	$newfiletitle=$new_version."_".$dbname_append."_".$tablename."_".$index2.".".$ext;
	//請注意,使用rename不能為新的檔案指定所在目錄,必須像以下使用原先的目錄,注意!注意!
	//有關CMSimple相關必須處理下列程式
	$command="rename \"".$filedir."\\upload_files\\".$oldfiletitle."\" \"".$newfiletitle."\"";
	system($command,$result);
	//echo $command;
//echo "<br>";
$msg = "沒有指定上傳的檔案."; 
}
//結束處理資料上傳的部分
/*
echo $uploadfilename;
echo "<br>";
echo $uploadfilesize;
echo "<br>";
*/
$output.= $msg;
$output.= "<br>";
//結束檔案上傳的處理
$output.= "資料已經更新!";
$output.= "<br>";
$output.=showmenu();
}
//this ends the $rep_mod cases, minor, major and replace
return $output;
}
//ends doupdate function
function deleteactionmenu()
{
global $sn,$su;
global $fieldsArray,$fieldsNum,$fieldsType,$fieldsVar;
global $tablename;
global $dbc,$connstr;
global $page,$item_per_page;
//global $name,$no;
//global $delete_string;
//必須根據$fieldsVar實質對應的值來拼湊出對應的$query_string
$keys=array_keys($fieldsArray);
$numofelement=sizeof($keys);
$count=0;
for($i=0;$i<$numofelement;$i++)
{
	if($fieldsVar[$fieldsArray[$keys[$i]]])
	{
	$count++;
		if($count==1)
		{
//第一個變數就查詢字串不需要有&
//就sql查詢的where後面字串則不需要有and(各變數的查詢交集)
		$query_string.=$fieldsArray[$keys[$i]]."=".$fieldsVar[$fieldsArray[$keys[$i]]];
		$after_where.=$fieldsArray[$keys[$i]]." like '%".$fieldsVar[$fieldsArray[$keys[$i]]]."%' ";
		}
		else
		{
//針對第一個有值的變數以後
		$after_where.=" and ".$fieldsArray[$keys[$i]]." like '%".$fieldsVar[$fieldsArray[$keys[$i]]]."%' ";
		$query_string.="&".$fieldsArray[$keys[$i]]."=".$fieldsVar[$fieldsArray[$keys[$i]]];
		}
	}
	else
	{
	//當沒有輸入查詢關鍵字時,該變數對應的字串使用空白
	$after_where.="";
	$query_string.="";
	}
}
//到這裡若$after_where仍為空白字串,則會安排列出全部的資料,以供使用者瀏覽
if($after_where=="" and $query_string=="")
{
		$after_where=$fieldsArray[$keys[0]]." like '%".$fieldsVar[$fieldsArray[$keys[0]]]."%' ";
		$query_string=$fieldsArray[$keys[0]]."=".$fieldsVar[$fieldsArray[$keys[0]]];
}
//$delete_string="name=".$name;
//$connstr=access_connect();
//$sql="SELECT * FROM user where name like '%".$name. "%'";
$sql="SELECT * FROM ".$tablename." where ".$after_where;
$rs=access_query($sql,$connstr);
if(!isset($page))
{
$page=$_GET['page'];
}
else
{
$page=$_POST['page'];
}
if(!isset($item_per_page))
{
$item_per_page=$_GET['item_per_page'];
}
else
{
$item_per_page=$_POST['item_per_page'];
}
  if(!$page)
   {
    $page=1;
   }
  if (!$item_per_page)
   {
    $item_per_page=10;
   }
if ($rs->EOF)
{
$output.=showmenu();
$output.= "<br>";
$output.="查不到你要的資料";
return $output;
exit;
}
$total_rows=$rs->RecordCount();
if (($total_rows % $item_per_page)==0)
$totalpage=$total_rows/$item_per_page;
else
$totalpage=(int)($total_rows/$item_per_page)+1;
$starti = $item_per_page * ($page - 1) + 1;
$endi = $starti + $item_per_page - 1;
//$rs->MoveFirst();
$rs->Move($starti - 1);
If ($page > 1)
{
 $page_num=$page-1;
    $output.= "<a href=".$PHP_SELF;
    $output.= $sn."?".$su."&menu=showdeletelist&page=".$page_num."&item_per_page=".$item_per_page."&".$query_string;
    $output.= ">上一頁</a><br><br>";
}
If ((int)($page * $item_per_page) < $total_rows)
{
$notlast = true;
$output.=access_delete_list($rs,$starti,$endi);
}
else
{
$output.=access_delete_list($rs,$starti,$total_rows);
}
If ($notlast == true)
{
   $nextpage=$page+1;
   $output.= "<br><a href=".$PHP_SELF;
   $output.= $sn."?".$su."&menu=showdeletelist&page=".$nextpage."&item_per_page=".$item_per_page."&".$query_string;
   $output.= ">下一頁</a><br><br>";
}
$output.=showmenu();
return $output;
}
function deleteaction()
{
global $sn,$su;
global $fieldsArray,$fieldsType;
global $tablename;
$keys_of_fields=array_keys($fieldsArray);
$numofelement2=sizeof($keys_of_fields);
global $dbc,$connstr;
global $serialno;
if(!isset($serialno))
{
$serialno=$_POST['serialno'];
}
if(!$serialno)
{
echo "請選擇所要刪除的項目";
exit;
}
//echo "你選擇要刪除的資料是第 ".$serialno."筆";
//$connstr=access_connect();
$sql="SELECT * FROM ".$tablename." where index2 = ".$serialno;
$rs=access_query($sql,$connstr);
//因為選擇要刪除的資料只有一筆,所以由0算起
$rs->MoveFirst();
//開始組成要做刪除的表單
 // echo "請利用以下表單完成資料刪除<br>";
//請注意是否要連upload_files內的對應檔也要刪除
  $output.= "<font color=\"#FF0000\">請注意! </font> 按下刪除鍵後 , 以下資料將被刪除<br>";
  $output.= "<form method=\"POST\" action=\"".$sn."?".$su."&menu=dodelete\">";
  $output.= "<p>";
$mydata=array();
for ($j=0;$j<$numofelement2;$j++)
{
//若$fieldsType為hidden的欄位表示要在輸出時show出,但是輸入時不顯示輸入欄位與相關標題
	if ($fieldsType[$keys_of_fields[$j]]=='hidden')
	{
	$mydata[$j]=access_qresult($rs,0,$fieldsArray[$keys_of_fields[$j]]);
	//當type為hidden,只列出input不要列出欄位title
	 $output.= "<input type=\"".$fieldsType[$keys_of_fields[$j]]."\" name=\"".$fieldsArray[$keys_of_fields[$j]]."\" value=\"".stripslashes(addslashes($mydata[$j]))."\"></p>";
	}
	else
	{
	$mydata[$j]=access_qresult($rs,0,$fieldsArray[$keys_of_fields[$j]]);
	//$output.= $keys_of_fields[$j].":<input type=\"".$fieldsType[$keys_of_fields[$j]]."\" name=\"".$fieldsArray[$keys_of_fields[$j]]."\" value=\"".stripslashes(addslashes($mydata[$j]))."\"></p>";
	$output.= $keys_of_fields[$j].": <font color=\"#0000FF\">".stripslashes(addslashes($mydata[$j]))."</font></p><BR>";
	}
}
//這裡是根據查詢資料列出所對應的檔案名稱,為配合檔案刪除,dodelete也要根據對應的index數,刪除對應的檔案
	$uploadfilename=access_qresult($rs,0,'upload');
	if ($uploadfilename)
	{
	$output.= "檔案 : <font color=\"#0000FF\">".stripslashes(addslashes($uploadfilename))."</font></p>";
	}
//請注意,這裡由資料庫取出的許功蓋等字必須要加上slash再strip掉,所顯示的字才會正確??
  $output.= "<p><input type=\"submit\" value=\"刪除\" name=\"B1\">";
  $output.= "<input type=\"reset\" value=\"重新設定\" name=\"B2\"></p>";
  $output.= "</form>";
  $output.=showmenu();
  return $output;
//$total_rows=access_num_rows($rs);
//echo $total_rows;
}
//ends deleteaction
function showquerylist()
{
global $sn,$su;
global $fieldsArray,$fieldsVar;
global $dbc,$connstr;
global $page,$item_per_page;
global $name;
global $tablename;
//global $query_string,$after_where;
//問題較大的是這裡根本沒有query_string與after_where
//這裡要將由表單所取得的變數轉過來,如何拼出該有的SQL
//######################################################################### start
//必須根據$fieldsVar實質對應的值來拼湊出對應的$query_string
$keys=array_keys($fieldsArray);
$numofelement=sizeof($keys);
$count=0;
for($i=0;$i<$numofelement;$i++)
{
	if($fieldsVar[$fieldsArray[$keys[$i]]])
	{
	$count++;
		if($count==1)
		{
//第一個變數就查詢字串不需要有&
//就sql查詢的where後面字串則不需要有and(各變數的查詢交集)
		$query_string.=$fieldsArray[$keys[$i]]."=".$fieldsVar[$fieldsArray[$keys[$i]]];
		$after_where.=$fieldsArray[$keys[$i]]." like '%".$fieldsVar[$fieldsArray[$keys[$i]]]."%' ";
		}
		else
		{
//針對第一個有值的變數以後
		$after_where.=" and ".$fieldsArray[$keys[$i]]." like '%".$fieldsVar[$fieldsArray[$keys[$i]]]."%' ";
		$query_string.="&".$fieldsArray[$keys[$i]]."=".$fieldsVar[$fieldsArray[$keys[$i]]];
		}
	}
	else
	{
	//當沒有輸入查詢關鍵字時,該變數對應的字串使用空白
	$after_where.="";
	$query_string.="";
	}
}
//到這裡若$after_where仍為空白字串,則會安排列出全部的資料,以供使用者瀏覽
if($after_where=="" and $query_string=="")
{
		$after_where=$fieldsArray[$keys[0]]." like '%".$fieldsVar[$fieldsArray[$keys[0]]]."%' ";
		$query_string=$fieldsArray[$keys[0]]."=".$fieldsVar[$fieldsArray[$keys[0]]];
}
//########################################################################ends
//$connstr=access_connect();
//$sql="SELECT * FROM ".$tablename." where name like '%".$name."%'";
$sql="SELECT * FROM ".$tablename." where ".$after_where;
$rs=access_query($sql,$connstr);
if (!isset($page))
{
$page=$_GET['page'];
}
if (!isset($item_per_page))
{
$item_per_page=$_GET['item_per_page'];
}
  if(!$page)
   {
    $page=1;
   }
  if (!$item_per_page)
   {
    $item_per_page=10;
   }
//所有資料筆數改由ADODB函式RecordCount取代
$total_rows=$rs->RecordCount();
if (($total_rows % $item_per_page)==0)
$totalpage=$total_rows/$item_per_page;
else
$totalpage=(int)($total_rows/$item_per_page)+1;
$starti = $item_per_page * ($page - 1) + 1;
$endi = $starti + $item_per_page - 1;
$rs->Move($starti - 1);
If ($page > 1)
{
 $page_num=$page-1;
    $output.= "<a href=".$PHP_SELF;
    $output.= $sn."?".$su."&menu=showquerylist&page=".$page_num."&item_per_page=".$item_per_page."&".$query_string;
    $output.= ">上一頁</a><br><br>";
}
If ((int)($page * $item_per_page) < $total_rows)
{
$notlast = true;
$output.= access_list($rs,$starti,$endi);
}
else
{
$output.= access_list($rs,$starti,$total_rows);
}
If ($notlast == true)
{
   $nextpage=$page+1;
   $output.= "<br><a href=".$PHP_SELF;
   $output.= $sn."?".$su."&menu=showquerylist&page=".$nextpage."&item_per_page=".$item_per_page."&".$query_string;
   $output.= ">下一頁</a><br><br>";
}
$output.= showmenu();
return $output;
}
//ends showquerylist
function showupdatelist()
{
global $sn,$su;
global $fieldsArray,$fieldsVar;
global $dbc,$connstr;
global $page,$item_per_page;
global $name;
global $tablename;
//######################################################################### start
//必須根據$fieldsVar實質對應的值來拼湊出對應的$query_string
$keys=array_keys($fieldsArray);
$numofelement=sizeof($keys);
$count=0;
for($i=0;$i<$numofelement;$i++)
{
	if($fieldsVar[$fieldsArray[$keys[$i]]])
	{
	$count++;
		if($count==1)
		{
//第一個變數就查詢字串不需要有&
//就sql查詢的where後面字串則不需要有and(各變數的查詢交集)
		$query_string.=$fieldsArray[$keys[$i]]."=".$fieldsVar[$fieldsArray[$keys[$i]]];
		$after_where.=$fieldsArray[$keys[$i]]." like '%".$fieldsVar[$fieldsArray[$keys[$i]]]."%' ";
		}
		else
		{
//針對第一個有值的變數以後
		$after_where.=" and ".$fieldsArray[$keys[$i]]." like '%".$fieldsVar[$fieldsArray[$keys[$i]]]."%' ";
		$query_string.="&".$fieldsArray[$keys[$i]]."=".$fieldsVar[$fieldsArray[$keys[$i]]];
		}
	}
	else
	{
	//當沒有輸入查詢關鍵字時,該變數對應的字串使用空白
	$after_where.="";
	$query_string.="";
	}
}
//到這裡若$after_where仍為空白字串,則會安排列出全部的資料,以供使用者瀏覽
if($after_where=="" and $query_string=="")
{
		$after_where=$fieldsArray[$keys[0]]." like '%".$fieldsVar[$fieldsArray[$keys[0]]]."%' ";
		$query_string=$fieldsArray[$keys[0]]."=".$fieldsVar[$fieldsArray[$keys[0]]];
}
//########################################################################ends
//$connstr=access_connect();
//$sql="SELECT * FROM ".$tablename." where name like '%".$name."%'";
$sql="SELECT * FROM ".$tablename." where ".$after_where;
$rs=access_query($sql,$connstr);
if (!isset($page))
{
$page=$_GET['page'];
}
if (!isset($item_per_page))
{
$item_per_page=$_GET['item_per_page'];
}
  if(!$page)
   {
    $page=1;
   }
  if (!$item_per_page)
   {
    $item_per_page=10;
   }
$total_rows=$rs->RecordCount();
if (($total_rows % $item_per_page)==0)
$totalpage=$total_rows/$item_per_page;
else
$totalpage=(int)($total_rows/$item_per_page)+1;
$starti = $item_per_page * ($page - 1) + 1;
$endi = $starti + $item_per_page - 1;
//$rs->MoveFirst();
$rs->Move($starti - 1);
If ($page > 1)
{
 $page_num=$page-1;
    $output.= "<a href=".$PHP_SELF;
    $output.= $sn."?".$su."&menu=showupdatelist&page=".$page_num."&item_per_page=".$item_per_page."&".$query_string;
    $output.= ">上一頁</a><br><br>";
}
If ((int)($page * $item_per_page) < $total_rows)
{
$notlast = true;
$output.= access_update_list($rs,$starti,$endi);
}
else
{
$output.= access_update_list($rs,$starti,$total_rows);
}
If ($notlast == true)
{
   $nextpage=$page+1;
   $output.= "<br><a href=".$PHP_SELF;
   $output.= $sn."?".$su."&menu=showupdatelist&page=".$nextpage."&item_per_page=".$item_per_page."&".$query_string;
   $output.= ">下一頁</a><br><br>";
}
$output.=showmenu();
return $output;
}
function showdeletelist()
{
global $sn,$su;
global $fieldsArray,$fieldsVar;
global $dbc,$connstr;
global $page,$item_per_page;
global $name;
global $tablename;
//######################################################################### start
//必須根據$fieldsVar實質對應的值來拼湊出對應的$query_string
$keys=array_keys($fieldsArray);
$numofelement=sizeof($keys);
$count=0;
for($i=0;$i<$numofelement;$i++)
{
	if($fieldsVar[$fieldsArray[$keys[$i]]])
	{
	$count++;
		if($count==1)
		{
//第一個變數就查詢字串不需要有&
//就sql查詢的where後面字串則不需要有and(各變數的查詢交集)
		$query_string.=$fieldsArray[$keys[$i]]."=".$fieldsVar[$fieldsArray[$keys[$i]]];
		$after_where.=$fieldsArray[$keys[$i]]." like '%".$fieldsVar[$fieldsArray[$keys[$i]]]."%' ";
		}
		else
		{
//針對第一個有值的變數以後
		$after_where.=" and ".$fieldsArray[$keys[$i]]." like '%".$fieldsVar[$fieldsArray[$keys[$i]]]."%' ";
		$query_string.="&".$fieldsArray[$keys[$i]]."=".$fieldsVar[$fieldsArray[$keys[$i]]];
		}
	}
	else
	{
	//當沒有輸入查詢關鍵字時,該變數對應的字串使用空白
	$after_where.="";
	$query_string.="";
	}
}
//到這裡若$after_where仍為空白字串,則會安排列出全部的資料,以供使用者瀏覽
if($after_where=="" and $query_string=="")
{
		$after_where=$fieldsArray[$keys[0]]." like '%".$fieldsVar[$fieldsArray[$keys[0]]]."%' ";
		$query_string=$fieldsArray[$keys[0]]."=".$fieldsVar[$fieldsArray[$keys[0]]];
}
//######################################################################## ends
//$connstr=access_connect();
//$sql="SELECT * FROM ".$tablename." where name like '%".$name."%'";
$sql="SELECT * FROM ".$tablename." where ".$after_where;
$rs=access_query($sql,$connstr);
if (!isset($page))
{
$page=$_GET['page'];
}
if(!isset($item_per_page))
{
$item_per_page=$_GET['item_per_page'];
}
  if(!$page)
   {
    $page=1;
   }
  if (!$item_per_page)
   {
    $item_per_page=10;
   }
$total_rows=$rs->RecordCount();
//沒有資料時送出錯誤訊息
	if ($total_rows==0)
	{
	$output.= ("沒有資料");
	$output.= ("<br><br>");
	$output.= showmenu();
	exit();
	}
if (($total_rows % $item_per_page)==0)
$totalpage=$total_rows/$item_per_page;
else
$totalpage=(int)($total_rows/$item_per_page)+1;
$starti = $item_per_page * ($page - 1) + 1;
$endi = $starti + $item_per_page - 1;
//$rs->MoveFirst();
$rs->Move($starti - 1);
If ($page > 1)
{
 $page_num=$page-1;
    $output.= "<a href=".$PHP_SELF;
    $output.= $sn."?".$su."&menu=showdeletelist&page=".$page_num."&item_per_page=".$item_per_page."&".$query_string;
    $output.= ">上一頁</a><br><br>";
}
If ((int)($page * $item_per_page) < $total_rows)
{
$notlast = true;
$output.=access_delete_list($rs,$starti,$endi);
}
else
{
$output.=access_delete_list($rs,$starti,$total_rows);
}
If ($notlast == true)
{
   $nextpage=$page+1;
   $output.= "<br><a href=".$PHP_SELF;
   $output.= $sn."?".$su."&menu=showdeletelist&page=".$nextpage."&item_per_page=".$item_per_page."&".$query_string;
   $output.= ">下一頁</a><br><br>";
}
$output.=showmenu();
return $output;
}
//ends show_delete_list
function generate_form_footer()
{
$output.= "<br>";
$output.= "<input type=submit value=\"送出\">";
$output.= "<input type=reset value=\"重寫\">";
$output.= "</form>";
return $output;
}
function generate_option_menu($title,$field)
{
    global $connstr;
    global $tablename;
    //由資料表$field的內容印出option表單,變數中文名稱是$title,欄位英文名稱為$field
    //查得資料筆數,若所對應的資料表內沒有資料則列出錯誤訊息
    //將各筆資料拼成所要的option表單
    // 2012
    //$sql="SELECT * FROM ".$field;
    //$rs=access_query($sql,$connstr);
    $all_data = R::find("type","1 order by id");
    $total_rows = count($all_data);
    //if ($rs->EOF)
    if($total_rows==0)
    {
        $output=showmenu();
        $output.= "<br>";
        exit("錯誤!資料庫中的".$field."資料表沒有資料");
    }
    //$total_rows=$rs->RecordCount();
    //將資料一筆一筆列出來
    //$rs->MoveFirst();
    $output.= $title.":<select name=".$field.">\n";
    $output.= "<option selected>\n";
            //for ($i=0;$i<$total_rows;$i++)
    // 設法選擇第一筆資料作為內定
    $order = 1;
            foreach ($all_data as $data)
            {
            //$fvalue=access_qresult($all_data,$i,$field);
                if($order == 1)
                {
                    $output.= "<option value=".$data->title." selected>".$data->title."\n";
            //$rs->MoveNext();
                }
                else
                {
                    $output.= "<option value=".$data->title.">".$data->title."\n";
                }
              $order++;
            }
    $output.= "</select>\n";
    $output.= "<br>";
    return $output;
    //ends generate_option_menu
}
//親自執行資料新增的動作
function addaction()
{
global $bean;

//$dbname是要用來附加在上傳檔, 以便區分上傳檔與存檔資料庫之間的關係
global $dbname;
//將傳值的矩陣設為global
global $fieldsArray,$fieldsNum,$fieldsType,$fieldsVar;
//這裡新增的全域$fieldsData,針對各欄位變數有auto,must與option 等三種情況,目前auto不使用,must表該變數一定要有資料
//$fieldsData值為option之變數則允許有值或不輸入
global $fieldsData;
//這裡照理說應該要針對$hiddenArray取hidden field內所有的值
//但是這裡有用的hidden field有 $follow,$folder與$upload,所以將這些變數設為global,下一階段再考慮要如何改
global $follow,$folder,$upload,$url;
//請注意,這裡的$follow 並沒有取到該有的值,所謂的根為0,其餘則各隨其主
global $hiddenArray;
$follow=$_POST['follow'];
//以上是取得非經由正式矩陣型式自動加入系統的變數
// 對於新增資料而言其follow的欄位值為0表示為各資料樹的源頭
//global $name,$no,$follow,$folder,$upload;
//global $superdat,$superdat_name,$superdat_size;
global $dbc,$connstr;
global $tablename;
$dbname_append=trim($dbname,".db");
//有關時間的from 及 to
//global $from_year,$to_year,$from_month,$to_month,$from_day,$to_day,$from_hour,$to_hour;
//取得表單內的各欄位變數值
$keys=array_keys($fieldsArray);
$numofelement=sizeof($keys);
$seq=0;
for($i=0;$i<$numofelement;$i++)
{
//這裡是將變數所對應的值設定為$fieldsArray所放的value
//請特別注意到${變數名稱}的用法
//請注意,這裡要處理#CMSimple語法中的#代換,希望將#換成網路符號
//$new_text1=preg_replace("/(\#)/","#",$text);
//主要是利用以下的函式進行#的編碼,找到#就是#
/*
function chars_encode($string, $encodeAll = false)
{
   // declare variables
   $chars = array();
   $ent = null;
   // split string into array
   $chars = preg_split("//", $string, -1, PREG_SPLIT_NO_EMPTY);
   // encode each character
   for ( $i = 0; $i < count($chars); $i++ )
   {
     if ( preg_match("/^(\w| )$/",$chars[$i]) && $encodeAll == false )
         $ent[$i] = $chars[$i];
     else
         $ent[$i] = "&#" . ord($chars[$i]) . ";";
   }
   if ( sizeof($ent) < 1)
     return "";
   return implode("",$ent);
}
*/
${$fieldsArray[$keys[$i]]}=$fieldsVar[$fieldsArray[$keys[$i]]];
//以下是針對要新增資料的SQL語法,分為$myfield與$myvalue兩個部分,其中也分為前頭與後面尾隨資料拼湊的差異(前有逗點與無逗點)
	if($fieldsType[$keys[$i]]!='hidden')
	{
    $seq++;
    //應該是組成資料的第一順位不要有逗號,因為第一欄位type有可能為hidden
    if($seq==1)
    {
    $myfield.=$fieldsArray[$keys[$i]];
    //進行值的編碼,也就是處理#,與CMSimple特殊斷頁符號
    $inside_text=preg_replace("/(\#)/","#",$fieldsVar[$fieldsArray[$keys[$i]]]);
    $new_text1=preg_replace("/(\[)/","[",$inside_text);
    $new_text2=preg_replace("/(\])/","]",$new_text1);
    $new_text3=preg_replace("/(\{)/","{",$new_text2);
    $do_pound_encode=preg_replace("/(\})/","}",$new_text3);
    //以下為原先未編碼的程式
    //$myvalue.="'".$fieldsVar[$fieldsArray[$keys[$i]]]."'";
    $myvalue.="'".$do_pound_encode."'";
    }
    else
    {
    //第二個起
    $myfield.=",".$fieldsArray[$keys[$i]];
    //進行值的編碼,也就是處理#,與CMSimple特殊斷頁符號
    $inside_text=preg_replace("/(\#)/","#",$fieldsVar[$fieldsArray[$keys[$i]]]);
    $new_text1=preg_replace("/(\[)/","[",$inside_text);
    $new_text2=preg_replace("/(\])/","]",$new_text1);
    $new_text3=preg_replace("/(\{)/","{",$new_text2);
    $do_pound_encode=preg_replace("/(\})/","}",$new_text3);
    //以下為原先未編碼的程式
    //$myvalue.=",'".$fieldsVar[$fieldsArray[$keys[$i]]]."'";
    $myvalue.=",'".$do_pound_encode."'";
    }
    
    //2012 $bean 的資料新增, 必須放在這裡, 請注意! 這裡只有 setup 宣告的欄位, 還沒有其他制式欄位附加
    $bean->$fieldsArray[$keys[$i]] = $fieldsVar[$fieldsArray[$keys[$i]]];
	}
    // 2012 必須加上其他制式欄位 (follow,upload,upload_size,mod_date,version,url 等 6 個欄位), 之後再 store
    //$id = R::store($bean);
    
//新增值的部分只針對欄位type不是hidden的部分,加以處理,也就是對於type為hidden的欄位不處理
}
//這裡的欄位判斷與資料新增必須要重新layout
//這裡對於資料掛檔的部分倒是可以保留原樣,只要將其他欄位的部分弄成泛用即可
//這裡必須要針對有哪些欄位必須是要有值才允許新增資料,至於其他非必要的欄位則可以不須有值
$num_must=$numofelement;
//若是所有欄位都要有值才可新增,那麼$num_must=$numofelement
//一開始欄位判斷值為真
$add_field_value=TRUE;
//由最前的變數算起,個數為$num_must,任一欄位為null就不被接受,印出欄位不可空白的警告
for($i=0;$i<$num_must;$i++)
{
//對於非hidden的欄位必須要有值否則$add_field_value將會設為FALSE,也就是會印出欄位不可空白的警告
//對於$fieldsData值為must且該欄位取值($fieldsArray)為null而且該欄位的類別($fieldsType不是hidden,就會列出"欄位不可空白的警告"
if(${$fieldsArray[$keys[$i]]}==null && $fieldsType[$keys[$i]]!='hidden'&&$fieldsData[$keys[$i]]=='must')
{
$add_field_value=FALSE;
}
}
//通過新增欄位有值測試的(該有值的欄位都有值),才可以新增資料
//資料要新增時取得當時的時間
$date=date("m-d-Y H:i:s");
/* 2012 修改
//get the maxindex
$sql="select index2 from ".$tablename." order by index2 DESC";
//執行SQL
$rs=access_query($sql,$connstr);
if ($rs->EOF)
{
$maxnow=1;
}
else
{
$rs->MoveFirst();
$maxnow=$rs->fields['index2'];
//若真正資料新增,目前最大的序號+1後,就是此一新增資料的序號
$maxnow=$maxnow+1;
}
2012 修改*/
//新增資料,其版次由0.1開始
$version='0.1';
if($add_field_value)
//if ($name and $pubno)
{
  //準備要運作的SQL字串
  //upload的附掛檔是option,因此若有$superdat_name表示有附檔,否則只是一般資料新增
  //將$date也視為系統自動取得並且新增至資料庫的特殊欄位,因此在pobs內要與$follow的處理方式一樣,不能用編碼取代
  //請注意, Yen 20060325 為了要弄成交卷用的系統,因此只允許以下的相關圖檔
  if (isset($_FILES['userfile']) && eregi('(\.jpg|\.png|\.htm|\.swf|\.pps|\.zip|\.pdf|\.wnk|\.prt|\.wmv|\.mp3|\.wma|\.mov)',$_FILES['userfile']['name']))
  {
  $uploadfilename=$_FILES['userfile']['name'];
  // 這裡是否改為唯一 unique id, 而且不要綁入附檔案名稱??可能比較安全??
  $newfilename=$version."_".$dbname_append."_".$tablename."_".$maxnow."_".$uploadfilename;
  $uploadfilesize=$_FILES['userfile']['size'];
  //這裡針對有掛檔的輸入,將版次掛在上傳的檔名前面
  //$insert_userfile_name=$version."_".$uploadfilename;
  //$sql="insert into user(name,pubno,follow,upload,upload_size) values ('$name','$pubno','$follow','$superdat_name','$superdat_size')";
  // 2012 修改 $sql="insert into ".$tablename."(".$myfield.",follow,upload,upload_size,mod_date,version,url) values (".$myvalue.",'$follow','$uploadfilename','$uploadfilesize','$date','$version','$url')";
// 這裡有一個兩難的情況, 也就是 $maxnow 也就是 $id, 但是在未存入資料庫前是否可以知道這個數值
  // 2012 以上為有附掛上傳檔案的情況
  $bean->follow = $follow;
  $bean->upload = $uploadfilename;
  $bean->upload_size = $uploadfilesize;
  $bean->mod_date = $date;
  $bean->version = $version;
  $bean->url = $url;
  }
  else
  {
  // 2012 以下則屬無附掛上傳檔案的情況
  $bean->follow = $follow;
  $bean->mod_date = $date;
  $bean->version = $version;
  $bean->url = $url;

  //$sql="insert into user(name,pubno,follow) values ('$name','$pubno','$follow')";
  //2012 修改 $sql="insert into ".$tablename."(".$myfield.",follow,mod_date,version,url) values (".$myvalue.",'$follow','$date','$version','$url')";
  }
// 2012
$id = R::store($bean);

//執行SQL
//2012 修改 $rs=access_query($sql,$connstr);
// 2012 以下在處理檔案上傳的實際存檔流程 (取得版次與實際檔案名稱, 然後存檔)

//此地要決定是直接在資料緒新增時的最前端,就加入上傳附檔或
//等利用serialno作為辨識序號以回覆資料時才加入上傳附檔的連結  2003/02/26
//若要在此上傳,則上傳的menu必須放在addmenu,並且在新增(addaction)的時候就要,上傳至特定位置,並將
//連結的檔名連同SQL insert加到資料庫中
$folder=dirname(__FILE__);
$ext = ereg_replace("^.+\\.([^.]+)$", "\\1", $uploadfilename);
//$finalfilename=$folder."/upload_files/".$version."_".$tablename."_".$maxnow."_".$uploadfilename;
$finalfilename=$folder."/upload_files/".$version."_".$dbname_append."_".$tablename."_".$maxnow.".".$ext;
$date=$_POST['date'];
$fname=$_POST['fname'];
$msg = "";
if(isset($_FILES['userfile']))
{
	if (@move_uploaded_file($_FILES['userfile']['tmp_name'],$finalfilename)) 
               {
               $msg = "檔案上傳已完成.";
               //$shortnewfilename=$version."_".$tablename."_".$maxnow."_".$uploadfilename;
		}
              else
             { $msg = "所指定的檔案無法上傳."; }
} else { $msg = "沒有指定上傳的檔案."; }
//結束處理資料上傳的部分
$output.= "<br>";
$output.="folder為".$folder;
$output.="<br>";
$output.= $msg;
$output.= "<br>";
$output.= "已新增一筆資料";
$output.= "<br>";
$output.= showmenu();
}
else
{
$output.= "欄位不可空白";
}
return $output;
}
//ends addaction
//因為CMSimple主系統內已經有一個download(),所以這裡下載的函式不可以命為download()
function pdmdownload()
{
global $index2;
global $dbc,$connstr;
global $tablename;
global $dbname;
$sql="select upload,version from ".$tablename." where index2=$index2";
//經由連線資料進行SQL運作
$rs=access_query($sql,$connstr);
//因為選擇要更新的資料只有一筆,所以由0算起
$rs->MoveFirst();
//這是 UTF-8 的上傳檔名資料
$upload_file_name=access_qresult($rs,0,"upload");
$version=access_qresult($rs,0,"version");
  $file_name=$upload_file_name;
  //假如希望各種語系的內容,也要擷取相同的plugins程式內容,就必須要確定是主程式,或是在語系目錄中
  //請注意,這裡不能直接將$path['folder']['base']設為全域變數,否則整個CMSimple都會無法執行(目前原因不明!)
  if (@is_dir('./cmsimple/')) $pth['folder']['base']='./'; else $pth['folder']['base']='./../';
  $file_dir =$pth['folder']['base']."plugins/".$tablename."/upload_files/";
  $ext = ereg_replace("^.+\\.([^.]+)$", "\\1", $upload_file_name);
  $dbname_append=trim($dbname,".db");
  $filepath=$file_dir.$version."_".$dbname_append."_".$tablename."_".$index2.".".$ext;
  $actualfilename=$version."_".$dbname_append."_".$tablename."_".$index2.".".$ext;
  //在這裡可能要將送出的資料先將output buffer clean一下,再送出純粹的數位資料
  ob_clean();
  //直接使用CMSimple cms.php程式中的下載程式
  //download($file_dir.yenbasename($actualfilename));
yendownload($filepath,yenbasename($actualfilename),$upload_file_name);
}
//結束 pdmdownload
function yendownload($fullpath,$savedfilename,$upload_file_name)
{
//for browser detection
$a_browser_data = browser_detection2('full');
$upload_file_name_big5=iconv("utf-8","big-5",$upload_file_name);
if (substr(php_uname(), 0, 7) == "Windows")
{
	$filename = $upload_file_name_big5;
}
else
{
	$filename = $upload_file_name;
}
//這個指令非常重要,否則在sqlite傳檔會產生無謂的標頭
   ob_end_clean();
if (!is_file($fullpath) or connection_status()!=0) return(FALSE);
$mime=get_mimetype($savedfilename);
$filesize=(string)filesize($fullpath);
  ob_end_clean();
  header('Content-Type: application/save-as');
if ($a_browser_data[0] != 'ie' )
{
	header('Content-Disposition: attachment; filename="'.$upload_file_name.'"');
	header('Content-Length:'.filesize($upload_file_name));
}
else 
// if it is msie, that is
{
	if ( $a_browser_data[1] >= 5 )
	{
           //在 linux,若加上下面的stripslashes(),許功蓋反而無法下載
           /*
	     if(ini_get('magic_quotes_gpc')=="1")
            {
	       //要將php自動增加在許功蓋上的反斜線去除
	       	$big5_filename=stripslashes($upload_file_name_big5);
            }
            else
            {
	       //不需要去掉許功蓋等字的反斜線
                }
           */
	header('Content-Disposition: attachment; filename="'.$upload_file_name_big5.'"');
	header('Content-Length:'.filesize($upload_file_name_big5));
	}
}
  //header('Content-Disposition: attachment; filename="'.$filename.'"');
  header("Content-Type: application/octet-stream; "); 
  header("Content-Transfer-Encoding: binary"); 
  //header("Content-Length: ".$filesize);
 @readfile($fullpath);
/*
echo $fullpath;
echo "<br>";
echo $filename;
echo "<br>";
echo $filesize;
echo "<br>";
echo $mime;
*/
exit;
}
function get_mimetype($filename)
{
 global $MIMEtypes;
 reset($MIMEtypes);
 $extension = strtolower(substr(strrchr($filename, "."),1));
 if ($extension == "")
  return "application/octet-stream";
  //return "Unknown/Unknown";
 while (list($mimetype, $file_extensions) = each($MIMEtypes))
  foreach (explode(" ", $file_extensions) as $file_extension)
   if ($extension == $file_extension)
    return $mimetype;
 return "Unknown/Unknown";
}
function showlayerlist()
{
global $sn,$su;
global $dbc,$connstr;
global $page,$item_per_page;
global $tablename;
//最前方show出表單
$output.=showmenu();
$output.= "<br><br>";
//$connstr=access_connect();
$sql="SELECT * FROM ".$tablename." where follow = '0'";
$rs=access_query($sql,$connstr);
  if(!$page)
   {
    $page=1;
   }
  if (!$item_per_page)
   {
    $item_per_page=10;
   }
$total_rows=$rs->RecordCount();
// 當資料庫內沒有資料的情況在 $rs=access_query($sql,$connstr)的函式內就已經處理
if (($total_rows % $item_per_page)==0)
$totalpage=$total_rows/$item_per_page;
else
$totalpage=(int)($total_rows/$item_per_page)+1;
$starti = $item_per_page * ($page - 1) + 1;
$endi = $starti + $item_per_page - 1;
//$rs->MoveFirst();
$rs->Move($starti - 1);
If ($page > 1)
{
 $page_num=$page-1;
    $output.= "<a href=".$PHP_SELF;
    $output.= $sn."?".$su."&menu=showlayerlist&page=".$page_num."&item_per_page=".$item_per_page;
    $output.= ">上一頁</a><br><br>";
}
If ((int)($page * $item_per_page) < $total_rows)
{
$notlast = true;
$output.= access_layer_list($rs,$starti,$endi);
}
else
{
$output.= access_layer_list($rs,$starti,$total_rows);
}
If ($notlast == true)
{
   $nextpage=$page+1;
   $output.= "<br><a href=".$PHP_SELF;
   $output.= $sn."?".$su."&menu=showlayerlist&page=".$nextpage."&item_per_page=".$item_per_page;
   $output.= ">下一頁</a><br><br>";
}
$output.= showmenu();
return $output;
}
//ends show_layer_list
function access_layer_list($result,$from,$to)
{
global $sn,$su;
global $fieldsArray;
global $tablename,$dbname;
$keys_of_fields=array_keys($fieldsArray);
/*
$keys=array_keys($result->fields);
$numofelement=sizeof($keys);
*/
$numofelement2=sizeof($keys_of_fields);
$result->MoveFirst();
$output.= "<ul>";
//必須要由$result得知資料的各欄位名稱
for ($i=0;$i<$numofelement2;$i++)
{
$output.= $keys_of_fields[$i];
$output.= " | ";
}
//############################################################## 取檔欄位
//echo "檔案";
$output.= "</ul>";
$output.= "<ul>";
/*
$result->MoveFirst();
echo "<ul>";
echo "序號 | 姓名 | 號碼";
echo "</ul>";
echo "<ul>";
*/
for ($i=$from-1;$i<$to;$i++)
{
$output.= "<li>";
$result->Move($i);
for ($j=0;$j<$numofelement2;$j++)
{
$output.= access_result($result,$i,$fieldsArray[$keys_of_fields[$j]]);
$output.= " | ";
}
/*
echo "<li>";
$result->Move($i);
access_result($result,$i,"index2");
echo " | ";
$serialno=access_qresult($result,$i,"index2");
//access_result會直接將查詢結果印出,access_qresult則會return變數
access_result($result,$i,"name");
// here yen want to implement upload function corresponding to the serialno
// yen want to use the regular input form to upload files,therefore yen blocked 
// the following line
//echo "</a>�<a href=upload?index2=$serialno>上載資料</a>";
echo " | ";
access_result($result,$i,"no");
echo " | ";
*/
$serialno=access_qresult($result,$i,"index2");
$output.= "<a href=".$sn."?".$su."&menu=addfollowupmenu&index2=$serialno>";
$output.= "<img src=graphics/addfile.gif border=0 alt=\"新增下屬資料\">";
$output.= "</a>";
//#############################################################開始簽入或簽出的link與icon
//這裡要show出checkin 或checkout,因為checkin是更新,checkout則將資料更新權關閉(擁有修改權,簽出後更新資料後再上傳)
//這裡必須要檢視資料欄位,若status為locked則show出-簽入,若status欄位值為open或空白,則show出-簽出
$status=access_qresult($result,$i,'status');
if ($status=='locked')
{
//這裡若直接將簽入導到updateactionmenu的選單,目前checkin函式並沒有用到
//請注意,這裡讓updateaction直接接受serialno,會造成資料的安全問題,請注意!最後要有安全機制的考量
$output.= "<a href=\"?menu=checkin&index2=".$indexno."\"><img src=graphics/locked.gif border=\"0\" alt=\"簽入\"></a>";
}
else
{
$output.= "<a href=\"?menu=checkout&index2=".$indexno."\"><img src=graphics/unlocked.gif border=\"0\" alt=\"簽出\"></a>";
}
//############################################################# 結束簽入或簽出的link與icon
//echo "<img src=graphics/edit.jpg border=0 alt=\"編輯\">";
//檢查是否該筆資料有掛檔
//here we want to check if any file linked
if (access_qresult($result,$i,"upload"))
{
$upload_file_name=access_qresult($result,$i,"upload");
$dbname_append=trim($dbname,".db");
$ext = ereg_replace("^.+\\.([^.]+)$", "\\1", $upload_file_name);
$version=access_qresult($result,$i,"version");
	//只有圖形檔有preview功能,接受.jpg,.png與.gif檔
	if (eregi('(\.jpg|\.png|\.gif)',$upload_file_name))
	{
	$output.= "<a href=\"".$sn."?".$su."&menu=pdmdownload&index2=".$serialno."\" target=\"_blank\" onmouseout=hide() onmouseover=\"show(event,'<img src=plugins/pdm/upload_files/".$version."_".$dbname_append."_".$tablename."_".$serialno.".".$ext.">')\">";
	$output.= "<img src=graphics/attachment.jpg border=0 alt=$upload_file_name>";
	$output.= "</a>";
	}
	else
	{
	$output.= "<a href=\"?menu=pdmdownload&index2=$serialno\" target=\"_blank\">";
	$output.= "<img src=graphics/attachment.jpg border=0 alt=$upload_file_name>";
	//echo "$upload_file_name";
	$output.= "</a>";	
	}
}
else
{
//沒有夾檔
}
//看看有無url連結資料
$url=access_qresult($result,$i,"url");
if ($url)
{
	$output.= "<a href=\"".$url."\" target=\"_blank\">";
	$output.= "<img src=graphics/link.gif border=0 alt=$url>";
	$output.= "</a>";	
}
else
{
//沒有url
}
$output.= "</li>";
$output.= show_flat_followup($serialno);
}
$output.= "</ul>";
return $output;
}
// end access_layer_list
function show_flat_followup($index_input)
{
global $sn,$su;
global $dbc,$connstr;
global $tablename;
global $fieldsArray;
global $dbname;
//$connstr=access_connect();
$sql="SELECT * FROM ".$tablename." where follow = '$index_input'";
$rs=access_query($sql,$connstr);
$keys_of_fields=array_keys($fieldsArray);
$numofelement2=sizeof($keys_of_fields);
if (!$rs->EOF)
{
$output.= "<ul>";
while (!$rs->EOF)
  {
$output.= "<li>";
for ($j=0;$j<$numofelement2;$j++)
{
$output.= access_result($rs,$i,$fieldsArray[$keys_of_fields[$j]]);
$output.= " | ";
}
/*
echo stripslashes($rs->fields['index2'] );
echo " | ";
$serialno=stripslashes($rs->fields['index2'] );
echo stripslashes($rs->fields['name'] );
// yen decide to add the upload link with the input form, therefore yen blocked
// the following line
//echo "</a>�<a href=upload&index2=$serialno>上載資料</a>";
echo " | ";
echo stripslashes($rs->fields['no'] );
*/
$serialno=stripslashes($rs->fields['index2'] );
$output.= "<a href=".$sn."?".$su."&menu=addfollowupmenu&index2=$serialno>";
$output.= "<img src=graphics/addfile.gif border=0 alt=\"新增下屬資料\">";
$output.= "</a>";
//#############################################################開始簽入或簽出的link與icon
//這裡要show出checkin 或checkout,因為checkin是更新,checkout則將資料更新權關閉(擁有修改權,簽出後更新資料後再上傳)
//這裡必須要檢視資料欄位,若status為locked則show出-簽入,若status欄位值為open或空白,則show出-簽出
$status=$rs->fields['status'];
if ($status=='locked')
{
//這裡若直接將簽入導到updateactionmenu的選單,目前checkin函式並沒有用到
//請注意,這裡讓updateaction直接接受serialno,會造成資料的安全問題,請注意!最後要有安全機制的考量
$output.= "<a href=\"?menu=checkin&index2=".$serialno."\"><img src=graphics/locked.gif border=\"0\" alt=\"簽入\"></a>";
}
else
{
$output.= "<a href=\"?menu=checkout&index2=".$serialno."\"><img src=graphics/unlocked.gif border=\"0\" alt=\"簽出\"></a>";
}
//############################################################# 結束簽入或簽出的link與icon
//echo "<img src=graphics/edit.jpg border=0 alt=\"編輯\">";
//這裡檢查該筆資料是否有掛檔
//here we want to check if any file linked
if ($rs->fields['upload'] )
{
$upload_file_name=$rs->fields['upload'] ;
$dbname_append=trim($dbname,".db");
$ext = ereg_replace("^.+\\.([^.]+)$", "\\1", $upload_file_name);
$version=$rs->fields['version'];
//echo "<a href=\"?menu=download&index2=$serialno\" target=\"_blank\">";
	//只有圖形檔有preview功能,接受.jpg,.png與.gif檔
	if (eregi('(\.jpg|\.png|\.gif)',$upload_file_name))
	{
	$output.= "<a href=\"".$sn."?".$su."&menu=pdmdownload&index2=".$serialno."\" target=\"_blank\" onmouseout=hide() onmouseover=\"show(event,'<img src=plugins/pdm/upload_files/".$version."_".$dbname_append."_".$tablename."_".$serialno.".".$ext." width=600>')\">";
	$output.= "<img src=graphics/attachment.jpg border=0 alt='$upload_file_name'>";
	$output.= "</a>";
	}
	else
	{
	$output.= "<a href=\"?menu=pdmdownload&index2=$serialno\" target=\"_blank\">";
	$output.= "<img src=graphics/attachment.jpg border=0 alt='$upload_file_name'>";
	//echo "$upload_file_name";
	$output.= "</a>";	
	}
}
else
{
//沒有夾檔
}
//看看有無url連結資料
$url=$rs->fields['url'];
if ($url)
{
	$output.= "<a href=\"".$url."\" target=\"_blank\">";
	$output.= "<img src=graphics/link.gif border=0 alt=$url>";
	$output.= "</a>";	
}
else
{
//沒有url
}
   $output.= "</li>";
//echo "<li>";
   $output.= show_flat_followup($serialno);
//echo "</li>";
   $rs->MoveNext();
   }
$output.= "</ul>";
} 
return $output;
}
function addfollowupmenu()
{
global $index2;
global $tablename;
global $fieldsArray,$fieldsNum,$fieldsType;
$keys=array_keys($fieldsArray);
$numofelement=sizeof($keys);
/*
// 可以先顯示第 $index筆的資料內容
$connstr=access_connect();
$sql="SELECT * FROM ".$tablename." where index2 =".$index2;
$rs=access_query($sql,$connstr);
access_result($rs,0,"index2");
echo "|";
access_result($rs,0,"name");
echo "|";
access_result($rs,0,"no");
echo "<br>";
// now here
*/
$actionScript="menu=addaction ENCTYPE=\"multipart/form-data\"";
$hiddenArray=array("follow"=>$index2);
$descriptionString="新增下屬資料表單";
$output.=generate_form_header($descriptionString,$actionScript,$hiddenArray,$fieldsArray,$fieldsNum,$fieldsType);
//此地要加入from and to option
//add_time_menu("由","from");
//add_time_menu("到","to");
//結束加入from to 的option
$output.= "連結: <input type=text name=\"url\" size=40><br>\n";
$output.= "附加檔案: <input type=\"file\" name=\"superdat\" size=30><br>\n";
$filedir=dirname(__FILE__);
//where we put the upload files
$output.= "<input type=\"hidden\" name=\"folder\" value=\"$filedir\">\n";
$output.= "<input type=\"submit\" name=\"upload\" value=\"新增\">\n";
$output.= "<input type=\"reset\" nmae=\"reset\" value=\"重寫\">\n";
$output.= "</form>";
/*
新增下屬資料<br>
<form method="POST" action=?menu=addfollowupaction>
<p>
  姓名:<input type="text" name="name" size="20"></p>
  <p>號碼:<input type="text" name="no" size="20"></p>
  <p><input type="hidden" name="follow" value="<?php echo $index2 ?>"></p>
  <p><input type="submit" value="提交" name="B1"><input type="reset" value="重新設定" name="B2"></p>
</form>
*/
$output.=showmenu();
return $output;
}
//親自執行資料followup新增的動作
function addfollowupaction()
{
global $dbc,$connstr;
global $tablename;
global $name,$no,$follow;
if ($name and $no)
{
//準備要運作的SQL字串
$sql="insert into ".$tablename."(name,no,follow) values ('$name','$no','$follow')";
//連接資料庫
//$connstr=access_connect();
//經由連線資料進行SQL運作
$rs=access_query($sql,$connstr);
$output.= "已新增一筆followup資料";
$output.= "<br>";
$output.= showmenu();
}
else
{
$output.= "followup中欄位不可空白";
}
}
//ends addfollowupaction
function browser_detection2( $which_test ) 
{
	// initialize variables
	$browser_name = '';
	$browser_number = '';
	// get userAgent string
	$browser_user_agent = ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) ? strtolower( $_SERVER['HTTP_USER_AGENT'] ) : '';
	//pack browser array
	// values [0]= user agent identifier, lowercase, [1] = dom browser, [2] = shorthand for browser,
	$a_browser_types[] = array('opera', true, 'op' );
	$a_browser_types[] = array('msie', true, 'ie' );
	$a_browser_types[] = array('konqueror', true, 'konq' );
	$a_browser_types[] = array('safari', true, 'saf' );
	$a_browser_types[] = array('gecko', true, 'moz' );
	$a_browser_types[] = array('mozilla/4', false, 'ns4' );
	$a_browser_types[] = array('other', false, 'other' );
	$i_count = count($a_browser_types);
	for ($i = 0; $i < $i_count; $i++)
	{
		$s_browser = $a_browser_types[$i][0];
		$b_dom = $a_browser_types[$i][1];
		$browser_name = $a_browser_types[$i][2];
		// if the string identifier is found in the string
		if (stristr($browser_user_agent, $s_browser)) 
		{
			// we are in this case actually searching for the 'rv' string, not the gecko string
			// this test will fail on Galeon, since it has no rv number. You can change this to 
			// searching for 'gecko' if you want, that will return the release date of the browser
			if ( $browser_name == 'moz' )
			{
				$s_browser = 'rv';
			}
			$browser_number = browser_version2( $browser_user_agent, $s_browser );
			break;
		}
	}
	// which variable to return
	if ( $which_test == 'browser' )
	{
		return $browser_name;
	}
	elseif ( $which_test == 'number' )
	{
		return $browser_number;
	}
	/* this returns both values, then you only have to call the function once, and get
	 the information from the variable you have put it into when you called the function */
	elseif ( $which_test == 'full' )
	{
		$a_browser_info = array( $browser_name, $browser_number );
		return $a_browser_info;
	}
}
// function returns browser number or gecko rv number
// this function is called by above function, no need to mess with it unless you want to add more features
function browser_version2( $browser_user_agent, $search_string )
{
	$string_length = 8;
	// this is the maximum  length to search for a version number
	//initialize browser number, will return '' if not found
	$browser_number = '';
	// which parameter is calling it determines what is returned
	$start_pos = strpos( $browser_user_agent, $search_string );
	// start the substring slice 1 space after the search string
	$start_pos += strlen( $search_string ) + 1;
	// slice out the largest piece that is numeric, going down to zero, if zero, function returns ''.
	for ( $i = $string_length; $i > 0 ; $i-- )
	{
		// is numeric makes sure that the whole substring is a number
		if ( is_numeric( substr( $browser_user_agent, $start_pos, $i ) ) )
		{
			$browser_number = substr( $browser_user_agent, $start_pos, $i );
			break;
		}
	}
	return $browser_number;
}
?>
