<?php

$pyprogram = <<<EOF
重複次數 = 3
for 變數 in range(重複次數):
    print(變數)
EOF;

function bconsoleMain($input){
global $pyprogram;
if($input == NULL) $input = $pyprogram;
$output=<<<EOF
<script src="jscript/brython/brython.js"></script>
<script>
window.onload=function(){
brython();
}
</script>
<script type="text/python">
import sys
import time
import dis

def reset_src():
    if sys.has_local_storage:
        doc['src'].value = local_storage["py_src"]

def to_str(xx):
    return str(xx)

#doc['version'].text = '.'.join(map(to_str,sys.version_info))
sys.stdout = object()

def write(data):
    doc["console"].value += str(data)
sys.stdout.write = write

sys.stderr = object()
sys.stderr.write = write

output = ''

def show_console():
    doc["console"].value = output

def run():
    global output
    doc["console"].value=''
    src = doc["src"].value
    if sys.has_local_storage:
        local_storage["py_src"]=src
    t0 = time.time()
    exec(src)
    output = doc["console"].value
    print('<done in %s ms>' %(time.time()-t0))

def show_js():
    src = doc["src"].value
    doc["console"].value = dis.dis(src)
</script>
<table width=80%>
<tr><td style="text-align:center"><b>Python</b>
</td>
<td>&nbsp;</td>
<th><button onClick="show_console()">Console</button></th>
<th><button onClick="show_js()">Javascript</button></th>
</tr>
<tr>
EOF;
$output .= "<td colspan><textarea id=\"src\" cols=\"60\" rows=\"20\">".$input."</textarea></td>";
$output .= <<<EOF
<td><button onClick="run()">run</button></td>
<td colspan=2><textarea id="console" cols=70 rows=20></textarea></td>
</tr>
</table>

EOF;
return $output;
}