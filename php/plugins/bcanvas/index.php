<?php

function bcanvasMain(){

$output = <<<EOF
<script src="jscript/brython/brython.js"></script>
<script>
window.onload = function(){
    brython();
}
</script>
<script type="text/python">
# 導入系統模組 time.js, math.js, datetime.js, 必須位於  CMSimple 根目錄
import time
import math
import datetime

sin,cos = math.sin,math.cos
width,height = 250,250 # canvas dimensions
ray = 100 # clock ray 時鐘線

def 指針(angle,r1,r2,color="#000000"):
    # draw a needle at specified angle in specified color
    # r1 and r2 are percentages of clock ray
    x1 = width/2-ray*cos(angle)*r1
    y1 = height/2-ray*sin(angle)*r1
    x2 = width/2+ray*cos(angle)*r2
    y2 = height/2+ray*sin(angle)*r2
    ctx.beginPath()
    ctx.strokeStyle = color
    ctx.moveTo(x1,y1)
    ctx.lineTo(x2,y2)
    ctx.stroke()

def set_clock():
    # erase clock
    ctx.beginPath()
    ctx.fillStyle = "#FFF"
    ctx.arc(width/2,height/2,ray*0.89,0,2*math.pi)
    ctx.fill()
    
    # redraw hours
    show_hours()

    # print day 
    now = datetime.datetime.now()
    day = now.day
    ctx.font = "bold 14px Arial"
    ctx.textAlign = "center"
    ctx.textBaseline = "middle"
    ctx.fillStyle="#FFF"
    ctx.fillText(day,width*0.7,height*0.5)

    # draw needles for hour, minute, seconds    
    ctx.lineWidth = 3
    hour = now.hour%12 + now.minute/60
    angle = hour*2*math.pi/12 - math.pi/2
    指針(angle,0.05,0.5)
    minute = now.minute
    angle = minute*2*math.pi/60 - math.pi/2
    指針(angle,0.05,0.85, "#0000FF") # 藍色分針
    ctx.lineWidth = 1
    second = now.second+now.microsecond/1000000
    angle = second*2*math.pi/60 - math.pi/2
    指針(angle,0.05,0.85,"#00FF00") # 綠色秒針

# 動畫的根源就是 set_clock 函式, 以每 100 micro seconds 更新    
time.set_interval(set_clock,100)

# 所謂畫布就是  document 中 id = clock 的物件
canvas = doc["clock"]

# 以下在繪製錶面外框
# draw clock border
ctx = canvas.getContext("2d")
ctx.beginPath()
ctx.lineWidth = 10
ctx.arc(width/2,height/2,ray,0,2*math.pi)
ctx.stroke()

# 以下在畫刻度
for i in range(60):
    ctx.lineWidth = 1
    if i%5 == 0:
        ctx.lineWidth = 3
    angle = i*2*math.pi/60 - math.pi/3
    x1 = width/2+ray*cos(angle)
    y1 = height/2+ray*sin(angle)
    x2 = width/2+ray*cos(angle)*0.9
    y2 = height/2+ray*sin(angle)*0.9
    ctx.beginPath()
    ctx.moveTo(x1,y1)
    ctx.lineTo(x2,y2)
    ctx.stroke()

def show_hours():
    ctx.beginPath()
    ctx.arc(width/2,height/2,ray*0.05,0,2*math.pi)
    ctx.fillStyle = "#000"
    ctx.fill()
    for i in range(1,13):
        angle = i*math.pi/6-math.pi/2
        x3 = width/2+ray*cos(angle)*0.75
        y3 = height/2+ray*sin(angle)*0.75
        ctx.font = "20px Arial"
        ctx.textAlign = "center"
        ctx.textBaseline = "middle"
        ctx.fillText(i,x3,y3)
    # cell for hour
    ctx.fillStyle = "#000"
    ctx.fillRect(width*0.65,height*0.47,width*0.1,height*0.06)
# 顯示小時數字
show_hours()
</script>

<canvas width="250" height="250" id="clock" style="border:1px solid black;">
<i>your browser does not support HTML5 canvas, or Javascript is turned off</i>
</canvas>
EOF;

return $output;
}