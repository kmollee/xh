<?php
	/**************************************************************************\
	* Axuploader CMSimple plugin 0.01
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

//reference: http://www.albanx.com/?pid=1

$menu=$_GET['menu'];

function axuploader_main()
{
global $menu;
global $adm;

// 以下要加入判定是否管理者的設定, 若非管理者登入的模式下, 根本無法執行檔案上傳
if ($adm != 1)
{
    return "Error! Please contact system administrator!";
}

	if ($menu)
	{
      switch($menu)
      {
      case "upload":
          $output=axuploaderUpload();
      break;

      default:
      $output.=axuploaderForm();
      }
	}
	else
	{
      $output.=axuploaderForm();
	}
  return $output;
}

function axuploaderForm()
{
    global $sn,$su;
    $output = <<< EOT
    <script src="jscript/axuploader/jquery.js" type="text/javascript"></script>
    <script src="jscript/axuploader/axuploader.js" type="text/javascript"></script>
    <script>
    $(document).ready(function(){
      $('.prova').axuploader({
EOT;
    // 將 php 程式中的變數傳給 javascript
    $output .= "url:'".$sn."?".$su."&menu=upload',";
        
    $output .= <<< EOT
        allowExt:['jpg','png','gif','7z','pdf'],
        finish:function(x,files)
			{
			    alert('All files have been uploaded: '+files);
			},
        enable:true,
        remotePath:function(){
          return 'downloads/';
        }
      });
    });
    </script>
    <div class="prova"></div>
      <input type="button" onclick="$('.prova').axuploader('disable')" value="asd" />
      <input type="button" onclick="$('.prova').axuploader('enable')" value="ok" />
    <div id="debug"></div>
EOT;
return $output;
}

function axuploaderUpload()
{
  $up=new FileUploader();
  $path=$_GET['ax-file-path'];
  $ext=$_GET['ax-allow-ext'];
  $res=$up->uploadfile($path,$ext);
}

/*==================================================================
 * Upload class for handling upload files
 *=================================================================*/
class AsyncUpload
{
    function save($remotePath,$allowext,$add) 
	{    
	    $file_name=$_GET['ax-file-name'];
    //讓上傳可以處理中文命名檔案
    if (substr(php_uname(), 0, 7) == "Windows")
   {
       $file_name=iconv("utf-8","big-5",$file_name);
   }
   
	    $file_info=pathinfo($file_name);	    

	    if(strpos($allowext, $file_info['extension'])!==false || $allowext=='all')
	    {
	    	$flag =($_GET['start']==0) ? 0:FILE_APPEND;
	    	$file_part=file_get_contents('php://input');//REMEMBER php::/input can be read only one in the same script execution, so better mem it in a var
	    	while(@file_put_contents($remotePath.$add.$file_name, $file_part,$flag)===FALSE)//strange bug
	    	{
	    		usleep(50);
	    	}
	        return true;
	    }
	    return $file_info['extension'].' extension not allowed to upload!';
    } 
}

class SyncUpload 
{  
    function save($remotePath,$allowext,$add)
	{
		$msg=true;
    	foreach ($_FILES['ax-files']['error'] as $key => $error) 
    	{
    		$tmp_name = $_FILES['ax-files']['tmp_name'][$key];
    		$name = $_FILES['ax-files']['name'][$key];
    		
    		$file_info=pathinfo($name);
            if ($error == UPLOAD_ERR_OK) 
            {
            	if(strpos($allowext, $file_info['extension'])!==false || $allowext=='all')
            	{
                	move_uploaded_file($tmp_name, $remotePath.$add.$name);
            	}
            	else
            	{
            		$msg=$file_info['extension'].' extension not allowed!';
            	}
            }
            else 
            {
                $msg='Error uploading!';
            }
        }
        echo $msg;
        return $msg;
    }
}

class FileUploader 
{
	private $file=false;
    function __construct($remotePath='',$allowext='')
	{
		if(isset($_FILES['ax-files'])) 
		{
            $this->file = new SyncUpload();
        }
        elseif(isset($_GET['ax-file-name'])) 
		{
            $this->file = new AsyncUpload();
        } 
		else 
		{
            $this->file = false; 
        }  
    }

    function uploadfile($remotePath='',$allowext='all',$add='')
	{
		$remotePath.=(substr($remotePath, -1)!='/')?'/':'';
		if(!file_exists($remotePath)) mkdir($remotePath,0777,true);
		
        $msg=$this->file->save($remotePath,$allowext,$add);
        return $msg;
    }    
}