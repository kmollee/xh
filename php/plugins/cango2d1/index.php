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

function cango2d1Main(){
$output = <<<EOF
<script src="jscript/brython/brython.js"></script>
  <style type="text/css">
     #cvs {
      position: relative;
      display: block;
      margin: 0 auto;
      background-color: wheat;
    }
  </style>
<script type="text/python">
import Cango2v03
g = Cango2v03.cango("cvs")
g.setViewport()
g.fillViewport("lightyellow")
g.setWorldCoords(0, 200, 0, 70)
g.setPenColor("blue")
x1 = 40
y1 = 10
cx1 = 90
cy1 = 60
x2 = 120
y2 = 50
cx2 = 130
cy2 = 10
cx3 = 150
cy3 = 60
x3 = 180
y3 = 30
grabOfsX = 0
grabOfsY = 0
def dragC1(mousePos):
    global cx1,cy1
    wPos = g.toWorldCoords(mousePos.x , mousePos.y)
    cx1 = wPos.x
    cy1 = wPos.y
    drawCurve()
def dragC2(mousePos):
    global cx2,cy2
    wPos = g.toWorldCoords(mousePos.x , mousePos.y)
    cx2 = wPos.x
    cy2 = wPos.y
    drawCurve()
def dragC3(mousePos):
    global cx3,cy3
    wPos = g.toWorldCoords(mousePos.x , mousePos.y)
    cx3 = wPos.x
    cy3 = wPos.y
    drawCurve()
def drawCurve():
    g.clearCanvas()
    g.setPenColor('blue')
    data = ['M', x1, y1, 'Q', cx1, cy1, x2, y2]
    g.drawPath(data)
    g.setPenColor('green')
    data = ['M', x2, y2, 'C', cx2, cy2, cx3, cy3, x3, y3]
    g.drawPath(data)
    g.setPenColor("rgba(0, 0, 0, 0.2)")
    data = ['M', x1, y1, 'L', cx1, cy1, x2, y2]
    g.drawPath(data)
    data = ['M', x2, y2, 'L', cx2, cy2]
    g.drawPath(data)
    data = ['M', x3, y3, 'L', cx3, cy3]
    g.drawPath(data)
    g.render(c1, cx1, cy1)
    g.render(c2, cx2, cy2)
    g.render(c3, cx3, cy3)
#dragC1 = null
#dragC2 = null
#dragC3 = null
dragObj1 = Cango2v03.drag2d(g, null, dragC1, null)
c1 = g.compileShape(Cango2v03.shapedefs.circle, 'red', 'red', 'iso', dragObj1)
c1.scale(4)
dragObj2 = Cango2v03.drag2d(g, null, dragC2, null)
c2 = g.compileShape(Cango2v03.shapedefs.circle, 'red', 'red', 'iso', dragObj2)
c2.scale(4)
dragObj3 = Cango2v03.drag2d(g, null, dragC3, null)
c3 = g.compileShape(Cango2v03.shapedefs.circle, 'red', 'red', 'iso', dragObj3)
c3.scale(4)
drawCurve()
</script>
<script>
window.onload=function(){
brython();
}
</script>
<canvas id="cvs" width="640" height="400"></canvas>
EOF;
return $output;
}