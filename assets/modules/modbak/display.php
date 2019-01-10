<?php
include_once($mods_path."/modbak/header.inc.php");
$o = <<<EOD
<div class="subTitle">
<span class="right"><img src="media/images/_tx_.gif" width="1" height="5"><br />ModBak Site Backup v$module_version</span>
</div>

<div class="sectionHeader"><img src='media/images/misc/dot.gif' alt="." />&nbsp;MODx Site Backup v$module_version</div><div class="sectionBody" id="lyr4">
EOD;

$out = $o.$out.'</div>';
return $out;
?>