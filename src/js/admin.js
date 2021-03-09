// gestion des blocs multilingues :
var groups = document.querySelectorAll('.scms-group'), i;
for (i = 0; i < groups.length; ++i) {
	var labels = groups[i].querySelectorAll('.scms-group-labels > label'), j;
	for (j = 0; j < labels.length; ++j) {
		labels[j].addEventListener("click", function() {
			var _label = this;
			var group = _label.parentNode.parentNode;
			console.log(group);
			var labels = group.querySelectorAll('.scms-group-labels > label');
			var inputs = group.querySelectorAll('.scms-group-inputs > .scms-input');
			console.log(inputs);
			var i = 0;
			var currentIndex = 0;
			for (i = 0; i < labels.length; ++i) {
				if(labels[i]===_label)
					currentIndex = i;
				labels[i].className = '';
			}
			for (i = 0; i < inputs.length; ++i) {
				console.log(inputs[i]);
				inputs[i].style.display = 'none';
			}
			console.log(currentIndex);
			labels[currentIndex].style.display = 'inline-block';
			labels[currentIndex].className = 'active';
			inputs[currentIndex].style.display = 'block';
		});
	}
	labels[0].click();
}

// initializer tinymce normalement
// initializer tinymce normalement
tinymce.init({
	selector: "textarea",
	language: 'fr_FR',
	language_url : window['tinymcelang'], // choix du fichier de langue tinymce
	max_height: 400,
	filemanager: window['filemanagerurl'], // <---- choisir le fichier php a executer, default: filemanager
	plugins: [
		"advlist autolink autoresize lists link image charmap print preview anchor",
		"searchreplace visualblocks code fullscreen",
		"insertdatetime media table paste",
	],
	toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image",

});
// initializer les autres champs de selection de fichier :
SLFileManager.init({
	selector:'input[type="filepicker"]', // selection des champs a convertir
});

// fonction spéciale pour mot de passe
function editPassword(el) {
	let title = el.querySelector('h5');
	el = el.querySelector('input');
	el.value = '';
	el.disabled = false;
	title.innerHTML = 'Nouveau mot de passe';
	document.getElementById('confirmPassword').style.display = 'block';
}

// gestion des slugs :
var slugs = document.querySelectorAll('.scms-slug'), j;
for (j = 0; j < slugs.length; ++j) {
	slugs[j].addEventListener("click", function() {
		this.querySelector('input').disabled = !this.querySelector('input').disabled;
		this.querySelector('input').placeholder = '';
		if(!this.querySelector('input').disabled)
			this.querySelector('input').focus();
		else
			this.querySelector('input').placeholder = '{privé}';
	});
}