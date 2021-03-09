<?php 
/**
 * Server side file manager for SLFileManager.js
 *
 * @author Slynt <theo@slynt.com>
 */

$allowed = str_ireplace(' ', '', isset($_GET['type']) ? $_GET['type'] : '*');
$dir = str_ireplace(' ', '', isset($_GET['dir']) ? $_GET['dir'] : '');

$UPLOADS_PATH = rtrim(SL_UPLOADS_PATH.$dir,"/").'/';
$UPLOADS_URL = rtrim(SL_UPLOADS_URL.$dir,"/").'/';

if(isset($_POST["delete"])) {
	$path = rawurldecode($_POST["delete"]);
	unlink($path);
}
elseif(isset($_GET["upload_attempt"])) {
	$error = false;
	if(!isset($_FILES['upload']) && !empty($_FILES)) {
		foreach($_FILES as $k=>$v) {
			if(stripos($k, 'upload')===0)
				$_FILES['upload'][] = $v;
		}
	} elseif(isset($_FILES['upload'])) { 
		if(is_array($_FILES['upload']["name"])) {
			$upls=$_FILES['upload'];
			$_FILES['upload'] = array();
			foreach($upls["name"] as $k=>$u) {
				$_FILES['upload'][] = array('name'=>$upls["name"][$k], 'tmp_name'=>$upls["tmp_name"][$k], 'size'=>$upls["size"][$k]);
			}
			unset($upls);
		} else $_FILES["upload"] = array($_FILES["upload"]);
	}
	if(isset($_FILES["upload"])) {
		$count = 0;
		foreach($_FILES["upload"] as $k=>$f) {
			if(!$f["tmp_name"]) {
				continue;
			}
			$target_file = $UPLOADS_PATH . uniqid() . '-' . preg_replace('/[^a-z0-9\_\-\+\.]/i', '_', basename($f["name"]));
			$imageFileType = mime_content_type($f["tmp_name"]);
			if ($f["size"] > 5000000) {
				$error = "Ce fichier est trop gros !";
			}
			elseif($allowed!='*' && !in_array($imageFileType, explode(',', $allowed))) {
				$error = "Cette extension de fichier n'est pas valide.";
			}
			if (!$error && move_uploaded_file($f["tmp_name"], $target_file)) {
				// OK !
			} elseif(!$error) {
				$error = "Une erreur inattendue s'est produite.";
			}
			$count++;
		}
		if(!$error && $count<count($_FILES["upload"]))
			$error = "Ce fichier est tros gros !";
	} else $error = "Ce fichier est trop gros !";
	if(isset($_GET['ajax'])) {
		echo json_encode(array('error'=>$error));
		exit();
	} elseif($error) echo '<p style="color:red">'.$error.'</p>';
}

?>
<style>
.hov { cursor:pointer;text-align:center;position:absolute;top:0;left:0;right:0;bottom:0;z-index:90;background-color:rgba(80,80,80,0.2);opacity:0.5; } 
.hov:hover { background-color:rgba(80,80,80,0.1);box-shadow: 0 0 0 2px #65d9d6; } 
.hovdir { -webkit-box-shadow:inset 0px 0px 20px 10px rgba(255,255,255,0.5);-moz-box-shadow:inset 0px 0px 20px 10px rgba(255,255,255,0.5);box-shadow:inset 0px 0px 20px 10px rgba(255,255,255,0.5);background-color:rgba(0,170,210,0.4);border-radius:10px; }
.hovdir:hover { background-color:rgba(0,170,210,0.2); }
.del { position:absolute;bottom:2px;right:2px;z-index:95;text-decoration:none;color:#fff;font-weight:bold;background-color:#ef5350;border-radius:50%;line-height:8px;padding:9px 6px; } 
.del:hover { background-color: #ff1744; }
body, html {
	font-family: 'Lato', sans-serif;
	margin:0;
	width:100%;
	height:100%;
	position: relative;
}
#drop_zone {
	text-align:center;
	padding:20px 10px;
	position: relative;
	border:4px dashed #65d9d6;
	margin:10px;
}

.dragover {
	background-color: rgba(100, 200, 200, 0.15);
	border: 4px solid #2f9c99 !important;
}
#overlay { background-color:rgba(0,0,0,0.1);position:absolute;top:0;left:0;bottom:0;right:0;z-index:99;backdrop-filter: blur(10px); }
.lds-ring {position: absolute;width: 80px;height: 80px;top:0;left:0;right:0;bottom:0;margin:auto;}
.lds-ring div {box-sizing: border-box;display: block;position: absolute;width: 64px;height: 64px;margin: 8px;border: 8px solid #fff;border-radius: 50%;animation: lds-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;border-color: #fff transparent transparent transparent;}
.lds-ring div:nth-child(1) {animation-delay: -0.45s;}.lds-ring div:nth-child(2) {animation-delay: -0.3s;}.lds-ring div:nth-child(3) {animation-delay: -0.15s;}
@keyframes lds-ring {0% {transform: rotate(0deg);}100% {transform: rotate(360deg);}}

</style>
<div id="overlay" style="display:none;"><div class="lds-ring"><div></div><div></div><div></div><div></div></div></div>
<div id="drop_zone">
	<span>D&eacute;posez un fichier</span>
	<form style="margin:0" action="filemanager.php<?php echo '?type='.$allowed.'&dir='.$dir; ?>&upload_attempt" method="post" enctype="multipart/form-data">
		<i style="color:#bbb">ou</i> <input id="upload" type="file" name="upload[]" <?php if($allowed!=='*') echo 'accept="'.$allowed.'"'; ?> multiple/>
	</form>
</div>

<hr>

<form id="delete_form" action="filemanager.php<?php echo '?type='.$allowed.'&dir='.$dir; ?>" method="post">
	<input type="hidden" name="delete"/>
</form>

<h2 style="margin-left:10px;margin-bottom:5px;font-weight:normal;position:relative;"><b>Mes fichiers :</b><i><?php if($dir) { echo ' /'.$dir; ?></i><a href="filemanager.php<?php echo '?type='.$allowed.'&dir='; ?>" class="hovdir" style="position:absolute;right:5px;top:5px;font-size:12px;color:#000;text-decoration:none;padding:2px 4px;border:1px solid #444;border-radius:10px;"> < retour dossier parent</a><?php } ?></h2>
<?php
$files_tmp=glob($UPLOADS_PATH.'*',GLOB_BRACE);
$files = array();
foreach($files_tmp as $k=>$file) {
	$mimetype = mime_content_type($UPLOADS_PATH.basename($file));
	if(!is_dir($file) && $allowed!='*' && !in_array($mimetype, explode(',', $allowed)))
		continue;
	$files[] = $file;
}
$tri_type = array();
$tri_date = array();
foreach($files as $k=>$v) {
	$tri_type[$k] = is_dir($v);
	$tri_date[$k] = filemtime($v);
}
array_multisort($tri_type, SORT_DESC, $tri_date, SORT_DESC, $files);
print('<div style="margin-left:5px;margin-right:5px;">');
print('<table width="'.(count($files)>=3 ? '100' : (count($files)>=2 ? '66' : '33')).'%">');
foreach($files as $k=>$file){
	$mimetype = mime_content_type($UPLOADS_PATH.basename($file));
	if(!$k%4) echo '<tr>';
	$is_showable = explode('/',$mimetype)[0]=='image' && (explode('/',$mimetype)[1]=='jpeg' || explode('/',$mimetype)[1]=='png');
	if(is_dir($file))
		print('<td style="position:relative;"><img src="https://via.placeholder.com/150" style="visibility:hidden" width="100%"/><div class="hov hovdir" onclick="location.href=\'filemanager.php?type='.$allowed.'&dir='.trim(str_ireplace(SL_UPLOADS_PATH,'',$file),"/").'\';"><span style="position:absolute;top:40%;left:1em;right:1em;word-break: break-all;"><i>Dossier :</i><br/>/'.htmlentities(trim(str_ireplace(SL_UPLOADS_PATH,'',$file),"/")).'</span></div></td>');
	else {
		print('<td style="position:relative;background-size:cover;'.($is_showable?'background-image:url('.addslashes($UPLOADS_URL.basename($file)).')':'').'"><img src="https://via.placeholder.com/150" style="visibility:hidden" width="100%"/><div class="hov" data-src="'.addslashes($UPLOADS_URL.basename($file)).'" onclick="choose(this)">'.(!$is_showable?'<span style="position:absolute;top:40%;left:1em;right:1em;word-break: break-all;">'.htmlentities(basename($file)).'</span>':'').'</div><a href="#" onclick="document.getElementById(\'delete_form\').querySelector(\'input\').value = \''.rawurlencode($UPLOADS_PATH.basename($file)).'\'; document.getElementById(\'delete_form\').submit();" onclick="return confirm(\'Etes vous sur de vouloir supprimer ce fichier ?\')" class="del">&#10006;</a></td>');
	}
	if($k%4==3) echo '</tr>';
}
print('</table>');
print('</div>');
?>

<script>
function choose(el) {
	window.parent.postMessage({mceAction: 'closeManager',url:el.getAttribute('data-src')}, '*');
}
  var element = document.getElementById('drop_zone');
  
  element.addEventListener('dragover', function(e) {
    e.preventDefault();
    e.stopPropagation();
    element.classList.add('dragover');
  });

  element.addEventListener('dragleave', function(e) {
    e.preventDefault();
    e.stopPropagation();
    element.classList.remove('dragover');
  });

  element.addEventListener('drop', function(e) {
    e.preventDefault();
    e.stopPropagation();
    element.classList.remove('dragover');
    triggerCallback(e);
  });

  function triggerCallback(e) {
    var files;
    if(e.dataTransfer) {
      files = e.dataTransfer.files;
    } else if(e.target) {
      files = e.target.files;
    }
	var formData = new FormData();
	var file = files[0];
	for(var i in files)
		formData.append("upload"+i, files[i]);
	var xhr = new XMLHttpRequest();
	document.getElementById('overlay').style.display = 'block';
	xhr.open('POST', 'filemanager.php<?php echo '?type='.$allowed.'&dir='.$dir; ?>&upload_attempt=1&ajax=1', true);
	xhr.onload = function() {
		console.log(xhr.responseText);
		if (xhr.status === 200) {
			if(JSON.parse(xhr.responseText).error)
				alert(JSON.parse(xhr.responseText).error);
			window.location = window.location.href;
		}
	};
	xhr.send(formData);
  }
  
  document.getElementById('upload').addEventListener('change', function() {
	  document.getElementById('overlay').style.display = 'block';
	  this.parentNode.submit();
  }, false);
</script>