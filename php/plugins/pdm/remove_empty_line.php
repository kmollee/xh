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

//$filename="test.php";

$filename=$_GET["filename"];

$newstring = '';
foreach(file($filename) as $line)
{
    if(trim($line) != '')
    {
        $newstring .= $line;        
    }
}

$fd=fopen($filename,"w");
fputs($fd,$newstring);
fclose($fd);
echo $filename." done";
