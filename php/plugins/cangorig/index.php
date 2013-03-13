<?php

function cangorigMain(){
$output = <<<EOF
 <script type="text/javascript" src="jscript/cango/Cango2v03.js"></script>
  <script type="text/javascript">
    function sayHullo(cvsID)
    {
      var g = new Cango(cvsID);
      g.setViewport();     // full canvas
      g.setWorldCoords(-10, 10, -5, 5);

      var hullo = g.drawText("Hullo World", 5, 3, 18, 5);
      g.animate(hullo, [5,0], [3,0], [0.5,2], [0,360], 1000, 1000, 'loop');
    }
  </script>
  <style type="text/css">
     #cvs {
      position: relative;
      display: block;
      margin: 0 auto;
      background-color: wheat;
    }
  </style>
<script>
window.onload=function(){
sayHullo('cvs');
}
</script>
<canvas id="cvs" width="640" height="400"></canvas>
EOF;
return $output;
}