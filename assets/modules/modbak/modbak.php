<?php
/**
* Main Modbak include code
*/
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODx Content Manager instead of accessing this file directly.");

if(!$modx->hasPermission('bk_manager')) {	
	$e->setError(3);
	$e->dumpError();
    exit;	
}

// module info
$module_version = '0.93';

$out ='';
// check if backup exists and is writable
if (!file_exists($modx_backup_dir))
{
    $BACKUPERROR = "Backup directory <strong>$modx_backup_dir</strong> does not exist";
} elseif(!is_writable($modx_backup_dir))
    {
        $BACKUPERROR = "Backup directory <strong>$modx_backup_dir</strong> is NOT writable!";
    }

if (isset($BACKUPERROR) && $BACKUPERROR!='') {
    $out .= $BACKUPERROR;
    include_once($mods_path.'modbak/display.php');
    return $out;
}

// --------------- Set Directories and files to include in archive
$modx_files_array = array($modx_root_dir.'assets',$modx_root_dir.'manager',$modx_root_dir.'index.php');
if (file_exists($modx_root_dir.'.htaccess'))
{
    $modx_files_array[]=$modx_root_dir.'.htaccess';
}  

if (!defined('PCLZIP_TEMPORARY_DIR')) { define( 'PCLZIP_TEMPORARY_DIR', $modx_backup_dir ); }

$archive_file = $modx_backup_dir.$archive_prefix;

$opcode     = isset($_POST['opcode']) ? $_POST['opcode']:'';
$dumpdbase  = isset($_POST['dumpdbase']) ? $_POST['dumpdbase']:'';
$droptables = isset($_POST['droptables']) ? $_POST['droptables']:'';
$filename   = isset($_REQUEST['filename']) ? $_REQUEST['filename']:'';



$out .= <<<EOD
<script language="JavaScript" type="text/javascript">
function postForm(opcode,filename){
document.module.opcode.value=opcode;
document.module.filename.value=filename;
document.module.submit();
}
</script>
<form name="module" method="post">
<input name="opcode" type="hidden" value="" />
<input name="filename" type="hidden" value="" />
EOD;

switch($opcode)
{
    case 'delete': // delete file
        $deletefile = $modx_backup_dir.$filename;
        if (!file_exists($deletefile))
        {
            $out .= "File $filename does not exist<br />";
        } else
            {
                unlink($deletefile);
                $out .= "$filename Deleted<br />";
            }
    break;
    
    case 'generate': // generate backup
        /**
        * Zip directories into archive
        */
		// attempt to change mem / time limits
		@set_time_limit($zip_time_limit);
		@ini_set("memory_limit",$zip_memory_limit);
        $archive_file .= $archive_suffix.'.zip';
        include_once($mods_path.'modbak/pclzip.lib.php');
        $archive = new PclZip($tempfile);
        $v_list = $archive->create($modx_files_array,PCLZIP_OPT_REMOVE_PATH, $modx_root_dir );
        if ($v_list == 0) {
            $out .= "Error : ".$archive->errorInfo(true);
            return $out;
        }
        rename($tempfile,$archive_file);       
        $out .= "<br />Modx Backup Successful <strong>--&gt <a href=\"".$modx->config['site_url']."assets/modules/modbak/download.php?filename=".basename($archive_file)."\">$archive_file</a></strong><br /><br />";    

        // add database, callback for dbdump
        if ($dumpdbase!='') {
            $out .= "Adding Database..<script type=\"text/javascript\" language=\"javascript\">postForm('dumpdbase','".basename($archive_file)."');</script>";
        }
        
    break;
    
    case 'dumpdbase': // add mysql database dump to archive
        // dump sql data to temp file
        include_once($mods_path.'modbak/dumpsql.php');
        
        /*
         * Code taken from Ralph A. Dahlgren MySQLdumper Snippet - Etomite 0.6 - 2004-09-27
         * Modified by Raymond 3-Jan-2005
         * Perform MySQLdumper data dump
         */
        @set_time_limit($db_time_limit); // set timeout limit to 2 minutes
        global $dbase,$database_user,$database_password,$dbname,$database_server;
        $dbname = str_replace("`","",$dbase);
        $dumper = new Mysqldumper($database_server, $database_user, $database_password, $dbname); # Variables have replaced original hard-coded values
        
		$dumper->setTablePrefix($table_prefix);
        $dumper->setDroptables(true);
        $dumpfinished = $dumper->createDump($dump_log_tables);
        $fh = fopen($modx_backup_dir.$database_filename,'w');
        
        if($dumpfinished) 
        {
            fwrite($fh,$dumpfinished);               
            fclose($fh);
            $out .= "<script type=\"text/javascript\" language=\"javascript\">postForm('adddumpfile','".basename($filename)."');</script>";
        }       
        else {
	        $e->setError(1,"Unable to Backup Database");
	        $e->dumpError();
        }    
    break;
    
    case 'adddumpfile':
        // add dump file to archive
        @set_time_limit($zip_time_limit);
        @ini_set("memory_limit",$zip_memory_limit);
        include_once($mods_path.'modbak/pclzip.lib.php');
        $archive = new PclZip($modx_backup_dir.$filename);
        $v_list = $archive->add($modx_backup_dir.$database_filename,PCLZIP_OPT_REMOVE_PATH, $modx_backup_dir );
        if ($v_list == 0) {
            $out .= "Error : ".$archive->errorInfo(true);
            return $out;
        }
        
		// 6 mar/ 07 adjusted to cater for names like mysite.com.au    ie extra . in filename
		$fileBits = explode('.',$filename);
		$ext = array_pop($fileBits); 
		$fname = implode('.',$fileBits);
		
		// list($fname,$ext) = explode('.',$filename);
        rename($modx_backup_dir.$filename,$modx_backup_dir.$fname.'_db.'.$ext);
        
    break;
}

/**
* Display list of backups with download
*/
$out .= "<strong>Current Backups:</strong><br />";
if ($handle = opendir($modx_backup_dir)) {
   /* Loop over backup directory */
   while (false !== ($file = readdir($handle))  ) {
       if ($file!='.' && $file!='..' && (strpos($file,$archive_prefix)!==false ) && $file!=$database_filename)
       {
           $fs = filesize($modx_backup_dir.$file)/1024; 
           $out .= "<a href=\"".$modx->config['site_url']."assets/modules/modbak/download.php?filename=$file\">$file [".ceil($fs)." kb]\n</a> "
                  ."<input type=\"submit\" name=\"delete\" value=\"Delete\" onclick=\"postForm('delete','$file')\" /><br />";
       }
   }
   closedir($handle);
}


$out .= <<<EOD
<br />
<label><input type="checkbox" name="dumpdbase" checked="checked" /> Add Database tables to archive</label><br />
<input type="submit" name="generate_backup" onclick="postForm('generate')" value="Generate Backup" />
</form>
EOD;
?>