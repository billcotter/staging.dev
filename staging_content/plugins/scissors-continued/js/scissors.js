function updateSwfuPostParams(swfu, id)
{
	var post_params = swfu.settings.post_params;
	post_params[id] = (jQuery('#' + id + ':checked').val() == null) ? 0 : 1;
	swfu.setPostParams(post_params);
}

function toInt(f) {
	return f | 0;
}

function scissorsConfigEnableChanged(size) {
	var enabledStr = jQuery('#scissors_watermark_enabled').val();
	if(jQuery('#scissors_watermark_target_' + size + ':checked').val() != null) {
		enabledStr = enabledStr.split('-' + size).join('');
		enabledStr += '+' + size; // set
	} else {
		enabledStr = enabledStr.split('+' + size).join('');
		enabledStr += '-' + size; // unset
	}
	jQuery('#scissors_watermark_enabled').val(enabledStr);
}

function scissorsResizeChanged(id, dim) {
	if(jQuery('#scissorsMaintainAspect-' + id + ':checked').val() == null)
		return;

	var first = '#scissors' + (dim == 0 ? 'Width' : 'Height') + '-' + id;
	var second = '#scissors' + (dim != 0 ? 'Width' : 'Height') + '-' + id;
	var src = jQuery(first).val();
	if(src == '') {
		jQuery(second).val('');
	} else {
		var aspect = toInt(jQuery('#scissorsCurWidth-' + id).val()) / toInt(jQuery('#scissorsCurHeight-' + id).val());
		if(dim == 0 && aspect != 0) aspect = 1 / aspect;
		jQuery(second).val(toInt(src * aspect));
	}
}

function scissorsSelected(id, c) {
	jQuery('#scissorsX-'+id).val(c.x);
	jQuery('#scissorsY-'+id).val(c.y);
	jQuery('#scissorsW-'+id).val(c.w);
	jQuery('#scissorsH-'+id).val(c.h);
	var str = (c.w <= 0 || c.h <= 0) ? '' : '('+c.x+', '+c.y+') - ('+c.x2+', '+c.y2+'), [' + c.w + ' x ' + c.h + ']';
	jQuery('#scissorsSel-'+id).html(str);
}

function scissorsAspectRatio(id) {
	var mode = jQuery('#scissorsCropTarget-'+id).val();
	if(scissors[mode + "AspectRatio"] > 0)
		return scissors[mode + "AspectRatio"];
	else
		return jQuery('#scissorsLockBox-'+id)[0].checked ? (jQuery('#scissorsLockX-'+id).val() / jQuery('#scissorsLockY-'+id).val()) : 0;
}

function scissorsAspectChange(id) {
	jQuery('#scissorsImg-'+id).Jcrop({ aspectRatio: scissorsAspectRatio(id) });
}

function scissorsManualAspectChange(id) {
	jQuery('#scissorsUserAspect-'+id).val(1); // set user aspect ratio flag
	scissorsAspectChange(id);
}

function gcd(x, y) {
	var w;
	while(y != 0) {
		w = x % y;
		x = y; y = w;
	}
	return x;
}

function scissorsStdImgAspectRatio(id) {
	jQuery('#scissorsUserAspect-'+id).val(0); // clear user aspect ratio flag
	var aspectX = jQuery('#scissorsCurWidth-'+id).val();
	var aspectY = jQuery('#scissorsCurHeight-'+id).val();
	var g;
	while((g = gcd(aspectX, aspectY)) > 1) {
		aspectX = toInt(aspectX / g);
		aspectY = toInt(aspectY / g);
	}
	jQuery('#scissorsLockX-'+id).val(aspectX);
	jQuery('#scissorsLockY-'+id).val(aspectY);
	scissorsAspectChange(id);
}

function scissorsCropTargetChange(id) {
	var mode = jQuery('#scissorsCropTarget-'+id).val();
	if(mode == 'chain') {
		jQuery('#scissorsReir-'+id).fadeIn('fast');
	} else {
		jQuery('#scissorsReir-'+id).fadeOut('fast');
	}

	if(scissors[mode + "AspectRatio"] > 0)
		jQuery('#scissorsAspect-'+id).fadeOut('fast', function() { scissorsAspectChange(id); });
	else
		jQuery('#scissorsAspect-'+id).fadeIn('fast', function() { scissorsAspectChange(id); });
}

function scissorsShowCrop(id, imgSrc) {
	jQuery('#scissorsShowBtn-'+id).fadeOut('fast', function() {
		var img = jQuery('#scissorsImg-'+id);
		if(img[0].src == '') {
			var imgUrl = imgSrc + '?rand=' + toInt(Math.random() * 1000000);
			jQuery('#scissorsWaitFld-'+id).fadeIn('fast', function() {
				img.load(function () {
					jQuery('#scissorsWaitFld-'+id).fadeOut('fast', function() {
						jQuery('#scissorsCropPane-'+id).show();
						img.Jcrop({
							boxWidth: 468, bgColor: 'transparent',
							aspectRatio: scissorsAspectRatio(id),
							onChange: function(c) { return scissorsSelected(id, c); },
							onSelect: function(c) { return scissorsSelected(id, c); }
						});
					});	
				})
	    		.attr('src', imgUrl); // force a reload of the image by appending a random query string to the url
			});
		}
		else
			jQuery('#scissorsCropPane-'+id).fadeIn('fast');
	});
	return false;
}

function scissorsAbortCrop(id) {
	jQuery('#scissorsCropPane-'+id).fadeOut('fast', function() {
		jQuery('#scissorsShowBtn-'+id).fadeIn('fast');
	});
	return false;
}

function scissorsResetCrop(id) {
	jQuery('#scissorsImgHost-'+id).html("<img id='scissorsImg-" + id + "' />");
	jQuery('#scissorsSel-'+id).html('');
	jQuery('#scissorsW-'+id).val('-1');
}

function scissorsPostQuery(id, data) {
	jQuery.ajax({
		type: 'POST', url: scissors.ajaxUrl, data: data,
		success: function(msg) {
			var m = msg.split(';');
			if(m.length == 2 && m[0] == 'done') {
				var s = m[1].split('+');
				for(var i = 0; i < s.length; i++) {
					if(s[i] == '*') continue;
					var t = s[i].split(',');
					var size = t[1] + '&nbsp;&times;&nbsp;' + t[2];
					var label = jQuery("label[for='image-size-" + t[0] + '-' + id + "']").filter('.help');
					label.html('(' + size + ')');
					if(t[0] == 'full') {
						// reload the full-size image on next crop and update the displayed size + aspect ratio
						scissorsResetCrop(id);
						jQuery('#scissorsSize-'+id).html(size);
						if(jQuery('#scissorsUserAspect-'+id).val() != 1) {
							// update displayed aspect ratio since it's not user-provided
							var aspectX = t[1], aspectY = t[2], g;
							while((g = gcd(aspectX, aspectY)) > 1) {
								aspectX = toInt(aspectX / g);
								aspectY = toInt(aspectY / g);
							}
							jQuery('#scissorsLockX-'+id).val(aspectX);
							jQuery('#scissorsLockY-'+id).val(aspectY);
						}
						jQuery('#scissorsWidth-' + id).val(t[1]);
						jQuery('#scissorsHeight-' + id).val(t[2]);
						jQuery('#scissorsCurWidth-' + id).val(t[1]);
						jQuery('#scissorsCurHeight-' + id).val(t[2]);
					} else if(t[0] == 'thumbnail') {
						// force a reload of the thumbnail by appending a random query string to the url
						var thumbnail = jQuery('#media-item-'+id);
						if(thumbnail.length == 0) {
							// when the flash uploader is employed media items are named 'media-item-SWFUpload_n_n' with n >= 0
							// we therefore try to locate a scissors pane and navigate up to the media-item-info div object
							thumbnail = jQuery('#scissorsShowBtn-'+id).parents('.media-item-info');
						}						
						thumbnail = thumbnail.find('.thumbnail');

						var imgSrc = thumbnail.attr('src');
						var randIndex = imgSrc.indexOf('?rand=');
						if(randIndex >= 0) imgSrc = imgSrc.substr(0, randIndex); // strip existing rand
						imgSrc += '?rand=' + toInt(Math.random() * 1000000);

						thumbnail.attr('src', imgSrc);
					}
				}
			} else {
				alert(msg);
			}
		},
		complete: function(req) {
			jQuery('#scissorsWaitFld-'+id).fadeOut('fast', function() {
				jQuery('#scissorsShowBtn-'+id).show();
			});
		}
	});
}

function scissorsCrop(id, nonce) {
	var w = parseInt(jQuery('#scissorsW-'+id).val()), h = parseInt(jQuery('#scissorsH-'+id).val());
	if (w > 0 && h > 0) {
		var x = parseInt(jQuery('#scissorsX-'+id).val()), y = parseInt(jQuery('#scissorsY-'+id).val());
		var data = { action: "scissorsCrop", _wpnonce: nonce, post_id: id, x: x, y: y, w: w, h: h,
			target: jQuery('#scissorsCropTarget-'+id).val(), reir: (jQuery('#scissorsReirEnable-'+id)[0].checked ? 1 : 0) };
		jQuery('#scissorsCropPane-'+id).fadeOut('fast', function() {
			jQuery('#scissorsWaitFld-'+id).fadeIn('fast', function() {
				scissorsPostQuery(id, data);
			});
		});
	}
	return false;
}

function scissorsShowResize(id) {
	jQuery('#scissorsShowBtn-'+id).fadeOut('fast', function() {
		jQuery('#scissorsResizePane-'+id).fadeIn('fast');
	});
	return false;
}

function scissorsAbortResize(id) {
	jQuery('#scissorsResizePane-'+id).fadeOut('fast', function() {
		jQuery('#scissorsShowBtn-'+id).fadeIn('fast');
	});
	return false;
}

function scissorsResize(id, nonce) {
	var width = parseInt(jQuery('#scissorsWidth-'+id).val());
	var height = parseInt(jQuery('#scissorsHeight-'+id).val());
	if (width > 0 && height > 0) {
		var data = { action: "scissorsResize", _wpnonce: nonce, post_id: id, width: width, height: height };
		jQuery('#scissorsResizePane-'+id).fadeOut('fast', function() {
			jQuery('#scissorsWaitFld-'+id).fadeIn('fast', function() {
				scissorsPostQuery(id, data);
			});
		});
	}
	return false;
}

function scissorsShowRotate(id) {
	jQuery('#scissorsShowBtn-'+id).fadeOut('fast', function() {
		jQuery('#scissorsRotatePane-'+id).fadeIn('fast');
	});
	return false;
}

function scissorsAbortRotate(id) {
	jQuery('#scissorsRotatePane-'+id).fadeOut('fast', function() {
		jQuery('#scissorsShowBtn-'+id).fadeIn('fast');
	});
	return false;
}

function scissorsRotate(id, nonce) {
	var angle = parseInt(jQuery('#scissorsRotateAngle-'+id).val());
	if(angle == -90 || angle == 90 || angle == 180) {
		var data = { action: "scissorsRotate", _wpnonce: nonce, post_id: id, angle: angle };
		jQuery('#scissorsRotatePane-'+id).fadeOut('fast', function() {
			jQuery('#scissorsWaitFld-'+id).fadeIn('fast', function() {
				scissorsPostQuery(id, data);
			});
		});
	}
	return false;
}

function scissorsShowWatermarks(id) {
	jQuery('#scissorsShowBtn-'+id).fadeOut('fast', function() {
		jQuery('#scissorsWatermarkPane-'+id).fadeIn('fast');
	});
	return false;
}

function scissorsAbortWatermark(id) {
	jQuery('#scissorsWatermarkPane-'+id).fadeOut('fast', function() {
		jQuery('#scissorsShowBtn-'+id).fadeIn('fast');
	});
	return false;
}

function scissorsWatermarkStateChanged(id, size) {
	var enabledStr = jQuery('#scissors_watermarking_state-'+id).val();
	if(jQuery('#scissors_watermark_target_' + size + '-' + id + ':checked').val() != null) {
		enabledStr = enabledStr.split('-' + size).join('');
		enabledStr += '+' + size; // set
	} else {
		enabledStr = enabledStr.split('+' + size).join('');
		enabledStr += '-' + size; // unset
	}
	jQuery('#scissors_watermarking_state-'+id).val(enabledStr);
}

function scissorsWatermark(id, nonce) {
	var data = { action: "scissorsWatermark", _wpnonce: nonce, post_id: id, state: jQuery('#scissors_watermarking_state-'+id).val() };
	jQuery('#scissorsWatermarkPane-'+id).fadeOut('fast', function() {
		jQuery('#scissorsWaitFld-'+id).fadeIn('fast', function() {
			scissorsPostQuery(id, data);
		});
	});
	return false;
}
