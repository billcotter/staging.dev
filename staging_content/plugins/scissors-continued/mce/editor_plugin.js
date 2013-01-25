/*
 * Scissors TinyMCE Plugin for Wordpress
 *
 * Allows resizing and cropping of images directly in the TinyMCE editor.
 * Uses code from Marc Hodgins' excellent Advanced Image Scaling plugin,
 * which can be found at http://code.google.com/p/tinymce-plugin-advimagescale/.
 *
 * @license    http://www.opensource.org/licenses/lgpl-3.0.html LGPLv3
 */

(function() {
	var adminUrl;

	tinymce.create('tinymce.plugins.scissors', {

		init : function(ed, url) {
			adminUrl = url.substring(0, url.indexOf('wp-content')) + 'wp-admin/admin-ajax.php';

			// Watch for mousedown (as a fall through to ensure that prepareImage() definitely
			// got called on an image tag before mouseup).
			ed.onMouseDown.add(function(ed, e) {
				var el = tinyMCE.activeEditor.selection.getNode();
				if (el.nodeName == 'IMG')					
					prepareImage(ed, e.target); // prepare image for resizing
				return true;
			});
			
			// Watch for mouseup (catch image resizes)
			ed.onMouseUp.add(function(ed, e) {			
				var el = tinyMCE.activeEditor.selection.getNode();	
				if (el.nodeName == 'IMG') {
					// setTimeout is necessary to allow the browser to complete the resize so we have new dimensions
					setTimeout(function() {
						resampleImage(ed, el);
					}, 100);
				}
				return true;
			});
		},

		getInfo : function() {
			return {
				longname : 'Scissors',
				author : 'Stephan Reiter',
				authorurl : 'http://stephanreiter.info',
				infourl : '',
				version : "1.0"
			};
		}
	});

	var lastDimensions = new Array();

	function prepareImage(ed, el) {
		var dom = ed.dom;
		var elId= dom.getAttrib(el, 'scissors_id');		
		if (!elId) { // is this the first time this image tag has been seen?
			var elId = ed.dom.uniqueId();
			dom.setAttrib(el, 'scissors_id', elId);
			lastDimensions[elId] = {width: dom.getAttrib(el, 'width', el.width), height: dom.getAttrib(el, 'height', el.height)};
		}		
		return elId;
	}

	function resampleImage(ed, el) {
		var dom = ed.dom;
		var elId = prepareImage(ed, el);
		var w = dom.getAttrib(el, 'width'), h = dom.getAttrib(el, 'height');
		if (w != lastDimensions[elId].width || h != lastDimensions[elId].height)
		{
			var classes = dom.getAttrib(el, 'class', '');
			if(classes.indexOf('scissors-resample') < 0)
			{
				classes += (classes == '') ? 'scissors-resample' : ' scissors-resample';
				dom.setAttrib(el, 'class', classes);
			}

			// remember "last dimensions" for next time
			lastDimensions[elId].width = w; lastDimensions[elId].height = h;
		}
	}

	tinymce.PluginManager.add('scissors', tinymce.plugins.scissors);
})();
