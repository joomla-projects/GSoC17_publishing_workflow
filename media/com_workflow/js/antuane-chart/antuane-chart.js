/*!
* \ntuaneChart.js
* http://antuane.com/
* Version: 1.0.0
*
* Copyright 2015 Diógenes Silveira
* Released under the MIT license
* https://github.com/antuane/
*/

'use strict';


function AntuaneChart(data) {
  // try{
    this.init(data);
    //console.log(this);
  // }
  // catch(error){
  //   console.log("Antuane Chart - Error: " + error);
  // }
}

AntuaneChart.prototype.helper = {
  getRandomInt: function(min, max) {
    return Math.floor(Math.random() * (max - min)) + min;
  },
  roundRect: function(context, x, y, width, height, radius, hiddenBg) {
    context.beginPath();
    context.moveTo(x + radius, y);
    context.lineTo(x + width - radius, y);
    context.quadraticCurveTo(x + width, y, x + width, y + radius);
    context.lineTo(x + width, y + height - radius);
    context.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
    context.lineTo(x + radius, y + height);
    context.quadraticCurveTo(x, y + height, x, y + height - radius);
    context.lineTo(x, y + radius);
    context.quadraticCurveTo(x, y, x + radius, y);
    context.closePath();
    context.stroke();
    if (!hiddenBg) {
      context.fill();
    }
  },
  wrapText: function(context, text, x, y, width, height, lineHeight) {

    context.textAlign = 'center';
    context.textBaseline = 'middle';
    x = x + (width/2);
    y = y + (height/2);

    var cars = text.split("\n");

    for (var ii = 0; ii < cars.length; ii++) {

      var line = "";
      var words = cars[ii].split(" ");

      for (var n = 0; n < words.length; n++) {
        var testLine = line + words[n] + " ";
        var metrics = context.measureText(testLine);
        var testWidth = metrics.width;

        if (testWidth > width) {
          line = words[n] + " ";
          y -= lineHeight/2;
        }
        else {
          line = testLine;
        }
      }

      line = "";

      for (var n = 0; n < words.length; n++) {
        var testLine = line + words[n] + " ";
        var metrics = context.measureText(testLine);
        var testWidth = metrics.width;

        if (testWidth > width) {
          context.fillText(line, x, y);
          line = words[n] + " ";
          y += lineHeight;
        }
        else {
          line = testLine;
        }
      }

      context.fillText(line, x, y);
      y += lineHeight;
    }
  },
  colorLuminance : function(hex, lum) {
    hex = String(hex).replace(/[^0-9a-f]/gi, '');
    if (hex.length < 6) {
      hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
    }
    lum = lum || 0;
    var rgb = "#", c, i;
    for (i = 0; i < 3; i++) {
      c = parseInt(hex.substr(i*2,2), 16);
      c = Math.round(Math.min(Math.max(0, c + (c * lum)), 255)).toString(16);
      rgb += ("00"+c).substr(c.length);
    }
    return rgb;
  },
  cloneObj:function(obj) {
    if(obj == null || typeof(obj) != 'object')
    return obj;

    var temp = obj.constructor();

    for(var key in obj) {
      if(obj.hasOwnProperty(key)) {
        temp[key] = cloneObj(obj[key]);
      }
    }
    return temp;
  },
  getObjById: function(listObj, idObj){
    for(var i = 0; i < listObj.length; i++){
      if(listObj[i].id ==  idObj){
        return listObj[i];
      }
    }
    return null;
  },
  trackTransforms: function(ctx){
    var svg = document.createElementNS("http://www.w3.org/2000/svg",'svg');
    var xform = svg.createSVGMatrix();
    ctx.getTransform = function(){ return xform; };

    var savedTransforms = [];
    var save = ctx.save;
    ctx.save = function(){
      savedTransforms.push(xform.translate(0,0));
      return save.call(ctx);
    };
    var restore = ctx.restore;
    ctx.restore = function(){
      xform = savedTransforms.pop();
      return restore.call(ctx);
    };

    var scale = ctx.scale;
    ctx.scale = function(sx,sy){
      xform = xform.scaleNonUniform(sx,sy);
      return scale.call(ctx,sx,sy);
    };
    var rotate = ctx.rotate;
    ctx.rotate = function(radians){
      xform = xform.rotate(radians*180/Math.PI);
      return rotate.call(ctx,radians);
    };
    var translate = ctx.translate;
    ctx.translate = function(dx,dy){
      xform = xform.translate(dx,dy);
      return translate.call(ctx,dx,dy);
    };
    var transform = ctx.transform;
    ctx.transform = function(a,b,c,d,e,f){
      var m2 = svg.createSVGMatrix();
      m2.a=a; m2.b=b; m2.c=c; m2.d=d; m2.e=e; m2.f=f;
      xform = xform.multiply(m2);
      return transform.call(ctx,a,b,c,d,e,f);
    };
    var setTransform = ctx.setTransform;
    ctx.setTransform = function(a,b,c,d,e,f){
      xform.a = a;
      xform.b = b;
      xform.c = c;
      xform.d = d;
      xform.e = e;
      xform.f = f;
      return setTransform.call(ctx,a,b,c,d,e,f);
    };
    var pt  = svg.createSVGPoint();
    ctx.transformedPoint = function(x,y){
      pt.x=x; pt.y=y;
      return pt.matrixTransform(xform.inverse());
    }
  }
}

AntuaneChart.prototype.init = function(data){

  var _this = this;
  _this.canvas = document.getElementById(data.config.element);

  if(_this.canvas){
    _this.context = _this.canvas.getContext("2d");
  }else{
    throw "Element not found"
  }

  _this.context = this.canvas.getContext("2d");
  _this.context.lineWidth = data.config.lineWidth;



  if(data.config.autoSize){
    this.canvas.width = this.canvas.parentNode.clientWidth;
    this.canvas.height = this.canvas.parentNode.clientHeight;
  }



  _this.helper.trackTransforms(this.context);


  data.config.scaleFactor = 1.1;
  data.config.zoomScale = 0;
  data.config.moveX = 0;
  data.config.moveY = 0;

  //this.data = data;
  this.config = data.config;
  this.update(data);
  this.draw();

  if(_this.config.mouseEvents){
    this.events();
  }
};

AntuaneChart.prototype.addZoom = function(size){
  var _this = this;
  _this.config.zoomScale += size;
  var pt = _this.context.transformedPoint(_this.canvas.width / 2,_this.canvas.height / 2);
  this.context.translate(pt.x,pt.y);
  var factor = Math.pow(_this.config.scaleFactor,size);
  _this.context.scale(factor,factor);
  _this.context.translate(-pt.x,-pt.y);
  this.draw();
};

AntuaneChart.prototype.resetZoom = function(){
  var _this = this;
  _this.addZoom(-_this.config.zoomScale);
  _this.context.translate(-_this.config.moveX,-_this.config.moveY);
  _this.config.zoomScale = 0;
  _this.config.moveX = 0;
  _this.config.moveY = 0;
};

AntuaneChart.prototype.events = function(){
  var _this = this;

  var lastX=_this.canvas.width/2;
  var lastY=_this.canvas.height/2;
  var dragStart = false;
  var dragged = false;

  _this.canvas.addEventListener('mousedown',function(evt){
    document.body.style.mozUserSelect = document.body.style.webkitUserSelect = document.body.style.userSelect = 'none';
    _this.canvas.style.cursor = "move";
    lastX = evt.offsetX || (evt.pageX - _this.canvas.offsetLeft);
    lastY = evt.offsetY || (evt.pageY - _this.canvas.offsetTop);
    dragStart = _this.context.transformedPoint(lastX,lastY);
    dragged = false;
    evt.returnValue = false;
  },false);

  _this.canvas.addEventListener('mousemove',function(evt){
    lastX = evt.offsetX || (evt.pageX - _this.canvas.offsetLeft);
    lastY = evt.offsetY || (evt.pageY - _this.canvas.offsetTop);

    dragged = true;
    if (dragStart){
      var pt = _this.context.transformedPoint(lastX,lastY);
      _this.context.translate(pt.x-dragStart.x,pt.y-dragStart.y);
      _this.draw();
      _this.config.moveX += pt.x-dragStart.x;
      _this.config.moveY += pt.y-dragStart.y;
    }
  },false);

  _this.canvas.addEventListener('mouseup',function(evt){
    dragStart = null;
    _this.canvas.style.cursor = "default";
    //if (!dragged) zoom(evt.shiftKey ? -1 : 1 );
  },false);

  var handleScroll = function(evt){
    var delta = evt.wheelDelta ? evt.wheelDelta/300 : evt.detail ? -evt.detail : 0;
    _this.addZoom(delta);
    return evt.preventDefault() && false;
  };

  _this.canvas.addEventListener('DOMMouseScroll',handleScroll,false);
  _this.canvas.addEventListener('mousewheel',handleScroll,false);

};

AntuaneChart.prototype.update = function(data){

  // if(data){
  //   data = this.data;
  // }

  var _this = this;
  //_this.data = data;

  var diagramList = [];
  var diagramLinesCount = [];
  var diagramColumnsCount = 0;
  var diagramColumnLower = 0;

  for(var i = 0; i < data.diagrams.length; i++){

    var objectDiagram = {
      id: data.diagrams[i].id,
      x: 0,
      y: 0,
      cx: 0,
      cy: 0,
      text: data.diagrams[i].text,
      color: data.diagrams[i].color,
      bgColor: data.diagrams[i].bgColor,
      parents: [],
      children: [],
      virgin: true,
      orphan: true,
    }

    for(var j = 0; j < data.links.length; j++){

      if(data.links[j].source == objectDiagram.id){
        objectDiagram.parents.push(data.links[j].parent);
        objectDiagram.orphan = false;
      }

      if(data.links[j].parent == objectDiagram.id){
        objectDiagram.children.push(data.links[j].source);
        objectDiagram.orphan = false;
      }

    }

    diagramList.push(objectDiagram);
  }

  var getLinesObjects = function getLinesObjects(object, hierarchy){
    if(object.virgin){
      object.virgin = false;
      object.y = hierarchy;

      if(hierarchy < diagramColumnLower){
        diagramColumnLower = hierarchy;
      }

      for(var i = 0; i < object.children.length; i++){
        var tmpObj = _this.helper.getObjById(diagramList, object.children[i]);
        getLinesObjects(tmpObj, hierarchy + 1);
      }

      for(var i = 0; i < object.parents.length; i++){
        var tmpObj = _this.helper.getObjById(diagramList, object.parents[i]);
        getLinesObjects(tmpObj, hierarchy - 1);
      }
    }
  }

  for(var i = 0; i < diagramList.length; i++){
    getLinesObjects(diagramList[i], 0);
  }

  for(var i = 0; i < diagramList.length; i++){
    var countObjTmp = _this.helper.getObjById(diagramLinesCount, diagramList[i].y);
    if(countObjTmp != null){
      if(!diagramList[i].orphan){
        countObjTmp.count++;
        diagramList[i].x = countObjTmp.count;
        if(countObjTmp.count > diagramColumnsCount){
          diagramColumnsCount = countObjTmp.count;
        }
      }
    }else{
      if(!diagramList[i].orphan){
        diagramLinesCount.push({ id: diagramList[i].y, count:1 });
        diagramList[i].x = 1;
      }
    }
  }

  this.config.linesCount = diagramLinesCount;
  this.config.columnsCount = diagramColumnsCount;
  this.config.columnLower = diagramColumnLower;
  this.diagrams = diagramList;

};

AntuaneChart.prototype.draw = function(){
  var _this = this;

  if(_this.context.transformedPoint){
    var p1 = _this.context.transformedPoint(0,0);
    var p2 = _this.context.transformedPoint(_this.canvas.width,_this.canvas.height);
    _this.context.clearRect(p1.x,p1.y,p2.x-p1.x,p2.y-p1.y);

  }else{
    _this.context.clearRect(0,0,_this.canvas.width,_this.canvas.height);
  }

  var diagramWithMargin = _this.config.width + (2 * _this.config.margin);

  if(_this.config.columnsCount == 0){
    _this.config.columnsCount = parseInt(_this.canvas.width / diagramWithMargin);
  }

  var maxWidth = _this.config.columnsCount * diagramWithMargin;
  var marginScreen = (parseInt(_this.canvas.width) / 2 ) - (maxWidth / 2);

  _this.context.rotate(2 * Math.PI);

  var countOrphanDemand = 0;
  for(var i = 0; i < _this.diagrams.length; i++){
    if(_this.diagrams[i].orphan){
      var positionX = marginScreen + _this.config.margin + parseInt(countOrphanDemand % (_this.config.columnsCount)) * (_this.config.width + (2 * _this.config.margin));
      var positionY = _this.config.margin + parseInt(countOrphanDemand / (_this.config.columnsCount)) * (_this.config.height + (2 * _this.config.margin));
      _this.context.fillStyle = _this.diagrams[i].bgColor;
      _this.context.strokeStyle = _this.diagrams[i].bgColor;
      _this.helper.roundRect(_this.context, positionX, positionY, _this.config.width, _this.config.height, _this.config.radius, _this.config.hiddenBg);
      //
      var nodeText = _this.context;
      nodeText.fillStyle= _this.diagrams[i].color;
      nodeText.font= _this.config.fontSize + "px " + _this.config.fontFamily;
      _this.helper.wrapText(_this.context, _this.diagrams[i].text, positionX + _this.config.padding, positionY, _this.config.width - (_this.config.padding*2), _this.config.height, (_this.config.fontSize + (_this.config.fontSize * 0.2)));

      countOrphanDemand++;
    }
  }

  //CALCULATE X Y NOT ORPHANS DIAGRAMS

  var marginTopNotOrphans = (_this.config.height + (3 * _this.config.margin)) + parseInt((countOrphanDemand-1) / _this.config.columnsCount) * (_this.config.height + (2 * _this.config.margin));

  if(isNaN(marginTopNotOrphans)){
    marginTopNotOrphans = _this.config.margin;
  }

  for(var i = 0; i < _this.diagrams.length; i++){
    if(!_this.diagrams[i].orphan){
      var maxColumnsInLine = _this.helper.getObjById(_this.config.linesCount, _this.diagrams[i].y).count;
      var ratio = maxWidth / maxColumnsInLine;
      var positionX = marginScreen + (ratio * _this.diagrams[i].x) + (ratio / 2 - (_this.config.width /2)) - ratio;
      var positionY = marginTopNotOrphans + (Math.abs(_this.config.columnLower) * (_this.config.height + (2 * _this.config.margin))) + _this.diagrams[i].y * (_this.config.height + (2 * _this.config.margin));
      _this.diagrams[i].left = positionX;
      _this.diagrams[i].top = positionY;
    }
  }

  //DRAW LINES

  for(var i = 0; i < _this.diagrams.length; i++){
    if(!_this.diagrams[i].orphan){

      for(var j = 0; j < _this.diagrams[i].parents.length; j++){
        var tmpObj = _this.helper.getObjById(_this.diagrams, _this.diagrams[i].parents[j]);
        var invertParent = false;
        var isDistant = false;
        var autoRef = false;
        var countDistant = 0;
        var countInvertParent = 0;

        if(_this.diagrams[i].id == tmpObj.id){
          autoRef = true;
        }else if (_this.diagrams[i].y < tmpObj.y){
          invertParent = true;
        }else if((_this.diagrams[i].y - tmpObj.y) > 1){
          isDistant = true;
        }

        if (_this.context.setLineDash) {
            _this.context.setLineDash([_this.config.lineWidth, 0]);
        }

        _this.context.strokeStyle = _this.config.lineColor || _this.diagrams[i].bgColor;
        _this.context.fillStyle= _this.config.lineColor || _this.diagrams[i].bgColor;

		if (invertParent)
		{
			_this.context.strokeStyle = '#ff0000';
			_this.context.fillStyle = '#ff0000';
		}

        var difference = 0;

        if(autoRef){

          difference = ((_this.config.margin/2)-((_this.config.margin / _this.config.columnsCount) * _this.diagrams[i].x));

          if(!isFinite(difference)){
            difference = 0;
          }

          _this.context.beginPath();
          _this.context.moveTo((tmpObj.left + (_this.config.width /2)) + (difference * -1) - difference, tmpObj.top + _this.config.height);
          _this.context.lineTo((tmpObj.left + (_this.config.width /2)) + (difference * -1) - difference, tmpObj.top + _this.config.height + (_this.config.margin/2.5));
          _this.context.lineTo((tmpObj.left + (_this.config.width /2)) + (difference * -1) + difference, tmpObj.top + _this.config.height + (_this.config.margin/2.5));
          _this.context.lineTo((tmpObj.left + (_this.config.width /2)) + (difference * -1) + difference, tmpObj.top + _this.config.height + (_this.config.arrowWidth/2));
          _this.context.stroke();

          _this.context.beginPath();
          _this.context.moveTo((tmpObj.left + (_this.config.width /2)) + (difference * -1) + difference, tmpObj.top + _this.config.height);
          _this.context.lineTo((tmpObj.left + (_this.config.width /2)) - (_this.config.arrowWidth/2) + (difference * -1) + difference, tmpObj.top + _this.config.height + _this.config.arrowWidth);
          _this.context.lineTo((tmpObj.left + (_this.config.width /2)) + (_this.config.arrowWidth/2) + (difference * -1) + difference, tmpObj.top + _this.config.height + _this.config.arrowWidth);
          _this.context.fill();

        }else if(invertParent){
          if(_this.config.lineDiff){
            difference = ((_this.config.margin/2)-((_this.config.margin / _this.config.columnsCount) * _this.diagrams[i].x)) + (_this.config.margin  - (_this.config.arrowWidth + (_this.config.lineWidth * 2 * countInvertParent)));
          }

          if (_this.context.setLineDash) {
              _this.context.setLineDash([_this.config.lineWidth, _this.config.lineWidth]);
          }

          _this.context.beginPath();
          _this.context.moveTo((_this.diagrams[i].left + (_this.config.width /2)) + difference, _this.diagrams[i].top + (0 * _this.config.height /2) + difference );
          _this.context.lineTo(_this.diagrams[i].left + _this.config.width + _this.config.margin + difference, _this.diagrams[i].top + (0 * _this.config.height /2) + difference);
          _this.context.lineTo(_this.diagrams[i].left + _this.config.width + _this.config.margin + difference, tmpObj.top - (2 * _this.config.margin) + difference);
          _this.context.lineTo((tmpObj.left + (_this.config.width /2)) + difference, tmpObj.top - (2 * _this.config.margin) + difference);
          _this.context.lineTo((tmpObj.left + (_this.config.width /2)) + difference, tmpObj.top - _this.config.arrowWidth + difference);
          _this.context.lineWidth = _this.config.lineWidth;
          _this.context.stroke();

          _this.context.beginPath();
          _this.context.moveTo((tmpObj.left + (_this.config.width /2)) + difference, tmpObj.top);
          _this.context.lineTo((tmpObj.left + (_this.config.width /2)) - (_this.config.arrowWidth/2) + difference, tmpObj.top - _this.config.arrowWidth);
          _this.context.lineTo((tmpObj.left + (_this.config.width /2)) + (_this.config.arrowWidth/2) + difference, tmpObj.top - _this.config.arrowWidth);
          _this.context.fill();

        }else if(isDistant){

          if(_this.config.lineDiff){
            difference = ((_this.config.margin/2)-((_this.config.margin / _this.config.columnsCount) * _this.diagrams[i].x)) + (_this.config.margin  - (_this.config.arrowWidth + (_this.config.lineWidth * 2 * countDistant)));
          }

          countDistant++;

          if(!isFinite(difference)){
            difference = 0;
          }

          if (_this.context.setLineDash) {
              _this.context.setLineDash([_this.config.lineWidth, _this.config.lineWidth]);
          }

          _this.context.beginPath();
          _this.context.moveTo((_this.diagrams[i].left + (_this.config.width /2)) + (difference * -1), _this.diagrams[i].top);
          _this.context.lineTo((_this.diagrams[i].left + (_this.config.width /2)) + (difference * -1), _this.diagrams[i].top - _this.config.margin + difference);
          _this.context.lineTo((tmpObj.left + (_this.config.width /2)) + (difference * -1), _this.diagrams[i].top - _this.config.margin + difference);
          _this.context.lineTo((tmpObj.left + (_this.config.width /2)) + (difference * -1), tmpObj.top + _this.config.height + _this.config.margin + difference);
          _this.context.lineTo((tmpObj.left + (_this.config.width /2)) + (difference * -1), tmpObj.top + _this.config.height + (_this.config.arrowWidth/2));
          _this.context.lineWidth = _this.config.lineWidth;
          _this.context.stroke();

          _this.context.beginPath();
          _this.context.moveTo((tmpObj.left + (_this.config.width /2)) + (difference * -1), tmpObj.top + _this.config.height);
          _this.context.lineTo((tmpObj.left + (_this.config.width /2)) - (_this.config.arrowWidth/2) + (difference * -1), tmpObj.top + _this.config.height + _this.config.arrowWidth);
          _this.context.lineTo((tmpObj.left + (_this.config.width /2)) + (_this.config.arrowWidth/2) + (difference * -1), tmpObj.top + _this.config.height + _this.config.arrowWidth);
          _this.context.fill();

        }else{
          //var
          if(_this.config.lineDiff){
            difference = ((_this.config.margin/2)-((_this.config.margin / _this.config.columnsCount) * _this.diagrams[i].x));
          }

          if(!isFinite(difference)){
            difference = 0;
          }

          _this.context.beginPath();
          _this.context.moveTo((_this.diagrams[i].left + (_this.config.width /2)) + (difference * -1), _this.diagrams[i].top);
          _this.context.lineTo((_this.diagrams[i].left + (_this.config.width /2)) + (difference * -1), _this.diagrams[i].top - _this.config.margin + difference);
          _this.context.lineTo((tmpObj.left + (_this.config.width /2)) + (difference * -1), tmpObj.top + _this.config.height + _this.config.margin + difference);
          _this.context.lineTo((tmpObj.left + (_this.config.width /2)) + (difference * -1), tmpObj.top + _this.config.height + (_this.config.arrowWidth/2));
          _this.context.lineWidth = _this.config.lineWidth;
          _this.context.stroke();

          _this.context.beginPath();
          _this.context.moveTo((tmpObj.left + (_this.config.width /2)) + (difference * -1), tmpObj.top + _this.config.height);
          _this.context.lineTo((tmpObj.left + (_this.config.width /2)) - (_this.config.arrowWidth/2) + (difference * -1), tmpObj.top + _this.config.height + _this.config.arrowWidth);
          _this.context.lineTo((tmpObj.left + (_this.config.width /2)) + (_this.config.arrowWidth/2) + (difference * -1), tmpObj.top + _this.config.height + _this.config.arrowWidth);
          _this.context.fill();
        }
      }
    }
  }

  //DRAW NOT ORPHANS DIAGRAMS

  for(var i = 0; i < _this.diagrams.length; i++){
    if(!_this.diagrams[i].orphan){
      var positionX = _this.diagrams[i].left;
      var positionY = _this.diagrams[i].top;
      _this.context.fillStyle = _this.diagrams[i].bgColor;
      _this.context.strokeStyle = _this.diagrams[i].bgColor;
      _this.helper.roundRect(_this.context, positionX, positionY, _this.config.width, _this.config.height, _this.config.radius, _this.config.hiddenBg);

      var nodeText = _this.context;
      nodeText.fillStyle= _this.diagrams[i].color;
      nodeText.font= _this.config.fontSize + "px " + _this.config.fontFamily;
      _this.helper.wrapText(_this.context, _this.diagrams[i].text, positionX + _this.config.padding, positionY, _this.config.width - (_this.config.padding*2), _this.config.height, (_this.config.fontSize + (_this.config.fontSize * 0.2)));
    }
  }
};
