<?php

class SortOrder {
    const NameAsc  = 1;
    const NameDesc = 2;
    const DateAsc  = 3;
    const DateDesc = 4;
    const SizeAsc  = 5;
    const SizeDesc = 6;
}

class FileTable
{
    var $dir;
    var $reldir;
    var $currentfile;
    var $sortorder;
    var $browsebelowroot;
    var $files;
    var $header1;
    var $header2;
    var $header3;
    var $sortimage1;
    var $sortimage2;
    var $sortimage3;
	private $asset_url;

    function __construct($reldir,$currentfile,$sortorder,$browsebelowroot)
    {
        $this->reldir=$reldir;
		//$this->dir=realpath(dirname(__FILE__)."/$reldir");
        $this->dir=realpath($reldir);
        $this->currentfile=$currentfile;
        $this->sortorder=$sortorder;
        $this->browsebelowroot=$browsebelowroot;
		// Räkna ut URL-sökväg till där bilderna finns
    	$this->asset_url = rtrim(str_replace($_SERVER['DOCUMENT_ROOT'], '', __DIR__), '/') . '/';
    }

	function getFiles()
	{
		$dir_handle = @opendir($this->dir);
		if (!$dir_handle) {
			return false;
		}
		//running the while loop
		$this->files=array();
		while ($file = readdir($dir_handle)) {
			if ($file!="." ) {
				if ((realpath($_SERVER['DOCUMENT_ROOT'].'/..') != realpath($this->dir.'/'.$file)) || ($this->browsebelowroot != 0)) {
					$fileitem=array('name'=>$file,'date'=>@filemtime($this->dir."/".$file),'size'=>@filesize($this->dir."/".$file),'path'=>$this->dir."/".$file);
					$this->files[]=$fileitem;
				}
			}
		}
		//closing the directory
		closedir($dir_handle);
        return true;
    }

    function createHeaders()
    {
		switch ($this->sortorder) {
		case SortOrder::NameAsc:
			$this->sortFiles($this->files,'name',false);
			$this->header1="class='sortcol' onClick='onChangeSortOrder(2)'";
			$this->header2="onClick='onChangeSortOrder(3)'";
			$this->header3="onClick='onChangeSortOrder(5)'";
			$this->sortimage1="<img src='{$this->asset_url}sort_up.gif'/>";
			break;
		case SortOrder::NameDesc:
			$this->sortFiles($this->files,'name',true);
			$this->header1="class='sortcol' onClick='onChangeSortOrder(1)'";
			$this->header2="onClick='onChangeSortOrder(3)'";
			$this->header3="onClick='onChangeSortOrder(5)'";
			$this->sortimage1="<img src='{$this->asset_url}sort_down.gif'/>";
			break;
		case SortOrder::DateAsc:
			$this->sortFiles($this->files,'date',false);
			$this->header2="class='sortcol' onClick='onChangeSortOrder(4)'";
			$this->header1="onClick='onChangeSortOrder(1)'";
			$this->header3="onClick='onChangeSortOrder(5)'";
			$this->sortimage2="<img src='{$this->asset_url}sort_up.gif'/>";
			break;
		case SortOrder::DateDesc:
			$this->sortFiles($this->files,'data',true);
			$this->header2="class='sortcol' onClick='onChangeSortOrder(3)'";
			$this->header1="onClick='onChangeSortOrder(1)'";
			$this->header3="onClick='onChangeSortOrder(5)'";
			$this->sortimage2="<img src='{$this->asset_url}sort_down.gif'/>";
			break;
		case SortOrder::SizeAsc:
			$this->sortFiles($this->files,'size',false);
			$this->header3="class='sortcol' onClick='onChangeSortOrder(6)'";
			$this->header2="onClick='onChangeSortOrder(3)'";
			$this->header1="onClick='onChangeSortOrder(1)'";
			$this->sortimage3="<img src='{$this->asset_url}sort_up.gif'/>";
			break;
		case SortOrder::SizeDesc:
			$this->sortFiles($this->files,'size',true);
			$this->header3="class='sortcol' onClick='onChangeSortOrder(5)'";
			$this->header2="onClick='onChangeSortOrder(3)'";
			$this->header1="onClick='onChangeSortOrder(1)'";
			$this->sortimage3="<img src='{$this->asset_url}sort_down.gif'/>";
			break;
		default:
			$this->header1="class='sortcol' onClick='onChangeSortOrder(1)'";
			$this->header2="onClick='onChangeSortOrder(3)'";
			$this->header3="onClick='onChangeSortOrder(5)'";
			break;
		}
    }

    function writeHeaders()
    {
        $ret ="<tr><th align='left' $this->header1 style='text-indent:30px;'>\n";
        $ret.=$this->sortimage1.'Name';
		$ret .="</th><th align='left' $this->header2>";
		$ret.=$this->sortimage2.'Date';
		$ret .="</th><th  align='right' $this->header3>";
		$ret.=$this->sortimage3.'Size ';//       
		$ret .="</th><th style='border-right:0px;'></th></tr>\n";
		return $ret;
    }

    function writefiles()
    {
		$k=0;
		$ret = "";
		foreach ($this->files as $file) {
			$path="{$this->reldir}/{$file['name']}";
			$perms=substr(sprintf('%o', fileperms(realpath($path))), -3);
			if ($file['name']=='..') {
				$perms='';
			}
			$ret.=($path==$this->currentfile) ? "<tr class='selrow'><td>\n" : "<tr class='row$k'><td>\n";
			if (is_dir(realpath($this->dir."/".$file['name']))) {
				if (is_readable(realpath($this->dir."/".$file['name']))) {
					$ret .="   <a href='#' onClick='javascript:onFolderClick(\"{$file['name']}\");return false;'><img class='fileimg' src='{$this->asset_url}folder_icon.png'/> ".$file['name']."</a>\n";
				} else {
					$ret .="   <img class='fileimg' src='{$this->asset_url}folder_icon.png'/> ".$file['name'];
				}
				$ret.="</td>\n<td> ".date("Y-m-d",$file['date'])." </td>\n<td align='right' style='padding-right:7px;'> </td>\n<td>\n<a href='#' onClick='javascript:onPermissionsClick(\"{$path}\",\"$perms\");return false;'>".$perms."</a>\n";
			} else {
				if (is_readable(realpath($this->dir."/".$file['name']))) {
					$ret .="   <a href='#' onClick='javascript:onFileClick(\"{$path}\");return false;'><img class='fileimg' src='{$this->asset_url}file_icon.png'/> ".$file['name']."</a>\n";
				} else {
					$ret .="   <img class='fileimg' src='{$this->asset_url}file_icon.png'/> ".$file['name'];
				}
				$ret .="</td>\n<td> ".date("Y-m-d",$file['date'])." </td>\n<td align='right' style='padding-right:7px;'>".$this->formatBytes($file['size'])."</td>\n<td><a href='#' onClick='javascript:onPermissionsClick(\"{$path}\",\"$perms\");return false;'>".$perms."</a>\n";
			}
			$ret .="</td></tr>\n";
			$k=1-$k;
		}
        return $ret;
    }

	function file_table()
	{
    	$ret = "";
		$ret .= "<script type=\"text/javascript\" src=\"{$this->asset_url}filetable.js\"></script>
    		<link rel=\"stylesheet\" type=\"text/css\" href=\"{$this->asset_url}filetable.css\">";
	    if ($this->getFiles() === false)
	    {
			$ret .="<table width='100%' cellpadding='0' border='0' cellspacing='0' CLASS='boldtable'><tr><td align='left'>\n";
			$ret .="Unable to read $dir<br>";
			$ret .="<a href='#' onClick='javascript:onFolderClick(\"./\");return false;'><img src='{$this->asset_url}folder.png'/> Home</a>\n";
			$ret .="</td></tr></table>";
            return $ret;
        }
        $this->createHeaders();
		if (count($this->files)) {
			$ret .="<table width='100%' cellpadding='0' border='0' cellspacing='0' CLASS='boldtable'>\n";
			$ret.=$this->writeHeaders();
            $ret.=$this->writeFiles();
			$ret .="</table>\n";
		}
	    return $ret;
	}

	function sortFiles(&$files, $Key, $Desc)
	{
		$dirs = [];
		$plainfiles = [];

		if (!is_array($files)) return;

		foreach ($files as $file) {
			if (is_dir(realpath($file['path'] ?? ''))) {
				$dirs[] = $file;
			} else {
				$plainfiles[] = $file;
			}
		}

		$this->sortFileChunk($dirs, $Key, $Desc, 0, count($dirs));
		$this->sortFileChunk($plainfiles, $Key, $Desc, 0, count($plainfiles));
		$files = array_merge($dirs, $plainfiles);
	}

    function sortFileChunk(&$files,$Key,$Desc,$min,$max)
    {
	    for ($i=$min; $i<$max; $i++) {
		    for ($j=$min; $j<$max; $j++) {
    			if ($Desc) {
    				if (strtolower($files[$i][$Key])>strtolower($files[$j][$Key])) {
    					$temp=$files[$i];
    					$files[$i]=$files[$j];
    					$files[$j]=$temp;
    				}
    			} else {
    				if (strtolower($files[$i][$Key])<strtolower($files[$j][$Key])) {
    					$temp=$files[$i];
    					$files[$i]=$files[$j];
    					$files[$j]=$temp;
    				}
    			}
    		}
    	}
    }

    function formatBytes($bytes, $precision = 2)
    {
    	$units = array('B', 'KB', 'MB', 'GB', 'TB');
    	$bytes = max($bytes, 0);
    	$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    	$pow = min($pow, count($units) - 1);
    	$bytes /= pow(1024, $pow);
    	return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
/*
class SortOrder {
    const NameAsc  = 1;
    const NameDesc = 2;
    const DateAsc  = 3;
    const DateDesc = 4;
    const SizeAsc  = 5;
    const SizeDesc = 6;
}

class FileTable
{
    // === INSTANSVARIABLER ===
    var $dir;
    var $reldir;
    var $currentfile;
    var $sortorder;
    var $browsebelowroot;
    var $files;
    var $header1;
    var $header2;
    var $header3;
    var $sortimage1;
    var $sortimage2;
    var $sortimage3;

    // === KONSTRUKTOR ===
    function __construct($reldir, $currentfile, $sortorder, $browsebelowroot = false) {
        $this->reldir = $reldir;
        $this->dir = realpath($reldir);
        $this->currentfile = $currentfile;
        $this->sortorder = $sortorder;
        $this->browsebelowroot = $browsebelowroot;
    }

    // === EMBED SCRIPT + CSS ===
    static function embedAssets($pathPrefix = '') {
        return <<<HTML
<!-- FileTable assets -->
<link rel="stylesheet" type="text/css" href="{$pathPrefix}filetable.css">
<script src="{$pathPrefix}filetable.js"></script>

HTML;
    }

    // === RENDER-WRAPPER ===
    static function render($dir, $currentFile = null, $sortOrder = SortOrder::NameAsc, $allowBelowRoot = false) {
        $table = new self($dir, $currentFile, $sortOrder, $allowBelowRoot);
        echo self::embedAssets();
        echo $table->file_table();
    }

    // === LISTA FILER ===
    function getFiles() {
        $dh = @opendir($this->dir);
        if (!$dh) return false;
        $this->files = [];
        while ($file = readdir($dh)) {
            if ($file !== '.') {
                if ((realpath($_SERVER['DOCUMENT_ROOT'] . '/..') !== realpath($this->dir . '/' . $file)) || $this->browsebelowroot) {
                    $this->files[] = [
                        'name' => $file,
                        'date' => @filemtime($this->dir . '/' . $file),
                        'size' => @filesize($this->dir . '/' . $file),
                        'path' => $this->dir . '/' . $file
                    ];
                }
            }
        }
        closedir($dh);
        return true;
    }

    // === SORTERA OCH BYGG TABELL ===
    function file_table() {
        if ($this->getFiles() === false) {
            return "<table class='boldtable'><tr><td>Unable to read {$this->reldir}<br><a href='#' onclick='onFolderClick(\"./\")'><img src='folder.png'> Home</a></td></tr></table>";
        }
        $this->createHeaders();
        $ret = "<table class='boldtable' cellpadding='0' cellspacing='0' width='100%'>";
        $ret .= $this->writeHeaders();
        $ret .= $this->writeFiles();
        $ret .= "</table>";
        return $ret;
    }

    function createHeaders() {
        switch ($this->sortorder) {
            case SortOrder::NameAsc:
                $this->sortFiles($this->files, 'name', false);
                $this->header1 = "class='sortcol' onclick='onChangeSortOrder(2)'";
                $this->sortimage1 = "<img src='sort_up.gif'/>";
                break;
            case SortOrder::NameDesc:
                $this->sortFiles($this->files, 'name', true);
                $this->header1 = "class='sortcol' onclick='onChangeSortOrder(1)'";
                $this->sortimage1 = "<img src='sort_down.gif'/>";
                break;
            case SortOrder::DateAsc:
                $this->sortFiles($this->files, 'date', false);
                $this->header2 = "class='sortcol' onclick='onChangeSortOrder(4)'";
                $this->sortimage2 = "<img src='sort_up.gif'/>";
                break;
            case SortOrder::DateDesc:
                $this->sortFiles($this->files, 'date', true);
                $this->header2 = "class='sortcol' onclick='onChangeSortOrder(3)'";
                $this->sortimage2 = "<img src='sort_down.gif'/>";
                break;
            case SortOrder::SizeAsc:
                $this->sortFiles($this->files, 'size', false);
                $this->header3 = "class='sortcol' onclick='onChangeSortOrder(6)'";
                $this->sortimage3 = "<img src='sort_up.gif'/>";
                break;
            case SortOrder::SizeDesc:
                $this->sortFiles($this->files, 'size', true);
                $this->header3 = "class='sortcol' onclick='onChangeSortOrder(5)'";
                $this->sortimage3 = "<img src='sort_down.gif'/>";
                break;
        }
        $this->header1 ??= "onclick='onChangeSortOrder(1)'";
        $this->header2 ??= "onclick='onChangeSortOrder(3)'";
        $this->header3 ??= "onclick='onChangeSortOrder(5)'";
    }

    function writeHeaders() {
        return "<tr>
<th $this->header1 style='text-indent:30px'>{$this->sortimage1}Name</th>
<th $this->header2>{$this->sortimage2}Date</th>
<th $this->header3 align='right'> {$this->sortimage3}Size</th>
<th></th></tr>";
    }

    function writeFiles() {
        $k = 0;
        $rows = "";
        foreach ($this->files as $file) {
            $path = "{$this->reldir}/{$file['name']}";
            $perms = substr(sprintf('%o', @fileperms(realpath($path))), -3);
            $isDir = is_dir(realpath($this->dir . '/' . $file['name']));
            $isReadable = is_readable(realpath($this->dir . '/' . $file['name']));
            $rowClass = ($path === $this->currentfile) ? 'selrow' : "row$k";
            $icon = $isDir ? 'folder.png' : 'file.png';

            $rows .= "<tr class='$rowClass'><td>";
            if ($isDir && $isReadable) {
                $rows .= "   <a href='#' onclick='onFolderClick(\"$file[name]\");return false;'><img src='$icon'> $file[name]</a>";
            } elseif (!$isDir && $isReadable) {
                $rows .= "   <a href='#' onclick='onFileClick(\"$path\");return false;'><img src='$icon'> $file[name]</a>";
            } else {
                $rows .= "<img src='$icon'> $file[name]";
            }

            $rows .= "</td><td>" . date("Y-m-d", $file['date']) . "</td><td align='right' style='padding-right:7px'>";
            $rows .= $isDir ? '' : $this->formatBytes($file['size']);
            $rows .= "</td><td><a href='#' onclick='onPermissionsClick(\"$path\",\"$perms\");return false;'>$perms</a></td></tr>";

            $k = 1 - $k;
        }
        return $rows;
    }

    function sortFiles(&$files, $key, $desc) {
        usort($files, function ($a, $b) use ($key, $desc) {
            return $desc ? strnatcasecmp($b[$key], $a[$key]) : strnatcasecmp($a[$key], $b[$key]);
        });
    }

    function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
*/
?>