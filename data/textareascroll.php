<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
            "http://www.w3.org/TR/html4/strict.dtd"><html>
   <head>
   <style type="text/css">
   #codeTextarea{
     position:absolute;
     left:50px;
	right:0px;
	height:100%;
border-left:2px solid #e3e3e3;
padding:4px;
   }
   .textAreaWithLines{
	position:absolute;
      font-family:courier;
      border:1px solid #f1f1f1;
	background-color:#f1f1f1;
        width:100%;
        height:100%;
        overflow:hidden;
   }
   .textAreaWithLines textarea,.textAreaWithLines div{
      border:0px;
      line-height:120%;
      font-size:12px;
   }
   .lineObj{
   	position:absolute;
      color:#808080;
      background-color:#f1f1f1;
      border-right:2px solid #e3e3e3;
padding-right:4px;
top:0px;
height:100%;
width:47px;
text-align:right;
   }
   </style>

   <script type="text/javascript">

   var lineObjOffsetTop = 4;

   function createTextAreaWithLines(id)
   {
      var el = document.createElement('DIV');
      var ta = document.getElementById(id);
      ta.parentNode.insertBefore(el,ta);
      el.appendChild(ta);

      el.className='textAreaWithLines';
      //el.style.width = (ta.offsetWidth + 50) + 'px';
      //ta.style.position = 'absolute';
      //ta.style.left = '50px';
      //el.style.height = '100%'//(ta.offsetHeight + 1) + 'px';
      //el.style.overflow='hidden';
      //el.style.position = 'relative';
      //el.style.width = (ta.offsetWidth + 50) + 'px';
      var lineObj = document.createElement('DIV');
      //lineObj.style.position = 'absolute';
      lineObj.style.top = lineObjOffsetTop + 'px';
      //lineObj.style.left = '0px';
      //lineObj.style.width = '47px';
      el.insertBefore(lineObj,ta);
      //lineObj.style.textAlign = 'right';
      lineObj.className='lineObj';
      var string = '';
      for(var no=1;no<7000;no++){
         if(string.length>0)string = string + '<br>';
         string = string + no;
      }

      //ta.onkeydown = function() { positionLineObj(lineObj,ta); };
      //ta.onmousedown = function() { positionLineObj(lineObj,ta); };
      ta.onscroll = function() { positionLineObj(lineObj,ta); };
      //ta.onblur = function() { positionLineObj(lineObj,ta); };
      //ta.onfocus = function() { positionLineObj(lineObj,ta); };
      //ta.onmouseover = function() { positionLineObj(lineObj,ta); };
      lineObj.innerHTML = string;

   }

   function positionLineObj(obj,ta)
   {
      obj.style.top = (ta.scrollTop * -1 + lineObjOffsetTop) + 'px';


   }

   </script>

   </head>
   <body>
   <form>
   <textarea id="codeTextarea" WRAP='OFF'><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
            "http://www.w3.org/TR/html4/strict.dtd"><html>
   <head>
   <style type="text/css">
   #codeTextarea{
      width:400px;
 height:510px;
border-left:2px solid #e3e3e3;
padding:4px;
   }
   .textAreaWithLines{
      font-family:courier;
      border:1px solid #f1f1f1;
background-color:#f1f1f1;

   }
   .textAreaWithLines textarea,.textAreaWithLines div{
      border:0px;
      line-height:120%;
      font-size:12px;
   }
   .lineObj{
      color:#808080;
      background-color:#f1f1f1;
      border-right:2px solid #e3e3e3;
padding-right:4px;
height:510px;
   }
   </style>

   <script type="text/javascript">

   var lineObjOffsetTop = 4;

   function createTextAreaWithLines(id)
   {
      var el = document.createElement('DIV');
      var ta = document.getElementById(id);
      ta.parentNode.insertBefore(el,ta);
      el.appendChild(ta);

      el.className='textAreaWithLines';
      el.style.width = (ta.offsetWidth + 50) + 'px';
      ta.style.position = 'absolute';
      ta.style.left = '50px';
      el.style.height = 100%;//(ta.offsetHeight + 1) + 'px';
      el.style.overflow='hidden';
      el.style.position = 'relative';
      el.style.width = (ta.offsetWidth + 50) + 'px';
      var lineObj = document.createElement('DIV');
      lineObj.style.position = 'absolute';
      lineObj.style.top = lineObjOffsetTop + 'px';
      lineObj.style.left = '0px';
      lineObj.style.width = '47px';
      el.insertBefore(lineObj,ta);
      lineObj.style.textAlign = 'right';
      lineObj.className='lineObj';
      var string = '';
      for(var no=1;no<7000;no++){
         if(string.length>0)string = string + '<br>';
         string = string + no;
      }

      ta.onkeydown = function() { positionLineObj(lineObj,ta); };
      ta.onmousedown = function() { positionLineObj(lineObj,ta); };
      ta.onscroll = function() { positionLineObj(lineObj,ta); };
      ta.onblur = function() { positionLineObj(lineObj,ta); };
      ta.onfocus = function() { positionLineObj(lineObj,ta); };
      ta.onmouseover = function() { positionLineObj(lineObj,ta); };
      lineObj.innerHTML = string;

   }

   function positionLineObj(obj,ta)
   {
      obj.style.top = (ta.scrollTop * -1 + lineObjOffsetTop) + 'px';


   }

   </script>

   </head>
   <body style="height:100%;width:100%;">
   <form style="height:100%;width:100%;">
   <textarea id="codeTextarea" WRAP='OFF'></textarea>
   </form>
   <script type="text/javascript">
   createTextAreaWithLines('codeTextarea');
   </script>
   </body>
</html></textarea>
   </form>
   <script type="text/javascript">
   createTextAreaWithLines('codeTextarea');
   </script>
   </body>
</html>