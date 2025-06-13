<?php
require_once 'http_authenticate.php';
$ide=new options;
$ide->IDE();

class options
{
	var $Conf;
	var $Out;

	function IDE()
	{
		include('./Page.phpclass');
		include('./Conf.phpclass');
		$this->Out=new Page;
		$this->Conf=new Conf;
		if (isSet($_POST['action']) && $_POST['action'] == "save_options") {
			$this->options_page_save(array('Protect_entities',
			'Eval_executable', 'Unix_newlines','Overwrite_original','Phpneturl','Allow_browse_below_root','UseCodeMirror','CodeMirrorTheme','Right_trim','Eval_suffix_list','editorfont','editorfontsize','editorlinespace'));
		}
		if (isSet($_POST['options_action'])) {
			if ($_POST['options_action'] == "add_suffix") {
				$add_suffix = preg_replace('/^\.*(.+)/', '.\1', trim($_POST['add_remove_suffix']));
				if ($add_suffix && !in_array($add_suffix, $this->Conf->Eval_suffix_list)) {
					$this->Conf->Eval_suffix_list[] = $add_suffix;
				}
				$this->Conf->save_to_file(['Eval_suffix_list']);
			} elseif ($_POST['options_action'] == "remove_suffix") {
				$remove_suffix = preg_replace('/^\.*(.+)/', '.\1', trim($_POST['add_remove_suffix']));
				if ($remove_suffix && in_array($remove_suffix, $this->Conf->Eval_suffix_list)) {
					foreach ($this->Conf->Eval_suffix_list as $i => $suffix) {
						if (preg_match('/^' . preg_quote($remove_suffix, '/') . '$/', $suffix)) {
							unset($this->Conf->Eval_suffix_list[$i]);
						}
					}
					$this->Conf->Eval_suffix_list = array_values($this->Conf->Eval_suffix_list); // reindex
					$this->Conf->save_to_file(['Eval_suffix_list']);
				}
			}
		}
		echo $this->Out->html_top();
		echo $this->options_page();
		echo $this->Out->html_bottom();
	}

	function options_page()
	{
		//$fancy_view_line_numbers_checked = $this->Conf->Fancy_view_line_numbers ? "CHECKED" : "";
		$themes = array(
			'default', '3024-day', '3024-night', 'abbott', 'abcdef', 'ambiance', 'ayu-dark', 'ayu-mirage',
			'base16-dark', 'base16-light', 'bespin', 'blackboard', 'cobalt', 'colorforth', 'darcula',
			'dracula', 'duotone-dark', 'duotone-light', 'eclipse', 'elegant', 'erlang-dark',
			'gruvbox-dark', 'hopscotch', 'icecoder', 'idea', 'isotope', 'lesser-dark', 'liquibyte',
			'lucario', 'material', 'material-darker', 'material-palenight', 'material-ocean',
			'mbo', 'mdn-like', 'midnight', 'monokai', 'moxer', 'neat', 'neo', 'night', 'nord',
			'oceanic-next', 'panda-syntax', 'paraiso-dark', 'paraiso-light', 'pastel-on-dark',
			'railscasts', 'rubyblue', 'seti', 'shadowfox', 'solarized', 'ssms', 'the-matrix',
			'tomorrow-night-bright', 'tomorrow-night-eighties', 'ttcn', 'twilight', 'vibrant-ink',
			'xq-dark', 'xq-light', 'yeti', 'yonce', 'zenburn'
		);
		$themes = array_combine($themes, $themes);
		$themeSelect = $this->Out->select_list_associated('CodeMirrorTheme', $this->Conf->CodeMirrorTheme, $themes) . " CodeMirror Theme";
		$protect_entities_checked = $this->Conf->Protect_entities ? "CHECKED" : "";
		$eval_executable_checked = $this->Conf->Eval_executable ? "CHECKED" : "";
		$unix_newlines_checked = $this->Conf->Unix_newlines ? "CHECKED" : "";
		$overwriteoriginal_checked = $this->Conf->Overwrite_original ? "CHECKED" : "";
		$Allow_browse_below_root_checked = $this->Conf->Allow_browse_below_root ? "CHECKED" : "";
		$UseCodeMirror_checked = $this->Conf->UseCodeMirror ? "CHECKED" : "";
		$Right_trim_checked = $this->Conf->Right_trim ? "CHECKED" : "";
		reset($this->Conf->Eval_suffix_list);
		$sections = array("<P><INPUT TYPE='CHECKBOX' NAME='Overwrite_original' VALUE='1' $overwriteoriginal_checked>
		Overwrite original file on 'Run' and 'Sync' if not a temporary file is used for evaluation. ( Original file will be stored in a backup file, use revert to saved to undo changes. )<br/>
		<INPUT TYPE='CHECKBOX' NAME='Allow_browse_below_root' VALUE='1' $Allow_browse_below_root_checked>
		Allow browsing below web root<br/>
		<INPUT TYPE='CHECKBOX' NAME='Right_trim' VALUE='1' $Right_trim_checked>
		Right trim white space on save<br/>
		<INPUT TYPE='CHECKBOX' NAME='Protect_entities' VALUE='1' $protect_entities_checked>
		Protect HTML entities(IE4/5)<br/>
		<INPUT TYPE='CHECKBOX' NAME='Eval_executable' VALUE='1' $eval_executable_checked>Make temporary files executable(CGI on UNIX)<br/>
		<INPUT TYPE='CHECKBOX' NAME='Unix_newlines' VALUE='1' $unix_newlines_checked>
		Use UNIX newlines(CGI on UNIX)<br/>
		<INPUT TYPE='CHECKBOX' NAME='UseCodeMirror' VALUE='1' $UseCodeMirror_checked>
		Use CodeMirror Editor<br/>
		{$themeSelect}
		</P>",
		"<P CLASS='indentall'>Suffix list:&nbsp;&nbsp;<b><I>&nbsp;" . join(" &nbsp;", $this->Conf->Eval_suffix_list) . "</I></b></P>\n
		<P CLASS='indentall'><INPUT TYPE='text' NAME='add_remove_suffix' SIZE='8'> Add/remove suffix
		<div style='padding-left:20pt;'>".$this->Out->menu_button('Add','document.options_form.options_action.value="add_suffix"; document.options_form.action.value="options"; document.options_form.submit();')."
	   	".$this->Out->menu_button('Remove','document.options_form.options_action.value="remove_suffix"; document.options_form.action.value="options"; document.options_form.submit();')."</div></P>",
		"<p class='indentall'>".$this->Out->select_list('editorfont',$this->Conf->editorfont,array('Monaco','Andale mono','Lucida console','Lucidatypewriter','Courier New','Courier','Fixed','Monospace'))." Editor font<br/>
		".$this->Out->select_list('editorfontsize',$this->Conf->editorfontsize,array('8px','9px','10px','11px','12px','13px','14px','15px','16px','17px','18px'))." Editor font size<br/>
		".$this->Out->select_list('editorlinespace',$this->Conf->editorlinespace,array('1px','2px','3px','4px','5px','6px'))." Editor line space
		</p>",
		"<P CLASS='indentall'><INPUT TYPE='text' NAME='Phpneturl' VALUE='{$this->Conf->Phpneturl}' SIZE='42'> Help url</P>");
		//$ret .= "<DIV ALIGN='CENTER'>\n";
		//$ret .= "<H2>I D E . P H P&nbsp;&nbsp;&nbsp;&nbsp;O P T I O N S</H2></DIV>\n";
		$ret = "<div class='fixed_window' style='overflow:auto;background-color:{$this->Out->Bgcolor};'>";
		$ret .= "<FORM NAME='options_form' METHOD='POST' ACTION='{$_SERVER['PHP_SELF']}'>\n";
		$ret.="<br/>";
		$ret .= "<INPUT TYPE='hidden' NAME='action' VALUE='save_options'>\n";
		$ret .= "<INPUT TYPE='hidden' NAME='options_action' VALUE=''>\n";
		foreach ($sections as $content) {
			$ret .= $this->Out->info_box(600, $content);
			$ret .= "<BR>\n";
		}
		//$ret .= "<BR><DIV ALIGN='CENTER'>\n";
		//$ret .= "<A HREF='javascript: document.options_form.submit()' CLASS='netscapesucks'>[ s a v e ]</A></DIV>\n";
		$ret .= "</FORM>\n";
		$ret.="</div>";
		$ret .= "<style type=\"text/css\">
		table {
			width:96%;
			max-width:96%;
		}";

		return($ret);
	}

	function options_page_save($var_names_array)
	{
		global $_POST;
	
		$this->Conf->Right_trim = isset($_POST['Right_trim']) ? 1 : 0;
		$this->Conf->Overwrite_original = isset($_POST['Overwrite_original']) ? 1 : 0;
		$this->Conf->Allow_browse_below_root = isset($_POST['Allow_browse_below_root']) ? 1 : 0;
		$this->Conf->UseCodeMirror = isset($_POST['UseCodeMirror']) ? 1 : 0;
		$this->Conf->CodeMirrorTheme = $_POST['CodeMirrorTheme'] ?? 'default';
		$this->Conf->Protect_entities = isset($_POST['Protect_entities']) ? 1 : 0;
		$this->Conf->Eval_executable = isset($_POST['Eval_executable']) ? 1 : 0;
		$this->Conf->Unix_newlines = isset($_POST['Unix_newlines']) ? 1 : 0;
		$this->Conf->Phpneturl = $_POST['Phpneturl'] ?? '';
	
		$this->Conf->save_to_file($var_names_array);
	}
}
?>