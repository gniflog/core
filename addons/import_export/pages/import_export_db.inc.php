<?php
/**
 * This file is part of CONTEJO - CONTENT MANAGEMENT 
 * It is an open source content management system and had 
 * been forked from Redaxo 3.2 (www.redaxo.org) in 2006.
 * 
 * PHP Version: 5.3.1+
 *
 * @package     Addons
 * @subpackage  import_export
 * @version     2.7.x
 *
 * @author      Stefan Lehmann <sl@raumsicht.com>
 * @copyright   Copyright (c) 2008-2012 CONTEJO. All rights reserved. 
 * @link        http://contejo.com
 *
 * @license     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 *  CONTEJO is free software. This version may have been modified pursuant to the
 *  GNU General Public License, and as distributed it includes or is derivative
 *  of works licensed under the GNU General Public License or other free or open
 *  source software licenses. See _copyright.txt for copyright notices and
 *  details.
 * @filesource
 */

// Für größere Exports den Speicher für PHP erhöhen.

ini_set('memory_limit', '256M');
ini_set('max_execution_time', 300);

$addon = 'import_export';

if ($import_file) {

	$import_file = str_replace("/", "", $import_file);

	if (OOMedia::getExtension($import_file) != 'sql') {
	    unset($import_file);
	}
}

if (!cjo_request('export_file', 'bool')) {
	$export_file= cjo_specialchars(cjoProp::getServerName()).'-cjo'.cjoProp::getVersion().'-'.date("ymd");
}
if ($function == "delete") {
	// ------------------------------ FUNC DELETE
	if (@unlink(cjoPath::addonAssets($addon,$import_file)))
	   cjoMessage::addSuccess(cjoAddon::translate(3,"msg_file_deleted", $import_file));

}
elseif ($function == "import") {

	if (isset ($_FILES['FORM']) &&
	    $_FILES['FORM']['size']['import_file'] < 1 &&
	    !$import_file) {
		cjoMessage::addError(cjoAddon::translate(3,"err_no_import_file_chosen_or_wrong_version"));
	}
	else {
		if ($import_file) {
			$file_temp = cjoPath::addonAssets($addon,$import_file);
		}
		else  {
			$file_temp = cjoPath::addonAssets($addon,'sql.temp');
		}

		if ($import_file ||
		    @move_uploaded_file($_FILES['FORM']['tmp_name']['import_file'], $file_temp)) {

		    cjoImportExport::importSqlFile($file_temp);
			// temp datei löschen
			if (!$import_file) @unlink($file_temp);
		}
		else {
			cjoMessage::addError(cjoAddon::translate(3,"err_file_could_not_be_uploaded")." ".
			                     cjoAddon::translate(3,"err_you_have_no_write_permission_in",
			                     cjoPath::addonAssets($addon)));
		}
	}
}
elseif ($function == "export") {

    if (!empty($export_include)) {
    
    	$export_file = stripslashes($export_file);
    	$filename = preg_replace("/[^\.a-z0-9_\-]/", "", $export_file);
    
    	if ($filename != $export_file){
    		cjoMessage::addSuccess(cjoAddon::translate(3,"msg_filename_updated"));
    		$export_file = $filename;
    	}
    	
		$header = "plain/text";
		$ext = ".sql";
		$content = cjoImportExport::generateSqlExport(array_keys($export_include));
		

		if (cjo_post('download','bool')){
			$filename = $filename.$ext;
			
            cjoExtension::registerExtensionPoint('SQL_EXPORTED', 
                                                 array('filename' => $filename,
                                                       'download' => true));
			ob_end_clean();
			header("Content-type: ".$header);
			header("Content-Disposition: attachment; filename=".$filename);
			echo $content;
			exit;
		}
		elseif ($content != ""){
			// check filename ob vorhanden
			// aendern filename
			// speicher content in files

			$filename = cjoPath::addonAssets($addon,$filename);

			if (file_exists($filename.$ext)){
				for ($i = 0; $i < 1000; $i++){
					if (!file_exists($filename."_".$i."".$ext)){
						$filename = $filename."_".$i."".$ext;
						break;
					}
				}
			}
			else {
				$filename .= $ext;
			}

			if (cjoFile::put($filename, $content)) {
				cjoMessage::addSuccess(cjoAddon::translate(3,'msg_file_generated_in', $filename));
				
				//  EXTENSION POINT
                cjoExtension::registerExtensionPoint('SQL_EXPORTED', 
                                                     array('filename' => $filename,
                                                           'download' => true));
			}
			else {
				cjoMessage::addError( cjoAddon::translate(3,'err_file_could_not_be_generated').' '.
				                      cjoAddon::translate(3,'err_check_rights_in_directory', $dir_filename));
			}
		}
    } else {
        cjoMessage::addError(cjoAddon::translate(3,'err_file_could_not_be_generated'));
    }
}


$sql_table = '';
$dir = cjoImportExport::getImportDir();
$folder = cjoImportExport::readImportFolder(".sql");

if (count($folder) > 0) {

	foreach ($folder as $file) {

		if (preg_match('/(.*)-([0-9]{2}[0-1][0-9][0-3][0-9].*)\.(.*)$/', $file, $file_name)) {
		    $file_name = (strlen($file) > 40) ? substr($file_name[1],0, 25).'...'.$file_name[2] : $file;
		}
		else {
		    $file_name = $file;
		}


        $path = './get_file.php?file='.rawurlencode(cjoUrl::addonAssets($addon,$file));
        $link = '<a href="'.$path.'" target="_blank" title="'.$file.'"><b>'.$file_name.'</b></a>';
        
		$filepath = $dir.'/'.$file;
		$filec = date("d.m.y H:i", filemtime($filepath));
		$sql_table .= '<tr>'.
					  '	<td><img src="img/silk_icons/page_white_database.png" alt="" /> <b title="'.$file.'">'.$link.'</b></td>'.
					  '	<td>'.$filec.'</td>'.
					  '	<td class="icon">'.
					  '		<a href="index.php?page='.cjoProp::getPage().'&function=import&import_file='.$file.'" '.
					  '		   title="'.cjoAddon::translate(3,'label_import_sql', $file).'<br/><br/>'.cjoAddon::translate(3,'msg_proceed_sql_import').'" class="cjo_confirm">'.
					  '			 <img src="img/silk_icons/database_go.png" alt="'.cjoAddon::translate(3,'button_import').'" '.
					  '				  title="'.cjoAddon::translate(3,'label_import_sql', $file).'" />'.
					  '		</a>'.
					  '	</td>'.
					  '	<td class="icon">'.
					  '		<a href="index.php?page='.cjoProp::getPage().'&function=delete&import_file='.$file.'" '.
					  '		   title="'.cjoAddon::translate(3,'label_delete_sql', $file).'" class="cjo_confirm">'.
					  '			<img src="img/silk_icons/bin.png" alt="'.cjoI18N::translate("button_delete").'" '.
					  '		         title="'.cjoAddon::translate(3,"label_delete_sql", $file).'" />'.
					  '		</a>'.
					  '	</td>'.
					  '</tr>';
	}
}
else {
		$sql_table  = '<tr>'.
					  '	<td><img src="img/silk_icons/page_white_database.png" alt="" /> <b>--</b></td>'.
					  '	<td>--</td>'.
					  '	<td class="icon">&nbsp;</td>'.
					  '	<td class="icon">&nbsp;</td>'.
					  '</tr>';
}

$sub_table = '';
foreach (cjoSql::showTables() as $export_table) {

	$checked = '';
	if (!empty($export_include)) $checked = cjoAssistance::setChecked($export_table, array_keys(cjoAssistance::toArray($export_include)));

	$sub_table .= '<input type="checkbox" class="checkbox" id="exptables_'.$export_table.'" name="export_include['.$export_table.']" '.
				  '	  value="true"'.$checked.'/>&nbsp; '.
				  '<label for="exptables__'.$export_table.'">'.$export_table.'</label><br />';
}
	$sub_table .= '<br/><input type="checkbox" class="check_all" id="exptables_all" />&nbsp; '.
				  '<label for="exptables_all">'.cjoI18N::translate('label_select_deselect_all').'</label><br />';

$buttons = new buttonField();
$buttons->addButton('cjoform_submit_button',cjoAddon::translate(3,'label_start_export_db'), true, 'img/silk_icons/disk.png');
$buttons->setButtonAttributes('cjoform_submit_button','style="margin: 0 0 20px 0"');


echo '<div class="a22-cjolist">'.
     '	<div class="a22-cjolist-data">'.
	 '		<table class="cjo no_hover" cellspacing="0" cellpadding="0" border="0">'.
 	 '      <thead>'.
	 '		<tr>'.
	 '			<th>'.cjoAddon::translate(3,'label_import').'</th>'.
	 '			<th>'.cjoAddon::translate(3,'label_export').'</th>'.
	 '		</tr>'.
 	 '      </thead>'.
 	 '      <tbody>'.
	 '		<tr>'.
	 '			<td valign="top" width="50%">'.
     '				<p>'.cjoAddon::translate(3,"msg_intro_import").'</p>'.
	 '				<table cellspacing="0" cellpadding="0" border="0">'.
 	 '       		<thead>'.
	 '		 		<tr>'.
	 '    				<th align="left">'.cjoAddon::translate(3,"label_filename").'</th>'.
	 '    				<th width="110">'.cjoI18N::translate("label_createdate").'</th>'.
	 '    				<th width="60" colspan="2">'.cjoI18N::translate("label_functions").'</th>'.
	 '  			</tr>'.
 	 '      		</thead>'.
 	 '      		<tbody>'.
 	 '      		'.$sql_table.
 	 '      		</tbody>'.
 	 '      		</table>'.
     '				<form action="index.php" name="import1" method="post" enctype="multipart/form-data">'.
     '					<input type="hidden" name="page" value="'.cjoProp::getPage().'" />'.
     '					<table cellspacing="0" cellpadding="0" border="0">'.
 	 '       			<thead>'.
     '						<tr><th align="left" colspan="2">'.cjoAddon::translate(3,"label_import_upload").'</th></tr>'.
 	 '      			</thead>'.
 	 '      			<tbody>'.
     '						<tr>'.
     '							<td><input type="file" name="FORM[import_file]" size="55" /></td>'.
     '							<td class="icon">'.
     '								<input type="image" class="cjo_confirm" '.
     '									   name="function" value="import" '.
     '									   title="'.cjoAddon::translate(3,"label_selected_file").' '.cjoAddon::translate(3,'label_import_sql').'" '.
     '									   src="img/silk_icons/database_go.png" '.
     '									   alt="'.cjoAddon::translate(3,'label_import_sql').'" />'.
     '							</td>'.
     '							</tr>'.
 	 '      			</tbody>'.
     '				</table>'.
     '				</form>'.
	 '			</td>'.
	 '			<td valign="top" width="50%">'.
     '				<p>'.cjoAddon::translate(3,"label_intro_export").'</p>'.
	 '				<form action="index.php" method="post" enctype="multipart/form-data">'.
	 '				<input type="hidden" name="page" value="'.cjoProp::getPage().'" />'.
	 '				<input type="hidden" name="function" value="export" />'.
	 '				<table  cellspacing="0" cellpadding="0" border="0">'.
 	 '       		<thead>'.
     '					<tr><th align="left" colspan="2">'.cjoAddon::translate(3,"label_database_export").'</th></tr>'.
 	 '      		</thead>'.
 	 '      		<tbody>'.
	 '				<tr>'.
	 '					<td colspan="2"><strong>'.cjoAddon::translate(3,"label_select_tables").'</strong></td>'.
	 '				</tr>'.
	 '				<tr>'.
	 '					<td width="10">&nbsp;</td>'.
	 '					<td>'.$sub_table.'</td>'.
	 '				</tr>'.
	 '  		</tbody>'.
	 '		</table>'.
	 '		'.
	 '		<p><input type="text" size="20" name="export_file" class="inp94" value="'.$export_file.'" /></p>'.
	 '		<p><input type="radio" id="expdown_server" name="download" value="0"'.cjoAssistance::setChecked($download, array(1),false).'/> <label for="expdown_server">'.cjoAddon::translate(3,'label_save_on_server').'</label> &nbsp;'.
	 '		<input type="radio" id="expdown_download" name="download" value="1"'.cjoAssistance::setChecked($download, array(1)).'/> <label for="expdown_download">'.cjoAddon::translate(3,'label_save_as_file').'</label></p>'.
	 '				'.$buttons->_get().
	 '				</form>'.
						'</td>'.
	 '				</tr>'.
	 '  		</tbody>'.
	 '		</table>'.
	 '	</div>'.
	 '</div>';
?>
<script type="text/javascript">
/* <![CDATA[ */
$(function(){
		
     $('input[id^="expdirs_"]').click(function() {
     	$('#exptype_sql').removeAttr('checked');
     	$('#exptype_files').attr('checked', 'checked');
     });
    
      $('#exptype_sql').click(function() {
     	$('input[id^="expdirs_"]').removeAttr('checked');
     });

	$('tbody .checkbox').click(function(){
        if ($('tbody .checkbox:checked').length > 0 ||
			$(this).is(':checked')) {
			$('#cjoform_submit_button').removeAttr('disabled');
		} else {;
			$('#cjoform_submit_button').attr('disabled','disabled');
		}
	});

	$('tbody .check_all').click(function(){
		if($(this).is(':checked')){
			$('tbody .checkbox')
				.attr('checked','checked');
			$('#cjoform_submit_button')
				.removeAttr('disabled');
		}
		else {
			$('tbody .checkbox').removeAttr('checked');
			$('#cjoform_submit_button').attr('disabled','disabled');
		}
	});

	if ($('tbody .checkbox:checked').length > 0) {
		$('#cjoform_submit_button').removeAttr('disabled');
	} else {
		$('#cjoform_submit_button').attr('disabled','disabled');
	}
});
/* ]]> */
</script>