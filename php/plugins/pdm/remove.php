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

    $dir = dir("./");
    while( $filename = $dir->read() )
{
        
    if ( is_file($filename ) and $filename!="remove.php" )
    { 
// 本程式要將已經由pobs編碼過的程式中的跳行拿掉
// 不知可否將一個以上的空白全部轉為只有一個空白
//$filename = "db.php";
	$fd = fopen( $filename, "r" );
	$current = fread( $fd, filesize( $filename ) );
// 處理跳行,每一跳行有\r與\n兩個符號
	$after1=eregi_replace("\r","",$current);
	$after2=eregi_replace("\n","",$after1);
//處理一個以上的空白,全部轉為一個空白
	//$after4=eregi_replace(" +"," ",$after3);

	$fd=fopen($filename,"w");
	fputs($fd,$after2);
	fclose($fd);
	echo $filename." 已完成轉檔";
	echo "<br>";
	}
}
?>
