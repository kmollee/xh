/*=============================================================
  Filename: cvsGraphCtx4v02.js
  Rev: 4
  By: A.R.Collins
  Description: A basic graphics interface for the canvas
  element.
  License: Released into the public domain
  latest version at
  <http://www/arc.id.au/CanvasGraphics.html>
  Requires:
  - IE<9 canvas emulator 'excanvas-modified.js' from
  <http://groups.google.com/group/google-excanvas/files>.

  Date   |Description                                      |By
  -------------------------------------------------------------
  04Nov09 Rev 1.00 First release based on cvsGrapLib5v05
          SVGPath method moved to optional cvsSVGpaths.js   ARC
  27Aug10 dupCtx colors using slice(0) not reference copy   ARC
  04Sep10 Include State2D object definition for use with
          timeline.js animation.
          Include svgPath support as standard (was optional
          by including cvsSvgPath.js)                       ARC
  13Oct10 Force svgPath to start with 'M'
          pass xScale not scl to compileSVGPath             ARC
  14Oct10 bugfix: regexp didn't handle exponential notation ARC
  15Oct10 Make functions methods to keep clean namespace    ARC
  06Dec10 bugfix: clearCanvas didn't use rawHeight          ARC
  22Jun11 bugfix: getElementById hated this.cId as parm     ARC
  06Jul12 Added svgPath2 this is the old svgPath but with
          sign of yOfs switched, all y coords are flppied,
          use if importing pathdata from Inkscape.
          New svgPath does not flip any coords, use it for
          svg paths drawn from user coords                  ARC
  01Sep12 xRef, yRef origin coords added to svgPath2        ARC
  02Sep12 Added gradient fill support                       ARC
  03Sep12 Included rgbaColor.js into file
          Included canvasText.js into file                  ARC
  05Sep12 Dropped polygon, quadBezier, CubicBezier
          Added setLineCap, svgPath can accept array data   ARC
  06Sep12 bugfix: shapes not stroked if filled              ARC
  07Sep12 Added _toNativeCoords and _toRawCoords            ARC
  09Sep12 Renamed to toNativeCoords and toRawCoords         ARC
  =============================================================*/

  var _resized = new Array();   // keep track of which canvases are initialised

  function CvsGraphCtx(canvasId)
  {
    this.cId = canvasId;
    this.cnvs = document.getElementById(canvasId);
    if (this.cnvs == null)
    {
      alert("can't find canvas "+canvasId);
      return
    }
    this.rawWidth = this.cnvs.offsetWidth;
    this.rawHeight = this.cnvs.offsetHeight;
    this.aRatio = this.rawWidth/this.rawHeight;

    if (!(this.cId in _resized))
    {
    /* make canvas native aspect ratio equal style box aspect ratio.
       only do this once for each canvas as it clears the canvas.
       A second graph to be drawn will erase the first graph if the canvas
       width and height attributes are reset */
      /* Note: rawWidth and rawHeight are floats, assignment to ints will truncate */
        this.cnvs.setAttribute('width', this.rawWidth);     // this actually reset the number of graphics pixels
        this.cnvs.setAttribute('height', this.rawHeight);   // use this instead of style to match emulator

      /* create an element in associative array for this canvas
         element's existance is the test that resize has been done.
         Could have used the existance of the busy flag but lets use
         separate code for separate function */
      _resized[this.cId]= true;
    }

    this.ctx = this.cnvs.getContext('2d');
    this.ctx.save();

    this.vpW = this.rawWidth;         // vp width in pixels (default to full canvas size)
    this.vpH = this.rawHeight;        // vp height in pixels
    this.vpLLx = 0;                   // vp lower left from canvas left in pixels
    this.vpLLy = this.rawHeight;      // vp lower left from canvas top
    this.xscl = this.rawWidth/100;    // world x axis scale factor, default canvas width = 100 native units
    this.yscl = -this.rawWidth/100;   // world y axis scale factor, default +ve up and canavs height =100*aspect ratio (square pixels)
    this.xoffset = 0;                 // world x origin offset from viewport left in pixels
    this.yoffset = 0;                 // world y origin offset from viewport bottom in pixels
                                      // *** to move to world coord x ***
                                      // 1. from pixel x origin (canvas left) add vpLLx (gets to viewport left)
                                      // 2. add xoffset to get to pixel location of world x origin
                                      // 3. add x*xscl pixels to get to world x location.
                                      // ==> x (in world coords) == vpLLx + xoffset + x*xscl (pixels location of canvas)
                                      // ==> y (in world coords) == vpLLy + yoffset + y*xscl (pixels location of canvas)

    this.rotA = 0;                 // world coordinate rotation (in rads)
    this.rotX = 0;                    // origin for world corrd rotation in raw pixels;
    this.rotY = 0;

    this.penCol = "rgba(0, 0, 0, 1.0)";        // black
    this.penWid = 1;             // pixels
    this.lineCap = "butt";
    this.bkgCol = "rgba(255, 255, 255, 1.0)";  // white
    this.fontSize = 10;          // 10pt

    this.penX = 0;   // pen position in world coordinates
    this.penY = 0;

    this.dashX = 0;  // working variable for dashed lines
    this.dashY = 0;
  }

  CvsGraphCtx.prototype._setCtx = function()
  {
    // often used in the library calls as the penCol etc may have been set by assignment rather than setPenColor()
    this.ctx.fillStyle = this.penCol;
    this.ctx.lineWidth = this.penWid;
    this.ctx.lineCap = this.lineCap;
    this.ctx.strokeStyle = this.penCol;
  }

  CvsGraphCtx.prototype.toNativeCoords = function(x, y)
  {
    // transform x,y in world coords to x,y in native coords (ie. x axis is 0 to 100 and y axis has same scale)
    var xPx = this.vpLLx+this.xoffset+x*this.xscl;
    var yPx = this.vpLLy+this.yoffset+y*this.yscl;

    return {x: 100*xPx/this.rawWidth, y: 100*(this.rawHeight-yPx)/this.rawWidth};
  }

  CvsGraphCtx.prototype.toRawCoords = function(x, y)
  {
    // transform x,y in world coords to x,y in raw canvas coords (ie. top left is 0,0 y axis +ve down screen)
    var xPx = this.vpLLx+this.xoffset+x*this.xscl;
    var yPx = this.vpLLy+this.yoffset+y*this.yscl;

    return {x: xPx, y: yPx};
  }

  CvsGraphCtx.prototype.clearCanvas = function()
  {
    this.ctx.clearRect(0, 0, this.rawWidth, this.rawHeight);
    // all drawing erased
    // but all global graphics contexts remain intact
    this.clearRotation();   // reset rotation
  }

  CvsGraphCtx.prototype.setViewport = function(lowerLeftX, lowerLeftY, w, h)
  {
    if (h != undefined)
    {
      this.vpW = 0.01*w*this.rawWidth;
      this.vpH = 0.01*h*this.rawWidth;
      this.vpLLx = 0.01*lowerLeftX*this.rawWidth;
      this.vpLLy = this.rawHeight-0.01*lowerLeftY*this.rawWidth;
    }
    else
    {
      this.vpW = this.rawWidth;
      this.vpH = this.rawHeight;
      this.vpLLx = 0;
      this.vpLLy = this.rawHeight;
    }
    this.setWorldCoords();     // if new viewport, world coords are garbage, so reset to defaults
  }

  CvsGraphCtx.prototype.clearViewport = function()
  {
    /* Not supported by excanvas, which implements clearRect by 'deleteNode'
       so it becomes equivalent to clearCanvas,
       So it is recommended to use clearCanvas and avoid clearViewport until excanvas is fixed */

    this.ctx.clearRect(this.vpLLx, this.vpLLy - this.vpH, this.vpW, this.vpH); // referenced from top left corner
  }

  CvsGraphCtx.prototype.fillViewport = function(fillColor)
  {
    /* set background color and fill the viewport to that color or gradient */
    if ((fillColor != undefined)&&(fillColor != null))
    {
      if (typeof fillColor == "object")  // test for gradient object
        this.bkgCol = fillColor;
      else  // check if its a color string
      {
        var newCol = new RGBAColor(fillColor);
        if (newCol.ok)                          // else color doesn't change uses penCol
          this.bkgCol = newCol.toRGBA();
      }
    }

    if (this.rotA)
    {
      this.ctx.save();
      this.ctx.translate(this.rotX, this.rotY);   // move transform origin to the requested origin
      this.ctx.rotate(this.rotA);
      this.ctx.translate(-this.rotX, -this.rotY);   // move origin back
    }

    this.ctx.fillStyle = this.bkgCol;
    this.ctx.fillRect(this.vpLLx, (this.vpLLy-this.vpH), this.vpW, this.vpH); // fill referenced from top left corner

    if (this.rotA)
      this.ctx.restore();
  }

  CvsGraphCtx.prototype.setWorldCoords = function(leftX, rightX, lowerY, upperY)
  {
    if (upperY != undefined)
    {
      this.xscl = this.vpW/(rightX-leftX);
      this.yscl = -(this.vpH/(upperY-lowerY));
      this.xoffset = -leftX*this.xscl;
      this.yoffset = -lowerY*this.yscl;
    }
    else
    {
      this.xscl = this.rawWidth/100;    // makes xaxis = 100 native units
      this.yscl = -this.rawWidth/100;   // makes yaxis = 100*aspect ratio ie. square pixels
      this.xoffset = 0;
      this.yoffset = 0;
    }

    this.clearRotation();   // reset rotation
    // world coords have changed, reset pen world coords
		this.penX = 0;
		this.penY = 0;
  }

  CvsGraphCtx.prototype.setRotation = function(orgX, orgY, degs)
  {
    this.rotA = -degs*Math.PI/180.0;      // measure angles counter clockwise
    this.rotX = this.vpLLx+this.xoffset+orgX*this.xscl;
    this.rotY = this.vpLLy+this.yoffset+orgY*this.yscl;
  }

  CvsGraphCtx.prototype.clearRotation = function()
  {
    this.rotA = 0;
    this.rotX = 0;
    this.rotY = 0;
  }

  CvsGraphCtx.prototype.setPenColor = function(color)
  {
    if ((color == undefined)||(color == null))
    {
      this.penCol = "rgba(0, 0, 0, 1.0)";        // black
    }
    else    // some color has been passed
    {
      if (typeof color == "object")  // test for gradient object
        this.penCol = color;
      else  // check if its a color string
      {
        var newCol = new RGBAColor(color);
        if (newCol.ok)                          // else color doesn't change uses penCol
          this.penCol = newCol.toRGBA();
        // else penCol doesn't change
      }
    }

    this.ctx.strokeStyle = this.penCol;
    this.ctx.fillStyle = this.penCol;
  }

  CvsGraphCtx.prototype.setPenWidth = function(w)    // w in screen px
  {
    if (typeof w != "undefined")
      this.penWid = w;

    this.ctx.lineWidth = this.penWid;
  }

  CvsGraphCtx.prototype.setLineCap = function(c)    // c = 'butt', 'round' or 'square'
  {
    if (typeof c == "undefined")
      this.lineCap = "butt";
    else if ((c == "butt")||(c =="round")||(c == "square"))
      this.lineCap = c;

    this.ctx.lineCap = this.lineCap;
  }

  CvsGraphCtx.prototype.setFontSize = function(s)    // s in points
  {
    if (typeof s != "undefined")
      this.fontSize = s;
  }

  CvsGraphCtx.prototype.move = function(x, y) /* from current pen position to x,y */
  {
    if (this.rotA)
    {
      this.ctx.save();
      // setup for rotation by rot degrees
      this.ctx.translate(this.rotX, this.rotY);   // move transform origin to the requested origin
      this.ctx.rotate(this.rotA);
      this.ctx.translate(-this.rotX, -this.rotY);   // move origin back
    }

    this.ctx.beginPath();    // reset current path to null and move to 0,0
    this.ctx.moveTo(this.vpLLx+this.xoffset+x*this.xscl, this.vpLLy+this.yoffset+y*this.yscl);

    if (this.rotA)
      this.ctx.restore();

    // update world coords of pen
		this.penX = x;
		this.penY = y;
  }

  CvsGraphCtx.prototype.linearGradientFill = function(x1, y1, x2, y2)  /* maps to createLinearGradient */
  {
    // pixel version of world coordinate parms
    var px1 = this.vpLLx+this.xoffset+x1*this.xscl;
    var py1 = this.vpLLy+this.yoffset+y1*this.yscl;
    var px2 = this.vpLLx+this.xoffset+x2*this.xscl;
    var py2 = this.vpLLy+this.yoffset+y2*this.yscl;

    var gradFill = this.ctx.createLinearGradient(px1, py1, px2, py2);

    return gradFill;
  }

  CvsGraphCtx.prototype.radialGradientFill = function(x1, y1, r1, x2, y2, r2)  /* maps to createRadialGradient */
  {
    // pixel version of world coordinate parms
    var px1 = this.vpLLx+this.xoffset+x1*this.xscl;
    var py1 = this.vpLLy+this.yoffset+y1*this.yscl;
    var pr1 = r1*this.xscl;
    var px2 = this.vpLLx+this.xoffset+x2*this.xscl;
    var py2 = this.vpLLy+this.yoffset+y2*this.yscl;
    var pr2 = r2*this.xscl;

    var gradFill = this.ctx.createRadialGradient(px1, py1, pr1, px2, py2, pr2);

    return gradFill;
  }

  CvsGraphCtx.prototype.line = function(x, y, style)  /* from current pen position to x, y */
  {
    if (this.rotA)
    {
      this.ctx.save();
      // setup for rotation by rot degrees
      this.ctx.translate(this.rotX, this.rotY);   // move transform origin to the requested origin
      this.ctx.rotate(this.rotA);
      this.ctx.translate(-this.rotX, -this.rotY);   // move origin back
    }

    this._setCtx();   // set up the stroke and fill styles
		if (style == "dashed")
		{
		  // set the current pen postion for dashed line calcs
      this.dashX = this.vpLLx+this.xoffset+this.penX*this.xscl;
      this.dashY = this.vpLLy+this.yoffset+this.penY*this.yscl;
			this.dashTo(this.vpLLx+this.xoffset+x*this.xscl, this.vpLLy+this.yoffset+y*this.yscl, 6, 8)
      this.ctx.stroke();
		}
    else if (style == "dotted")
		{
		  // set the current pen postion for dashed line calcs
      this.dashX = this.vpLLx+this.xoffset+this.penX*this.xscl;
      this.dashY = this.vpLLy+this.yoffset+this.penY*this.yscl;
			this.dashTo(this.vpLLx+this.xoffset+x*this.xscl, this.vpLLy+this.yoffset+y*this.yscl, 2, 6)
      this.ctx.stroke();
		}
    else
		{
      this.ctx.lineTo(this.vpLLx+this.xoffset+x*this.xscl, this.vpLLy+this.yoffset+y*this.yscl);
      this.ctx.stroke();
		}

    if (this.rotA)
      this.ctx.restore();

    // update world coords of pen
		this.penX = x;
		this.penY = y;
  }

/*
 * @author Trevor McCauley, senocular.com
 * @version 1.0.2
 *
 */
	CvsGraphCtx.prototype.lineLength = function(sx, sy, ex, ey)
	{
		if (typeof ex == "undefined")
			return Math.sqrt(sx*sx + sy*sy);
		var dx = ex - sx;
		var dy = ey - sy;
		return Math.sqrt(dx*dx + dy*dy);
	}

	CvsGraphCtx.prototype.targetMoveTo = function(x, y)
	{
		this.dashX = x;
		this.dashY = y;
    this.ctx.moveTo(x, y);
	}

	CvsGraphCtx.prototype.targetLineTo = function(x, y)
	{
		if (x == this.dashX && y == this.dashY)
			return;
		this.dashX = x;
		this.dashY = y;
    this.ctx.lineTo(x, y);
	}

  /**
   * Draws a dashed line in target using the current line style from the current drawing position
   * to (x, y); the current drawing position is then set to (x, y).
   */
	CvsGraphCtx.prototype.dashTo = function(x, y, onLength, offLength)
	{
		var overflow = 0;
		var isLine = true;
		var dashLength = onLength + offLength;
		var dx = x-this.dashX;
    var dy = y-this.dashY;
		var a = Math.atan2(dy, dx);
		var ca = Math.cos(a), sa = Math.sin(a);
		var segLength = this.lineLength(dx, dy);
		if (overflow)
		{
			if (overflow > segLength)
			{
				if (isLine)
					this.targetLineTo(x, y);
				else this.targetMoveTo(x, y);
				overflow -= segLength;
				return;
			}
			if (isLine)
				this.targetLineTo(this.dashX + ca*overflow, this.dashY + sa*overflow);
			else
				this.targetMoveTo(this.dashX + ca*overflow, this.dashY + sa*overflow);
			segLength -= overflow;
			overflow = 0;
			isLine = !isLine;
			if (!segLength)
				return;
		}

		var fullDashCount = Math.floor(segLength/dashLength);
		if (fullDashCount)
		{
			var onx = ca*onLength,	ony = sa*onLength;
			var offx = ca*offLength,	offy = sa*offLength;
			for (var i=0; i<fullDashCount; i++)
			{
				if (isLine)
				{
					this.targetLineTo(this.dashX+onx, this.dashY+ony);
					this.targetMoveTo(this.dashX+offx, this.dashY+offy);
				}
        else
				{
					this.targetMoveTo(this.dashX+offx, this.dashY+offy);
					this.targetLineTo(this.dashX+onx, this.dashY+ony);
				}
			}
			segLength -= dashLength*fullDashCount;
		}

		if (isLine)
		{
			if (segLength > onLength)
			{
				this.targetLineTo(this.dashX+ca*onLength, this.dashY+sa*onLength);
				this.targetMoveTo(x, y);
				overflow = offLength-(segLength-onLength);
				isLine = false;
			}
      else
			{
				this.targetLineTo(x, y);
				if (segLength == onLength)
				{
					overflow = 0;
					isLine = !isLine;
				}
        else
				{
					overflow = onLength-segLength;
					this.targetMoveTo(x, y);
				}
			}
		}
    else
		{
			if (segLength > offLength)
			{
				this.targetMoveTo(this.dashX+ca*offLength, this.dashY+sa*offLength);
				this.targetLineTo(x, y);
				overflow = onLength-(segLength-offLength);
				isLine = true;
			}
      else
			{
				this.targetMoveTo(x, y);
				if (segLength == offLength)
				{
					overflow = 0;
					isLine = !isLine;
				}
        else
					overflow = offLength-segLength;
			}
		}
	}

  CvsGraphCtx.prototype.polyLine = function(data) // data is either data[n] or data[n][2]
  {
    if (this.rotA)
    {
      this.ctx.save();
      // setup for rotation by rot degrees
      this.ctx.translate(this.rotX, this.rotY);   // move transform origin to the requested origin
      this.ctx.rotate(this.rotA);
      this.ctx.translate(-this.rotX, -this.rotY);   // move origin back
    }

    this._setCtx();   // set up the stroke and fill styles
    this.ctx.beginPath();    // reset current path to null and move to 0,0
    if (data[0][0] != undefined)    // data is 2D array (or 3D but just use first 2 values) x=data[n][0], y=data[n][1]
    {
      this.ctx.moveTo(this.vpLLx+this.xoffset+data[0][0]*this.xscl, this.vpLLy+this.yoffset+data[0][1]*this.yscl);
      for (var i=1; i<data.length; i++)
        this.ctx.lineTo(this.vpLLx+this.xoffset+data[i][0]*this.xscl, this.vpLLy+this.yoffset+data[i][1]*this.yscl);
      this.ctx.stroke();
      // now update pen position
      this.penX = data[data.length-1][0];
      this.penY = data[data.length-1][1];
    }
    else // data is a simple array x values are data[0], data[2] ... data[2*n], y values data[1] data[3].. data[2*n+1]
    {
      this.ctx.moveTo(this.vpLLx+this.xoffset+data[0]*this.xscl, this.vpLLy+this.yoffset+data[1]*this.yscl);
      for (var i=1; i<data.length/2; i++)
      {
        this.ctx.lineTo(this.vpLLx+this.xoffset+data[2*i]*this.xscl, this.vpLLy+this.yoffset+data[2*i+1]*this.yscl);
      }
      this.ctx.stroke();
      // now update pen position
      this.penX = data[data.length-2];
      this.penY = data[data.length-1];
    }

    if (this.rotA)
      this.ctx.restore();
  }

  CvsGraphCtx.prototype.arrow = function(x1, y1, x2, y2, size)
  {
    var a = 20;   // half angle of arrow head in degrees
    var scale = 2;     // default size
    var dx = (x2-x1)*this.xscl;         // pixels
    var dy = (y2-y1)*this.yscl;
    var theta = -Math.atan2(dy, dx);
    var phiL = theta + Math.PI - (a*Math.PI/180.0);
    var phiR = theta + Math.PI + (a*Math.PI/180.0);
    var phiC = theta + Math.PI;

    if (size != undefined)
      scale = size;
    if (size<1)
      scale = 1;
    if (size>9)
      scale = 9;

    var r = scale*(3+4*Math.sqrt(this.penWid));     // size of arrow head, at least as wide as the line
    var x3 = x2 + r*Math.cos(phiL)/this.xscl;
    var y3 = y2 - r*Math.sin(phiL)/this.yscl;
    var x4 = x2 + r*Math.cos(phiR)/this.xscl;
    var y4 = y2 - r*Math.sin(phiR)/this.yscl;
    var xs = x2 + 0.9*r*Math.cos(phiC)/this.xscl;
    var ys = y2 - 0.9*r*Math.sin(phiC)/this.yscl;

    if (this.rotA)
    {
      this.ctx.save();
      // setup for rotation by rot degrees
      this.ctx.translate(this.rotX, this.rotY);   // move transform origin to the requested origin
      this.ctx.rotate(this.rotA);
      this.ctx.translate(-this.rotX, -this.rotY);   // move origin back
    }

    this._setCtx();   // set up the stroke and fill styles to current pen color
    this.ctx.beginPath();    // reset current path to null and move to 0,0
    this.ctx.moveTo(this.vpLLx+this.xoffset+x1*this.xscl, this.vpLLy+this.yoffset+y1*this.yscl);
    // stroke the line just short of the end so we get the sharp end point
    this.ctx.lineTo(this.vpLLx+this.xoffset+xs*this.xscl, this.vpLLy+this.yoffset+ys*this.yscl);
    this.ctx.stroke();
    this.ctx.moveTo(this.vpLLx+this.xoffset+x2*this.xscl, this.vpLLy+this.yoffset+y2*this.yscl);
    this.ctx.lineTo(this.vpLLx+this.xoffset+x3*this.xscl, this.vpLLy+this.yoffset+y3*this.yscl);
    this.ctx.lineTo(this.vpLLx+this.xoffset+x4*this.xscl, this.vpLLy+this.yoffset+y4*this.yscl);
    this.ctx.lineTo(this.vpLLx+this.xoffset+x2*this.xscl, this.vpLLy+this.yoffset+y2*this.yscl);
    this.ctx.fill();       // fill with current pen color

    if (this.rotA)
      this.ctx.restore();

    // update world coords of pen
		this.penX = x2;
		this.penY = y2;
  }

  CvsGraphCtx.prototype.rect = function(x, y, w, h, fillColor)
  {
    if (this.rotA)
    {
      this.ctx.save();
      // setup for rotation by rot degrees
      this.ctx.translate(this.rotX, this.rotY);   // move transform origin to the requested origin
      this.ctx.rotate(this.rotA);
      this.ctx.translate(-this.rotX, -this.rotY);   // move origin back
    }

    this._setCtx();   // set up the stroke and fill styles
    var fill = null;
    if ((fillColor != undefined)&&(fillColor != null))
    {
      this.ctx.closePath();          // its got some fillColor so close the path
      if (typeof fillColor == "object")  // test for gradient object
        fill = fillColor;
      else  // check if its a color string
      {
        var newCol = new RGBAColor(fillColor);
        if (newCol.ok)                          // else color doesn't change uses penCol
          fill = newCol.toRGBA();
      }
    }
    if (fill != null)
    {
      this.ctx.fillStyle = fill;
      this.ctx.fillRect(this.vpLLx+this.xoffset+x*this.xscl, this.vpLLy+this.yoffset+y*this.yscl, w*this.xscl, h*this.yscl);
    }
    this.ctx.strokeRect(this.vpLLx+this.xoffset+x*this.xscl, this.vpLLy+this.yoffset+y*this.yscl, w*this.xscl, h*this.yscl);

    this._setCtx();   // restore the stroke and fill styles
    if (this.rotA)    // restore rotation state
      this.ctx.restore();
  }

  CvsGraphCtx.prototype.shape = function(shape, x, y, size, fillColor)
  {
    var xLofs, yLofs;  /* label origin offsets */

    var d = size*this.xscl;     // size in x axis units

    if (this.rotA)
    {
      this.ctx.save();
      // setup for rotation by rot degrees
      this.ctx.translate(this.rotX, this.rotY);   // move transform origin to the requested origin
      this.ctx.rotate(this.rotA);
      this.ctx.translate(-this.rotX, -this.rotY);   // move origin back
    }

    this._setCtx();   // set up the stroke and fill styles
    var fill = null;
    if ((fillColor != undefined)&&(fillColor != null))
    {
      if (typeof fillColor == "object")  // test for gradient object
        fill = fillColor;
      else  // check if its a color string
      {
        var newCol = new RGBAColor(fillColor);
        if (newCol.ok)                          // else color doesn't change uses penCol
          fill = newCol.toRGBA();
      }
    }
    if (fill)
      this.ctx.fillStyle = fill;

    switch (shape)
    {
      case "square":
        xLofs = -0.5*d;
        yLofs = -0.5*d;
        if (fill)
          this.ctx.fillRect(this.vpLLx+this.xoffset+x*this.xscl+xLofs, this.vpLLy+this.yoffset+y*this.yscl+yLofs, d, d);
        this.ctx.strokeRect(this.vpLLx+this.xoffset+x*this.xscl+xLofs, this.vpLLy+this.yoffset+y*this.yscl+yLofs, d, d);
        break;
      case "triangle":
        this.ctx.beginPath();                             // Begin a shape
        xLofs = 0;
        yLofs = -0.5747*d;
        this.ctx.moveTo(this.vpLLx+this.xoffset+x*this.xscl+xLofs, this.vpLLy+this.yoffset+y*this.yscl+yLofs);
        xLofs = 0.5*d;
        yLofs = 0.2887*d;
        this.ctx.lineTo(this.vpLLx+this.xoffset+x*this.xscl+xLofs, this.vpLLy+this.yoffset+y*this.yscl+yLofs);
        xLofs = -0.5*d;
        yLofs = 0.2887*d;
        this.ctx.lineTo(this.vpLLx+this.xoffset+x*this.xscl+xLofs, this.vpLLy+this.yoffset+y*this.yscl+yLofs);
        this.ctx.closePath();
        if (fill)
          this.ctx.fill();                                  // Fill the shape
        this.ctx.stroke();
        break;
      case "circle":
      default:
        // method of drawing circle with an arc in excanvas is sensitve:
        // IE will not render if arc goes from 0 to 2*PI so stop short and join
        var ccw = false;

        this.ctx.beginPath();
        this.ctx.arc(this.vpLLx+this.xoffset+x*this.xscl, this.vpLLy+this.yoffset+y*this.yscl, 0.5*d, 0, 1.95*Math.PI, ccw);
        this.ctx.closePath();  // not required for fills
        if (fill)
          this.ctx.fill();
        this.ctx.stroke();
    }

    this._setCtx();   // restore the stroke and fill styles
    if (this.rotA)    // restore rotation state
      this.ctx.restore();
  }

  CvsGraphCtx.prototype.drawImg = function(imgURL, x, y, w, lorg, degs)
  {
    var img = new Image();

    // load the image
    img.src = imgURL;

    this.updateImg(img, x, y, w, lorg, degs);

    return img;
  }

  CvsGraphCtx.prototype.updateImg = function(img, x, y, w, lorg, degs)
  {
    var savThis = this;         // save 'this' for the closure when called back
    var savRotX = this.rotX;    // this try to save the rotation values when updateImg first called
    var savRotY = this.rotY;    // when internal function colled by timer these will have reset to 0
    var savRotA = this.rotA;

    function updateImgCallback()
    {
      if (img.complete)  // image loaded?
        modifyImg.call(savThis, img, x, y, w, lorg, degs, savRotX, savRotY, savRotA);
      else  // not loaded yet
        setTimeout(function(){updateImgCallback.call(savThis)}, 50);
    }
    updateImgCallback();
  }

  function modifyImg(img, x, y, w, lorg, degs, rotX, rotY, rotA)
  {
    var reScale = w*this.xscl/img.width;   // canvas is to be scaled by this pixel ratio
    var xLofs, yLofs;  /* label origin offsets */
    var rot = 0;

    if (degs != undefined)
      rot = -degs*Math.PI/180.0;      // measure angles counter clockwise

    switch (lorg)
    {
      case 1:
        xLofs = 0;
        yLofs = 0;
        break;
      case 2:
        xLofs = 0.5*img.width;          // work in pixels assume image already scaled
        yLofs = 0;
        break;
      case 3:
        xLofs = img.width;
        yLofs = 0;
        break;
      case 4:
        xLofs = 0;
        yLofs = 0.5*img.height;
        break;
      case 5:
        xLofs = 0.5*img.width;
        yLofs = 0.5*img.height;
        break;
      case 6:
        xLofs = img.width;
        yLofs = 0.5*img.height;
        break;
      case 7:
        xLofs = 0;
        yLofs = img.height;
        break;
      case 8:
        xLofs = 0.5*img.width;
        yLofs = img.height;
        break;
      case 9:
        xLofs = img.width;
        yLofs = img.height;
        break;
      default:
        xLofs = 0;
        yLofs = 0;
    }

    this.ctx.save();   // save the clean ctx

    if (rotA)      // world coords may be rotated
    {
      // setup for rotation by rot degrees
      this.ctx.translate(rotX, rotY);   // move transform origin to the requested origin
      this.ctx.rotate(rotA);
      this.ctx.translate(-rotX, -rotY);   // move origin back
    }
    // This is just the image getting raotated abot the Lorg point
    this.ctx.translate(this.vpLLx+x*this.xscl+this.xoffset, this.vpLLy+y*this.yscl+this.yoffset);
    this.ctx.rotate(rot);

    this.ctx.drawImage(img, -reScale*xLofs, -reScale*yLofs, reScale*img.width, reScale*img.height);
    this.ctx.restore();
  }

  CvsGraphCtx.prototype.label = function(str, x, y, lorg, degs, ptSize)
  {
    var fSize = this.fontSize;
    var xLofs, yLofs;  /* label origin offsets */
    var rot = 0;

    if (degs != undefined)
      rot = -degs*Math.PI/180.0;      // measure angles counter clockwise

    if ((ptSize != undefined)&&(ptSize>4))    // ptSize is points
      fSize = ptSize;

    var strLen = CanvasTextFunctions.measure(0, fSize, str);

    /* Note: char cell is 33 pixels high, char size is 21 pixels (0 to 21), decenders go to -7 to 21.
       passing 'size' to text function scales char height by size/25.
       So reference height for vertically alignment is charHeight = 21/25 (=0.84) of the fontSize. */
    switch (lorg)
    {
      case 1:
      default:
        xLofs = 0;
        yLofs = 0.84*fSize;
        break;
      case 2:
        xLofs = 0.5*strLen;
        yLofs = 0.84*fSize;
        break;
      case 3:
        xLofs = strLen;
        yLofs = 0.84*fSize;
        break;
      case 4:
        xLofs = 0;
        yLofs = 0.42*fSize;
        break;
      case 5:
        xLofs = 0.5*strLen;
        yLofs = 0.42*fSize;
        break;
      case 6:
        xLofs = strLen;
        yLofs = 0.42*fSize;
        break;
      case 7:
        xLofs = 0;
        yLofs = 0;
        break;
      case 8:
        xLofs = 0.5*strLen;
        yLofs = 0;
        break;
      case 9:
        xLofs = strLen;
        yLofs = 0;
        break;
    }
    this.ctx.save();   // save the clean ctx

    if (this.rotA)
    {
      // setup for rotation by rot degrees
      this.ctx.translate(this.rotX, this.rotY);   // move transform origin to the requested origin
      this.ctx.rotate(this.rotA);
      this.ctx.translate(-this.rotX, -this.rotY);   // move origin back
    }

    this._setCtx();   // set up the stroke and fill styles
    // setup for rotation by rot degrees
    this.ctx.translate(this.vpLLx+x*this.xscl+this.xoffset, this.vpLLy+y*this.yscl+this.yoffset);       // move origin to the lorg point of the text
    this.ctx.rotate(rot);
    CanvasTextFunctions.draw(this.ctx, 0, fSize, -xLofs, yLofs, str);
    this.ctx.restore();
  }

  /* svgPath2 is for 3rd party software compatablity svgPath data expects y coords +ve DOWN
     'data' is a string cut and pasted from 'd' parameter of svg path
     'xRef, yRef' is a point in svg coords that will become the reference point for drawing in canvas
     'x, y' is the point in world coords on the canvas that the reference point will be drawn
     'cx, cy' is an optional point in svg coord space that will be centre of rotation by angle 'deg'
  */
  CvsGraphCtx.prototype.svgPath2 = function(data, x, y, xRef, yRef, scl, fillColor, cx, cy, degs)
  {
    var drawCmds = [];
    var worldCoords = [];
    var xOfs = xRef || 0;
    var yOfs = -yRef || 0;
    var xScale = 1;
    var yScale = -1;
    var fill = null;
    var rot = 0;

    if (degs != undefined)
      rot = -degs*Math.PI/180.0;      // measure angles counter clockwise

    if ((scl != undefined)&&(scl>0))
    {
      xScale *= scl;
      yScale *= scl;
    }

    if (this.rotA)
    {
      this.ctx.save();   // save the clean ctx  (there may be a double rotate)
      // setup for rotation by rotA degrees
      this.ctx.translate(this.rotX, this.rotY);   // move transform origin to the requested origin
      this.ctx.rotate(this.rotA);
      this.ctx.translate(-this.rotX, -this.rotY);   // move origin back
    }

    // This is just the SVG shape getting rotated about the point cx, cy
    if (rot)
    {
      this.ctx.save();   // save current rotation if any
      this.ctx.translate(this.vpLLx+(x+xScale*(cx-xOfs))*this.xscl+this.xoffset, this.vpLLy+(y+yScale*(cy+yOfs))*this.yscl+this.yoffset);
      this.ctx.rotate(rot);
      this.ctx.translate(-(this.vpLLx+(x+xScale*(cx-xOfs))*this.xscl+this.xoffset), -(this.vpLLy+(y+yScale*(cy+yOfs))*this.yscl+this.yoffset));
    }

    drawCmds = this.compileSVGPath(data, -xOfs*xScale, yOfs*yScale, xScale, yScale);  // get canvas drawCmds as strings with array of parameters

    this.ctx.beginPath();
    for (var i=0; i<drawCmds.length; i++)
    {
      for (var j=0; j<drawCmds[i].parms.length; j+=2)      // convert x,y coord pairs to world coords
      {
        worldCoords[j] = this.vpLLx+this.xoffset+(x+drawCmds[i].parms[j])*this.xscl;
        worldCoords[j+1] = this.vpLLy+this.yoffset+(y+drawCmds[i].parms[j+1])*this.yscl;
      }
      this.ctx[drawCmds[i].drawFn].apply(this.ctx, worldCoords);   // actually draw the SVG shape
    }
    // svgPath now drawn (but not filled) or stroked

    // set up the stroke and fill styles
    this._setCtx();
    if ((fillColor != undefined)&&(fillColor != null))
    {
      this.ctx.closePath();          // its got some fillColor so close the path
      if (typeof fillColor == "object")  // test for gradient object
        fill = fillColor;
      else  // check if its a color string
      {
        var newCol = new RGBAColor(fillColor);
        if (newCol.ok)                          // else color doesn't change uses penCol
          fill = newCol.toRGBA();
      }
    }
    // before we fill and stroke undo any rotation so fill pattern is not rotated
    if (rot)
      this.ctx.restore();   // restore rotation from 'rot' so fill pattern not rotated

    if (fill != null)
    {
      this.ctx.fillStyle = fill;
      this.ctx.fill();
    }
    this.ctx.stroke();

    this._setCtx();   // restore the stroke and fill styles
    if (this.rotA)    // restore from any world rotate
      this.ctx.restore();
  }

  CvsGraphCtx.prototype.svgPath = function(data, x, y, scl, fillColor, cx, cy, degs)
  {
    var drawCmds = [];
    var worldCoords = [];
    var xOfs = x || 0;          // use a different name x, y are used as current point in SVG parsing
    var yOfs = y || 0;
    var xScale = 1;
    var yScale = 1;
    var fill = null;
    var rot = 0;
    var strData;

    if (data instanceof Array)
      strData = data.toString();
    else
      strData = data;  // already a string

    if (degs != undefined)
      rot = -degs*Math.PI/180.0;      // measure angles counter clockwise

    if ((scl != undefined)&&(scl>0))
    {
      xScale *= scl;
      yScale *= scl;
    }

    if (this.rotA)
    {
      this.ctx.save();   // save the clean ctx  (there may be a double rotate)
      // setup for rotation by rotA degrees
      this.ctx.translate(this.rotX, this.rotY);   // move transform origin to the requested origin
      this.ctx.rotate(this.rotA);
      this.ctx.translate(-this.rotX, -this.rotY);   // move origin back
    }

    // This is just the SVG shape getting rotated about the point cx, cy
    if (rot)
    {
      this.ctx.save();   // save current rotation if any
      this.ctx.translate(this.vpLLx+(xOfs+xScale*cx)*this.xscl+this.xoffset, this.vpLLy+(yOfs+yScale*cy)*this.yscl+this.yoffset);
      this.ctx.rotate(rot);
      this.ctx.translate(-(this.vpLLx+(xOfs+xScale*cx)*this.xscl+this.xoffset), -(this.vpLLy+(yOfs+yScale*cy)*this.yscl+this.yoffset));
    }

    drawCmds = this.compileSVGPath(strData, xOfs, yOfs, xScale, yScale);  // get canvas drawCmds as strings with array of parameters

    this.ctx.beginPath();
    for (var i=0; i<drawCmds.length; i++)
    {
      for (var j=0; j<drawCmds[i].parms.length; j+=2)      // convert x,y coord pairs to world coords
      {
        worldCoords[j] = this.vpLLx+this.xoffset+drawCmds[i].parms[j]*this.xscl;
        worldCoords[j+1] = this.vpLLy+this.yoffset+drawCmds[i].parms[j+1]*this.yscl;
      }
      this.ctx[drawCmds[i].drawFn].apply(this.ctx, worldCoords);   // actually draw the SVG shape
    }

    // set up the stroke and fill styles
    this._setCtx();
    if ((fillColor != undefined)&&(fillColor != null))
    {
      this.ctx.closePath();          // its got some fillColor so close the path
      if (typeof fillColor == "object")  // test for gradient object
        fill = fillColor;
      else  // check if its a color string
      {
        var newCol = new RGBAColor(fillColor);
        if (newCol.ok)                          // else color doesn't change uses penCol
          fill = newCol.toRGBA();
      }
    }
    // before we fill and stroke undo any rotation so fill pattern is not rotated
    if (rot)
      this.ctx.restore();   // restore rotation from 'rot' so fill pattern not rotated

    if (fill != null)
    {
      this.ctx.fillStyle = fill;
      this.ctx.fill();
    }
    this.ctx.stroke();

    this._setCtx();   // restore the stroke and fill styles
    if (this.rotA)    // restore from any world rotate
      this.ctx.restore();
  }

  function DrawCmd(cmdStr, coords)
  {
    this.drawFn = cmdStr;       // String version of the canvas command to call
    this.parms = coords || [];  // array of parameters to pass to drawFn
  }

  // Significantly modified version of the 'cake.js' compileSVGPath
  CvsGraphCtx.prototype.compileSVGPath = function(svgPath, xOffset, yOffset, xScl, yScl)
  {
    var xOfs = xOffset || 0;
    var yOfs = yOffset || 0;
    var xScale = xScl || 1;
    var yScale = yScl || 1;
    var segs = svgPath.split(/(?=[a-df-z])/i);  // avoid e in exponents
    var x = 0;
    var y = 0;
    var px, py;
    var cmd, pc;
    var cmdObj;
    var seg, cmdLetters, coords;
    var commands = [];

    for (var i=0; i<segs.length; i++)
    {
      seg = segs[i];
      cmdLetters = seg.match(/[a-z]/i);
      if (!cmdLetters)
        return [];
      cmd = cmdLetters[0];
      if ((i==0)&&(cmd != 'M'))   // check that the first move is absolute
        cmd = 'M';
      coords = seg.match(/[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?/gi);
      if (coords)
        coords = coords.map(parseFloat);
      switch(cmd)
      {
        case 'M':
          x = xOfs + xScale*coords[0];
          y = yOfs + yScale*coords[1];
          px = py = null;
          cmdObj = new DrawCmd('moveTo', [x, y]);
          commands.push(cmdObj);
          coords.splice(0, 2);      // delete the 2 coords from the front of the array
          while (coords.length>0)
          {
            x = xOfs + xScale*coords[0];                // eqiv to muliple 'L' calls
            y = yOfs + yScale*coords[1];
            cmdObj = new DrawCmd('lineTo', [x, y]); // any coord pair after first move is regarded as line
            commands.push(cmdObj);
            coords.splice(0, 2);
          }
          break
        case 'm':
          x += xScale*coords[0];
          y += yScale*coords[1];
          px = py = null;
          cmdObj = new DrawCmd('moveTo', [x, y]);
          commands.push(cmdObj);
          coords.splice(0, 2);      // delete the 2 coords from the front of the array
          while (coords.length>0)
          {
            x += xScale*coords[0];                     // eqiv to muliple 'l' calls
            y += yScale*coords[1];
            cmdObj = new DrawCmd('lineTo', [x, y]); // any coord pair after first move is regarded as line
            commands.push(cmdObj);
            coords.splice(0, 2);
          }
          break

        case 'L':
          while (coords.length>0)
          {
            x = xOfs + xScale*coords[0];
            y = yOfs + yScale*coords[1];
            cmdObj = new DrawCmd('lineTo', [x, y]);
            commands.push(cmdObj);
            coords.splice(0, 2);
          }
          px = py = null;
          break
        case 'l':
          while (coords.length>0)
          {
            x += xScale*coords[0];
            y += yScale*coords[1];
            cmdObj = new DrawCmd('lineTo', [x, y]);
            commands.push(cmdObj);
            coords.splice(0, 2);
          }
          px = py = null
          break
        case 'H':
          x = xOfs + xScale*coords[0];
          px = py = null ;
          cmdObj = new DrawCmd('lineTo', [x, y]);
          commands.push(cmdObj);
          break
        case 'h':
          x += xScale*coords[0];
          px = py = null ;
          cmdObj = new DrawCmd('lineTo', [x, y]);
          commands.push(cmdObj);
          break
        case 'V':
          y = yOfs + yScale*coords[0];
          px = py = null;
          cmdObj = new DrawCmd('lineTo', [x, y]);
          commands.push(cmdObj);
          break
        case 'v':
          y += yScale*coords[0];
          px = py = null;
          cmdObj = new DrawCmd('lineTo', [x, y]);
          commands.push(cmdObj);
          break
        case 'C':
          while (coords.length>0)
          {
            c1x = xOfs + xScale*coords[0];
            c1y = yOfs + yScale*coords[1];
            px = xOfs + xScale*coords[2];
            py = yOfs + yScale*coords[3];
            x = xOfs + xScale*coords[4];
            y = yOfs + yScale*coords[5];
            cmdObj = new DrawCmd('bezierCurveTo', [c1x, c1y, px, py, x, y]);
            commands.push(cmdObj);
            coords.splice(0, 6);
          }
          break
        case 'c':
          while (coords.length>0)
          {
            c1x = x + xScale*coords[0];
            c1y = y + yScale*coords[1];
            px = x + xScale*coords[2];
            py = y + yScale*coords[3];
            x += xScale*coords[4];
            y += yScale*coords[5];
            cmdObj = new DrawCmd('bezierCurveTo', [c1x, c1y, px, py, x, y]);
            commands.push(cmdObj);
            coords.splice(0, 6);
          }
          break
        case 'S':
          if (px == null || !pc.match(/[sc]/i))
          {
            px = x;                // already absolute coords
            py = y;
          }
          cmdObj = new DrawCmd('bezierCurveTo', [x-(px-x), y-(py-y),
                                                    xOfs + xScale*coords[0], yOfs + yScale*coords[1],
                                                    xOfs + xScale*coords[2], yOfs + yScale*coords[3]]);
          commands.push(cmdObj);
          px = xOfs + xScale*coords[0];
          py = yOfs + yScale*coords[1];
          x = xOfs + xScale*coords[2];
          y = yOfs + yScale*coords[3];
          break
        case 's':
          if (px == null || !pc.match(/[sc]/i))
          {
            px = x;
            py = y;
          }
          cmdObj = new DrawCmd('bezierCurveTo', [x-(px-x), y-(py-y),
                                                    xOfs + xScale*coords[0], yOfs + yScale*coords[1],
                                                    xOfs + xScale*coords[2], yOfs + yScale*coords[3]]);
          commands.push(cmdObj);
          px = x + xScale*coords[0];
          py = y + yScale*coords[1];
          x += xScale*coords[2];
          y += yScale*coords[3];
          break
        case 'Q':
          px = xOfs + xScale*coords[0];
          py = yOfs + yScale*coords[1];
          x = xOfs + xScale*coords[2];
          y = yOfs + yScale*coords[3];
          cmdObj = new DrawCmd('quadraticCurveTo', [px, py, x, y]);
          commands.push(cmdObj);
          break
        case 'q':
          cmdObj = new DrawCmd('quadraticCurveTo', [x + xScale*coords[0], y + yScale*coords[1],
                                                       x + xScale*coords[2], y + yScale*coords[3]]);
          commands.push(cmdObj);
          px = x + xScale*coords[0];
          py = y + yScale*coords[1];
          x += xScale*coords[2];
          y += yScale*coords[3];
          break
        case 'T':
          if (px == null || !pc.match(/[qt]/i))
          {
            px = x;
            py = y;
          }
          else
          {
            px = x-(px-x);
            py = y-(py-y);
          }
          cmdObj = new DrawCmd('quadraticCurveTo', [px, py,
                                                        xOfs + xScale*coords[0], yOfs + yScale*coords[1]]);
          commands.push(cmdObj);
          px = x-(px-x);
          py = y-(py-y);
          x = xOfs + xScale*coords[0];
          y = yOfs + yScale*coords[1];
          break
        case 't':
          if (px == null || !pc.match(/[qt]/i))
          {
            px = x;
            py = y;
          }
          else
          {
            px = x-(px-x);
            py = y-(py-y);
          }
          cmdObj = new DrawCmd('quadraticCurveTo', [px, py,
                                                        x + xScale*coords[0], y + yScale*coords[1]]);
          commands.push(cmdObj);
          x += xScale*coords[0];
          y += yScale*coords[1];
          break
        case 'A':
          coords[0] *= xScale;
          coords[1] *= xScale;
          coords[2] *= -1;          // rotationX: swap for CCW +ve
          // coords[3] large arc    should be ok
          coords[4] = 1 - coords[4];  // sweep: swap for CCW +ve
          coords[5] *= xScale;
          coords[5] += xOfs;
          coords[6] *= yScale;
          coords[6] += yOfs;
          var arc_segs = this.arcToBezier(x, y, coords[0], coords[1], coords[2], coords[3], coords[4], coords[5], coords[6]);
          for (var l=0; l<arc_segs.length; l++)
          {
            cmdObj = new DrawCmd('bezierCurveTo', arc_segs[l]);
            commands.push(cmdObj);
          }
          x = coords[5];
          y = coords[6];
          break
        case 'a':
          coords[0] *= xScale;
          coords[1] *= xScale;
          coords[2] *= -1;          // rotationX: swap for CCW +ve
          // coords[3] large arc    should be ok
          coords[4] = 1 - coords[4];  // sweep: swap for CCW +ve
          coords[5] *= xScale;
          coords[5] += x;
          coords[6] *= yScale;
          coords[6] += y;
          var arc_segs = this.arcToBezier(x, y, coords[0], coords[1], coords[2], coords[3], coords[4], coords[5], coords[6]);
          for (var l=0; l<arc_segs.length; l++)
          {
            cmdObj = new DrawCmd('bezierCurveTo', arc_segs[l]);
            commands.push(cmdObj);
          }
          x = coords[5];
          y = coords[6];
          break
        case 'Z':
          cmdObj = new DrawCmd('closePath', []);
          commands.push(cmdObj);
          break
        case 'z':
          cmdObj = new DrawCmd('closePath', []);
          commands.push(cmdObj);
          break
      }
      pc = cmd     // save the previous command for possible reflected control points
    }
    return commands
  }

  CvsGraphCtx.prototype.arcToBezier = function(ox, oy, rx, ry, rotateX, large, sweep, x, y)
  {
    var th = rotateX * (Math.PI/180)
    var sin_th = Math.sin(th)
    var cos_th = Math.cos(th)
    rx = Math.abs(rx)
    ry = Math.abs(ry)
    var px = cos_th * (ox - x) * 0.5 + sin_th * (oy - y) * 0.5
    var py = cos_th * (oy - y) * 0.5 - sin_th * (ox - x) * 0.5
    var pl = (px*px) / (rx*rx) + (py*py) / (ry*ry)
    if (pl > 1)
    {
      pl = Math.sqrt(pl)
      rx *= pl
      ry *= pl
    }

    var a00 = cos_th / rx
    var a01 = sin_th / rx
    var a10 = (-sin_th) / ry
    var a11 = (cos_th) / ry
    var x0 = a00 * ox + a01 * oy
    var y0 = a10 * ox + a11 * oy
    var x1 = a00 * x + a01 * y
    var y1 = a10 * x + a11 * y

    var d = (x1-x0) * (x1-x0) + (y1-y0) * (y1-y0)
    var sfactor_sq = 1 / d - 0.25
    if (sfactor_sq < 0)
      sfactor_sq = 0
    var sfactor = Math.sqrt(sfactor_sq)
    if (sweep == large)
      sfactor = -sfactor
    var xc = 0.5 * (x0 + x1) - sfactor * (y1-y0)
    var yc = 0.5 * (y0 + y1) + sfactor * (x1-x0)

    var th0 = Math.atan2(y0-yc, x0-xc)
    var th1 = Math.atan2(y1-yc, x1-xc)

    var th_arc = th1-th0
    if (th_arc < 0 && sweep == 1)
    {
      th_arc += 2*Math.PI
    }
    else if (th_arc > 0 && sweep == 0)
    {
      th_arc -= 2 * Math.PI
    }

    var segments = Math.ceil(Math.abs(th_arc / (Math.PI * 0.5 + 0.001)))
    var result = []
    for (var i=0; i<segments; i++)
    {
      var th2 = th0 + i * th_arc / segments
      var th3 = th0 + (i+1) * th_arc / segments
      result.push(this.segmentToBezier(xc, yc, th2, th3, rx, ry, sin_th, cos_th));
    }

    return result
  }

  CvsGraphCtx.prototype.segmentToBezier = function(cx, cy, th0, th1, rx, ry, sin_th, cos_th)
  {
    var a00 = cos_th * rx
    var a01 = -sin_th * ry
    var a10 = sin_th * rx
    var a11 = cos_th * ry

    var th_half = 0.5 * (th1 - th0)
    var t = (8/3) * Math.sin(th_half * 0.5) * Math.sin(th_half * 0.5) / Math.sin(th_half)
    var x1 = cx + Math.cos(th0) - t * Math.sin(th0)
    var y1 = cy + Math.sin(th0) + t * Math.cos(th0)
    var x3 = cx + Math.cos(th1)
    var y3 = cy + Math.sin(th1)
    var x2 = x3 + t * Math.sin(th1)
    var y2 = y3 - t * Math.cos(th1)
    return [
              a00 * x1 + a01 * y1, a10 * x1 + a11 * y1,
              a00 * x2 + a01 * y2, a10 * x2 + a11 * y2,
              a00 * x3 + a01 * y3, a10 * x3 + a11 * y3
            ]
  }

  if (!Array.prototype.map)
  {
    Array.prototype.map = function(fun)
    {
      var len = this.length;
      if (typeof fun != "function")
        throw new TypeError();

      var res = new Array(len);
      var thisp = arguments[1];
      for (var i = 0; i < len; i++)
      {
        if (i in this)
          res[i] = fun.call(thisp, this[i], i, this);
      }

      return res;
    };
  }

  // copy the basic graphics context values (for an overlay)
  CvsGraphCtx.prototype.dupCtx = function(src_graphCtx)
  {
    // copy all the graphics context parameters into the overlay ctx.
    this.vpLLx = src_graphCtx.vpLLx;      // vp lower left from canvas left in pixels
    this.vpLLy = src_graphCtx.vpLLy;      // vp lower left from canvas top
    this.xscl = src_graphCtx.xscl;        // world x axis scale factor
    this.yscl = src_graphCtx.yscl;        // world y axis scale factor
    this.xoffset = src_graphCtx.xoffset;  // world x origin offset from viewport left in pixels
    this.yoffset = src_graphCtx.yoffset;  // world y origin offset from viewport bottom in pixels
    this.penCol = src_graphCtx.penCol.slice(0);
    this.penWid = src_graphCtx.penWid;    // pixels
    this.lineCap = src_graphCtx.lineCap.slice(0);
    this.bkgCol = src_graphCtx.bkgCol.slice(0);
    this.fontSize = src_graphCtx.fontSize;
    this.penX = src_graphCtx.penX;
    this.penY = src_graphCtx.penY;
  }

  function RGBAColor(color_string)
  {
  /**
   * A class to parse color values
   * @author Stoyan Stefanov <sstoo@gmail.com>
   * @link   http://www.phpied.com/rgb-color-parser-in-javascript/
   * @license Use it if you like it
   *
   * supplemented to handle rgba format (alpha 0 .. 1.0)  by arc 04SEP09
   */
    this.ok = false;
    if (typeof color_string != "string")       // bugfix: crashed if passed a number. arc
      return;

    // strip any leading #
    if (color_string.charAt(0) == '#')  // remove # if any
      color_string = color_string.substr(1,6);

    color_string = color_string.replace(/ /g,'');
    color_string = color_string.toLowerCase();

    // before getting into regexps, try simple matches
    // and overwrite the input
    var simple_colors = {
      aliceblue: 'f0f8ff',
      antiquewhite: 'faebd7',
      aqua: '00ffff',
      aquamarine: '7fffd4',
      azure: 'f0ffff',
      beige: 'f5f5dc',
      bisque: 'ffe4c4',
      black: '000000',
      blanchedalmond: 'ffebcd',
      blue: '0000ff',
      blueviolet: '8a2be2',
      brown: 'a52a2a',
      burlywood: 'deb887',
      cadetblue: '5f9ea0',
      chartreuse: '7fff00',
      chocolate: 'd2691e',
      coral: 'ff7f50',
      cornflowerblue: '6495ed',
      cornsilk: 'fff8dc',
      crimson: 'dc143c',
      cyan: '00ffff',
      darkblue: '00008b',
      darkcyan: '008b8b',
      darkgoldenrod: 'b8860b',
      darkgray: 'a9a9a9',
      darkgreen: '006400',
      darkkhaki: 'bdb76b',
      darkmagenta: '8b008b',
      darkolivegreen: '556b2f',
      darkorange: 'ff8c00',
      darkorchid: '9932cc',
      darkred: '8b0000',
      darksalmon: 'e9967a',
      darkseagreen: '8fbc8f',
      darkslateblue: '483d8b',
      darkslategray: '2f4f4f',
      darkturquoise: '00ced1',
      darkviolet: '9400d3',
      deeppink: 'ff1493',
      deepskyblue: '00bfff',
      dimgray: '696969',
      dodgerblue: '1e90ff',
      feldspar: 'd19275',
      firebrick: 'b22222',
      floralwhite: 'fffaf0',
      forestgreen: '228b22',
      fuchsia: 'ff00ff',
      gainsboro: 'dcdcdc',
      ghostwhite: 'f8f8ff',
      gold: 'ffd700',
      goldenrod: 'daa520',
      gray: '808080',
      green: '008000',
      greenyellow: 'adff2f',
      honeydew: 'f0fff0',
      hotpink: 'ff69b4',
      indianred : 'cd5c5c',
      indigo : '4b0082',
      ivory: 'fffff0',
      khaki: 'f0e68c',
      lavender: 'e6e6fa',
      lavenderblush: 'fff0f5',
      lawngreen: '7cfc00',
      lemonchiffon: 'fffacd',
      lightblue: 'add8e6',
      lightcoral: 'f08080',
      lightcyan: 'e0ffff',
      lightgoldenrodyellow: 'fafad2',
      lightgrey: 'd3d3d3',
      lightgreen: '90ee90',
      lightpink: 'ffb6c1',
      lightsalmon: 'ffa07a',
      lightseagreen: '20b2aa',
      lightskyblue: '87cefa',
      lightslateblue: '8470ff',
      lightslategray: '778899',
      lightsteelblue: 'b0c4de',
      lightyellow: 'ffffe0',
      lime: '00ff00',
      limegreen: '32cd32',
      linen: 'faf0e6',
      magenta: 'ff00ff',
      maroon: '800000',
      mediumaquamarine: '66cdaa',
      mediumblue: '0000cd',
      mediumorchid: 'ba55d3',
      mediumpurple: '9370d8',
      mediumseagreen: '3cb371',
      mediumslateblue: '7b68ee',
      mediumspringgreen: '00fa9a',
      mediumturquoise: '48d1cc',
      mediumvioletred: 'c71585',
      midnightblue: '191970',
      mintcream: 'f5fffa',
      mistyrose: 'ffe4e1',
      moccasin: 'ffe4b5',
      navajowhite: 'ffdead',
      navy: '000080',
      oldlace: 'fdf5e6',
      olive: '808000',
      olivedrab: '6b8e23',
      orange: 'ffa500',
      orangered: 'ff4500',
      orchid: 'da70d6',
      palegoldenrod: 'eee8aa',
      palegreen: '98fb98',
      paleturquoise: 'afeeee',
      palevioletred: 'd87093',
      papayawhip: 'ffefd5',
      peachpuff: 'ffdab9',
      peru: 'cd853f',
      pink: 'ffc0cb',
      plum: 'dda0dd',
      powderblue: 'b0e0e6',
      purple: '800080',
      red: 'ff0000',
      rosybrown: 'bc8f8f',
      royalblue: '4169e1',
      saddlebrown: '8b4513',
      salmon: 'fa8072',
      sandybrown: 'f4a460',
      seagreen: '2e8b57',
      seashell: 'fff5ee',
      sienna: 'a0522d',
      silver: 'c0c0c0',
      skyblue: '87ceeb',
      slateblue: '6a5acd',
      slategray: '708090',
      snow: 'fffafa',
      springgreen: '00ff7f',
      steelblue: '4682b4',
      tan: 'd2b48c',
      teal: '008080',
      thistle: 'd8bfd8',
      tomato: 'ff6347',
      turquoise: '40e0d0',
      violet: 'ee82ee',
      violetred: 'd02090',
      wheat: 'f5deb3',
      white: 'ffffff',
      whitesmoke: 'f5f5f5',
      yellow: 'ffff00',
      yellowgreen: '9acd32'
    };
    for (var key in simple_colors)
    {
      if (color_string == key)
        color_string = simple_colors[key];
    }
    // end of simple type-in colors

    // array of color definition objects
    var color_defs = [
        {
          re: /^rgba\((\d{1,3}),\s*(\d{1,3}),\s*(\d{1,3}),\s*((1(\.0)?)|0?(\.\d*)?)\)$/,
          example: ['rgba(123, 234, 45, 0.5)', 'rgba(255,234,245,1)'],
          process: function (bits){
            return [
              parseInt(bits[1]),
              parseInt(bits[2]),
              parseInt(bits[3]),
              parseFloat(bits[4])
            ];
          }
        },
        {
          re: /^rgb\((\d{1,3}),\s*(\d{1,3}),\s*(\d{1,3})\)$/,
          example: ['rgb(123, 234, 45)', 'rgb(255,234,245)'],
          process: function (bits){
            return [
                parseInt(bits[1]),
                parseInt(bits[2]),
                parseInt(bits[3])
            ];
          }
        },
        {
          re: /^(\w{2})(\w{2})(\w{2})$/,
          example: ['#00ff00', '336699'],
          process: function (bits){
            return [
              parseInt(bits[1], 16),
              parseInt(bits[2], 16),
              parseInt(bits[3], 16)
            ];
          }
        },
        {
          re: /^(\w{1})(\w{1})(\w{1})$/,
          example: ['#fb0', 'f0f'],
          process: function (bits){
            return [
                parseInt(bits[1] + bits[1], 16),
                parseInt(bits[2] + bits[2], 16),
                parseInt(bits[3] + bits[3], 16)
            ];
          }
        }
    ];

    // search through the definitions to find a match
    for (var i = 0; i < color_defs.length; i++)
    {
      var re = color_defs[i].re;
      var processor = color_defs[i].process;
      var bits = re.exec(color_string);
      if (bits)
      {
        var channels = processor(bits);    // bugfix: was global. [ARC 17Jul12]
        this.r = channels[0];
        this.g = channels[1];
        this.b = channels[2];
        if (bits.length>3)
          this.a = channels[3];
        else
          this.a = 1.0;
        this.ok = true;
      }
    }

    // validate/cleanup values
    this.r = (this.r < 0 || isNaN(this.r)) ? 0 : ((this.r > 255) ? 255 : this.r);
    this.g = (this.g < 0 || isNaN(this.g)) ? 0 : ((this.g > 255) ? 255 : this.g);
    this.b = (this.b < 0 || isNaN(this.b)) ? 0 : ((this.b > 255) ? 255 : this.b);
    this.a = (this.a < 0 || isNaN(this.a)) ? 1.0 : ((this.a > 1) ? 1.0 : this.a);

    // some getters
    this.toRGBA = function () {
      return 'rgba(' + this.r + ', ' + this.g + ', '  + this.b + ', ' + this.a + ')';
    }
    this.toRGB = function () {
      return 'rgb(' + this.r + ', ' + this.g + ', ' + this.b + ')';
    }
    this.toHex = function () {
      var r = this.r.toString(16);
      var g = this.g.toString(16);
      var b = this.b.toString(16);
      if (r.length == 1) r = '0' + r;
      if (g.length == 1) g = '0' + g;
      if (b.length == 1) b = '0' + b;
      return '#' + r + g + b;
    }

    // help
    this.getHelpXML = function () {
      var examples = new Array();
      // add regexps
      for (var i = 0; i < color_defs.length; i++)
      {
        var example = color_defs[i].example;
        for (var j = 0; j < example.length; j++)
        {
          examples[examples.length] = example[j];
        }
      }
      // add type-in colors
      for (var sc in simple_colors)
      {
        examples[examples.length] = sc;
      }

      var xml = document.createElement('ul');
      xml.setAttribute('id', 'rgbcolor-examples');
      for (var i = 0; i < examples.length; i++)
      {
        try
        {
          var list_item = document.createElement('li');
          var list_color = new RGBColor(examples[i]);
          var example_div = document.createElement('div');
          example_div.style.cssText =
                  'margin: 3px; '
                  + 'border: 1px solid black; '
                  + 'background:' + list_color.toHex() + '; '
                  + 'color:' + list_color.toHex()
          ;
          example_div.appendChild(document.createTextNode('test'));
          var list_item_value = document.createTextNode(
              ' ' + examples[i] + ' -> ' + list_color.toRGB() + ' -> ' + list_color.toHex()
          );
          list_item.appendChild(example_div);
          list_item.appendChild(list_item_value);
          xml.appendChild(list_item);

        } catch(e){}
      }
      return xml;
    }
  }

  // constructor for position and rotation state of an object in 2D.
  function State2D(x, y, rot, cx, cy)
  {
    this.x = x;
    this.y = y;
    this.rot = rot;
    this.cx = cx;       // center of rotation wrt x,y
    this.cy = cy;
  }

var CanvasTextFunctions = { };
//
// This code is released to the public domain by Jim Studt, 2007.
// He may keep some sort of up to date copy at http://www.federated.com/~jim/canvastext/
//

CanvasTextFunctions.letters = {
    ' ': { width: 16, points: [] },
    '!': { width: 10, points: [[5,21],[5,7],[-1,-1],[5,2],[4,1],[5,0],[6,1],[5,2]] },
    '"': { width: 16, points: [[4,21],[4,14],[-1,-1],[12,21],[12,14]] },
    '#': { width: 21, points: [[11,25],[4,-7],[-1,-1],[17,25],[10,-7],[-1,-1],[4,12],[18,12],[-1,-1],[3,6],[17,6]] },
    '$': { width: 20, points: [[8,25],[8,-4],[-1,-1],[12,25],[12,-4],[-1,-1],[17,18],[15,20],[12,21],[8,21],[5,20],[3,18],[3,16],[4,14],[5,13],[7,12],[13,10],[15,9],[16,8],[17,6],[17,3],[15,1],[12,0],[8,0],[5,1],[3,3]] },
    '%': { width: 24, points: [[21,21],[3,0],[-1,-1],[8,21],[10,19],[10,17],[9,15],[7,14],[5,14],[3,16],[3,18],[4,20],[6,21],[8,21],[10,20],[13,19],[16,19],[19,20],[21,21],[-1,-1],[17,7],[15,6],[14,4],[14,2],[16,0],[18,0],[20,1],[21,3],[21,5],[19,7],[17,7]] },
    '&': { width: 26, points: [[23,12],[23,13],[22,14],[21,14],[20,13],[19,11],[17,6],[15,3],[13,1],[11,0],[7,0],[5,1],[4,2],[3,4],[3,6],[4,8],[5,9],[12,13],[13,14],[14,16],[14,18],[13,20],[11,21],[9,20],[8,18],[8,16],[9,13],[11,10],[16,3],[18,1],[20,0],[22,0],[23,1],[23,2]] },
    '\'': { width: 10, points: [[5,19],[4,20],[5,21],[6,20],[6,18],[5,16],[4,15]] },
    '(': { width: 14, points: [[11,25],[9,23],[7,20],[5,16],[4,11],[4,7],[5,2],[7,-2],[9,-5],[11,-7]] },
    ')': { width: 14, points: [[3,25],[5,23],[7,20],[9,16],[10,11],[10,7],[9,2],[7,-2],[5,-5],[3,-7]] },
    '*': { width: 16, points: [[8,21],[8,9],[-1,-1],[3,18],[13,12],[-1,-1],[13,18],[3,12]] },
    '+': { width: 26, points: [[13,18],[13,0],[-1,-1],[4,9],[22,9]] },
    ',': { width: 10, points: [[6,1],[5,0],[4,1],[5,2],[6,1],[6,-1],[5,-3],[4,-4]] },
    '-': { width: 26, points: [[4,9],[22,9]] },
    '.': { width: 10, points: [[5,2],[4,1],[5,0],[6,1],[5,2]] },
    '/': { width: 22, points: [[20,25],[2,-7]] },
    '0': { width: 20, points: [[9,21],[6,20],[4,17],[3,12],[3,9],[4,4],[6,1],[9,0],[11,0],[14,1],[16,4],[17,9],[17,12],[16,17],[14,20],[11,21],[9,21]] },
    '1': { width: 20, points: [[6,17],[8,18],[11,21],[11,0]] },
    '2': { width: 20, points: [[4,16],[4,17],[5,19],[6,20],[8,21],[12,21],[14,20],[15,19],[16,17],[16,15],[15,13],[13,10],[3,0],[17,0]] },
    '3': { width: 20, points: [[5,21],[16,21],[10,13],[13,13],[15,12],[16,11],[17,8],[17,6],[16,3],[14,1],[11,0],[8,0],[5,1],[4,2],[3,4]] },
    '4': { width: 20, points: [[13,21],[3,7],[18,7],[-1,-1],[13,21],[13,0]] },
    '5': { width: 20, points: [[15,21],[5,21],[4,12],[5,13],[8,14],[11,14],[14,13],[16,11],[17,8],[17,6],[16,3],[14,1],[11,0],[8,0],[5,1],[4,2],[3,4]] },
    '6': { width: 20, points: [[16,18],[15,20],[12,21],[10,21],[7,20],[5,17],[4,12],[4,7],[5,3],[7,1],[10,0],[11,0],[14,1],[16,3],[17,6],[17,7],[16,10],[14,12],[11,13],[10,13],[7,12],[5,10],[4,7]] },
    '7': { width: 20, points: [[17,21],[7,0],[-1,-1],[3,21],[17,21]] },
    '8': { width: 20, points: [[8,21],[5,20],[4,18],[4,16],[5,14],[7,13],[11,12],[14,11],[16,9],[17,7],[17,4],[16,2],[15,1],[12,0],[8,0],[5,1],[4,2],[3,4],[3,7],[4,9],[6,11],[9,12],[13,13],[15,14],[16,16],[16,18],[15,20],[12,21],[8,21]] },
    '9': { width: 20, points: [[16,14],[15,11],[13,9],[10,8],[9,8],[6,9],[4,11],[3,14],[3,15],[4,18],[6,20],[9,21],[10,21],[13,20],[15,18],[16,14],[16,9],[15,4],[13,1],[10,0],[8,0],[5,1],[4,3]] },
    ':': { width: 10, points: [[5,14],[4,13],[5,12],[6,13],[5,14],[-1,-1],[5,2],[4,1],[5,0],[6,1],[5,2]] },
    ';': { width: 10, points: [[5,14],[4,13],[5,12],[6,13],[5,14],[-1,-1],[6,1],[5,0],[4,1],[5,2],[6,1],[6,-1],[5,-3],[4,-4]] },
    '<': { width: 24, points: [[20,18],[4,9],[20,0]] },
    '=': { width: 26, points: [[4,12],[22,12],[-1,-1],[4,6],[22,6]] },
    '>': { width: 24, points: [[4,18],[20,9],[4,0]] },
    '?': { width: 18, points: [[3,16],[3,17],[4,19],[5,20],[7,21],[11,21],[13,20],[14,19],[15,17],[15,15],[14,13],[13,12],[9,10],[9,7],[-1,-1],[9,2],[8,1],[9,0],[10,1],[9,2]] },
    '@': { width: 27, points: [[18,13],[17,15],[15,16],[12,16],[10,15],[9,14],[8,11],[8,8],[9,6],[11,5],[14,5],[16,6],[17,8],[-1,-1],[12,16],[10,14],[9,11],[9,8],[10,6],[11,5],[-1,-1],[18,16],[17,8],[17,6],[19,5],[21,5],[23,7],[24,10],[24,12],[23,15],[22,17],[20,19],[18,20],[15,21],[12,21],[9,20],[7,19],[5,17],[4,15],[3,12],[3,9],[4,6],[5,4],[7,2],[9,1],[12,0],[15,0],[18,1],[20,2],[21,3],[-1,-1],[19,16],[18,8],[18,6],[19,5]] },
    'A': { width: 18, points: [[9,21],[1,0],[-1,-1],[9,21],[17,0],[-1,-1],[4,7],[14,7]] },
    'B': { width: 21, points: [[4,21],[4,0],[-1,-1],[4,21],[13,21],[16,20],[17,19],[18,17],[18,15],[17,13],[16,12],[13,11],[-1,-1],[4,11],[13,11],[16,10],[17,9],[18,7],[18,4],[17,2],[16,1],[13,0],[4,0]] },
    'C': { width: 21, points: [[18,16],[17,18],[15,20],[13,21],[9,21],[7,20],[5,18],[4,16],[3,13],[3,8],[4,5],[5,3],[7,1],[9,0],[13,0],[15,1],[17,3],[18,5]] },
    'D': { width: 21, points: [[4,21],[4,0],[-1,-1],[4,21],[11,21],[14,20],[16,18],[17,16],[18,13],[18,8],[17,5],[16,3],[14,1],[11,0],[4,0]] },
    'E': { width: 19, points: [[4,21],[4,0],[-1,-1],[4,21],[17,21],[-1,-1],[4,11],[12,11],[-1,-1],[4,0],[17,0]] },
    'F': { width: 18, points: [[4,21],[4,0],[-1,-1],[4,21],[17,21],[-1,-1],[4,11],[12,11]] },
    'G': { width: 21, points: [[18,16],[17,18],[15,20],[13,21],[9,21],[7,20],[5,18],[4,16],[3,13],[3,8],[4,5],[5,3],[7,1],[9,0],[13,0],[15,1],[17,3],[18,5],[18,8],[-1,-1],[13,8],[18,8]] },
    'H': { width: 22, points: [[4,21],[4,0],[-1,-1],[18,21],[18,0],[-1,-1],[4,11],[18,11]] },
    'I': { width: 8, points: [[4,21],[4,0]] },
    'J': { width: 16, points: [[12,21],[12,5],[11,2],[10,1],[8,0],[6,0],[4,1],[3,2],[2,5],[2,7]] },
    'K': { width: 21, points: [[4,21],[4,0],[-1,-1],[18,21],[4,7],[-1,-1],[9,12],[18,0]] },
    'L': { width: 17, points: [[4,21],[4,0],[-1,-1],[4,0],[16,0]] },
    'M': { width: 24, points: [[4,21],[4,0],[-1,-1],[4,21],[12,0],[-1,-1],[20,21],[12,0],[-1,-1],[20,21],[20,0]] },
    'N': { width: 22, points: [[4,21],[4,0],[-1,-1],[4,21],[18,0],[-1,-1],[18,21],[18,0]] },
    'O': { width: 22, points: [[9,21],[7,20],[5,18],[4,16],[3,13],[3,8],[4,5],[5,3],[7,1],[9,0],[13,0],[15,1],[17,3],[18,5],[19,8],[19,13],[18,16],[17,18],[15,20],[13,21],[9,21]] },
    'P': { width: 21, points: [[4,21],[4,0],[-1,-1],[4,21],[13,21],[16,20],[17,19],[18,17],[18,14],[17,12],[16,11],[13,10],[4,10]] },
    'Q': { width: 22, points: [[9,21],[7,20],[5,18],[4,16],[3,13],[3,8],[4,5],[5,3],[7,1],[9,0],[13,0],[15,1],[17,3],[18,5],[19,8],[19,13],[18,16],[17,18],[15,20],[13,21],[9,21],[-1,-1],[12,4],[18,-2]] },
    'R': { width: 21, points: [[4,21],[4,0],[-1,-1],[4,21],[13,21],[16,20],[17,19],[18,17],[18,15],[17,13],[16,12],[13,11],[4,11],[-1,-1],[11,11],[18,0]] },
    'S': { width: 20, points: [[17,18],[15,20],[12,21],[8,21],[5,20],[3,18],[3,16],[4,14],[5,13],[7,12],[13,10],[15,9],[16,8],[17,6],[17,3],[15,1],[12,0],[8,0],[5,1],[3,3]] },
    'T': { width: 16, points: [[8,21],[8,0],[-1,-1],[1,21],[15,21]] },
    'U': { width: 22, points: [[4,21],[4,6],[5,3],[7,1],[10,0],[12,0],[15,1],[17,3],[18,6],[18,21]] },
    'V': { width: 18, points: [[1,21],[9,0],[-1,-1],[17,21],[9,0]] },
    'W': { width: 24, points: [[2,21],[7,0],[-1,-1],[12,21],[7,0],[-1,-1],[12,21],[17,0],[-1,-1],[22,21],[17,0]] },
    'X': { width: 20, points: [[3,21],[17,0],[-1,-1],[17,21],[3,0]] },
    'Y': { width: 18, points: [[1,21],[9,11],[9,0],[-1,-1],[17,21],[9,11]] },
    'Z': { width: 20, points: [[17,21],[3,0],[-1,-1],[3,21],[17,21],[-1,-1],[3,0],[17,0]] },
    '[': { width: 14, points: [[4,25],[4,-7],[-1,-1],[5,25],[5,-7],[-1,-1],[4,25],[11,25],[-1,-1],[4,-7],[11,-7]] },
    '\\': { width: 14, points: [[0,21],[14,-3]] },
    ']': { width: 14, points: [[9,25],[9,-7],[-1,-1],[10,25],[10,-7],[-1,-1],[3,25],[10,25],[-1,-1],[3,-7],[10,-7]] },
    '^': { width: 16, points: [[6,15],[8,18],[10,15],[-1,-1],[3,12],[8,17],[13,12],[-1,-1],[8,17],[8,0]] },
    '_': { width: 16, points: [[0,-2],[16,-2]] },
    '`': { width: 10, points: [[6,21],[5,20],[4,18],[4,16],[5,15],[6,16],[5,17]] },
    'a': { width: 19, points: [[15,14],[15,0],[-1,-1],[15,11],[13,13],[11,14],[8,14],[6,13],[4,11],[3,8],[3,6],[4,3],[6,1],[8,0],[11,0],[13,1],[15,3]] },
    'b': { width: 19, points: [[4,21],[4,0],[-1,-1],[4,11],[6,13],[8,14],[11,14],[13,13],[15,11],[16,8],[16,6],[15,3],[13,1],[11,0],[8,0],[6,1],[4,3]] },
    'c': { width: 18, points: [[15,11],[13,13],[11,14],[8,14],[6,13],[4,11],[3,8],[3,6],[4,3],[6,1],[8,0],[11,0],[13,1],[15,3]] },
    'd': { width: 19, points: [[15,21],[15,0],[-1,-1],[15,11],[13,13],[11,14],[8,14],[6,13],[4,11],[3,8],[3,6],[4,3],[6,1],[8,0],[11,0],[13,1],[15,3]] },
    'e': { width: 18, points: [[3,8],[15,8],[15,10],[14,12],[13,13],[11,14],[8,14],[6,13],[4,11],[3,8],[3,6],[4,3],[6,1],[8,0],[11,0],[13,1],[15,3]] },
    'f': { width: 12, points: [[10,21],[8,21],[6,20],[5,17],[5,0],[-1,-1],[2,14],[9,14]] },
    'g': { width: 19, points: [[15,14],[15,-2],[14,-5],[13,-6],[11,-7],[8,-7],[6,-6],[-1,-1],[15,11],[13,13],[11,14],[8,14],[6,13],[4,11],[3,8],[3,6],[4,3],[6,1],[8,0],[11,0],[13,1],[15,3]] },
    'h': { width: 19, points: [[4,21],[4,0],[-1,-1],[4,10],[7,13],[9,14],[12,14],[14,13],[15,10],[15,0]] },
    'i': { width: 8, points: [[3,21],[4,20],[5,21],[4,22],[3,21],[-1,-1],[4,14],[4,0]] },
    'j': { width: 10, points: [[5,21],[6,20],[7,21],[6,22],[5,21],[-1,-1],[6,14],[6,-3],[5,-6],[3,-7],[1,-7]] },
    'k': { width: 17, points: [[4,21],[4,0],[-1,-1],[14,14],[4,4],[-1,-1],[8,8],[15,0]] },
    'l': { width: 8, points: [[4,21],[4,0]] },
    'm': { width: 30, points: [[4,14],[4,0],[-1,-1],[4,10],[7,13],[9,14],[12,14],[14,13],[15,10],[15,0],[-1,-1],[15,10],[18,13],[20,14],[23,14],[25,13],[26,10],[26,0]] },
    'n': { width: 19, points: [[4,14],[4,0],[-1,-1],[4,10],[7,13],[9,14],[12,14],[14,13],[15,10],[15,0]] },
    'o': { width: 19, points: [[8,14],[6,13],[4,11],[3,8],[3,6],[4,3],[6,1],[8,0],[11,0],[13,1],[15,3],[16,6],[16,8],[15,11],[13,13],[11,14],[8,14]] },
    'p': { width: 19, points: [[4,14],[4,-7],[-1,-1],[4,11],[6,13],[8,14],[11,14],[13,13],[15,11],[16,8],[16,6],[15,3],[13,1],[11,0],[8,0],[6,1],[4,3]] },
    'q': { width: 19, points: [[15,14],[15,-7],[-1,-1],[15,11],[13,13],[11,14],[8,14],[6,13],[4,11],[3,8],[3,6],[4,3],[6,1],[8,0],[11,0],[13,1],[15,3]] },
    'r': { width: 13, points: [[4,14],[4,0],[-1,-1],[4,8],[5,11],[7,13],[9,14],[12,14]] },
    's': { width: 17, points: [[14,11],[13,13],[10,14],[7,14],[4,13],[3,11],[4,9],[6,8],[11,7],[13,6],[14,4],[14,3],[13,1],[10,0],[7,0],[4,1],[3,3]] },
    't': { width: 12, points: [[5,21],[5,4],[6,1],[8,0],[10,0],[-1,-1],[2,14],[9,14]] },
    'u': { width: 19, points: [[4,14],[4,4],[5,1],[7,0],[10,0],[12,1],[15,4],[-1,-1],[15,14],[15,0]] },
    'v': { width: 16, points: [[2,14],[8,0],[-1,-1],[14,14],[8,0]] },
    'w': { width: 22, points: [[3,14],[7,0],[-1,-1],[11,14],[7,0],[-1,-1],[11,14],[15,0],[-1,-1],[19,14],[15,0]] },
    'x': { width: 17, points: [[3,14],[14,0],[-1,-1],[14,14],[3,0]] },
    'y': { width: 16, points: [[2,14],[8,0],[-1,-1],[14,14],[8,0],[6,-4],[4,-6],[2,-7],[1,-7]] },
    'z': { width: 17, points: [[14,14],[3,0],[-1,-1],[3,14],[14,14],[-1,-1],[3,0],[14,0]] },
    '{': { width: 14, points: [[9,25],[7,24],[6,23],[5,21],[5,19],[6,17],[7,16],[8,14],[8,12],[6,10],[-1,-1],[7,24],[6,22],[6,20],[7,18],[8,17],[9,15],[9,13],[8,11],[4,9],[8,7],[9,5],[9,3],[8,1],[7,0],[6,-2],[6,-4],[7,-6],[-1,-1],[6,8],[8,6],[8,4],[7,2],[6,1],[5,-1],[5,-3],[6,-5],[7,-6],[9,-7]] },
    '|': { width: 8, points: [[4,25],[4,-7]] },
    '}': { width: 14, points: [[5,25],[7,24],[8,23],[9,21],[9,19],[8,17],[7,16],[6,14],[6,12],[8,10],[-1,-1],[7,24],[8,22],[8,20],[7,18],[6,17],[5,15],[5,13],[6,11],[10,9],[6,7],[5,5],[5,3],[6,1],[7,0],[8,-2],[8,-4],[7,-6],[-1,-1],[8,8],[6,6],[6,4],[7,2],[8,1],[9,-1],[9,-3],[8,-5],[7,-6],[5,-7]] },
    '~': { width: 24, points: [[3,6],[3,8],[4,11],[6,12],[8,12],[10,11],[14,8],[16,7],[18,7],[20,8],[21,10],[-1,-1],[3,8],[4,10],[6,11],[8,11],[10,10],[14,7],[16,6],[18,6],[20,7],[21,10],[21,12]] }
};

CanvasTextFunctions.letter = function (ch)
{
  return CanvasTextFunctions.letters[ch];
}

CanvasTextFunctions.ascent = function(font, size)
{
  return size;
}

CanvasTextFunctions.descent = function(font, size)
{
  return 7.0*size/25.0;
}

CanvasTextFunctions.measure = function(font, size, str)
{
  var total = 0;
  var len = str.length;

  for (i = 0; i < len; i++)
  {
  	var c = CanvasTextFunctions.letter(str.charAt(i));
  	if (c)
      total += c.width * size / 25.0;
  }

  return total;
}

CanvasTextFunctions.draw = function(ctx, font, size, x, y, str)
{
  var total = 0;
  var len = str.length;
  var mag = size / 25.0;

  ctx.save();
  ctx.lineCap = "round";
  ctx.lineWidth = 2.0 * mag;

  for (i = 0; i < len; i++)
  {
  	var c = CanvasTextFunctions.letter(str.charAt(i));
  	if (!c) continue;

  	ctx.beginPath();

  	var penUp = 1;
  	var needStroke = 0;
  	for (j = 0; j < c.points.length; j++)
    {
  	  var a = c.points[j];
  	  if (a[0] == -1 && a[1] == -1)
      {
    		penUp = 1;
    		continue;
  	  }
  	  if (penUp)
      {
    		ctx.moveTo(x + a[0]*mag, y - a[1]*mag);
    		penUp = false;
  	  }
      else
      {
  		  ctx.lineTo(x + a[0]*mag, y - a[1]*mag);
  	  }
  	}
  	ctx.stroke();
  	x += c.width*mag;
  }
  ctx.restore();
  return total;
}
