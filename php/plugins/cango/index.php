<?php
function cangoMain(){
$output = <<<EOF
<script src="jscript/brython/brython.js"></script>
<script type="text/python">
import math

ray = 100
values = [20,10,30,15,25]

colors = ["C8E0A2","A6BED1","E4CC85","D7D7D7","90AF97","698EA8",
        "BFA166","A8ADB0","FF6600"]

panel = doc["panel"]
legend = None

標題 = SVG.text('',x=150,y=25,
    font_size=22,text_anchor="middle",
    style={"stroke":"black"})
panel <= 標題

paths = {}

def 繪製餅圖():
    global paths,legend
    # clear SVG doc
    for child in panel: # iteration on child nodes
        if child != 標題:
            panel.remove(child)

    # zone for legend
    legend = SVG.text('',x=350,y=150,
        font_size=20,text_anchor="middle",
        style={"stroke":"black"})
    panel <= legend

    設定標題()
        
    paths = {}
    data = {}
    for i,cell in enumerate(cells):
        data['項目 %s' %(i+1)]=float(cell.value)
    style={"fill-opacity": 1,"stroke":"black","stroke-width": 1}
    width = 3.8*ray
    height = 2.2*ray
    x_center = 150
    y_center = 160
    x = x_center
    y = y_center-ray
    total = sum(data.values())
    items = list(data.items())
    cumul = 0
    for i,(key,value) in enumerate(items):
        angle1 = 2*math.pi*cumul
        cumul += float(value)/total
        angle = 2*math.pi*cumul
        x_end = x_center + ray*math.cos((math.pi/2)-angle)
        y_end = y_center - ray*math.sin((math.pi/2)-angle)
        path = "M%s,%s " %(x_center,y_center)
        path += "L%s,%s " %(int(x),int(y))
        if angle-angle1 <= math.pi:
            path += "A%s,%s 0 0,1 " %(ray,ray)
        else:
            path += "A%s,%s 0 1,1 " %(ray,ray)
        path += "%s,%s z" %(int(x_end),int(y_end))
        x,y = x_end,y_end
        color = colors[i % len(colors)]
        style["fill"]='#'+color
        path = SVG.path(d=path,style=style,
            onMouseOver="show_legend('%s')" %key,
            onMouseOut="hide_legend()")
        panel <= path
        paths[key]=path

def 設定標題():
    標題.text = 標題輸入.value
    
def show_legend(key):
    legend.text = key

def hide_legend():
    legend.text = ''

def update(cell):
    try:
        float(cell.value)
    except ValueError:
        alert('Numeric value requested')
        return
    繪製餅圖()

nb_cols = 2
nb_lines = 5

t = TABLE()
tb = TBODY()
cells = []

標題輸入 = INPUT(value='餅形比例圖',onchange="設定標題()")
tb <= TD('Title')+TD(標題輸入)

for i in range(nb_lines):
    row = TR()
    row <= TD('項目 %s' %i)
    cell = INPUT(value=values[i],onchange="update(this)")
    row <= TD(cell)
    cells.append(cell)
    tb <= row
t <= tb
doc['data'] <= t

繪製餅圖()
</script>

</head>
<body onLoad="brython(1)">
<h1>SVG pie chart</h1>
<p>

<table>
<tr>
<td id="data"></td>
<td>
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" 
    width="400" height="300" style="border-style:solid;border-width:1;border-color:#000;">
  <g id="panel">
  </g>
</svg>
</td>
</tr>
</table>
<script>
window.onload=function(){
brython();
}
</script>
<canvas id="cvs" width="640" height="400"></canvas>
EOF;
return $output;
}