<?php
	/**************************************************************************\
	* Brython CMSimple plugin 0.01
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

function brythonMain(){
    $output = <<<EOF
<!-- 當 brython.js 內容更新後, 必須自行建立 brython.js -->
<script src="jscript/brython/brython.js"></script>
<script type="text/python">
def 呼叫():
    # alert() 為 Javascript 函式
    # doc["zone"] 表示為 id=zone 的資料
    # 當使用者按下 clic 按鈕則會執行 echo() 函式的呼叫
    呼叫次數 = 3
    for 索引 in range(呼叫次數):
        alert(int(doc["zone"].value)+索引)
</script>
<script>
window.onload = function(){
    brython();
}
</script>
<input id="zone"><button onclick="呼叫()">按下進行呼叫!</button>
</body>
</html>

EOF;
    return $output;

}