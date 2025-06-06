<?php
/*******************************************************************************\
*    IDE.PHP, a web based editor for quick PHP development                     *
*    Copyright (C) 2000  Johan Ekenberg                                        *
*                                                                              *
*    This program is free software; you can redistribute it and/or modify      *
*    it under the terms of the GNU General Public License as published by      *
*    the Free Software Foundation; either version 2 of the License, or         *
*    (at your option) any later version.                                       *
*                                                                              *
*    This program is distributed in the hope that it will be useful,           *
*    but WITHOUT ANY WARRANTY; without even the implied warranty of            *
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             *
*    GNU General Public License for more details.                              *
*                                                                              *
*    You should have received a copy of the GNU General Public License         *
*    along with this program; if not, write to the Free Software               *
*    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA  *
*                                                                              *
*    To contact the author regarding this program,                             *
*    please use this email address: <ide.php@ekenberg.se>                      *
\*******************************************************************************/

if (isset($_POST['action']) && $_POST['action'] == 'set_download') {
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: private",false);
    header("Content-type: application/force-download");
    header("Content-Disposition: attachment; filename=\"".basename($_POST['save_as_filename'])."\";" );
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: ".filesize($_POST['save_as_filename']));
    readfile($_POST['save_as_filename']);
}

if (!file_exists('./.htpwd')) {
	include('admin_ide.php');
	exit;
}
require_once 'http_authenticate.php';
error_reporting(E_ERROR | E_WARNING | E_PARSE);
//set_magic_quotes_runtime(0);
include('./Page.phpclass');
include('./Conf.phpclass');

$Ide = new Ide;

function php_alert_safe($data) {
    echo "<script>alert(JSON.stringify(" . json_encode($data) . "));</script>";
}

class Ide
{
	//var $code;
    var $alert_message, $success_message;
	var $IDE_homepage_url	= "http://www.ekenberg.se/php/ide/";
	var $GPL_link		= "<A HREF='http://www.gnu.org/copyleft/gpl.html'>GNU General Public License</A>";
	var $PHP_link		= "<A HREF='http://www.php.net'>PHP</A>";
	var $IDE_version		= "2 . 0";
	var $recentfiles;
	var $recentdirs;
	var $recentevals;
	var $Conf,$Out,$Edit;

	function __construct()
	{
		global $_POST,$_FILES;//, $HTTP_GET_VARS;
		$this->Conf = new Conf;
		$this->Out  = new Page;
        $this->Edit= new Editor($this->Conf->IsBinary,$this->Conf->Protect_entities,$this->Conf->Unix_newlines,$this->Conf->Encoding);
		$this->recentfiles=new RecentList($this->Conf->recentfiles,$this->Conf->Current_file);
		$this->recentdirs=new RecentList($this->Conf->recentdirs,$this->Conf->Dir_path);
		$this->recentevals=new RecentList($this->Conf->recentevals,$this->Conf->Eval_path);

        //php_alert_safe($_POST);

		if (isset($_POST['prev_submit']) && $_POST['prev_submit']==$this->Conf->Previous_submit) {
			$_POST=array();
		}
		if (count($_POST)) {
			$this->Conf->Previous_submit=$_POST['prev_submit'];
			if ($this->Conf->Current_file != $_POST['Current_filename']) {
				$_POST=array();
			}
		}
		if (isset($_POST['change_counter'])) {
			$this->Conf->Dirtyfile=$_POST['change_counter'];
		}
        //code is in $_POST
		if (isset($_POST['code'])) {
		    $this->Edit->createFromCode($_POST['code']);
		}
        //code is not in $_POST
		if (!$this->Edit->dataSet()) {
			if (file_exists($this->Conf->Code_file)) {
				$this->Edit->createFromCodeFile($this->Conf->Code_file);
			} else {
				if (file_exists($this->Conf->Current_file)) {
				    $this->Conf->IsBinary=$this->Edit->createFromFile($this->Conf->Current_file);
				    $this->Conf->Encoding=$this->Edit->encoding;
					$_POST['code_file_name']=$this->Conf->Current_file;
					$_POST['save_as_filename'] = $this->Conf->Current_file;
					// make backup
					$this->make_backup($this->Conf->Current_file);
				}
			}
		}

/*
** Check our environment.
*/
		if ($error = $this->Conf->Is_bad_environment()) {
			print $this->Out->html_top();
			print '<H3><BLOCKQUOTE>'.$error.'</BLOCKQUOTE></H2>';
			print $this->Out->html_bottom();
			exit;
		}
		/*
** Always save the code in our code and tmp files
*/
		if ($this->Edit->dataSet()) {
            $this->save_code_files();
        }

		/*
** Act according to 'action'
*/
		if(isset($_POST['action'])) {
			if ($_POST['action']=='set_sortorder') {
				$this->Conf->Dir_sortorder=$_POST['sortorder'];
			} elseif($_POST['action']=='beautify') {
				$beauty=new Beautifier;
				$this->Edit->createFromCode($beauty->publicProcessHandler($this->Edit->getCode(), 1));
				$this->Conf->Dirtyfile=1;
			} elseif($_POST['action']=='layoutstyle') {
				$this->Conf->LayoutStyle=$_POST['layoutstyle'];
			} elseif($_POST['action']=='phpnet') {
				$this->Conf->Phpnet=$_POST['phpnet'];
			} elseif($_POST['action']=='fancy') {
				$this->Conf->Fancy=$_POST['fancy'];
			//} elseif($_POST['action']=='use_code_mirror') {
			//	$this->Conf->UseCodeMirror=$_POST['use_code_mirror'];
			} elseif($_POST['action']=='chmod_file') {
				if (!@chmod($_POST['some_file_name'],octdec(substr('0000'.$_POST['chmod_value'], -4)))) {
					$this->alert_message = "Could not CHMOD file {$_POST['some_file_name']}";
				} else {
					clearstatcache();
				}
			} elseif($_POST['action']=='set_rename') {
				if (! strlen($_POST['save_as_filename'])) {
					$this->alert_message = "Can't rename file without a filename!!";
				} else if(file_exists($_POST['save_as_filename'])) {
					$_POST['overwrite_ok']=1;
				} else {
					$oldfilename=$this->Conf->Current_file;
					rename($this->Conf->Current_file,$this->Conf->Dir_path.'/'.$_POST['save_as_filename']);
					$this->open_file($this->Conf->Dir_path.'/'.$_POST['save_as_filename']);
					$this->recentfiles->remove($oldfilename);
					if ($this->Conf->Eval_path==$oldfilename) {
						$this->Conf->Eval_path=$this->Conf->Current_file;
						$this->recentevals->append($this->Conf->Current_file);
					}
				}
			} elseif($_POST['action']=='set_rename_replace') {
				$oldfilename=$this->Conf->Current_file;
				rename($this->Conf->Current_file,$this->Conf->Dir_path.'/'.$_POST['save_as_filename']);
				$this->open_file($this->Conf->Dir_path.'/'.$_POST['save_as_filename']);
				$this->recent´files->remove($oldfilename);
				if ($this->Conf->Eval_path==$oldfilename) {
					$this->Conf->Eval_path=$this->Conf->Current_file;
					$this->recentevals->append($this->Conf->Current_file);
				}
			} elseif($_POST['action']=='set_undo') {
				if (file_exists($this->Conf->Current_file)) {
					$this->undo_file($this->Conf->Current_file);
					$this->open_file($this->Conf->Current_file);
				}
			} elseif($_POST['action']=='set_new') {
				if (! strlen($_POST['save_as_filename'])) {
					$this->alert_message = 'Can\'t create file without a filename!!';
				} else if(file_exists($_POST['save_as_filename'])) {
					$_POST['overwrite_ok']=1;
				} else {
					$newfile=fopen($_POST['save_as_filename'], 'w');
					fputs($newfile,$this->Conf->Code_template);
					fclose($newfile);
				}
			} elseif($_POST['action']=='set_new_replace') {
				$newfile=fopen($_POST['save_as_filename'], 'w');
				fputs($newfile,'');
				fclose($newfile);
			} elseif($_POST['action']=='set_upload') {
				if (! strlen($_FILES['uploadedfile']['name'])) {
					$this->alert_message = 'Can\'t upload file without a filename!!';
				} else {

					$target_path = $this->Conf->Dir_path . '/' . basename( $_FILES['uploadedfile']['name']);
					if(file_exists($target_path)) {
						$overwriting=true;
					}

					if(move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path)) {
						if ($overwriting)
						{
							$this->success_message= "The file ".  basename( $_FILES['uploadedfile']['name']). " was uploaded overwriting an existing file";
						}
						else
						{
							$this->success_message= "The file ".  basename( $_FILES['uploadedfile']['name']). " was uploaded";
						}
					} else{
						$this->alert_message= "There was an error uploading the file, please try again!";
					}
				}
				/*
			} elseif($_POST['action']=='set_set_upload_replace') {
				$newfile=fopen($_POST['save_as_filename'], 'w');
				fputs($newfile,'');
				fclose($newfile);
				*/
			} elseif($_POST['action']=='set_file_from_url') {
				if (! strlen($_POST['save_as_filename'])) {
					$this->alert_message = 'Can\'t get file without an URL!!';
				} else if(file_exists($this->Conf->Dir_path.'/'.basename($_POST['save_as_filename']))) {
					$_POST['overwrite_ok']=1;
				} else {
					$fh = fopen($this->Conf->Dir_path.'/'.basename($_POST['save_as_filename']), "w");
					fputs($fh,$this->getRemoteFile($_POST['save_as_filename']));
					fclose($fh);
					$this->success_message= "The file ".  $this->Conf->Dir_path.'/'.basename($_POST['save_as_filename']). " was saved";
				}
			} elseif($_POST['action']=='set_file_from_url_replace') {
					$fh = fopen($this->Conf->Dir_path.'/'.basename($_POST['save_as_filename']), "w");
					fputs($fh,$this->getRemoteFile($_POST['save_as_filename']));
					fclose($fh);
					$this->success_message= "The file ".  $this->Conf->Dir_path.'/'.basename($_POST['save_as_filename']). " was saved";
			} elseif($_POST['action']=='set_new_directory') {
				if (! strlen($_POST['save_as_filename'])) {
					$this->alert_message = 'Can\'t create directory without a filename!!';
				} else {
					mkdir($_POST['save_as_filename']);
				}
			} elseif(($_POST['action']=='set_copy') || ($_POST['action']=='set_copy_discard') || ($_POST['action']=='set_copy_save')) {
				if (!$this->Conf->IsBinary)	{
					if ($_POST['action']=='set_copy_discard') {
						$this->undo_file($this->Conf->Current_file);
					} elseif ($_POST['action']=='set_copy_save') {
						$this->save_file($this->Conf->Current_file);
					}
				}
				$this->Conf->Copy_file=$this->Conf->Current_file;
			} elseif($_POST['action']=='set_paste') {
				if (file_exists($this->Conf->Copy_file)) {
					$filename=basename($this->Conf->Copy_file);
					if (file_exists($this->Conf->Dir_path.'/'.$filename)) {
						$filename='copy of '.$filename;
					}
					@copy($this->Conf->Copy_file,$this->Conf->Dir_path.'/'.$filename);
				}
			} elseif($_POST['action']=='set_kill') {
				if (file_exists($this->Conf->Current_file)) {
					unlink($this->Conf->Current_file);
					$this->recentfiles->remove($this->Conf->Current_file);
					$this->Conf->Current_file='';
					$this->Edit->createFromData('');
					$this->save_code_files();
					$this->success_message = 'File deleted';
				} else {
					$this->alert_message = 'File not found';
				}
			} elseif($_POST['action']=='set_kill_directory') {
				if (!is_dir($this->Conf->Dir_path)) {
					$this->alert_message = $this->Conf->Dir_path.' directory not found';
				}
				if (!$this->dir_is_empty($this->Conf->Dir_path)) {
					$this->alert_message = $this->Conf->Dir_path.' is not empty';
				} else {
					//delTree(dirname($this->Conf->Current_file));
					$reldir="{$this->Conf->Dir_path}/..";
					$reldir=$this->shorten_reldir($reldir);
					rmdir($this->Conf->Dir_path);
					$this->recentdirs->remove($this->Conf->Dir_path);
					$this->success_message = $this->Conf->Dir_path.' directory deleted';
					$this->Conf->Dir_path=$reldir;
					$this->Conf->Current_file='';
					$this->Edit->createFromData('');
					$this->save_code_files();
				}
			} elseif($_POST['action']=='set_directory') {
				$reldir="{$this->Conf->Dir_path}/{$_POST['current_directory']}";
				$reldir=$this->shorten_reldir($reldir);
				if (is_dir($reldir)) {
					$this->Conf->Dir_path=$reldir;
					$this->recentdirs->append($reldir);
				} else {
					$this->alert_message .= $this->Conf->Dir_path.', access not permitted';
				}
			} elseif($_POST['action'] == 'eval') {
				if (($this->Conf->Overwrite_original) && (substr($this->Conf->Syncmode,0,4)!='temp')) {
					if (!@copy($this->Conf->Code_file, $this->Conf->Current_file)) {
						$this->alert_message .= $this->Conf->Current_file.' is not writable';
					}
				}
				$this->Conf->Phpnet=0;
				//print $this->js_evaluation_window ();
			} elseif($_POST['action'] == 'eval_sync') {
				$this->Conf->Syncmode=$_POST['syncmode'];
				$this->Conf->Phpnet=0;
				if ($_POST['syncmode']=='sync') {
					if ($this->Conf->Overwrite_original) {
						$this->Edit->saveFile($this->Conf->Current_file);
					}
				}
				if (substr($this->Conf->Syncmode,0,4) == 'temp') {
					$this->save_code_files();
				}
			} elseif($_POST['action'] == 'eval_change') {
				$this->Conf->Eval_path=$_POST['eval_path'];
				$this->Conf->Phpnet=0;
				$this->Conf->Syncmode='';
				if ($this->Conf->Overwrite_original) {
					$this->Edit->saveFile($this->Conf->Current_file);
				}
				$this->recentevals->append($this->Conf->Eval_path);
				$this->Conf->Phpnet=0;
			} elseif($_POST['action'] == 'save') {
				$this->save_file($this->Conf->Current_file);
			} elseif($_POST['action'] == 'save_as') {
				if (file_exists($_POST['save_as_filename'])) {
					$_POST['overwrite_ok']=1;
				} else {
					$this->save_file($_POST['save_as_filename']);
					$this->recentfiles->append($_POST['save_as_filename']);
				}
			} elseif($_POST['action'] == 'save_as_replace') {
				$this->save_file($_POST['save_as_filename']);
				$this->recentfiles->append($_POST['save_as_filename']);
			} elseif($_POST['action'] == 'open_file') {
				$filepath="{$this->Conf->Data_dir}/{$_POST['code_file_name']}";
				$this->open_file($filepath);
				$this->recentfiles->append($filepath);
			} elseif(($_POST['action']=='load_browse_file') || ($_POST['action']=='load_browse_discard') || ($_POST['action']=='load_browse_save')) {
				if (!$this->Conf->IsBinary)	{
					if ($_POST['action']=='load_browse_discard') {
						$this->undo_file($this->Conf->Current_file);
					} elseif ($_POST['action']=='load_browse_save') {
						$this->save_file($this->Conf->Current_file,true);
					}
				}
				$filepath=$_POST['some_file_name'];
				$filepath=$this->shorten_reldir($filepath);
				$this->open_file($filepath);
				$this->recentfiles->append($filepath);
			}
			elseif($_POST['action'] == 'show_template')
			{
				$this->Conf->IsBinary=$this->Edit->createFromData($this->Conf->Code_template);
				$this->Conf->Encoding=$this->Edit->encoding;
				$this->save_code_files();
				$this->Conf->Dirtyfile='1';
			} elseif($_POST['action'] == 'save_as_template') {
				$this->Conf->Code_template = $this->Edit->getCode();
				$this->success_message .= 'Template was saved';
			} /*elseif($_POST['action'] == 'set_unzip') {
				$zippath=realpath($this->Conf->Dir_path);
				exec("cd $zippath; unzip ".realpath($this->Conf->Current_file),$output,$return_var);
				if ($return_var==0)
				{
					$this->success_message .= 'Archive '.basename($this->Conf->Current_file).' was unzipped';
				}
				else
				{
					$this->alert_message .= 'Could not unzip archive '.basename($this->Conf->Current_file);
				}
			} */elseif ($_POST['action'] == 'set_unzip') {
				$zippath = realpath($this->Conf->Dir_path);
				$zipfile = realpath($this->Conf->Current_file);

				$zip = new ZipArchive();
				if ($zip->open($zipfile) === TRUE) {
					$zip->extractTo($zippath);
					$zip->close();
					$this->success_message .= 'Archive ' . basename($this->Conf->Current_file) . ' was unzipped';
				} else {
					$this->alert_message .= 'Could not unzip archive ' . basename($this->Conf->Current_file);
				}
			} /*elseif($_POST['action'] == 'zip_folder') {
				$zippath=realpath($this->Conf->Dir_path.'/..');
				$zipname=$zippath.'/'.basename($this->Conf->Dir_path).'.zip';
				exec("cd $zippath; zip -r $zipname ./".basename($this->Conf->Dir_path),$output,$return_var);
				if ($return_var==0)
				{
					$this->success_message .= 'Archive '.$zipname.' was created';
					$reldir="{$this->Conf->Dir_path}/..";
					$reldir=$this->shorten_reldir($reldir);
					$this->Conf->Dir_path=$reldir;
					$this->Conf->Current_file='';
					$this->Edit->createFromData('');
					$this->save_code_files();
				}
				else
				{
					$this->alert_message .= 'Could not create archive '.$zipname;
				}
			}*/elseif ($_POST['action'] == 'zip_folder') {
				$zippath = realpath($this->Conf->Dir_path . '/..');
				$sourceDir = realpath($this->Conf->Dir_path);
				$zipname = $zippath . '/' . basename($sourceDir) . '.zip';

				$zip = new ZipArchive();
				if ($zip->open($zipname, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
					$files = new RecursiveIteratorIterator(
						new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
						RecursiveIteratorIterator::LEAVES_ONLY
					);

					foreach ($files as $file) {
						$filePath = $file->getRealPath();
						$relativePath = substr($filePath, strlen($sourceDir) + 1);
						$zip->addFile($filePath, $relativePath);
					}

					$zip->close();
					$this->success_message .= 'Archive ' . basename($zipname) . ' was created';

					$reldir = "{$this->Conf->Dir_path}/..";
					$reldir = $this->shorten_reldir($reldir);
					$this->Conf->Dir_path = $reldir;
					$this->Conf->Current_file = '';
					$this->Edit->createFromData('');
					$this->save_code_files();
				} else {
					$this->alert_message .= 'Could not create archive ' . basename($zipname);
				}
			} elseif(($_POST['action'] == 'zip_system') || ($_POST['action'] == 'download_system')) {
				$this->systemZip();
			} elseif($_POST['action'] == 'set_ftp_file') {
				if ($this->file_to_ftp($_POST['save_as_filename'],$this->Conf->Current_file))
				{
					$this->Conf->ftp_path=$_POST['save_as_filename'];
				}
			} elseif($_POST['action'] == 'set_ftp_system') {
				$this->systemZip();
				if ($this->file_to_ftp($_POST['save_as_filename'],'./systemzip/idephp.zip'))
				{
					$this->Conf->ftp_system_path=$_POST['save_as_filename'];
				}
			}
		}
    	if (substr($this->Conf->Syncmode,0,4) == 'temp') {
	        /*
            ** Set file permissions as desired
            */
            if ($this->Conf->Eval_executable) {
                chmod ($this->current_eval(), 0755);
            }
            else {
                chmod ($this->current_eval(), 0644);
            }
	   }
		/*
** Print top of page
*/
		print $this->Out->html_top($this->Conf->Encoding);
		/*
** Print the main page and exit
*/
		print $this->main_page();
		print $this->Out->html_bottom();
		exit;
	}
	/*
** Functions
*/
	function file_to_ftp($uri,$file)
	{
		$retval=false;
		$conn_id=$this->getFtpConnection($uri);
		if ($conn_id != null)
		{
			if (ftp_put($conn_id, basename($file), realpath($file), FTP_BINARY)) {
				$this->success_message .= basename($file) .' was uploaded to '. $uri;
				$retval=true;
			} else {
				$this->alert_message .= 'Could not upload to '.$uri;
			}
			// close the connection
			ftp_close($conn_id);
		}
		else
		{
			$this->alert_message .= 'Could not connect to '.$uri;
		}
		return $retval;
	}

	function getFtpConnection($uri)
	{
		// Split FTP URI into:
		// $match[0] = ftp://username:password@sld.domain.tld/path1/path2/
		// $match[1] = ftp://
		// $match[2] = username
		// $match[3] = password
		// $match[4] = sld.domain.tld
		// $match[5] = /path1/path2/
		preg_match("/(ftp:\/\/)(.*?):(.*?)@(.*?)(\/.*)/i", $uri, $match);

		if ($match[1] != 'ftp://')
	    {
        	return null;
        }
		// Set up a connection
		$conn = ftp_connect($match[4]);

		// Login
		if (ftp_login($conn, $match[2], $match[3]))
		{
			// Change the dir
			ftp_chdir($conn, $match[5]);
			// Return the resource
			return $conn;
		}

		// Or return null
		return null;
	}
/*
	function systemZip()
	{
		$sysdir=realpath('./');
		mkdir("$sysdir/systemzip");
		mkdir("$sysdir/systemzip/idephp");
		$tempdir=realpath('./systemzip/idephp');
		mkdir("$tempdir/data");
		$sysfiles=array('index.php','about_ide.php','admin_ide.php','Changes.txt','Conf.phpclass','encoding_ide.php','http_authenticate.php','license.txt','options_ide.php','Page.phpclass','readme.txt','web_about_ide.php','data/example');
		foreach ($sysfiles as $sysfile)
		{
			copy("$sysdir/$sysfile","$tempdir/$sysfile");
		}
		$sysdirs=array('images','javascript','css');
		foreach ($sysdirs as $dir)
		{
			exec("cp -r -a $sysdir/$dir $tempdir/$dir 2>&1");
		}
		exec("cd $sysdir/systemzip; zip -r $sysdir/systemzip/idephp.zip ./idephp/*");
		exec("rm -rf $sysdir/systemzip/idephp");
	}
*/

	function systemZip()
	{
		$sysdir = realpath('./');
		$zipDir = $sysdir . '/systemzip';
		$tempDir = $zipDir . '/idephp';
		$zipFile = $zipDir . '/idephp.zip';

		// Skapa tempstruktur
		if (!is_dir($tempDir . '/data')) {
			mkdir($tempDir . '/data', 0777, true);
		}

		// Kopiera specifika filer
		$sysfiles = [
			'index.php', 'about_ide.php', 'admin_ide.php', 'Changes.txt', 'Conf.phpclass',
			'encoding_ide.php', 'http_authenticate.php', 'license.txt', 'options_ide.php',
			'Page.phpclass', 'readme.txt', 'web_about_ide.php', 'data/example'
		];

		foreach ($sysfiles as $file) {
			$source = $sysdir . '/' . $file;
			$destination = $tempDir . '/' . $file;

			// Se till att undermappar finns (t.ex. data/)
			if (!is_dir(dirname($destination))) {
				mkdir(dirname($destination), 0777, true);
			}

			copy($source, $destination);
		}

		// Kopiera kataloger (rekursivt)
		$sysdirs = ['images', 'javascript', 'css'];
		foreach ($sysdirs as $dir) {
			$sourceDir = $sysdir . '/' . $dir;
			$targetDir = $tempDir . '/' . $dir;

			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
				RecursiveIteratorIterator::SELF_FIRST
			);

			foreach ($iterator as $item) {
				$destPath = $targetDir . '/' . $iterator->getSubPathName();
				if ($item->isDir()) {
					mkdir($destPath, 0777, true);
				} else {
					if (!is_dir(dirname($destPath))) {
						mkdir(dirname($destPath), 0777, true);
					}
					copy($item, $destPath);
				}
			}
		}

		// Skapa ZIP
		$zip = new ZipArchive();
		if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
			$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
				RecursiveIteratorIterator::LEAVES_ONLY
			);

			foreach ($files as $file) {
				$filePath = $file->getRealPath();
				$relativePath = substr($filePath, strlen($tempDir) + 1);
				$zip->addFile($filePath, $relativePath);
			}

			$zip->close();
		} else {
			$this->alert_message .= "ZipArchive: Kunde inte skapa zipfil.";
			return false;
		}

    // Rensa temporär mapp
    $this->delTree($tempDir);

    $this->success_message .= "Zip-fil skapad: idephp.zip";
    return true;
}

    function getRemoteFile($url)
    {
    // get the host name and url path
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'];
        if (isset($parsedUrl['path'])) {
            $path = $parsedUrl['path'];
        } else {
            // the url is pointing to the host like http://www.mysite.com
            $path = '/';
        }

        if (isset($parsedUrl['query'])) {
            $path .= '?' . $parsedUrl['query'];
        }

        if (isset($parsedUrl['port'])) {
            $port = $parsedUrl['port'];
        } else {
        // most sites use port 80
            $port = '80';
        }

        $timeout = 10;
        $response = '';

        // connect to the remote server
        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout );

        if( $fp ) {
            // send the necessary headers to get the file
            fputs($fp, "GET $path HTTP/1.0\r\n" .
                 "Host: $host\r\n" .
                 "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.0.3) Gecko/20060426 Firefox/1.5.0.3\r\n" .
                 "Accept: */*\r\n" .
                 "Accept-Language: en-us,en;q=0.5\r\n" .
                 "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n" .
                 "Keep-Alive: 300\r\n" .
                 "Connection: keep-alive\r\n" .
                 "Referer: http://$host\r\n\r\n");

            // retrieve the response from the remote server
            while ( $line = fread( $fp, 4096 ) ) {
                $response .= $line;
            }

            fclose( $fp );

            // strip the headers
            $pos      = strpos($response, "\r\n\r\n");
            $response = substr($response, $pos + 4);
        }

        // return the file content
        return $response;
    }

	function code_style()
	{
		$ret ='font-size:'.($this->Conf->editorfontsize+7).'px;';
		$ret.='line-height:'.($this->Conf->editorfontsize+7+$this->Conf->editorlinespace).'px;';
		switch ($this->Conf->editorfont) {
		case 1:
			$ret.="font-family: Monaco, 'Andale Mono', 'Lucida Console', Lucidatypewriter,'Courier New', Courier, Fixed, monospace  !important;";
			break;
		case 2:
			$ret.="font-family: 'Andale Mono', 'Lucida Console', Lucidatypewriter,'Courier New', Courier, Fixed, monospace  !important;";
			break;
		case 3:
			$ret.="font-family: 'Lucida Console', Lucidatypewriter,'Courier New', Courier, Fixed, monospace  !important;";
			break;
		case 4:
			$ret.="font-family: Lucidatypewriter,'Courier New', Courier, Fixed, monospace  !important;";
			break;
		case 5:
			$ret.="font-family:'Courier New', Courier, Fixed, monospace  !important;";
			break;
		case 6:
			$ret.="font-family:  Courier,Fixed, monospace  !important;";
			break;
		case 7:
			$ret.="font-family:Fixed, monospace  !important;";
			break;
		case 8:
			default:
			$ret.="font-family: monospace  !important;";
			break;
		}
		return $ret;
	}

	function temp_file()
	{
		$ext = "";
		if ($this->Conf->Syncmode=='temp') {
			$ext='.'.$this->get_extension($this->Conf->Current_file);
			if ($ext=='.') {
				$ext='.php';
			}
		} else if(substr($this->Conf->Syncmode,0,4)=='temp') {
			$ext=str_replace('temp','',$this->Conf->Syncmode);
		}
		return $this->Conf->Code_file.'_temp'.$ext;
	}

	function get_extension($filename)
	{
		$info = pathinfo($filename);
		return $info['extension'];
	}

	function make_backup($filepath)
	{
		if (!@copy($this->Conf->Current_file, $this->Conf->Backup_file)) {
			return false;
		} else {
			$this->Conf->Dirtyfile=0;
			return true;
		}
	}

	function delTree($dir)
	{
		$files = glob($dir . '*', GLOB_MARK );
		foreach ($files as $file ) {
			if (is_dir($file ) ) {
				this->delTree($file );
			} else {
				unlink($file );
			}
		}
		if (is_dir($dir)) {
			rmdir($dir );
		}
	}

	function is_url($url)
	{
		$UrlElements = parse_url($url);
		if ((empty($UrlElements)) or(!$UrlElements)) {
			return false;
		}
		if ((!isset($UrlElements['host'])) || (empty($UrlElements['host']))) {
			return false;
		}
		return true;
	}

	function dir_is_empty($dir)
	{
		//return(count(glob('$dir/*')) === 0) ? true : false;
		return (count(glob($dir . '/*')) === 0);
	}
	/*
	function shorten_reldir($reldir,$realpath='')
	{
		//optimization of relative a path
		$dirparts=explode('/',realpath($reldir));
		if (strlen($realpath)==0) {
			$realpath=dirname(__FILE__);
		}
		$filedirparts=explode('/',$realpath);
		for ($i=0; $i<count($dirparts); $i++) {
			if ($i >= count($filedirparts)) {
				break;
			}
			if ($dirparts[$i] != $filedirparts[$i]) {
				break;
			}
		}
		$matching=$i;
		$missing=count($filedirparts)-$i;
		$ret='.';
		for ($i=0; $i<$missing; $i++) {
			$ret.='/..';
		}
		for ($i=$matching; $i<count($dirparts); $i++) {
			$ret.='/'.$dirparts[$i];
		}
		return $ret;
	}
	*/
	function shorten_reldir($reldir, $base = __DIR__) {
		return str_replace($base, '.', realpath($reldir));
	}

	function undo_file($filepath)
	{
		if (!file_exists($this->Conf->Backup_file)) {
		    $this->save_file($filepath,true);
    	    $this->alert_message .= 'Backup file does not exist! Code was saved anyway.';
			return;
		}
		// undo
		if (!@copy($this->Conf->Backup_file, $filepath)) {
			$this->alert_message .= $this->Conf->Current_file.' is not writable';
		} else {
			$this->success_message .= $this->Conf->Current_file.' was restored from backup';
			$this->Conf->Dirtyfile=0;
		}
	}

	function save_file($filepath,$silent=false)
	{
		if (! strlen($filepath)) {
			if (!$silent) {
				$this->alert_message = 'Can\'t save file without a filename!!';
			}
		} else if (!is_writable($filepath)) {
			if (!$silent) {
				$this->alert_message = 'The file is read only. Change permissions.';
			}
		} else {
			$this->Edit->saveFile($filepath,$this->Conf->Right_trim);
			$this->Conf->Dirtyfile=0;
			if (!$silent) {
				$this->success_message .= "Current code was saved to file: {$filepath}";
			}
			$this->open_file($this->Conf->Current_file);
		}
	}

	function save_code_files()
	{
		$this->Edit->saveCode($this->Conf->Code_file);
		$this->Edit->saveFile($this->temp_file());
	}

	function open_file($filepath)
	{
		if (!file_exists($filepath)) {
			return;
		}
		$this->Conf->Current_file = $filepath;
		$this->Conf->IsBinary=$this->Edit->createFromFile($this->Conf->Current_file);
		$this->Conf->Encoding=$this->Edit->encoding;
        if ($this->Conf->IsBinary)
        {
            if (substr($this->Conf->Syncmode,0,4)=='temp')
            {
                $this->Conf->Syncmode='temp';
            }
        }
		$this->Conf->Dirtyfile='0';
		$_POST['code_file_name']=$filepath;
		$_POST['save_as_filename'] = $filepath;
		// make backup
		$this->make_backup($filepath);
        $this->save_code_files();
	}

	function toolbar_left()
	{
		$ret ="<div class='top_window_no_border' style='width:18%;'>";
		//$ret .= $this->Out->menu_button('  I D E . P H P  ','window.location = "'.$_SERVER[PHP_SELF].'";');
		$ret .= $this->Out->menu_button('  I D E . P H P  ', 'window.location = "'.$_SERVER['PHP_SELF'].'";');
		$ret .= $this->Out->menu_button($_SERVER['PHP_AUTH_USER'],'clearAuthenticationCache("'.$_SERVER['PHP_SELF'].'");');
		$ret .= "</div>";
		return $ret;
	}

	function toolbar_middle()
	{
		$ret ="<div class='top_window_no_border' style='left:18%';width:41%>";
		$ret .= "<div class='inside_menu_text'>\n";
		//$ret .= "<FONT COLOR='{$this->Conf->Alert_message_color}'>{$this->alert_message}</FONT>\n";
		//$ret .= "<FONT COLOR='{$this->Conf->Success_message_color}'>{$this->success_message}</FONT>\n";
		if (!empty($this->alert_message)) {
			$ret .= "<div style='line-height:1; display:inline-flex; align-items:center; gap:4px;'>
						<img src='./images/warning.png' style='height:1em; vertical-align:middle;'>
						<span style='color: {$this->Conf->Alert_message_color};'>{$this->alert_message}</span>
					 </div>\n";
		}

		if (!empty($this->success_message)) {
			$ret .= "<div style='line-height:1; display:inline-flex; align-items:center; gap:4px;'>
						<img src='./images/success.png' style='height:1em; vertical-align:middle;'>
						<span style='color: {$this->Conf->Success_message_color};'>{$this->success_message}</span>
					 </div>\n";
		}
		$ret .= "</div>";
		$ret .= "</div>";
		return $ret;
	}

	function toolbar_right()
	{
		$ret ="<div class='top_window_no_border' style='left:59%;width:41%;'>";
		//$ret .= $this->Out->menu_button('Log out','clearAuthenticationCache("'.$_SERVER['PHP_SELF'].'");',false,false,true);
		$ret .= $this->Out->menu_button('About','showFrame("./about_ide.php","","About","Close",false);return false;',false,false,true);
		if (USER_AUTHENTICATED==1) {
			$ret .= $this->Out->menu_button('Manage users','showFrame("./admin_ide.php","","Manage users","Close",false);return false;',false,false,true);
		}
		$ret .= $this->Out->menu_button('Options','showFrame("./options_ide.php","","Options","Close",true);return false;',false,false,true);
		$ret .= $this->Out->menu_button('Layout','main_form.layoutstyle.value=(1-main_form.layoutstyle.value);main_submit("layoutstyle");',false,(!$this->Conf->LayoutStyle),true);
		$ret .= "</div>";
		return $ret;
	}

	function file_menu()
	{
		$ret ="<div class='top_window_z1000'>";
		//$ret .= $this->Out->menu_top('File');
		$newfilename=$this->Conf->Dir_path.'/new.php';
		$i=0;
		while (file_exists($newfilename))
		{
		    $i++;
            $newfilename=$this->Conf->Dir_path.'/new_'.$i.'.php';
        }
		$menu = $this->Out->menu_item('Download file...','main_form.save_as_filename.value="'.$this->Conf->Current_file.'";main_submit("set_download");');
		$menu .=$this->Out->menu_item('Upload file...','upload_file();');
		$menu .=$this->Out->menu_item('Get file from URL...','get_url_file();');
		//$menu .=$this->Out->menu_item('Give permissions','chmod_file("'.$this->Conf->Current_file.'","0666");',!file_exists($this->Conf->Current_file));
		$menu .=$this->Out->menu_item('File to ftp...','ftp_file("'.$this->Conf->ftp_path.'")');
        $menu .= "<hr/>";
		$menu .=$this->Out->menu_item('New file...','new_file("'.$newfilename.'");');
		$menu .=$this->Out->menu_item('Delete file','ae_confirm(callback_submit,"Delete '.$this->Conf->Current_file.' ? <b>This can NOT be undone!!!</b>","set_kill")',!file_exists($this->Conf->Current_file));
		$menu .="<hr/>";
		$newfilename=$this->Conf->Dir_path.'/newdir';
		$i=0;
		while (file_exists($newfilename))
		{
		    $i++;
            $newfilename=$this->Conf->Dir_path.'/newdir_'.$i;
        }
		$menu .=$this->Out->menu_item('New directory...','new_directory("'.$newfilename.'");');
		$menu .=$this->Out->menu_item('Delete directory','ae_confirm(callback_submit,"Delete '.$this->Conf->Dir_path.' ?","set_kill_directory")',!$this->dir_is_empty($this->Conf->Dir_path));
		$menu .="<hr/>";
		$zippath=realpath($this->Conf->Dir_path.'/..');
		$zipname=$zippath.'/'.basename($this->Conf->Dir_path).'.zip';
		$menu .=$this->Out->menu_item('Zip directory to parent','ae_confirm(callback_submit,"Create '.$zipname.' ?","zip_folder");');
		$menu .=$this->Out->menu_item('Unzip here','ae_confirm(callback_submit,"Files might be overwritten, continue ?","set_unzip");',$this->get_extension($this->Conf->Current_file)!='zip');
		if (USER_AUTHENTICATED==1) {
			$menu .="<hr/>";
			$menu .=$this->Out->menu_item('Zip system','ae_confirm(callback_submit,"Create ./idephp.zip ?","zip_system");');
			$menu .=$this->Out->menu_item('Download system','ae_confirm(callback_submit,"Create ./idephp.zip ?","download_system");');
			$menu .=$this->Out->menu_item('System to ftp...','ftp_system("'.$this->Conf->ftp_system_path.'")');
		}
		$menu .="<hr/>";
		$menu .=$this->Out->menu_item('Copy file','copy_file("'.$this->Conf->Current_file.'");',!file_exists($this->Conf->Current_file));
		$menu .=$this->Out->menu_item('Paste file','main_submit("set_paste");',!file_exists($this->Conf->Copy_file));
		$menu .=$this->Out->menu_item('Rename file...','rename_file("'.basename($this->Conf->Current_file).'");',!file_exists($this->Conf->Current_file));
		$ret .= $this->Out->menu_create('File',$menu);
		//$menu .= $this->Out->menu_bottom();
		//$ret .= $this->Out->menu_top($this->Conf->Dir_path);
		$menu='';
		for ($i=$this->recentdirs->count()-1; $i>=0; $i--) {
			$relpath=$this->shorten_reldir(realpath($this->recentdirs->item($i)),realpath($this->Conf->Dir_path));
			$menu .=$this->Out->menu_item($this->recentdirs->item($i),'submit_dir("'.$relpath.'")',!file_exists($this->recentdirs->item($i)));
			if ($i==$this->recentdirs->count()-1) {
				if ($this->recentdirs->count()>1)
                {
                    $menu.="<hr/>";
                }
			}
		}
		//$ret .= $this->Out->menu_bottom();
		$ret .= $this->Out->menu_create($this->Conf->Dir_path,$menu);
		return $ret;
	}

	function code_menu()
	{
		$ret ="<div class='top_window_z1000'>";
		$menu = "";
		if (!$this->Conf->UseCodeMirror) {
			$menu .= $this->Out->menu_item('Highlight','main_form.fancy.value=(1-main_form.fancy.value);main_submit("fancy");',($this->Conf->IsBinary),($this->Conf->Fancy));
			$menu .="<hr/>";
		}
		$menu .=$this->Out->menu_item('Search...','search_textarea(true);',($this->Conf->Fancy or $this->Conf->IsBinary));
		$menu .=$this->Out->menu_item('Replace...','replace_textarea();',($this->Conf->Fancy or $this->Conf->IsBinary));
		$menu .=$this->Out->menu_item('Beautify','main_submit("beautify");',($this->Conf->Fancy or $this->Conf->IsBinary));
		$menu .="<hr/>";
		$menu .=$this->Out->menu_item('Open tpl','ae_confirm(callback_submit,"Replace current code with new template?","show_template")',($this->Conf->IsBinary));
		$menu .=$this->Out->menu_item('Save tpl','ae_confirm(callback_submit,"Replace current template?","save_as_template")',(!file_exists($this->Conf->Current_file or $this->Conf->IsBinary)));
		$menu .="<hr/>";
		$menu .=$this->Out->menu_item('Revert to saved','if (checkDirty()){ae_confirm(callback_submit,"Discard changes?","set_undo");}else{main_submit("set_undo");}');
		$menu .=$this->Out->menu_item('Save','main_submit("save");',(!file_exists($this->Conf->Current_file)));
		$menu .=$this->Out->menu_item('Save as...','save_as("'.$this->Conf->Current_file.'")');
		$menu .=$this->Out->menu_item('Encoding...','showFrame("./encoding_ide.php","","Encoding","Close",true);return false;');
		$ret .= $this->Out->menu_create('Code',$menu);
		$menu='';
		for ($i=$this->recentfiles->count()-1; $i>=0; $i--) {
			$menu .=$this->Out->menu_item($this->recentfiles->item($i),'submit_file("'.$this->recentfiles->item($i).'")',!file_exists($this->recentfiles->item($i)));
			if ($i==$this->recentfiles->count()-1) {
				if ($this->recentfiles->count()>1)
                {
                    $menu.="<hr/>";
                }
			}
		}
		$ret .= $this->Out->menu_create($this->Conf->Current_file,$menu);
		$ret .= "<div class='inside_menu'>";
		$ret .= "<span id='dirty_p'>\n";
		$ret .=($this->Conf->Dirtyfile>0) ? '<a href="#" class="imgbutton" onClick="main_submit(\'save\');" title="Save"> <img src="images/savel.png"/> </a>':'';
		$ret .="</span>\n";
		$ret .= "</div>\n";
		$ret .="</div>\n";
		return $ret;
	}

	function eval_menu()
	{
		global $menu_id;
		$ret ="<div class='top_window_z1000'>";
		//$ret .= $this->Out->menu_button('- RUN -','main_form.phpnet.value=0;main_submit("eval");',(strlen($this->Conf->Eval_path)==0));
		$ret .= "<div class='inside_menu'>";
		$ret .= "<a href='#' class='imgbutton' onClick='main_form.phpnet.value=0;main_submit(\"eval\");' title='Run'>  <img src='images/play.png'/>  </a>";
		$ret .= "</div>";
		//$ret.=$this->Out->menu_top('Evaluate');
		$menu = $this->Out->menu_item('Sync','main_form.phpnet.value=0;if ("'.$this->Conf->Syncmode.'"=="sync"){main_form.syncmode.value="";}else{main_form.syncmode.value="sync";}main_submit("eval_sync");',!file_exists($this->Conf->Current_file),$this->Conf->Syncmode=='sync');
		$menu.="<hr/>";
		$menu .=$this->Out->menu_item('Temporary (auto ext)','main_form.phpnet.value=0;if ("'.$this->Conf->Syncmode.'"=="temp"){main_form.syncmode.value="";}else{main_form.syncmode.value="temp";}main_submit("eval_sync")',!file_exists($this->temp_file()),$this->Conf->Syncmode=='temp');
		for ($i=0; $i<count($this->Conf->Eval_suffix_list); $i++) {
			$menu .=$this->Out->menu_item('Temporary'.$this->Conf->Eval_suffix_list[$i],'main_form.phpnet.value=0;if ("'.$this->Conf->Syncmode.'"=="temp'.$this->Conf->Eval_suffix_list[$i].'"){main_form.syncmode.value="";}else{main_form.syncmode.value="temp'.$this->Conf->Eval_suffix_list[$i].'";}main_submit("eval_sync");',(!file_exists($this->temp_file()) or ($this->Conf->IsBinary)),$this->Conf->Syncmode=='temp'.$this->Conf->Eval_suffix_list[$i]);
		}
		$menu.="<hr/>";
		$menu .=$this->Out->menu_item('PHP.net','main_form.phpnet.value=(1-main_form.phpnet.value);main_submit("phpnet");',!$this->is_url($this->Conf->Phpneturl),($this->Conf->Phpnet));
		$ret .= $this->Out->menu_create('Evaluate',$menu);
		//$ret.=$this->Out->menu_bottom();
		$this->recentevals->append($this->current_eval());
		//$ret.=$this->Out->menu_top('');
		$menu='';
		for ($i=$this->recentevals->count()-1; $i>=0; $i--) {
			$menu .=$this->Out->menu_item($this->recentevals->item($i),'main_form.syncmode.value="";main_form.phpnet.value=0;main_form.eval_path.value="'.$this->recentevals->item($i).'";main_submit("eval_change");',(!file_exists($this->recentevals->item($i))) && (!$this->is_url($this->recentevals->item($i))));
			if ($i==$this->recentevals->count()-1) {
				if ($this->recentevals->count()>1)
                {
                    $menu.="<hr/>";
                }
			}
		}
		$ret .= $this->Out->menu_create('',$menu);
		//$ret.=$this->Out->menu_bottom();
		$ret .= "<div class='inside_menu' style='padding-top:3px;'>";
		$ret .= "<input name='eval_path' class='menu_textbox' id='eval_path' type='text' size='20' value='".$this->current_eval()."' onKeyDown='if (checkEnter(event)){return false;};' onMouseOver='showHideLayer(show=true, sub_id=\"sub_$menu_id\")' onMouseOut='showHideLayer(show=false)'/>\n";
		$ret .= "<input type='submit' class='hiddenbutton' value='Change eval path' id='submit_eval' onClick='main_form.syncmode.value=\"\";main_form.phpnet.value=0;main_submit(\"eval_change\");'/>\n";
		$ret .="</div>";
		//$ret .= "<a href='#' class='btn' onClick='eval_history(-1);'>Back</a>\n";
		//$ret .= "<a href='#' class='btn' onClick='eval_history(1);'>Fwd</a>\n";
		$ret .="</div>";
		return $ret;
	}

	function file_window($borderstyle)
	{

		require 'filetable/filetable.php';
	    $filetable = new FileTable($this->Conf->Dir_path,$this->Conf->Current_file,$this->Conf->Dir_sortorder,$this->Conf->Allow_browse_below_root);
		$ret ="<div class='fixed_window'>\n";
		$ret .="<div class='scroll_window' style='$borderstyle'>\n";
		$ret .= $filetable->file_table();
		$ret .= "<script type=\"text/javascript\">
			document.addEventListener(\"DOMContentLoaded\", function () {
				if (typeof FileTableEvents !== 'undefined') {
					FileTableEvents.onFileClick = function (path) {
						//alert(path);
						submit_file(path);
					};
					FileTableEvents.onFolderClick = function (path) {
						//alert(path);
						submit_dir(path);
					};
					FileTableEvents.onChangeSortOrder = function (sortorder) {
						//alert(sortorder);
						submit_sort(sortorder);
					}
					FileTableEvents.onPermissionsClick = function (file,value)
					{
						//alert(file + value);
						submit_chmod(file,value);
					}
				}
			});
			</script>";
		$ret .="</div>\n";
		$ret .="</div>\n";
		return $ret;
	}

	function code_window($borderstyle)
	{
		$ret ="<div class='fixed_window'>\n";
		if ($this->Conf->UseCodeMirror) {
			$theme = $this->Conf->CodeMirrorTheme ?: 'default';
			if ($theme !== 'default') {
				$ret .= "<link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/codemirror@5.65.13/theme/{$theme}.css\">\n";
			}			
			$ret .= "
				<!-- CSS och JS -->
				<link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/codemirror@5.65.13/lib/codemirror.css\">
				<script src=\"https://cdn.jsdelivr.net/npm/codemirror@5.65.13/lib/codemirror.js\"></script>

				<!-- Språkmoduler -->
				<script src=\"https://cdn.jsdelivr.net/npm/codemirror@5.65.13/mode/javascript/javascript.js\"></script>
				<script src=\"https://cdn.jsdelivr.net/npm/codemirror@5.65.13/mode/php/php.js\"></script>
				<script src=\"https://cdn.jsdelivr.net/npm/codemirror@5.65.13/mode/htmlmixed/htmlmixed.js\"></script>
				<script src=\"https://cdn.jsdelivr.net/npm/codemirror@5.65.13/mode/xml/xml.js\"></script>
				<script src=\"https://cdn.jsdelivr.net/npm/codemirror@5.65.13/mode/clike/clike.js\"></script>
				<script src=\"https://cdn.jsdelivr.net/npm/codemirror@5.65.13/mode/css/css.js\"></script>
				<script>
				let editor = null;
				document.addEventListener(\"DOMContentLoaded\", function() {
					if (typeof(CodeMirror) !== 'undefined') {
						editor = CodeMirror.fromTextArea(document.getElementById('code'), {
							lineNumbers: true,
							//mode: \"text/html\", // Byt till t.ex. \"javascript\" eller \"php\" beroende på filtyp
							mode: document.getElementById('code').dataset.mode,
							theme: \"$theme\", // <--- här sätts temat
							indentUnit: 4,
							tabSize: 4,
							extraKeys: {
								\"Ctrl-S\": function(cm) {
									// Din sparlogik här
									console.log(\"Ctrl+S pressed\");
									main_submit('save'); // Om du har en sådan funktion
								},
								\"Cmd-S\": function(cm) {
									// För Mac
									console.log(\"Cmd+S pressed\");
									main_submit('save');
								}
							}
						});
						editor.on(\"change\", () => checkDirtyCodeMirror());
						editor.on(\"cursorActivity\", () => checkDirtyCodeMirror());
						editor.on(\"focus\", () => checkDirtyCodeMirror());
						editor.on(\"refresh\", () => checkDirtyCodeMirror());
						checkDirtyCodeMirror();
					}
				});
				</script>";
			$ret .= "<style>
				.CodeMirror, .CodeMirror pre {
					{$this->code_style()}
				}
				</style>";
		    $ret .="<div class='scroll_window_no' style='$borderstyle'>\n";
			$ret.='<div class="leftwrapperinfo" style="border-left:0;'.$this->code_style().'">';
			$ret .= '<textarea class="absolute" name="CodeMirrorTextArea" id="code" data-mode="'.$this->Edit->detectCodeMirrorMode($this->Conf->Current_file).'">'.$this->Edit->getTextareaCode().'</textarea>';
			$ret .= "</div>";
			$ret .= "<div id='infobarborder'></div>";
			$ret .= "<div  onselectstart='return false' unselectable = 'on' id='infobar'></div>";
			$ret .= "<div  onselectstart='return false' unselectable = 'on' id='infobarright'>";
			$ret .= "<a href='#' title='Encoding' onclick='showFrame(\"./encoding_ide.php\",\"\",\"Encoding\",\"Close\",true);return false;'>".$this->Conf->Encoding.'</a>  '.date('Y-m-d H:i:s',filemtime($this->Conf->Current_file))."<a href='#' title='Revert to saved' onClick='if (checkDirty()){ae_confirm(callback_submit,\"Discard changes?\",\"set_undo\");}else{main_submit(\"set_undo\");}'> <img src='images/lock.gif'> </a>".date('Y-m-d H:i:s',filemtime($this->Conf->Backup_file)).' ';
			$ret .= "</div>";
			$ret .= '</div>';

		}
        else if (!$this->Conf->IsBinary)
        {
    		$ret .="<div class='scroll_window_no' style='$borderstyle'>\n";
	       	if ($this->Conf->Fancy == 0) {
	   	       	$ret.='<div class="leftwrapperinfo" style="'.$this->code_style().'">';
    			$ret .='<textarea class="absolute" style="'.$this->code_style().'" spellcheck="false" WRAP="OFF" ID="code" NAME="code">'.$this->Edit->getTextareaCode().'</textarea>\n';
	       		$ret.='</div>';
			}else{
			    $ret.='<div id="code" name="code" class="leftwrapper" style="background-color:#f3f3f3;'.$this->code_style().'">';
		        $ret.='<div class="fancywrapper" style="background-color:#f3f3f3;'.$this->code_style().'">';
		        $ret.=str_replace('<code>','<code class="codeprint" style="'.$this->code_style().'">',$this->Edit->getHighlightCode());
		        $ret.='</div>';
	       		$ret.='</div>';
    		}
	       	$leftheaderclass=($this->Conf->Fancy == 0) ? 'leftheaderinfo':'leftheader';
    		$ret.='<div class="'.$leftheaderclass.'" style="'.$this->code_style().'">';
	       	$ret.='<div id="code_numbers" name="code_numbers" class="codeprint" unselectable = "on" onselectstart="return false" style="'.$this->code_style().'">';
    		//$ret.= '<code class="codeprint" style="'.$this->code_style().'">'. implode(range(1, $this->Edit->getlen()), '<br />'). '</code>';
    		$ret.= '<code class="codeprint" style="'.$this->code_style().'">'. implode('<br />', range(1, $this->Edit->getlen())). '</code>';
	       	$ret.= '</div>';
    		$ret.= '</div>';
	       	if ($this->Conf->Fancy == 0) {
		      	$ret .= "<div id='infobarborder'></div>";
       			$ret .= "<div  onselectstart='return false' unselectable = 'on' id='infobar'></div>";
	       		$ret .= "<div  onselectstart='return false' unselectable = 'on' id='infobarright'>";
		      	$ret .= "<a href='#' title='Encoding' onclick='showFrame(\"./encoding_ide.php\",\"\",\"Encoding\",\"Close\",true);return false;'>".$this->Conf->Encoding.'</a>  '.date('Y-m-d H:i:s',filemtime($this->Conf->Current_file))."<a href='#' title='Revert to saved' onClick='if (checkDirty()){ae_confirm(callback_submit,\"Discard changes?\",\"set_undo\");}else{main_submit(\"set_undo\");}'> <img src='images/lock.gif'> </a>".date('Y-m-d H:i:s',filemtime($this->Conf->Backup_file)).' ';
    			$ret .= "</div>";
	       	}
    		$ret .="</div>\n";
        }
        else
        {
    		$ret .="<div class='scroll_window_no' style='$borderstyle'>\n";
            $ret.='<div class="leftwrapper" style="border-left:0px solid #e5e5e5;'.$this->code_style().'">';

   			$ret .='<textarea class="absolute" style="'.$this->code_style().'" spellcheck="false" WRAP="OFF" ID="code" NAME="code">'.$this->Edit->getCode().'</textarea>\n';

	       	$ret.='</div>';

    		$ret.='<div class="leftheader" style="'.$this->code_style().'">';
	       	$ret.='<div id="code_numbers" name="code_numbers" class="codeprint" unselectable = "on" onselectstart="return false" style="width:157px;'.$this->code_style().'">';
    		$ret.= '<code class="codeprint" style="position:absolute;left:0px;top:0px;width:32px;text-align:right;'.$this->code_style().'">';

            for($i = 0; $i <= $this->Edit->getlen(); $i += 16)
            {
                $ret .= dechex($i).'<br/>';
            }

            $ret.= '</code>';
		    $ret.='<div class="fancywrapper" style="width:120px;position:absolute;left:34px;top:0px;border-right:1px dotted #aaaaaa;background-color:#e5e5e5;color:#202020;text-align:left;'.$this->code_style().'">';
            $ret.=str_replace(chr(0x0d),'<br/>',$this->Edit->getAscii());
	       	$ret.='</div>';
	       	$ret.= '</div>';
    		$ret.= '</div>';

	       	$ret.='</div>';
        }
		$ret .="</div>\n";
		return $ret;
	}

	function current_eval()
	{
		$evalpath=$this->Conf->Eval_path;
		if (substr($this->Conf->Syncmode,0,4)=='temp') {
			$evalpath=$this->temp_file();
		}
		if ($this->Conf->Syncmode=='sync') {
			$evalpath=$this->Conf->Current_file;
		}
		if ($this->Conf->Phpnet==1) {
			$evalpath=$this->Conf->Phpneturl;
		}
		return $evalpath;
	}

	function eval_window($borderstyle)
	{
		$src=$this->current_eval();
		if ($this->get_extension($src)=='zip')
		{
			$src='';
		}
		$ret ="<div class='fixed_window'>\n";
		$ret .="<iframe id='evaluationwindow' name='evaluationwindow' frameborder='0' width='100%' height='100%' class='scroll_window' style='$borderstyle' src='".$src."'></iframe></div>\n";
		return $ret;
	}
/*
	function main_page()
	{
		global $_POST;
		$ret = "<FORM NAME='main_form' ID='main_form' enctype='multipart/form-data' METHOD='POST' ACTION='{$_SERVER['PHP_SELF']}'>\n";
		$ret .= "<INPUT TYPE='hidden' NAME='action' ID='action' VALUE=''>\n";
		//$ret .= "<INPUT TYPE='hidden' NAME='prev_submit' VALUE='".md5(time()+session_id)."'>\n";
		$ret .= "<INPUT TYPE='hidden' NAME='prev_submit' VALUE='" . md5(time() . session_id()) . "'>\n";
		$ret .= "<INPUT TYPE='hidden' id='change_counter' NAME='change_counter' VALUE='{$this->Conf->Dirtyfile}'>\n";
		$ret .= "<INPUT TYPE='hidden' NAME='Current_filename' id='Current_filename' VALUE='{$this->Conf->Current_file}'>\n";
		$ret .= "<INPUT TYPE='hidden' id='save_as_filename' NAME='save_as_filename' VALUE='{$_POST['save_as_filename']}'>\n";
		$ret .= "<INPUT TYPE='hidden' id='fancy' NAME='fancy' VALUE='{$this->Conf->Fancy}'>";
		$ret .= "<INPUT TYPE='hidden' id='phpnet' NAME='phpnet' VALUE='{$this->Conf->Phpnet}'>\n";
		$ret .= "<INPUT TYPE='hidden' id='syncmode' NAME='syncmode' VALUE='{$this->Conf->Syncmode}'>\n";
		$ret .= "<INPUT TYPE='hidden' id='layoutstyle' NAME='layoutstyle' VALUE='{$this->Conf->LayoutStyle}'>\n";
		$ret .= "<input name='current_directory' id='current_directory' type='hidden' value='' />\n";
		$ret .= "<input name='sortorder' id='sortorder' type='hidden' value='' />\n";
		$ret .= "<input name='some_file_name' id='some_file_name' type='hidden' value='' />\n";
		$ret .= "<input name='chmod_value' id='chmod_value' type='hidden' value='' />\n";
		$this->Conf->tdleftstyle=isset($_POST['td_left_style']) ? $_POST['td_left_style']:$this->Conf->tdleftstyle;
		$ret .= "<input type='hidden' name='td_left_style' id='td_left_style' value='{$this->Conf->tdleftstyle}'/>\n";//width 18%
		$this->Conf->tdmiddlestyle=isset($_POST['td_middle_style']) ? $_POST['td_middle_style']:$this->Conf->tdmiddlestyle;
		$ret .= "<input type='hidden' name='td_middle_style' id='td_middle_style' value='{$this->Conf->tdmiddlestyle}'/>\n"; //width 41%
		$this->Conf->tdrightstyle=isset($_POST['td_right_style']) ? $_POST['td_right_style']:$this->Conf->tdrightstyle;
		$ret .= "<input type='hidden' name='td_right_style' id='td_right_style' value='{$this->Conf->tdrightstyle}'/>\n"; //width 41%
		$this->Conf->tdtopleftstyle=isset($_POST['td_top_left_style']) ? $_POST['td_top_left_style']:$this->Conf->tdtopleftstyle;
		$ret .= "<input type='hidden' name='td_top_left_style' id='td_top_left_style' value='{$this->Conf->tdtopleftstyle}'/>\n";//width:18%;height:50%
		$this->Conf->tdtoprightstyle=isset($_POST['td_top_right_style']) ? $_POST['td_top_right_style']:$this->Conf->tdtoprightstyle;
		$ret .= "<input type='hidden' name='td_top_right_style' id='td_top_right_style' value='{$this->Conf->tdtoprightstyle}'/>\n"; //width:82%;height:50%
		$this->Conf->tdbottomstyle=isset($_POST['td_bottom_style']) ? $_POST['td_bottom_style']:$this->Conf->tdbottomstyle;
		$ret .= "<input type='hidden' name='td_bottom_style' id='td_bottom_style' value='{$this->Conf->tdbottomstyle}'/>\n"; //width:100%;height:50%
		$ret .= "<div class='wrapper' id='wrapper_div'>\n";
		$ret .= "<div class='relative'>";
		if ($this->Conf->LayoutStyle==1) {
			$ret .= "<table class='insidediv'><tr>\n";
			$ret .= "<td style='{$this->Conf->tdleftstyle}' class='insidedivpad' id='td_left'>\n";
			$ret .= "<div class='relative'>";
			$ret .= "<div class='insidewrapper'>";
			$ret .= $this->file_window('border-left:0px;border-bottom:0px;');
			$ret .= "</div>";
			$ret .= "<div class='header'>\n";
			$ret .= $this->file_menu();
			$ret .= "</div>";
			$ret.="</div>";
			$ret .= "</td>\n";
			$ret .= "<td style='{$this->Conf->tdmiddlestyle}' class='insidedivpad' id='td_middle'>\n";
			$ret .= "<div class='vert_container'><div id='splitter1' class='vert_split' onmousedown='dragStart(event,this.id,\"td_left\")'></div></div>";
			$ret .= "<div class='relative'>";
			$ret .= "<div class='insidewrapper'>";
			$ret .= $this->code_window('border-left:0px;border-bottom:0px;');
			$ret .= "</div>";
			$ret .= "<div class='header'>\n";
			$ret .= $this->code_menu();
			$ret .= "</div>";
			$ret.="</div>";
			$ret .= "</td>\n";
			$ret .= "<td style='{$this->Conf->tdrightstyle}' class='insidedivpad' id='td_right'>\n";
			$ret .= "<div class='vert_container'><div id='splitter2' class='vert_split' onmousedown='dragStart(event,this.id,\"td_middle\")'></div></div>";
			$ret .= "<div class='relative'>";
			$ret .= "<div class='insidewrapper'>";
			$ret .= $this->eval_window('border-left:0px;border-bottom:0px;border-right:0px;');
			$ret .= "</div>";
			$ret .= "<div class='header'>\n";
			$ret .= $this->eval_menu();
			$ret .= "</div>";
			$ret.="</div>";
			$ret .= "</td></tr></table>";
		} else {
			$ret .= "<table class='insidediv'><tr>\n";
			$ret .= "<td class='insidedivpad' style='{$this->Conf->tdtopleftstyle}' id='td_top_left'>\n";
			$ret .= "<div class='relative'>";
			$ret .= "<div class='insidewrapper'>";
			$ret .= $this->file_window('border-left:0px;');
			$ret .= "</div>";
			$ret .= "<div class='header'>\n";
			$ret .= $this->file_menu();
			$ret .= "</div>";
			$ret.="</div>";
			$ret .= "</td>\n";
			$ret .= "<td class='insidedivpad' style='{$this->Conf->tdtoprightstyle}' id='td_top_right'>\n";
			$ret .= "<div class='vert_container'><div id='splitter1' class='vert_split' onmousedown='dragStart(event,this.id,\"td_top_left\")'></div></div>";
			$ret .= "<div class='relative'>";
			$ret .= "<div class='insidewrapper'>";
			$ret .= $this->code_window('border-left:0px;border-right:0px;');
			$ret .= "</div>";
			$ret .= "<div class='header'>\n";
			$ret .= $this->code_menu();
			$ret .= "</div>";
			$ret.="</div>";
			$ret .= "</td>\n";
			$ret .= "</tr><tr>\n";
			$ret .= "<td colspan='2' class='insidedivpad' style='{$this->Conf->tdbottomstyle}' id='td_bottom'>\n";
			$ret .= "<div class='horiz_container'><div id='splitter2' class='horiz_split' onmousedown='dragStart(event,this.id,\"td_top_left%td_top_right\")'></div></div>";
			$ret .= "<div class='relative'>";
			$ret .= "<div class='insidewrapper'>";
			$ret .= $this->eval_window('border-left:0px;border-bottom:0px;border-right:0px;');
			$ret .= "</div>";
			$ret .= "<div class='header'>\n";
			$ret .= $this->eval_menu();
			$ret .= "</div>";
			$ret.="</div>";
			$ret .= "</td></tr></table>";
		}
		$ret.="</div>\n";
		$ret.="</div>\n";
		$ret .= "<div class='globalheader'>\n";
		$ret.=$this->toolbar_left();
		$ret.=$this->toolbar_middle();
		$ret.=$this->toolbar_right();
		$ret .= "</div>\n";
		$ret .= "</FORM>\n";
		$this->Conf->recentfiles=$this->recentfiles->save();
		$this->Conf->recentdirs=$this->recentdirs->save();
		$this->Conf->recentevals=$this->recentevals->save();
		if (isset($_POST['UIdata'])) {
			$this->Conf->UIdata=$_POST['UIdata'];
		}
		$this->Conf->save_to_file();
		$ret .= "<SCRIPT LANGUAGE='JavaScript'>\n";
		$ret.="syncTextarea('{$this->Conf->UIdata}');";
		$ret .= "</SCRIPT>\n";
		if ($_POST['overwrite_ok']) {
			$ret .= "<SCRIPT LANGUAGE='JavaScript'>\n";
			$ret .= "ae_confirm(callback_submit,'The file {$_POST['save_as_filename']} already exists, replace?','{$_POST['action']}_replace');\n";
			$ret .= "</SCRIPT>\n";
		}
		if ($_POST['action']=='download_system')
		{
			$ret .= "<SCRIPT LANGUAGE='JavaScript'>\n";
			$ret .= "window.onload=startdownload;";
			//$ret .= "window.onload=function(){";
			//$ret .= "document.getElementById('save_as_filename').value='./systemzip/idephp.zip';";
			//$ret .= "document.getElementById('action').value='set_download';";
			//$ret .= "document.getElementById('main_form').submit();}";
			$ret .= "</SCRIPT>\n";
			echo $ret;
		}
		return($ret);
	}
*/
	function main_page()
	{
		global $_POST;

		$h = fn($str) => htmlspecialchars($str ?? '', ENT_QUOTES);

		$ret = <<<HTML
	<form name="main_form" id="main_form" enctype="multipart/form-data" method="POST" action="{$h($_SERVER['PHP_SELF'])}">
	<input type="hidden" name="action" id="action" value="">
	<input type="hidden" name="prev_submit" value="{$h(md5(time() . session_id()))}">
	<input type="hidden" id="change_counter" name="change_counter" value="{$h($this->Conf->Dirtyfile)}">
	<input type="hidden" name="Current_filename" id="Current_filename" value="{$h($this->Conf->Current_file)}">
	<input type="hidden" id="save_as_filename" name="save_as_filename" value="{$h($_POST['save_as_filename'] ?? '')}">
	<input type="hidden" id="fancy" name="fancy" value="{$h($this->Conf->Fancy)}">
	<input type="hidden" id="use_code_mirror" name="use_code_mirror" value="{$h($this->Conf->UseCodeMirror)}">
	<input type="hidden" id="phpnet" name="phpnet" value="{$h($this->Conf->Phpnet)}">
	<input type="hidden" id="syncmode" name="syncmode" value="{$h($this->Conf->Syncmode)}">
	<input type="hidden" id="layoutstyle" name="layoutstyle" value="{$h($this->Conf->LayoutStyle)}">
	<input name="current_directory" id="current_directory" type="hidden" value="">
	<input name="sortorder" id="sortorder" type="hidden" value="">
	<input name="some_file_name" id="some_file_name" type="hidden" value="">
	<input name="chmod_value" id="chmod_value" type="hidden" value="">
	HTML;

		// Layout style values, using fallback from POST
		foreach ([
			'td_left_style' => 'tdleftstyle',
			'td_middle_style' => 'tdmiddlestyle',
			'td_right_style' => 'tdrightstyle',
			'td_top_left_style' => 'tdtopleftstyle',
			'td_top_right_style' => 'tdtoprightstyle',
			'td_bottom_style' => 'tdbottomstyle'
		] as $postKey => $confProp) {
			$this->Conf->$confProp = $_POST[$postKey] ?? $this->Conf->$confProp;
			$ret .= "<input type='hidden' name='{$h($postKey)}' id='{$h($postKey)}' value='{$h($this->Conf->$confProp)}'>\n";
		}

		// Start wrapper div
		$ret .= "<div class='wrapper' id='wrapper_div'><div class='relative'>";

		if ($this->Conf->LayoutStyle == 1) {
			// 3-column layout
			$ret .= <<<HTML
	<table class='insidediv'><tr>
	<td style="{$h($this->Conf->tdleftstyle)}" class="insidedivpad" id="td_left">
	<div class="relative"><div class="insidewrapper">
	{$this->file_window('border-left:0px;border-bottom:0px;')}
	</div><div class="header">{$this->file_menu()}</div></div></td>
	<td style="{$h($this->Conf->tdmiddlestyle)}" class="insidedivpad" id="td_middle">
	<div class="vert_container"><div id="splitter1" class="vert_split" onmousedown="dragStart(event,this.id,'td_left')"></div></div>
	<div class="relative"><div class="insidewrapper">
	{$this->code_window('border-left:0px;border-bottom:0px;')}
	</div><div class="header">{$this->code_menu()}</div></div></td>
	<td style="{$h($this->Conf->tdrightstyle)}" class="insidedivpad" id="td_right">
	<div class="vert_container"><div id="splitter2" class="vert_split" onmousedown="dragStart(event,this.id,'td_middle')"></div></div>
	<div class="relative"><div class="insidewrapper">
	{$this->eval_window('border-left:0px;border-bottom:0px;border-right:0px;')}
	</div><div class="header">{$this->eval_menu()}</div></div></td>
	</tr></table>
	HTML;
		} else {
			// Split top/bottom layout
			$ret .= <<<HTML
	<table class='insidediv'>
	<tr>
	<td class='insidedivpad' style="{$h($this->Conf->tdtopleftstyle)}" id="td_top_left">
	<div class="relative"><div class="insidewrapper">
	{$this->file_window('border-left:0px;')}
	</div><div class="header">{$this->file_menu()}</div></div></td>
	<td class='insidedivpad' style="{$h($this->Conf->tdtoprightstyle)}" id="td_top_right">
	<div class="vert_container"><div id="splitter1" class="vert_split" onmousedown="dragStart(event,this.id,'td_top_left')"></div></div>
	<div class="relative"><div class="insidewrapper">
	{$this->code_window('border-left:0px;border-right:0px;')}
	</div><div class="header">{$this->code_menu()}</div></div></td>
	</tr><tr>
	<td colspan="2" class='insidedivpad' style="{$h($this->Conf->tdbottomstyle)}" id="td_bottom">
	<div class="horiz_container"><div id="splitter2" class="horiz_split" onmousedown="dragStart(event,this.id,'td_top_left%td_top_right')"></div></div>
	<div class="relative"><div class="insidewrapper">
	{$this->eval_window('border-left:0px;border-bottom:0px;border-right:0px;')}
	</div><div class="header">{$this->eval_menu()}</div></div></td>
	</tr></table>
	HTML;
		}

		// Global toolbar
		$ret .= <<<HTML
	</div></div>
	<div class="globalheader">
	{$this->toolbar_left()}
	{$this->toolbar_middle()}
	{$this->toolbar_right()}
	</div>
	</form>
	HTML;

		// Save UI data
		$this->Conf->recentfiles = $this->recentfiles->save();
		$this->Conf->recentdirs = $this->recentdirs->save();
		$this->Conf->recentevals = $this->recentevals->save();
		if (isset($_POST['UIdata'])) {
			$this->Conf->UIdata = $_POST['UIdata'];
		}
		$this->Conf->save_to_file();

		// JavaScript block
		$ret .= "<script>\nsyncTextarea(" . json_encode($this->Conf->UIdata) . ");\n</script>\n";

		// Confirm overwrite if needed
		if (!empty($_POST['overwrite_ok'])) {
			$fileName = $h($_POST['save_as_filename']);
			$action = $h($_POST['action']) . '_replace';
			$ret .= "<script>\nae_confirm(callback_submit,'The file {$fileName} already exists, replace?','{$action}');\n</script>\n";
		}

		// Start download on load
		if (isSet($_POST['action']) && $_POST['action'] === 'download_system') {
			$ret .= "<script>window.onload = startdownload;</script>\n";
			echo $ret;
		}

		return $ret;
	}

}
/*
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

    function __construct($reldir,$currentfile,$sortorder,$browsebelowroot)
    {
        $this->reldir=$reldir;
		//$this->dir=realpath(dirname(__FILE__)."/$reldir");
        $this->dir=realpath($reldir);
        $this->currentfile=$currentfile;
        $this->sortorder=$sortorder;
        $this->browsebelowroot=$browsebelowroot;
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
		case 1:
			$this->sortFiles($this->files,'name',false);
			$this->header1="class='sortcol' onClick='submit_sort(2)'";
			$this->header2="onClick='submit_sort(3)'";
			$this->header3="onClick='submit_sort(5)'";
			$this->sortimage1="<img src='images/sort_up.gif'/>";
			break;
		case 2:
			$this->sortFiles($this->files,'name',true);
			$this->header1="class='sortcol' onClick='submit_sort(1)'";
			$this->header2="onClick='submit_sort(3)'";
			$this->header3="onClick='submit_sort(5)'";
			$this->sortimage1="<img src='images/sort_down.gif'/>";
			break;
		case 3:
			$this->sortFiles($this->files,'date',false);
			$this->header2="class='sortcol' onClick='submit_sort(4)'";
			$this->header1="onClick='submit_sort(1)'";
			$this->header3="onClick='submit_sort(5)'";
			$this->sortimage2="<img src='images/sort_up.gif'/>";
			break;
		case 4:
			$this->sortFiles($this->files,'data',true);
			$this->header2="class='sortcol' onClick='submit_sort(3)'";
			$this->header1="onClick='submit_sort(1)'";
			$this->header3="onClick='submit_sort(5)'";
			$this->sortimage2="<img src='images/sort_down.gif'/>";
			break;
		case 5:
			$this->sortFiles($this->files,'size',false);
			$this->header3="class='sortcol' onClick='submit_sort(6)'";
			$this->header2="onClick='submit_sort(3)'";
			$this->header1="onClick='submit_sort(1)'";
			$this->sortimage3="<img src='images/sort_up.gif'/>";
			break;
		case 6:
			$this->sortFiles($this->files,'size',true);
			$this->header3="class='sortcol' onClick='submit_sort(5)'";
			$this->header2="onClick='submit_sort(3)'";
			$this->header1="onClick='submit_sort(1)'";
			$this->sortimage3="<img src='images/sort_down.gif'/>";
			break;
			default:
			$this->header1="class='sortcol' onClick='submit_sort(1)'";
			$this->header2="onClick='submit_sort(3)'";
			$this->header3="onClick='submit_sort(5)'";
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
					$ret .="   <a href='#' onClick='javascript:submit_dir(\"{$file['name']}\");return false;'><img src='images/folder.png'/> ".$file['name']."</a>\n";
				} else {
					$ret .="   <img src='images/file.png'/> ".$file['name'];
				}
				$ret.="</td>\n<td> ".date("Y-m-d",$file['date'])." </td>\n<td align='right' style='padding-right:7px;'> </td>\n<td>\n<a href='#' onClick='javascript:chmod_file(\"{$path}\",\"$perms\");return false;'>".$perms."</a>\n";
			} else {
				if (is_readable(realpath($this->dir."/".$file['name']))) {
					$ret .="   <a href='#' onClick='javascript:submit_file(\"{$path}\");return false;'><img src='images/file.png'/> ".$file['name']."</a>\n";
				} else {
					$ret .="   <img src='images/file.png'/> ".$file['name'];
				}
				$ret .="</td>\n<td> ".date("Y-m-d",$file['date'])." </td>\n<td align='right' style='padding-right:7px;'>".$this->formatBytes($file['size'])."</td>\n<td><a href='#' onClick='javascript:chmod_file(\"{$path}\",\"$perms\");return false;'>".$perms."</a>\n";
			}
			$ret .="</td></tr>\n";
			$k=1-$k;
		}
        return $ret;
    }

	function file_table()
	{
		$ret = "";
	    if ($this->getFiles() === false)
	    {
			$ret .="<table width='100%' cellpadding='0' border='0' cellspacing='0' CLASS='boldtable'><tr><td align='left'>\n";
			$ret .="Unable to read $dir<br>";
			$ret .="<a href='#' onClick='javascript:submit_dir(\"./\");return false;'><img src='images/folder.png'/> Home</a>\n";
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
*/
class Editor
{

    var $hex;
    var $data;
    var $ascii;
    var $isbinary;
    var $protocthtml;
    var $unixnewlines;
    var $encoding;

    function __construct($isbinary,$protecthtml,$unixnewlines,$encoding)
    {
        $this->isbinary=$isbinary;
        $this->protecthtml=$protecthtml;
        $this->unixnewlines=$unixnewlines;
        $this->encoding=$encoding;
    }

	function make_textarea_safe($code)
	{
		$safe_code = preg_replace("/<\/(TEXTAREA)>/i", "</ide\\1>", $code);

		if ($this->protecthtml) {
			$safe_code = preg_replace("/&/", "&", $safe_code);
		}

		return $safe_code;
	}

    function dataIsBinary()
    {
        return preg_match('/[\x00-\x08\x0b-\x0c\x0e\x1f]/', $this->data);
    }

    function dataSet()
    {
        return isset($this->data);
    }

    function modifyCode()
    {
        if (!$this->isbinary)
        {
            /*
            ** Since the code is displayed in a <TEXTAREA>, it can't contain the tag </TEXTAREA>,
            ** since that would break our editor :/ Thus we replace it with </TEXTAREA>
            ** and put it in $this->textarea_safe_code. The reverse substitution is first
            ** performed on $this->code, to restore any previous replacements.
            */
			$this->data = preg_replace("/<\/ide(TEXTAREA)>/i", "</\\1>", $this->data);
		    //$this->textarea_safe_code	= $this->make_textarea_safe($this->code);
            /*
            ** Htmlentities are not literally shown inside TEXTAREA in some (all?) browsers.
            */

			if ($this->protecthtml) {
				$this->data = preg_replace("/(&)+&/", "&", $this->data);
		    }
            /*
            ** Remove \r\f if desired, needed for cgi on UNIX
            */
		    if ($this->unixnewlines) {
			    $this->data = preg_replace('/[\r\f]/', '', $this->data);
		    }
		}
    }

    function createFromData($data)
    {
        $this->data=$data;
        $this->isbinary=$this->dataIsBinary();
        if (!$this->isbinary)
        {
            $this->encoding = mb_detect_encoding($data,'UTF-8, UTF-7, ASCII, EUC-JP,SJIS, eucJP-win, SJIS-win, JIS, ISO-2022-JP, ISO-8859-1, WINDOWS-1252');
            $this->modifyCode();
        }
        return $this->isbinary;
    }

    function createFromFile($file)
    {
        if (filesize($file) == 0) {
    		return;
    	}
		$handle = fopen($file, 'r');
		$this->data = fread($handle, filesize($file));
		fclose($handle);

		$this->isbinary=$this->dataIsBinary();
        if (!$this->isbinary)
        {
			/*
        	if (get_magic_quotes_runtime()) {
				$this->data = stripslashes($this->data);	//??
			}
			*/
        }
        return $this->createFromData($this->data);
    }

    function createFromCode($code,$isfile=false)
    {
        if ($this->isbinary)
        {
            $hexcode= preg_replace('/[\x00-\x20]/','',$code);
            $this->data=pack('H*',$hexcode);
        }
        else
        {
    		/*
            ** Remove slashes if necessary, put code in $this->code
            */

            if (!$isfile)
			{
			/*
				if (get_magic_quotes_gpc()) {
					$code = stripslashes($code);
				}
				*/
            }
            $this->data=$code;
            $this->modifyCode();
        }
    }

    function createFromCodeFile($file)
    {
    	if (filesize($file) == 0) {
    		return;
    	}
		$handle = fopen($file, 'r');
		$code = fread($handle, filesize($file));
		fclose($handle);
        /*
		if (!$this->isbinary)
        {
        	if (get_magic_quotes_runtime()) {
				$code = stripslashes($code);	//??

			}
        }
        */
        $this->createFromCode($code,true);
    }

    function trimData()
    {
    	$this->data=preg_replace('/[ \t]+([\n\r])/','\\1',$this->data);
	    $this->data=preg_replace('/(\s+$)/','',$this->data);
    }

    function saveCode($file,$trim=false)
    {
         $handle = fopen($file, 'w+');
         if ($this->isbinary)
         {
            $hex=$this->getHex();
            fwrite($handle, $hex);
         }
         else
         {
            if ($trim)
            {
				$this->trimData();
            }
            fwrite($handle, $this->data);
         }
         fclose($handle);
    }

    function getCode()
    {
         if ($this->isbinary)
         {
            return $this->getHex();
         }
         else
         {
            return $this->data;
         }
    }
    function getTextareaCode()
    {
         if ($this->isbinary)
         {
            return $this->getHex();
         }
         else
         {
            return $this->make_textarea_safe($this->data);
         }
    }

    function getHighlightCode()
    {
		ob_start();
		highlight_string($this->getCode());
		$fancy_code_str = ob_get_contents();
		ob_end_clean();
		return $fancy_code_str;
    }

    function saveFile($file,$trim=false)
    {
   		if (!is_writable($file)) {
			$this->alert_message = 'The file \"$file\" is read only.\\nChange permissions.';
			return;
		}
         $handle = fopen($file, 'w+');
         if ($this->isbinary)
         {
            fwrite($handle, $this->data);
         }
         else
         {
            if ($trim)
            {
				$this->trimData();
            }
            fwrite($handle, $this->data);
         }
         fclose($handle);
    }

    /*
    function saveFile($file, $trim = false)
	{
		// Kolla om filen är skrivbar, annars visa varning
		if (!is_writable($file)) {
			$this->alert_message = 'The file \"$file\" is read only.\\nChange permissions.';
			return;
		}

		$handle = @fopen($file, 'w+');
		if (!$handle) {
			$this->alert_message = 'Could not open file \"$file\" for write access.';
			return;
		}

		if ($this->isbinary) {
			fwrite($handle, $this->data);
		} else {
			if ($trim) {
				$this->trimData();
			}
			fwrite($handle, $this->data);
		}

		fclose($handle);
	}
    */
    function getHex()
    {
         $tmp=unpack('H*',$this->data);
         $this->hex=trim(chunk_split(chunk_split($tmp[1],2,' '),48,chr(0x0d)));
         return $this->hex;
    }
    function getAscii()
    {
         $this->ascii.=trim(preg_replace('/([\x80-\xff])/e',"'&#'.ord('\\1').';'",htmlentities(chunk_split(preg_replace('/[\x00-\x20\x80-\xaf]/','.',$this->data),16,chr(0x0d))) ));
         return $this->ascii;
    }
    function getlen()
    {
         if ($this->isbinary)
         {
             return strlen($this->data);
         }
         return count(preg_split('/[\n]/',$this->data));

    }
    function detectCodeMirrorMode($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    $modeMap = [
        "groovy" => "groovy",
        "ini" => "properties",
        "properties" => "properties",
        "css" => "css",
        "scss" => "css",
        "html" => "htmlmixed",
        "htm" => "htmlmixed",
        "shtm" => "htmlmixed",
        "shtml" => "htmlmixed",
        "xhtml" => "htmlmixed",
        "cfm" => "htmlmixed",
        "cfml" => "htmlmixed",
        "cfc" => "htmlmixed",
        "dhtml" => "htmlmixed",
        "xht" => "htmlmixed",
        "tpl" => "htmlmixed",
        "twig" => "htmlmixed",
        "hbs" => "htmlmixed",
        "handlebars" => "htmlmixed",
        "kit" => "htmlmixed",
        "jsp" => "htmlmixed",
        "aspx" => "htmlmixed",
        "ascx" => "htmlmixed",
        "asp" => "htmlmixed",
        "master" => "htmlmixed",
        "cshtml" => "htmlmixed",
        "vbhtml" => "htmlmixed",
        "ejs" => "htmlembedded",
        "dust" => "htmlembedded",
        "erb" => "htmlembedded",
        "js" => "javascript",
        "jsx" => "javascript",
        "jsm" => "javascript",
        "_js" => "javascript",
        "vbs" => "vbscript",
        "vb" => "vb",
        "json" => "javascript",
        "xml" => "xml",
        "svg" => "xml",
        "wxs" => "xml",
        "wxl" => "xml",
        "wsdl" => "xml",
        "rss" => "xml",
        "atom" => "xml",
        "rdf" => "xml",
        "xslt" => "xml",
        "xsl" => "xml",
        "xul" => "xml",
        "xbl" => "xml",
        "mathml" => "xml",
        "config" => "xml",
        "plist" => "xml",
        "xaml" => "xml",
        "php" => "php",
        "php3" => "php",
        "php4" => "php",
        "php5" => "php",
        "phtm" => "php",
        "phtml" => "php",
        "ctp" => "php",
        "c" => "clike",
        "h" => "clike",
        "i" => "clike",
        "cc" => "clike",
        "cp" => "clike",
        "cpp" => "clike",
        "c++" => "clike",
        "cxx" => "clike",
        "hh" => "clike",
        "hpp" => "clike",
        "hxx" => "clike",
        "h++" => "clike",
        "ii" => "clike",
        "ino" => "clike",
        "cs" => "clike",
        "asax" => "clike",
        "ashx" => "clike",
        "java" => "clike",
        "scala" => "clike",
        "sbt" => "clike",
        "coffee" => "coffeescript",
        "cf" => "coffeescript",
        "cson" => "coffeescript",
        "_coffee" => "coffeescript",
        "clj" => "clojure",
        "cljs" => "clojure",
        "cljx" => "clojure",
        "pl" => "perl",
        "pm" => "perl",
        "rb" => "ruby",
        "ru" => "ruby",
        "gemspec" => "ruby",
        "rake" => "ruby",
        "py" => "python",
        "pyw" => "python",
        "wsgi" => "python",
        "sass" => "sass",
        "lua" => "lua",
        "sql" => "sql",
        "diff" => "diff",
        "patch" => "diff",
        "md" => "markdown",
        "markdown" => "markdown",
        "mdown" => "markdown",
        "mkdn" => "markdown",
        "yaml" => "yaml",
        "yml" => "yaml",
        "hx" => "haxe",
        "sh" => "shell",
        "command" => "shell",
        "bash" => "shell"
    ];

    return $modeMap[$ext] ?? 'text/plain';
}

}

class Beautifier
{
    function publicProcessHandler($str, $indent)
    {
	    // placeholders prevent strings and comments from being processed
        preg_match_all('/(?Ux:([\'\"])(?:.*[^\\\\]+)*(?:(?:\\\\{2})*)+\\1)|(?m:(\/\/[^\r\n]*))|(?m:(\/\*(.|[\r\n])*?\*\/))/',$str,$matches);
	    $matches=$matches[0];
    	$matches=array_values(array_unique($matches));
	    for ($i=0; $i<count($matches); $i++) {
	   	    $patterns[]='/'.preg_quote($matches[$i], '/').'/';
		    $placeholders[]="%placeholder$i%";
		    //remove too much whitespace from strings and comments too
		    $matches[$i]=preg_replace('/[\n\r]+\s*[\n\r]+/m',"\n",$matches[$i]);
		    // double backslashes must be escaped if we want to use them in the replacement argument
		    $matches[$i]=str_replace("\\\\", "\\\\\\\\", $matches[$i]);
	    }
	    if ($placeholders) {
		    $str=preg_replace($patterns, $placeholders, $str);
	    }
	    //parsing and indenting
	    $str=$this->privateIndentParsedString($this->privateParseString($str), $indent);
	    // insert original strings and comments
	    for ($i=count($placeholders)-1; $i>=0; $i--) {
		    $placeholders[$i]='/'.$placeholders[$i].'/';
	    }
	    if ($placeholders) {
		    $str=preg_replace($placeholders, $matches, $str);
	    }
	    return $str;
    }

    function privateParseString($str)
    {
	    // inserting missing braces (does only match up to 2 nested parenthesis)
	    $str=preg_replace('/^\s*(if|foreach|for|while|switch)\s*(\([^()]*(\([^()]*\)[^()]*)*\))([^\{;]*;)/mi', "\\1 \\2 {\\4\n}", $str);
    	// missing braces for else statements
    	$str=preg_replace('/(elseif|else if|else)\s*([^{;]*;)/i', "\\1 {\\2\n}", $str);
    	// line break check
    	//$str=preg_replace('/(;|case\s[^:]+:)/i', '\\1 \n', $str);
    	$str=preg_replace('/^\s*(function|class)\s+([^\n\r]+){/mi', "\\1 \\2 \n{", $str);
    	// remove inserted line breaks at else and for statements
    	$str=preg_replace('/(\}\s*else\s*\{)/mi', "} else {\n", $str);
    	$str=preg_replace('/\}\s*(elseif|else if)\s*(\([^()]*(\([^()]*\)[^()]*)*\))\s*\{/mi', "} \\1 \\2 {\n", $str);
    	$str=preg_replace('/^\s*(for\s*\()([^;]+;)(\s*)([^;]+;)(\s*)/mi', "\\1\\2 \\4 ", $str);
    	// remove spaces between function call and parenthesis and start of argument list
    	$str=preg_replace('/(\w+)\s*\(\s*/', "\\1(", $str);
    	// remove line breaks between condition and brace,
    	// set one space between control keyword and condition
    	$str=preg_replace('/^\s*(if|foreach|for|while|switch)\s*(\([^\{]+\))\s*\{/mi', "\\1 \\2 {\n", $str);
    	//remove empty lines
    	$str=preg_replace('/[\n\r]+\s*[\n\r]+/m',"\n",$str);
    	//add an empty line before functions and classes
    	$str=preg_replace('/^\s*(function|class)/mi',"\n\\1",$str);
    	return $str;
    }

    function privateIndentParsedString($str, $indent)
    {
    	$count = substr_count($str, "}")-substr_count($str, "{");
    	if ($count<0) {
    		$count = 0;
    	}
    	$strarray=explode("\n", $str);
    	for ($i=0; $i<count($strarray); $i++) {
    		$strarray[$i]=trim($strarray[$i]);
    		if (strstr($strarray[$i], "}")) {
    			if (!preg_match("/\{.*\}/",$strarray[$i])) {
    				$count--;
    			}
    			if ($count<0) {
    				$count = 0;
    			}
    		}
    		if (preg_match("/^case\s/i", $strarray[$i])) {
    			$level=str_repeat("\t", $indent*($count-1));
    		} else if(preg_match("/^or\s/i", $strarray[$i])) {
    			$level=str_repeat("\t", $indent*($count+1));
    		} else {
    			$level=str_repeat("\t", $indent*$count);
    		}
    		$strarray[$i]=$level.$strarray[$i];
    		if (strstr($strarray[$i], "{")) {
    			if (!preg_match("/\{.*\}/",$strarray[$i])) {
    				$count++;
    			}
    		}
    	}
    	$parsedstr=implode("\n", $strarray);
    	return $parsedstr;
    }
}

class RecentList
{

    var $items;

    function __construct($list,$current)
    {
		$this->items=unserialize($list);
		if ($this->items=='') {
		    $this->items=array($current);
		}
    }

	function append($item)
	{
		$this->remove($item);
		$this->items[]=$item;
		for ($i=count($this->items)-11; $i>=0; $i--) {
			unset($this->items[$i]);
		}
		if (!is_array($this->items)) {
			$this->items=array($this->items);
		}
		$this->items=array_values($this->items);
	}

	function remove($item)
	{
		for ($i=count($this->items)-1; $i>=0; $i--) {
			if (strlen(trim($this->items[$i]))==0) {
				unset($this->items[$i]);
			}
			else if ((!file_exists($this->items[$i])) && (!$this->is_url($this->items[$i])))
			{
				unset($this->items[$i]);
			} else if($this->items[$i]==$item) {
				unset($this->items[$i]);
			}
		}
		if (!is_array($this->items)) {
			$this->items=array($this->items);
		}
		$this->items=array_values($this->items);
	}

    function count()
    {
        return count($this->items);
    }

	function is_url($url)
	{
		$UrlElements = parse_url($url);
		if ((empty($UrlElements)) or(!$UrlElements)) {
			return false;
		}
		if ((!isset($UrlElements['host'])) || (empty($UrlElements['host']))) {
			return false;
		}
		return true;
	}

    function item($i)
    {
        return $this->items[$i];
    }

    function save()
    {
		return serialize($this->items);
    }
}
?>