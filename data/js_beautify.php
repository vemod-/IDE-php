<html><head>
<SCRIPT LANGUAGE='JavaScript'>

String.prototype.trim = function() { return this.replace(/^\s+|\s+$/g, ''); }

String.prototype.repeat = function( num )
{
    return new Array( num + 1 ).join( this );
}

function unique(a)
{
	var r = new Array();
	for(var i = 0; i < a.length ; i++)
	{
	    var match=false;
		for (var x = 0, y = r.length; x < y; x++) {
			if (r[x]==a[i]) {
				match=true;
				break;
			}
		}
		if (!match)
		{
		  r[r.length] = a[i];
		}
	}
	return r;
}

function preg_quote(s,d)
{
	var replace = new Array(".", "*","+", "?","^", "$","{", "}","[", "]","\\");
	for (var i=0; i<replace.length; i++) {
		s = s.replace(replace[i], d+replace[i]);
	}
	return s;
}


RegExp.escape = function(text) {
  if (!RegExp.escape.sRE) {
    var specials = [
      '/', '.', '*', '+', '?', '|',
      '(', ')', '[', ']', '{', '}','$','^','\\'
    ];
    RegExp.escape.sRE = new RegExp(
      '(\\' + specials.join('|\\') + ')', 'g'
    );
  }
  return text.replace(RegExp.escape.sRE, '\\$1');
}

function publicProcessHandler(str, indent)
{
	alert(str);
	// placeholders prevent strings and comments from being processed
	//var matches=str.match(/('(?:.*)')|("(?:.*)")|((\/\/.*?)[\r\n])|(\/\*(.|[\r\n])*?\*\/)/gm);
	//var matches=str.match(/(([\"\'])(?:.*?[^\\\\]+?)*?(?:(?:\\\\{2}?)*?)+?\\1)|((\/\/.*?)[\r\n])|((\/\*(.|[\r\n])*?\*\/)/gm);
	var matches=str.match(/('(?:.*?[^\\\\]+?)*?(?:(?:\\\\{2}?)*?)+?')|(\"(?:.*?[^\\\\]+?)*?(?:(?:\\\\{2}?)*?)+?\")|(\/\/[^\r\n]*)|(\/\*(.|[\r\n])*?\*\/)/gm);
	//var matches=str.match(/(([\"\'])(?:.*[^\\\\]+)*(?:(?:\\\\{2})*)+\1)/g);
	//var matches=str.match("/(?Ux:([\"\'])(?:.*[^\\\\]+)*(?:(?:\\\\{2})*)+\\1)|(?m:(\/\/.*?)[\r\n])|(?m:(\/\*(.|[\r\n])*?\*\/)/g");
	if (!matches)
	{
		matches=new Array();
	}
	matches=unique(matches);
	for (var i=0; i<matches.length; i++) {
		var pattern=RegExp.escape(matches[i]);
		//remove too much whitespace from strings and comments too
		matches[i]=matches[i].replace(/[\n\r]+\s*[\n\r]+/gm,"\n");
		// double backslashes must be escaped if we want to use them in the replacement argument
		matches[i]=matches[i].replace('\\\\', '\\\\\\\\');
		str=str.replace(new RegExp(pattern,'g'), "%placeholder"+i+"%");
	}
	str=privateIndentParsedString(privateParseString(str),indent);
	// insert original strings and comments
	for (var i=0; i<matches.length; i++) {
		str=str.replace(new RegExp("%placeholder"+i+"%",'g'), matches[i]);
	}
	alert(str);
	return str;
}

function privateParseString(str)
{
	// inserting missing braces (does only match up to 2 nested parenthesis)
	str=str.replace(/^\s*(if|foreach|for|while|switch)\s*(\([^()]*(\([^()]*\)[^()]*)*\))([^\{;]*;)/gmi, "\$1 \$2 {\$4\n}");
	// missing braces for else statements
	str=str.replace(/(elseif|else if|else)\s*([^{;]*;)/gi, "\$1 {\$2\n}");
	// line break check
	str=str.replace(/\s*([;\{\}]|case\s[^:]+:)[ \n\r]?/gi, "\$1 \n");
	str=str.replace(/^\s*(function|class)\s+([^\n\r]+){/gmi, "\$1 \$2 \n{");
	// remove inserted line breaks at else and for statements
	str=str.replace(/(\}\s*else\s*\{)/gm, "} else {\n");
	str=str.replace(/\}\s*(elseif|else if)\s*(\([^()]*(\([^()]*\)[^()]*)*\))\s*\{/gmi, "} \$1 \$2 {\n");
	str=str.replace(/^\s*(for\s*\()([^;]+;)(\s*)([^;]+;)(\s*)/gmi, "\$1\$2 \$4 ");
	// remove spaces between function call and parenthesis and start of argument list
	str=str.replace(/(\w+)\s*\(\s*/g, "$1(");
	// remove line breaks between condition and brace,
	// set one space between control keyword and condition
	str=str.replace(/^\s*(if|foreach|for|while|switch)\s*(\([^\{]+\))\s*\{/gmi, "\$1 \$2 {\n");
	//remove empty lines
	str=str.replace(/[\n\r]+\s*[\n\r]+/gm,"\n");
	//add an empty line before functions and classes
	str=str.replace(/^\s*(function|class)/gmi,"\n\$1");
	return str;
}

// This counts the number of times a string matching the
// given regular expression can be found in the given text.
function substr_count( strText, reTargetString ){
	var intCount = 0;

	// Use replace to globally iterate over the matching
	// strings.
	strText.replace(
		new RegExp(RegExp.escape(reTargetString),''),

		// This function will get called for each match
		// of the regular expression.
		function(){
			intCount++;
		}
	);

	// Return the updated count variable.
	return( intCount );
}

function privateIndentParsedString(str, indent)
{
    var count = substr_count(str, '}')-substr_count(str, '{');
    if (count<0){
        count = 0;
    }
    var strarray=str.split("\n");
    for(var i=0;i<strarray.length;i++){
        strarray[i]=strarray[i].trim();
        if (substr_count(strarray[i], '}')){
            if (!substr_count(strarray[i],'{.*\}'))
            {
                count--;
            }
        }
        var level='';

        if (substr_count(strarray[i],'^case\s')){
            level="\t".repeat(indent*(count-1));
        } else if (substr_count(strarray[i],'^or\s')){
            level="\t".repeat(indent*(count+1));
        } else {
            level="\t".repeat(indent*(count));
        }
        strarray[i]=level+strarray[i];
        if (substr_count(strarray[i], '{')){
            if (!substr_count(strarray[i],'{.*\}'))
            {
                count++;
            }
        }
    }
    return strarray.join("\n");

}
				</SCRIPT>
				</head>
				<body>
				<?php
				$fp = fopen('test.php', 'r');
				$code = @fread($fp, filesize('test.php'));
				fclose($fp);
				?>
				<input type="button" value="test" onClick="publicProcessHandler('function hello{\n\t\t//comment here\n\t\t echo \'showmesome\';$x=$ && $x;}\n /*a comment\n\n\n	*/',1);"/>
				</body>
				</html>