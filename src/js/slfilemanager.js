/**
 * A simple file manager for TinyMCE 5
 *
 * @author Slynt <theo@slynt.com>
 *
 *	// initializer tinymce normalement
 *	tinymce.init({
 *		selector: "textarea",
 *		// PARAMETRES SLFileManager : ------------------------------------------------
 *		// choix des types de fichiers acceptes :
 *		mimes: {
 *			'image':'image/jpeg, image/gif, image/png', 
 *			'file':'*', 
 *			'media':'video/mpeg, video/ogg' 
 *		},
 *		// choisir le fichier php a executer, default: filemanager.php
 *		filemanager: 'my-custom-filemanager.php', 
 *	});
 *
 *	// initializer les autres champs de selection de fichier :
 *	SLFileManager.init({
 *		selector:'input[type="filepicker"]', // selection des champs a convertir
 *	});
 *
 */

function SLFileManager() {
	return this;
}
SLFileManager.init = function(params) {
	if(typeof params.selector !== "undefined") {
		if(typeof params.filemanager !== "undefined" && params.filemanager)
			SLFileManager.setFileManagerURL(params.filemanager);
		if(typeof params.directory !== "undefined" && params.directory)
			SLFileManager.setDirectory(params.directory);
		let inputs = document.querySelectorAll(params.selector);
		[].forEach.call(inputs, function(n) {
			let w = document.createElement("label");
			w.style.display = 'inline-block';
			w.style.padding = '5px';
			w.style.marginBottom = '4px';
			w.style.marginRight = '4px';
			w.style.border = '1px solid #ccc';
			let s = document.createElement("section");
			s.style.display = 'inline-block';
			let b = document.createElement("button");
			b.innerHTML = 'Choisir un fichier';
			b.type = 'button';
			b.addEventListener('click', function(event) {
				event.preventDefault();
				let callback = function(url) { 
					this.parentNode.parentNode.querySelector('input').value = url; 
					if(this.parentNode.parentNode.querySelector('div')) {
						if(url)
							this.parentNode.parentNode.querySelector('div').style.background = 'url('+url+')';
						else
							this.parentNode.parentNode.querySelector('div').style.background = '#eee';
						this.parentNode.parentNode.querySelector('div').style.backgroundSize = 'cover';
					}
				}.bind(this);
				SLFileManager.__dialog(callback, typeof this.parentNode.parentNode.querySelector('input').getAttribute('accept')!== "undefined" ? this.parentNode.parentNode.querySelector('input').getAttribute('accept') : 'image/jpeg, image/gif, image/png');
			}, false);
			let el = document.createElement("input");
			let attributes = n.attributes;
			[].forEach.call(attributes, function(a) {
				el.setAttribute(a.name, a.value);
			});
			el.type = 'text';
			s.appendChild(b);
			s.appendChild(el);
			if(el.hasAttribute('thumbnail')) {
				el.removeAttribute('thumbnail');
				let i = document.createElement("div");
				i.style.display = 'inline-block';
				i.style.width = '50px';
				i.style.height = '50px';
				i.style.verticalAlign = 'middle';
				i.style.marginRight = '5px';
				if(i.style.value)
					i.style.background = 'url('+i.value+')';
				else
					i.style.background = '#eee';
				i.style.backgroundSize = 'cover';
				w.appendChild(i);
			}
			w.appendChild(s);
			n.parentNode.replaceChild(w, n);
		});			
	}
}
SLFileManager.mimes = {'image':'image/jpeg, image/gif, image/png', 'file':'*', 'media':'video/mpeg, video/ogg' };
SLFileManager.fileManagerURL = 'filemanager.php';
SLFileManager.directory = '';
SLFileManager.setFileManagerURL = function(url) {
	SLFileManager.fileManagerURL = url;
}
SLFileManager.setDirectory = function(url) {
	SLFileManager.directory = url;
}
SLFileManager.setMimes = function(mimes) {
	SLFileManager.mimes = mimes;
}
SLFileManager.__openDialog = function(callback, mime) {
	tinymce.activeEditor.windowManager.openUrl({
	  title: '', // The dialog's title - displayed in the dialog header
	  height: 640,
	  width: 640,
	  url: SLFileManager.fileManagerURL+'?type='+mime+'&dir='+SLFileManager.directory,
	  onMessage: function (api, data) {
		if(data.mceAction=='closeManager') {
			console.log(data);
			if(data.url)
				callback(data.url, {alt: 'My alt text'});
		}
		api.close();
	  },
	  onCancel: function (api) {
		callback('');
	  }
	}, {callback:callback, mime:mime});		
}
SLFileManager.__dialog = function(callback, mime) {
	if(typeof tinymce === "undefined")
		throw 'Please embed TinyMCE first';
	if(!tinymce.activeEditor) {
		let w = document.createElement("div");
		w.style.display = 'none';
		let d = document.createElement("div");
		d.id = 'SLFileManagerTinyEditor';
		w.appendChild(d);
		document.body.appendChild(w);
		tinymce.init({ selector: "#SLFileManagerTinyEditor", init_instance_callback: function(inst) { SLFileManager.__initTiny(inst); SLFileManager.__openDialog(callback, mime); } });
	} else SLFileManager.__openDialog(callback, mime);
}
SLFileManager.__overrideTinyDefaults = function() {
	if(typeof tinymce !== "undefined")
		tinymce.overrideDefaults({convert_urls: false, file_picker_callback: function(callback, value, meta) {SLFileManager.__dialog(callback, SLFileManager.mimes[meta.filetype]);}, init_instance_callback: function(inst) { SLFileManager.__initTiny(inst); }});	
}
SLFileManager.__initTiny = function(inst) {
	if(typeof inst.settings.filemanager !== "undefined" && inst.settings.filemanager)
		SLFileManager.setFileManagerURL(inst.settings.filemanager);
	if(typeof inst.settings.directory !== "undefined" && inst.settings.directory)
		SLFileManager.setDirectory(inst.settings.directory);
	if(typeof inst.settings.mimes !== "undefined" && inst.settings.mimes)
		SLFileManager.setMimes(inst.settings.mimes);
}
SLFileManager.__overrideTinyDefaults();