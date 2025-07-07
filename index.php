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
		if (isset($_POST['Current_filename'])) {
			if (isset($_POST['UIdata'])) {
				$this->recentfiles->append($_POST['Current_filename'],$_POST['UIdata']);
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
				$dir = $_POST['current_directory'];
				if (!str_starts_with($dir,'./')) {
					$dir="{$this->Conf->Dir_path}/{$_POST['current_directory']}";
				}
				$reldir=$this->shorten_reldir($dir);
				$alert = "{$this->Conf->Dir_path} - {$_POST['current_directory']} - {$dir} - {$reldir}";
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
					$uidata = $_POST['UIdata'] ?? '';
					//php_alert_safe("save as");
					$this->recentfiles->append($_POST['save_as_filename'],$uidata);
				}
			} elseif($_POST['action'] == 'save_as_replace') {
				$this->save_file($_POST['save_as_filename']);
				$uidata = $_POST['UIdata'] ?? '';
				//php_alert_safe("save as replace");
				$this->recentfiles->append($_POST['save_as_filename'],$uidata);
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
		$sysdirs=array('images','javascript','css','filetable');
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
		$sysdirs = ['images', 'javascript', 'css', 'filetable'];
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
	/*
	function shorten_reldir($reldir, $base = __DIR__) {
		return str_replace($base, '.', realpath($reldir));
	}
*/
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
	
	function getAllFilesInDir() {
		$base = rtrim($this->Conf->Dir_path, '/');
		$fileList = [];
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
		);
	
		foreach ($iterator as $file) {
			if ($file->isFile()) {
				$fullPath = $file->getPathname();
				//$relativePath = './' . ltrim(str_replace($base, '', $fullPath), '/\\');
				//$fileList[] = $relativePath;
				$fileList[] = $file->getPathname();
			}
		}
	
		return $fileList;
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
		$ret .= "<div id = 'alertbar' class='inside_menu_text'>\n";
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
		$menu .="<hr/>";
		$menu .=$this->Out->menu_item('Add file to Current Project','addFileToCurrentProject("'.$this->Conf->Current_file.'")',!file_exists($this->Conf->Current_file));
		$menu .=$this->Out->menu_item('Add directory to Current Project','addFolderToCurrentProject('.json_encode($this->getAllFilesInDir()).')',$this->dir_is_empty($this->Conf->Dir_path));
		$ret .= $this->Out->menu_create('File',$menu);
		//$menu .= $this->Out->menu_bottom();
		//$ret .= $this->Out->menu_top($this->Conf->Dir_path);
		$menu='';
		for ($i=$this->recentdirs->count()-1; $i>=0; $i--) {
			$relpath=$this->shorten_reldir(realpath($this->recentdirs->path($i)),realpath($this->Conf->Dir_path));
			$menu .=$this->Out->menu_item($this->recentdirs->path($i),'submit_dir("'.$relpath.'")',!file_exists($this->recentdirs->path($i)));
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
		$menu .=$this->Out->menu_item('Search&emsp;&emsp;&emsp;&emsp;cmd+F','search_editor(true);');
		//$menu .=$this->Out->menu_item('Replace...&emsp;&emsp;cmd+H','replace_editor();',$this->Conf->IsBinary);
		$menu .=$this->Out->menu_item('Beautify','main_submit("beautify");',$this->Conf->IsBinary);
		$menu .="<hr/>";
		$menu .=$this->Out->menu_item('Open tpl','ae_confirm(callback_submit,"Replace current code with new template?","show_template")',($this->Conf->IsBinary));
		$menu .=$this->Out->menu_item('Save tpl','ae_confirm(callback_submit,"Replace current template?","save_as_template")',(!file_exists($this->Conf->Current_file or $this->Conf->IsBinary)));
		$menu .="<hr/>";
		$menu .=$this->Out->menu_item('Revert to saved','if (checkDirty()){ae_confirm(callback_submit,"Discard changes?","set_undo");}else{main_submit("set_undo");}');
		$menu .=$this->Out->menu_item('Save&emsp;&emsp;&emsp;&emsp;&emsp;cmd+S','main_submit("save");',(!file_exists($this->Conf->Current_file)));
		$menu .=$this->Out->menu_item('Save as...','save_as("'.$this->Conf->Current_file.'")');
		$menu .=$this->Out->menu_item('Encoding...','showFrame("./encoding_ide.php","","Encoding","Close",true);return false;');
		$ret .= $this->Out->menu_create('Code',$menu);
		$menu='';
		for ($i=$this->recentfiles->count()-1; $i>=0; $i--) {
			$menu .=$this->Out->menu_item($this->recentfiles->path($i),'submit_file("'.$this->recentfiles->path($i).'")',!file_exists($this->recentfiles->path($i)));
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
		$ret .=($this->Conf->Dirtyfile>0) ? '<a href="#" class="imgbutton" onClick="main_submit(\'save\');" title="Save cmd+S"> <img src="images/savel.png"/> </a>':'';
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
		$ret .= "<a href='#' class='imgbutton' onClick='main_form.phpnet.value=0;main_submit(\"eval\");' title='Run cmd+R'>  <img src='images/play.png'/>  </a>";
		$ret .= "</div>";
		//$ret.=$this->Out->menu_top('Evaluate');
		$menu = $this->Out->menu_item('Sync','main_form.phpnet.value=0;if ("'.$this->Conf->Syncmode.'"=="sync"){main_form.syncmode.value="";}else{main_form.syncmode.value="sync";}main_submit("eval_sync");',!file_exists($this->Conf->Current_file),$this->Conf->Syncmode=='sync');
		$menu.="<hr/>";
		$menu .=$this->Out->menu_item('Temporary (auto ext)','main_form.phpnet.value=0;if ("'.$this->Conf->Syncmode.'"=="temp"){main_form.syncmode.value="";}else{main_form.syncmode.value="temp";}main_submit("eval_sync")',!file_exists($this->temp_file()),$this->Conf->Syncmode=='temp');
		for ($i=0; $i<count($this->Conf->Eval_suffix_list); $i++) {
			$menu .=$this->Out->menu_item('Temporary'.$this->Conf->Eval_suffix_list[$i],'main_form.phpnet.value=0;if ("'.$this->Conf->Syncmode.'"=="temp'.$this->Conf->Eval_suffix_list[$i].'"){main_form.syncmode.value="";}else{main_form.syncmode.value="temp'.$this->Conf->Eval_suffix_list[$i].'";}main_submit("eval_sync");',(!file_exists($this->temp_file()) or ($this->Conf->IsBinary)),$this->Conf->Syncmode=='temp'.$this->Conf->Eval_suffix_list[$i]);
		}
		$menu.="<hr/>";
		$menu .=$this->Out->menu_item('Console','console_toggle();');
		$menu .=$this->Out->menu_item('View Source...','showEvalSource();');
		$menu .=$this->Out->menu_item('View DOM tree...','showEvalDomTree();');
		$menu.="<hr/>";
		$menu .=$this->Out->menu_item('PHP.net','main_form.phpnet.value=(1-main_form.phpnet.value);main_submit("phpnet");',!$this->is_url($this->Conf->Phpneturl),($this->Conf->Phpnet));
		$ret .= $this->Out->menu_create('Evaluate',$menu);
		//$ret.=$this->Out->menu_bottom();
		$this->recentevals->append($this->current_eval());
		//$ret.=$this->Out->menu_top('');
		$menu='';
		for ($i=$this->recentevals->count()-1; $i>=0; $i--) {
			$menu .=$this->Out->menu_item($this->recentevals->path($i),'main_form.syncmode.value="";main_form.phpnet.value=0;main_form.eval_path.value="'.$this->recentevals->path($i).'";main_submit("eval_change");',(!file_exists($this->recentevals->path($i))) && (!$this->is_url($this->recentevals->path($i))));
			if ($i==$this->recentevals->count()-1) {
				if ($this->recentevals->count()>1)
                {
                    $menu.="<hr/>";
                }
			}
		}
		$ret .= $this->Out->menu_create('',$menu);
		$ret .= "<div class='inside_menu' style='padding-top:3px;'>";
		$ret .= "<input name='eval_path' class='menu_textbox' id='eval_path' type='text' size='20' value='".$this->current_eval()."' onKeyDown='if (checkEnter(event)){return false;};' onMouseOver='showLayer(\"sub_$menu_id\")' onMouseOut='hideLayer(\"sub_$menu_id\")'/>\n";
		$ret .= "<input type='submit' class='hiddenbutton' value='Change eval path' id='submit_eval' onClick='main_form.syncmode.value=\"\";main_form.phpnet.value=0;main_submit(\"eval_change\");'/>\n";
		$ret .="</div>";
		$ret .="</div>";
		return $ret;
	}

	function file_window($borderstyle)
	{
		require 'filetable/filetable.php';
	    $filetable = new FileTable($this->Conf->Dir_path,$this->Conf->Current_file,$this->Conf->Dir_sortorder,$this->Conf->Allow_browse_below_root);
		$ret ="<div class='fixed_window'>\n";
		  $ret .="<div class='scroll_window' style='height:50%;$borderstyle'>\n";
		    $ret .= $filetable->file_table();
			$ret .= "<script type=\"text/javascript\">
				document.addEventListener(\"DOMContentLoaded\", function () {
					if (typeof FileTableEvents !== 'undefined') {
						FileTableEvents.onFileClick = function (path) {
							submit_file(path);
						};
						FileTableEvents.onFolderClick = function (path) {
							submit_dir(path);
						};
						FileTableEvents.onChangeSortOrder = function (sortorder) {
							submit_sort(sortorder);
						}
						FileTableEvents.onPermissionsClick = function (file,value)
						{
							chmod_file(file,value);
						}
					}
				});
				</script>";
		  $ret .="</div>\n";
		  $ret .= "<div class='scroll_window' style='height:50%;top:50%;$borderstyle'>";
		    require_once('./projecttree/projecttree.php');
		    $prj = new ProjectTree($this->Conf->Current_file);
		    $ret .= $prj->getHTML();
		    $ret .= "<script type=\"text/javascript\">
				document.addEventListener(\"DOMContentLoaded\", function () {
					if (typeof ProjectTreeEvents !== 'undefined') {
						ProjectTreeEvents.onFileClick = function (path) {
							submit_file(path);
						};
						ProjectTreeEvents.onFolderClick = function (path) {
							submit_dir(path);
						};
					}
				});
				</script>";
		  $ret .= "</div>";
		$ret .="</div>\n";
		return $ret;
	}

	function code_window($borderstyle)
	{
		$UIdata = $this->recentfiles->latestUIdata();
		if (isset($_POST['some_file_selection'])) {
			if ($_POST['some_file_selection'] != "") {
				$UIdata = $_POST['some_file_selection'];
			}
		}
		$ret = "<div id = 'codewindow' class='fixed_window'>\n";
		if ($this->Conf->UseCodeMirror && !$this->Conf->IsBinary) {
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
				document.addEventListener(\"DOMContentLoaded\", function() {
					if (typeof(CodeMirror) !== 'undefined') {
						editor = CodeMirror.fromTextArea(document.getElementById('code'), {
							lineNumbers: true,
							mode: document.getElementById('code').dataset.mode,
							theme: \"$theme\",
							indentUnit: 4,
							tabSize: 4
						});
						syncEditor(" . json_encode($UIdata) . ");
					}
				});
				</script>";
			$ret .= "<style>
				.CodeMirror, .CodeMirror pre {
					{$this->code_style()}
				}
				</style>";
		    $ret .="<div class='scroll_window_no' id = 'codewrapper' style='$borderstyle'>\n";
			  $ret .='<div class="leftwrapperinfo" style="border-left:0;'.$this->code_style().'">';
			//$ret .= "<div id = 'codewrapper' style = 'width:100%;height:100%;display:block;'>";			
			    $ret .= '<textarea class="absolute" name="CodeMirrorTextArea" id="code" data-mode="'.$this->Edit->detectCodeMirrorMode($this->Conf->Current_file).'">'.$this->Edit->getTextareaCode().'</textarea>';
			//$ret .= "</div>";
			  $ret .= "</div>";
			  $ret .= "<div id='infobarborder'></div>";
			  $ret .= "<div  onselectstart='return false' unselectable = 'on' id='infobar'></div>";
			  $ret .= "<div  onselectstart='return false' unselectable = 'on' id='infobarright'>";
			    $ret .= "<a href='#' title='Encoding' onclick='showFrame(\"./encoding_ide.php\",\"\",\"Encoding\",\"Close\",true);return false;'>".$this->Conf->Encoding.'</a>  '.FileTable::formatDate(filemtime($this->Conf->Current_file))."<a href='#' title='Revert to saved' onClick='if (checkDirty()){ae_confirm(callback_submit,\"Discard changes?\",\"set_undo\");}else{main_submit(\"set_undo\");}'> <img src='images/lock.gif'> </a>".FileTable::formatDate(filemtime($this->Conf->Backup_file)).' ';
			  $ret .= "</div>";
			$ret .= '</div>';
			$ret .= $this->search_window();
		}
        else {
        	$ret .= "<script>
			document.addEventListener(\"DOMContentLoaded\", function () {
				syncEditor(" . json_encode($UIdata) . ");
			});
			</script>";
			if (!$this->Conf->IsBinary)
			{
				$ret .="<div class='scroll_window_no' id = 'codewrapper' style='$borderstyle'>\n";
				  $ret.='<div class="leftwrapperinfo" style="'.$this->code_style().'">';
				    $ret .='<textarea class="absolute" style="'.$this->code_style().'" spellcheck="false" WRAP="OFF" ID="code" NAME="code">'.$this->Edit->getTextareaCode().'</textarea>\n';
				  $ret.='</div>';
				  $ret.='<div class="leftheaderinfo" style="'.$this->code_style().'">';
				    $ret.='<div id="code_numbers" name="code_numbers" class="codeprint" unselectable = "on" onselectstart="return false" style="'.$this->code_style().'">';
					  $ret.= '<code class="codeprint" style="'.$this->code_style().'">'. implode('<br />', range(1, $this->Edit->getlen())). '</code>';
				    $ret.= '</div>';
				  $ret.= '</div>';
				  $ret .= "<div id='infobarborder'></div>";
				  $ret .= "<div  onselectstart='return false' unselectable = 'on' id='infobar'></div>";
				  $ret .= "<div  onselectstart='return false' unselectable = 'on' id='infobarright'>";
				    $ret .= "<a href='#' title='Encoding' onclick='showFrame(\"./encoding_ide.php\",\"\",\"Encoding\",\"Close\",true);return false;'>".$this->Conf->Encoding.'</a>  '.FileTable::formatDate(filemtime($this->Conf->Current_file))."<a href='#' title='Revert to saved' onClick='if (checkDirty()){ae_confirm(callback_submit,\"Discard changes?\",\"set_undo\");}else{main_submit(\"set_undo\");}'> <img src='images/lock.gif'> </a>".FileTable::formatDate(filemtime($this->Conf->Backup_file)).' ';
				  $ret .= "</div>";
				$ret .="</div>\n";
			}
			else
			{
				$ret .="<div class='scroll_window_no' id = 'codewrapper' style='$borderstyle'>\n";
				$ret.='<div class="leftwrapperinfo" style="border-left:172px solid #e5e5e5;'.$this->code_style().'">';
				//$ret .= "<div id = 'codeWindow' style = 'width:100%;height:100%;display:block;'>";			
				$ret .='<textarea class="absolute" style="'.$this->code_style().'" spellcheck="false" WRAP="OFF" ID="code" NAME="code">'.$this->Edit->getCode().'</textarea>\n';
				//$ret.='</div>';
				//$ret .= "<div id = 'searchWindow' style = 'width:100%;height:30%;display:none;background-color:#E0E4EA;font-size:12px;'></div>";
				$ret.='</div>';
				$ret.='<div class="leftheaderinfo" style="'.$this->code_style().'">';
				$ret.='<div id="code_numbers" name="code_numbers" class="codeprint" unselectable = "on" onselectstart="return false" style="width:170px;'.$this->code_style().'">';
				$ret.= '<code class="codeprint" style="position:absolute;left:0px;top:0px;width:34px;text-align:right;'.$this->code_style().'">';
				for($i = 0; $i <= $this->Edit->getlen(); $i += 16) {
					$ret .= dechex($i).'<br/>';
				}
				$ret.= '</code>';
				$ret.='<code class = "asciiwrapper" style="left:34px;"'.$this->code_style().'">';
				$ret.=$this->Edit->getAscii();
				$ret.='</code>';
				$ret.= '</div>';
				$ret.= '</div>';
				$ret .= "<div id='infobarborder'></div>";
				$ret .= "<div  onselectstart='return false' unselectable = 'on' id='infobar'></div>";
				$ret .= "<div  onselectstart='return false' unselectable = 'on' id='infobarright'>";
				$ret .= FileTable::formatDate(filemtime($this->Conf->Current_file))."<a href='#' title='Revert to saved' onClick='if (checkDirty()){ae_confirm(callback_submit,\"Discard changes?\",\"set_undo\");}else{main_submit(\"set_undo\");}'> <img src='images/lock.gif'> </a>".FileTable::formatDate(filemtime($this->Conf->Backup_file)).' ';
				$ret .= "</div>";

				$ret.='</div>';
			}
			$ret .= $this->search_window();
        }
		$ret .="</div>\n";
		return $ret;
	}

	function search_window()
	{
		$ret = "<div id = 'searchWindow' style = 'width:100%;height:30%;bottom:0;position:absolute;display:none;background-color:#E0E4EA;font-size:12px;border-top:1px solid #888888;border-right:1px solid #888888;box-sizing:border-box;'>";
		  $ret .= "<div id = 'searchdiv' style = 'display:flex;width:100%;white-space:nowrap;align-items:center;'>";
		  	$ret .= $this->Out->search_button('closesearchbutton','✖');
		  	$ret .= $this->Out->search_input('searchText','Search...');
		  	$ret .= $this->Out->search_button('searchnextbutton','Find next');
		  	$ret .= $this->Out->search_checkbox('matchcasecb', 'Match case');
		  	$ret .= $this->Out->search_checkbox('wholewordcb', 'Whole word');
		  	$ret .= $this->Out->search_checkbox('searchselectedcb', 'Search selected');
		  	$ret .= $this->Out->search_checkbox('searchinprojectcb', 'Search in Project');
		  	$ret .= "<div id = 'searchstats' style = 'text-align:center;margin-left:20px;'></div>";
		  $ret .= "</div>";
		  $ret .= "<div id = 'replacediv' style = 'display:flex;width:100%;white-space:nowrap;align-items:center;padding-left:24px;'>";
		    $ret .= $this->Out->search_input('replaceText','Replace with...');
		  	$ret .= $this->Out->search_button('replacefindbutton','Replace and find next');
		  	$ret .= $this->Out->search_button('replaceallbutton','Replace all');
		  $ret .= "</div>";	
		  $ret .= '<div id = "showalldiv" style = "width:100%;height:calc(100% - 36px);overflow:scroll;background-color:white;padding:0;margin:0;'.$this->code_style().'">';
		    $ret .= "<ul id = 'hitlist' style = 'padding:0;margin:0;'></ul>";
		  $ret .= "</div>";	
		$ret .= "</div>";
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
		$ret .="<iframe id='evaluationwindow' name='evaluationwindow' frameborder='0' class='scroll_window' style='width:100%;height:100%;$borderstyle' src='".$src."'></iframe>";
		$ret .= '<div id="frame-console" style="position:absolute;bottom:0;height:30%;width:100%;background:#eee; white-space:pre-wrap; font-family:monospace;overflow:scroll;box-sizing:border-box;display:none">';
			$ret .= "<div id = 'consolediv' style = 'display:flex;width:100%;white-space:nowrap;align-items:center;background-color:#E0E4EA;'>";
		  		$ret .= $this->Out->search_button('consoleclosebutton','✖');
		  	$ret .= '</div>';
		$ret .= '</div>';
		$ret .= "</div>\n";
		return $ret;
	}

function main_page()
	{		
		$h = fn($str) => htmlspecialchars($str ?? '', ENT_QUOTES);
		$ret = "<script type=\"text/javascript\" src=\"splitters/splitters.js\"></script>
    		<link rel=\"stylesheet\" type=\"text/css\" href=\"splitters/splitters.css\">";
		$ret .= <<<HTML
	<form name="main_form" id="main_form" enctype="multipart/form-data" method="POST" action="{$h($_SERVER['PHP_SELF'])}">
	<input type="hidden" name="action" id="action" value="">
	<input type="hidden" name="prev_submit" value="{$h(md5(time() . session_id()))}">
	<input type="hidden" id="change_counter" name="change_counter" value="{$h($this->Conf->Dirtyfile)}">
	<input type="hidden" name="Current_filename" id="Current_filename" value="{$h($this->Conf->Current_file)}">
	<input type="hidden" id="save_as_filename" name="save_as_filename" value="{$h($_POST['save_as_filename'] ?? '')}">
	<input type="hidden" id="use_code_mirror" name="use_code_mirror" value="{$h($this->Conf->UseCodeMirror)}">
	<input type="hidden" id="phpnet" name="phpnet" value="{$h($this->Conf->Phpnet)}">
	<input type="hidden" id="syncmode" name="syncmode" value="{$h($this->Conf->Syncmode)}">
	<input type="hidden" id="layoutstyle" name="layoutstyle" value="{$h($this->Conf->LayoutStyle)}">
	<input name="current_directory" id="current_directory" type="hidden" value="">
	<input name="sortorder" id="sortorder" type="hidden" value="">
	<input name="some_file_name" id="some_file_name" type="hidden" value="">
	<input name="some_file_selection" id="some_file_selection" type="hidden" value="">
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
    require('splitters/splitters.php');
    $f = new SplitterFactory;
    $ret .= $f->buildAssets();

		if ($this->Conf->LayoutStyle == 1) {
			// 3-column layout
			$ret .= "<table class='insidediv'><tr>";
			$ret .= $f->buildNeutralCell("td_left",$this->Conf->tdleftstyle,
			"<div class=\"relative\"><div class=\"insidewrapper\">
			{$this->file_window('border-left:0px;border-bottom:0px;')}
			</div><div class=\"header\">{$this->file_menu()}</div></div>"
			);
			$ret .= $f->buildVertCell("td_middle",$this->Conf->tdmiddlestyle,"splitter1","td_left",
			"<div class=\"relative\"><div class=\"insidewrapper\">
			{$this->code_window('border-left:0px;border-bottom:0px;')}
			</div><div class=\"header\">{$this->code_menu()}</div></div>"
			);
			$ret .= $f->buildVertCell("td_right",$this->Conf->tdrightstyle,"splitter2","td_middle",
			"<div class=\"relative\"><div class=\"insidewrapper\">
			{$this->eval_window('border-left:0px;border-bottom:0px;border-right:0px;')}
			</div><div class=\"header\">{$this->eval_menu()}</div></div>"
			);
			$ret .= "</tr></table>";
		} else {
			// Split top/bottom layout
			$ret .= "<table class='insidediv'><tr>";
			$ret .= $f->buildNeutralCell("td_top_left",$this->Conf->tdtopleftstyle,
			"<div class=\"relative\"><div class=\"insidewrapper\">
			{$this->file_window('border-left:0px;border-bottom:0px;')}
			</div><div class=\"header\">{$this->file_menu()}</div></div>"
			);
			$ret .= $f->buildVertCell("td_top_right",$this->Conf->tdtoprightstyle,"splitter1","td_top_left",
			"<div class=\"relative\"><div class=\"insidewrapper\">
			{$this->code_window('border-left:0px;border-bottom:0px;')}
			</div><div class=\"header\">{$this->code_menu()}</div></div>"
			);
			$ret .= "</tr><tr>";
			$ret .= $f->buildHorizCell("td_bottom",$this->Conf->tdbottomstyle,"splitter2","td_top_left%td_top_right",
			"<div class=\"relative\"><div class=\"insidewrapper\">
			{$this->eval_window('border-left:0px;border-bottom:0px;border-right:0px;')}
			</div><div class=\"header\">{$this->eval_menu()}</div></div>"
			);
			$ret .= "</tr></table>";
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
		$this->Conf->save_to_file();

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
		 /*
		** Since the code is displayed in a <TEXTAREA>, it can't contain the tag </TEXTAREA>,
		** since that would break our editor :/ Thus we replace it with </TEXTAREA>
		** and put it in $this->textarea_safe_code. The reverse substitution is first
		** performed on $this->code, to restore any previous replacements.
		*/
		$safe_code = preg_replace("/<\/(TEXTAREA)>/i", "</ide\\1>", $code);
		return $safe_code;
	}
    
    function escape_code($code) {
    	$escapedCode = $code;
		if ($this->protecthtml) {
			$escapedCode = preg_replace('/&(#[0-9]+|[a-zA-Z0-9]+);/', '&amp;$1;', $escapedCode);
		}
		if ($this->unixnewlines) {
			$escapedCode = preg_replace('/[\r\f]/', '', $escapedCode);
		}
		return $escapedCode;
    }
    
	function dataIsBinary() {
		if (!function_exists('finfo_buffer')) // fallback 
		{
			// Strip UTF-8 BOM
			$data = ltrim($this->data, "\xEF\xBB\xBF");
			// Allow up to N control characters
			$sample = substr($data, 0, 1000);
			$nonText = preg_match_all('/[\x00-\x08\x0B-\x0C\x0E-\x1F]/', $sample);
			return $nonText > 5;
		}
	
		$finfo = new finfo(FILEINFO_MIME_TYPE);
		$mime = $finfo->buffer($this->data);
		$textTypes = [
			'text/',
			'application/javascript',
			'application/json',
			'application/xml',
			'application/x-javascript',
			'application/x-httpd-php',
			'application/xhtml+xml',
			'application/x-shellscript'
		];
		foreach ($textTypes as $type) {
			if (strpos($mime, $type) === 0) {
				return false; // inte binär
			}
		}
		return true; // default = binär
	}
	
    function dataSet()
    {
        return isset($this->data);
    }

    function createFromData($data)
    {
        $this->data=$data;
        $this->isbinary=$this->dataIsBinary();
        if (!$this->isbinary)
        {
            $this->encoding = mb_detect_encoding($data,'UTF-8, UTF-7, ASCII, EUC-JP,SJIS, eucJP-win, SJIS-win, JIS, ISO-2022-JP, ISO-8859-1, WINDOWS-1252');
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
        return $this->createFromData($this->data);
    }

    function createFromCode($code,$isfile=false)
    {
        if ($this->isbinary)
        {
            //$hexcode= preg_replace('/[\x00-\x20]/','',$code);
            //$this->data=pack('H*',$hexcode);
            $hexcode = str_replace([" ", "\r", "\n"], '', $code);
			$this->data = pack('H*', $hexcode);
        }
        else
        {
            $this->data=$code;
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
            $originalData = preg_replace('/<\/idetextarea>/i', '</textarea>', $this->data);
            fwrite($handle, $originalData);
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
    
    function getEscapedCode()
	{
         if ($this->isbinary)
         {
            return $this->getHex();
         }
         else
         {
            return $this->escape_code($this->data);
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
            return $this->escape_code($this->make_textarea_safe($this->data));
         }
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

    function getHex()
    {
        $tmp = unpack('H*', $this->data);
		$this->hex = trim(chunk_split(chunk_split($tmp[1], 2, ' '), 48, "\r"));
        return $this->hex;
    }
    /*
    function getAscii()
    {
         $this->ascii.=trim(preg_replace('/([\x80-\xff])/e',"'&#'.ord('\\1').';'",htmlentities(chunk_split(preg_replace('/[\x00-\x20\x80-\xaf]/','.',$this->data),16,chr(0x0d))) ));
         return $this->ascii;
    }
    */
    /*
    function getAscii()
	{
		$ascii = "<p style='margin:0;'>";
		$len = strlen($this->data);
	
		for ($i = 0; $i < $len; $i++) {
			$char = $this->data[$i];
			$byte = ord($char);
			if ($byte >= 32 && $byte <= 126) {
				$ascii .= $char;
			} else {
				$ascii .= '.';
			}
			if (($i + 1) % 16 === 0) {
				$ascii .= "</p><p style='margin:0;'>";
			}
		}
		$ascii .= "</p>";
		$this->ascii = $ascii;
		return $ascii;
	}
	*/
	function getAscii()
	{
		$ascii = '';
		$len = strlen($this->data);
		$line = '';
		$lineCount = 0;
	
		for ($i = 0; $i < $len; $i++) {
			$char = $this->data[$i];
			$byte = ord($char);
	
			$line .= ($byte >= 32 && $byte <= 126) ? htmlspecialchars($char) : '.';
	
			if (($i + 1) % 16 === 0 || $i === $len - 1) {
				// Använd bakgrund baserat på radnummer (jämn/udda)
				$bg = ($lineCount % 2 === 0) ? '#ffffff' : '#eef7ff';
				$ascii .= "<p style='margin:0;background:$bg;font-family:monospace;'>$line</p>";
				$line = '';
				$lineCount++;
			}
		}
	
		$this->ascii = $ascii;
		return $ascii;
	}
	
    function getlen()
    {
         if ($this->isbinary)
         {
             return strlen($this->data);
         }
         return count(preg_split('/[\n]/',$this->data));

    }
    /*
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
        "phpclass" => "php",
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
*/
	function detectCodeMirrorMode($filename) {
		static $modeMap = null;
	
		if ($modeMap === null) {
			$jsonPath = __DIR__ . '/code_modes.json';
			if (file_exists($jsonPath)) {
				$modeMap = json_decode(file_get_contents($jsonPath), true);
			} else {
				$modeMap = []; // fallback om filen saknas
			}
		}
	
		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
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

    function __construct($list, $current)
	{
		$this->items = @unserialize($list);
		if (!is_array($this->items) || empty($this->items)) {
			$this->items = [['file' => $current]];
		}
	}

	function append($file, $uidata = [])
	{
		if ($uidata === []) {
			$uidata = $this->UIdata($file); 
		}
		$this->remove($file);
		$this->items[] = ['file' => $file, 'uidata' => $uidata];
		$this->items = array_slice($this->items, -10);
	}

	function remove($filePath) {
		$this->items = array_values(array_filter($this->items, function($item) use ($filePath) {
			if (!is_array($item) || empty($item['file'])) return false;
			if ((!file_exists($item['file'])) && (!$this->is_url($item['file']))) return false;
			return $item['file'] !== $filePath;
		}));
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
        return $this->path($i);
    }

	function path($i) {
		if (isset($this->items[$i]['file'])) {
			return $this->items[$i]['file'];
		}
		return is_string($this->items[$i]) ? $this->items[$i] : null;
	}
	
	function UIdata($file)
	{
		foreach ($this->items as $item) {
			if (isset($item['file']) && $item['file'] === $file) {
				return $item['uidata'] ?? [];
			}
		}
		return [];
	}	
	
	function latestUIdata()
	{
		$last = array_slice($this->items, -1)[0] ?? null;
		return is_array($last) && isset($last['uidata']) ? $last['uidata'] : [];
	}	

    function save()
    {
		return serialize($this->items);
    }
}
?>