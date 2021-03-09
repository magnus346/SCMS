<?php
global $dbing, $url;

$fields = Field::getFieldsFromStruct($ss_struct);

$schema = Table::getSchemaFor($dbing, $table);
if(isset($_POST) && !empty($_POST)) {
	try {
		$data = Table::validateInput($dbing, $table, $_POST);
	} catch (Exception $e) {
		$error = $e;
		$data = FALSE;
	}
	if($data!==FALSE) {
		if(isset($_POST['set_order'])) {
			Order::update($dbing, $table, $ss_struct['primary'], $_POST['set_order']['id'], $_POST['set_order']['up'] ? true : false);
		}
		elseif(isset($_POST[$ss_struct['primary']])) {
			if(isset($_POST['delete'])) {
				if(isset($schema['order'])) {
					Order::delete($dbing, $table, $ss_struct['primary'], $_POST[$ss_struct['primary']]);
				}
				$stmt=$dbing->prepare("DELETE FROM ".$table." WHERE ".$ss_struct['primary']." = ?");
				$stmt->execute(array($_POST[$ss_struct['primary']]));
				if(isset($ss_struct['sluggable']) && $ss_struct['sluggable']) {
					Slug::delete($dbing, $table, $_POST[$ss_struct['primary']]);			
				}
			} else {
				$sets = [];
				foreach($data as $k=>$v)
					$sets[] = $k.' = ?';
				if(isset($schema['updated_at']))
					$sets[] = 'updated_at = NOW()';
				$data[$ss_struct['primary']] = $_POST[$ss_struct['primary']];
				if(!empty($sets)) {
					$dbing->beginTransaction();
					try {
						$stmt=$dbing->prepare("UPDATE ".$table." SET ".implode(',',$sets)." WHERE ".$ss_struct['primary']." = ?");
						$stmt->execute(array_values($data));
						if(isset($ss_struct['sluggable']) && $ss_struct['sluggable']) {
							Slug::update($dbing, $table, $_POST[$ss_struct['primary']], $_POST);
						}
						$dbing->commit();
					}
					catch(PDOException $e) {
						$dbing->rollBack();
						$error = "Mise a jour impossible : vérifiez que les slugs n'existent pas déjà.";
					}
				}
			}
		} elseif(!empty($data)) {
			$dbing->beginTransaction();
			$inserted = true;
			try {
				$stmt=$dbing->prepare("INSERT INTO ".$table." (".implode(',',array_keys($data)).") VALUES (".implode(',',array_map(function($e) {return '?';}, $data)).")");
				$stmt->execute(array_values($data));
				$inserted_id = $dbing->lastInsertId();
				if(isset($ss_struct['sluggable']) && $ss_struct['sluggable']) {
					Slug::insert($dbing, $table, $inserted_id, $_POST);	
				}
				$dbing->commit();
			}
			catch(PDOException $e) {
				$inserted = false;
				$dbing->rollBack();
				$error = "Insertion impossible : vérifiez que les slugs n'existent pas déjà.";
			}
			if($inserted) {
				if(isset($schema['order'])) {
					Order::insert($dbing, $table, $ss_struct['primary'], $inserted_id);
				}
				Router::redirect(BASE_URL.'/admin/'.$table.'/'.$inserted_id);
			}
		}
	}
}

$ss_entry = false;
if(isset($id)) {
	$stmt = $dbing->prepare("SELECT * FROM ".$table." where ".$ss_struct['primary']." = ? LIMIT 1");
	if($stmt->execute(array($id))) {
		if($row = $stmt->fetch()) {
			$ss_entry = $row;
		}
	}
	if(!$ss_entry)
		return Router::redirect(BASE_URL.'/admin/'.$table);
}
?>

<?php require_once 'includes/admin.header.php'; ?>
<?php if(isset($error)) { ?>
<div class="alert alert-danger" role="alert"><?= $error; ?></div>
<?php } ?>
<?php if($is_index) { ?>
	<!------------------
	------ INDEX -------
	------------------->
	<h5 style="margin-left:5px;">Liste de vos <b><?= $rubriques[$table]; ?></b>(s) :</h5>
	<ul class="list-group">
	<?php
	$stmt = $dbing->prepare("SELECT * FROM ".$table." ".(isset($schema['order']) ? 'ORDER BY `order` ASC' : ''));	
	if($stmt->execute(array())) {
		$count = 0;
		while($row=$stmt->fetch()) {
			$count++;
			$first_field = array_keys($fields)[0];
			if(is_array($fields[$first_field]))
				$first_field = $first_field.'_'.array_keys($fields[$first_field])[0];
		?>
		<li class="list-group-item d-flex justify-content-between align-items-center">
			<table class="table-as-line">
			<tr>
			<td><?php echo strip_tags(strtok($row[$first_field], "\n")); ?></td>
			<?php if(isset($schema['order'])) { 
				echo Order::getForm($dbing, $table, $row, $ss_struct, $count, $url);
			} ?>
			<?php if(isset($row['created_at'])) { echo '<td width="1%" style="white-space:nowrap;"><span class="material-icons" style="color:#ccc">file_upload</span> '.$row['created_at'].'</td>'; } ?>
			<?php if(isset($row['updated_at'])) { echo '<td width="1%" style="white-space:nowrap;"><span class="material-icons" style="color:#ccc">published_with_changes</span> '.$row['updated_at'].'</td>'; } ?>
			<td width="1%" style="white-space:nowrap;">
				<button class="btn btn-info badge-info badge-pill" onclick="location.href='<?php echo BASE_URL.'/admin/'.$table.'/'.$row[$ss_struct['primary']]; ?>'">
					<span class="material-icons">chevron_right</span>		
				</button>
			</td>
			</tr>
			</table>
		</li>
		<?php
		}
	}
	?>
	</ul>
	<?php 
	if(!isset($ss_struct['maxEntries']) || (isset($ss_struct['maxEntries']) && intval($ss_struct['maxEntries'])>$count)) { ?>
	<hr>
	<button class="btn btn-info" onclick="location.href='<?php echo BASE_URL.'/admin/'.$table.'/create'; ?>';">Créer une nouvelle entrée</button>
	<?php } ?>
	<!-- FIN INDEX -->
	<?php
} else { ?>
	<!------------------
	------ ENTRY -------
	------------------->
	<form style="margin-bottom:1em;" method="post" action="<?php echo BASE_URL; ?>/<?php echo $url; ?>">
	<?php foreach($fields as $k=>$v) {
		if(is_array($v)) { ?>
			<div class="scms scms-group card">
				<div class="card-body">
					<h5 class="card-title"><?= ucfirst(str_replace('_', ' ', __($k))); ?> :</h5>
					<div class="scms-group-labels">
						<?php foreach($v as $l=>$e) { ?>
							<label><img src="<?php echo BASE_URL.'/src/images/flag_'.$l; ?>"/></label>
						<?php } ?>
					</div>
					<div class="scms-group-inputs">
						<?php foreach($v as $l=>$e) { ?>
							<div class="scms-input"><?php echo Field::getHtml($k.'_'.$l, $e, ($ss_entry ? $ss_entry[$k.'_'.$l] : NULL )); ?></div>
						<?php } ?>
					</div>
				</div>
			</div>
		<?php } else { ?>
			<div class="scms card">
				<div class="card-body">
					<h5 class="card-title"><?= ucfirst(str_replace('_', ' ', __($k))); ?></h5>
					<?php echo Field::getHtml($k, $v, ($ss_entry ? $ss_entry[$k] : NULL )); ?>
				</div>
			</div>
		<?php }
	} ?>
	<?php if(isset($id) && isset($ss_struct['sluggable']) && $ss_struct['sluggable']) { 
		echo Slug::getForm($dbing, $table, $id);
	} ?>
	<?php if(isset($id)) { ?>
	<input type="hidden" name="<?php echo $ss_struct['primary']; ?>" value="<?php echo $ss_entry[$ss_struct['primary']]; ?>"/>
	<button class="btn btn-info">Sauvegarder</button>
	<button class="btn btn-danger" name="delete" value="1">Supprimer</button>
	<?php } else { ?>
	<button class="btn btn-info">Créer</button>
	<?php } ?>
	</form>
	<!-- FIN ENTRY -->
<?php } ?>
<?php require_once 'includes/admin.footer.php'; ?>