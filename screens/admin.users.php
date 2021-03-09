<?php
global $struct, $dbing, $url;

$table = 'users';
$ss_struct = $struct['users'];

if(isset($_POST) && !empty($_POST)) {
	try {
		$data = Table::validateInput($dbing, $table, $_POST);
	} catch (Exception $e) {
		$error = $e;
		$data = FALSE;
	}
	if(isset($_POST['id_user'])) {
		if(isset($_POST['delete'])) {
			$stmt=$dbing->prepare("DELETE FROM ".$table." WHERE ".'id_user'." = ?");
			$stmt->execute(array($_POST['id_user']));
		} else {
			$sets = [];
			$data['name'] = $_POST['name'];
			if(isset($_POST['password']) && strlen($_POST['password'])>5 && User::checkCredentials($dbing, $_POST['email'], $_POST['confirm_password'])) {
				$data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
			}
			foreach($data as $k=>$v) {
				$sets[] = $k.' = ?';
			}
			$data['id_user'] = $_POST['id_user'];
			if(!empty($sets)) {
				$stmt=$dbing->prepare("UPDATE ".$table." SET ".implode(',',$sets)." WHERE ".'id_user'." = ?");
				//var_dump($stmt);exit();
				$stmt->execute(array_values($data));
			}
		}
	} else {
		$dbing->beginTransaction();
		$inserted = true;
		try {
			$data['name'] = $_POST['name'];
			$data['email'] = $_POST['email'];
			$data['password'] = $_POST['password'];
			$data['role'] = $_POST['role'];
			$stmt=$dbing->prepare("INSERT INTO ".$table." (".implode(',',array_keys($data)).") VALUES (".implode(',',array_map(function($e) {return '?';}, $data)).")");
			$stmt->execute(array_values($data));
			$inserted_id = $dbing->lastInsertId();
			$dbing->commit();
		}
		catch(PDOException $e) {
			$inserted = false;
			$dbing->rollBack();
			$error = "Insertion impossible : un utilisateur existe dejà avec cet email.";
		}
		if($inserted)
			Router::redirect(BASE_URL.'/admin/'.$table.'/'.$inserted_id);
	}
}

$ss_entry = false;
if(isset($id)) {
	$stmt = $dbing->prepare("SELECT * FROM ".$table." where ".'id_user'." = ? LIMIT 1");
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
	<h5 style="margin-left:5px;">Liste de vos <b><?= $rubriques[$table]; ?></b>(s) :</h5>
	<ul class="list-group">
	<?php $stmt = $dbing->prepare("SELECT * FROM ".$table."");
	if($stmt->execute(array())) {
		while($row=$stmt->fetch()) {
		?>
		<li class="list-group-item d-flex justify-content-between align-items-center">
			<table class="table-as-line">
			<tr>
			<td><?php echo $row['name']; ?></td>
			<td width="1%" style="white-space:nowrap;">
				<button class="btn btn-info badge-info badge-pill" onclick="location.href='<?php echo BASE_URL.'/admin/'.$table.'/'.$row['id_user']; ?>'">
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
	<hr>
	<button class="btn btn-info" onclick="location.href='<?php echo BASE_URL.'/admin/'.$table.'/create'; ?>';">Créer une nouvelle entrée</button>
	<?php
} else { ?>
	<form style="margin-bottom:1em;" method="post" action="<?php echo BASE_URL; ?>/<?php echo $url; ?>">
		<div class="scms card">
			<div class="card-body">
				<h5 class="card-title">Nom :</h5>
				<input class="form-control" type="text" name="name" value="<?= $ss_entry ? $ss_entry['name'] : ''; ?>"/>
			</div>
		</div>
		<div class="scms card">
			<div class="card-body">
				<h5 class="card-title">Email :</h5>
				<input class="form-control" type="email" name="email" value="<?= $ss_entry ? $ss_entry['email'] : ''; ?>" <?php if($ss_entry) { ?>disabled<?php } ?>/>
				<?php if($ss_entry) { ?><input type="hidden" name="email" value="<?= $ss_entry['email']; ?>"/><?php } ?>
			</div>
		</div>
		<div class="scms card">
			<div class="card-body">
				<h5 class="card-title">Role :</h5>
				<select name="role">
					<option value="admin" <?= $ss_entry && $ss_entry['role']=='admin' ? 'selected' : ''; ?>>Admin</option>
					<option value="editor" <?= $ss_entry && $ss_entry['role']=='editor' ? 'selected' : ''; ?>>Editeur</option>
				</select>
			</div>
		</div>
		<div class="scms card">
			<div class="card-body" <?php if($ss_entry) { ?>onclick="editPassword(this)"<?php } ?>>
				<h5 class="card-title">Mot de passe :</h5>
				<input class="form-control" type="password" name="password" value="" <?php if($ss_entry) { ?>placeholder="Cliquez pour modifier" disabled<?php } ?>/>
			</div>
		</div>
		<div class="scms card" id="confirmPassword" style="display:none">
			<div class="card-body">
				<h5 class="card-title">Confirmer l'ancien mot de passe :</h5>
				<input class="form-control" type="password" name="confirm_password" value=""/>
			</div>
		</div>
	<?php if(isset($id)) { ?>
	<input type="hidden" name="<?php echo 'id_user'; ?>" value="<?php echo $ss_entry['id_user']; ?>"/>
	<button class="btn btn-info">Sauvegarder</button>
	<button class="btn btn-danger" name="delete" value="1">Supprimer</button>
	<?php } else { ?>
	<button class="btn btn-info">Créer</button>
	<?php } ?>
	</form>
<?php } ?>
<?php require_once 'includes/admin.footer.php'; ?>