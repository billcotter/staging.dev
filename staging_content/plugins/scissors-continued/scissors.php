<?php
/*
Plugin Name: Scissors Continued
Plugin URI: http://dev.huiz.net
Description: Scissors Continued enhances WordPress' handling of images by introducing cropping, resizing, rotating, and watermarking functionality. Works from WordPress v2.9 and up.
Version: 2.1
Author: A. Huizinga
Author URI: http://www.huiz.net/

Original Plugin Name: Scissors
Original Plugin URI: http://vimeo.com/7363026
Original Version: 1.3.7
Original Author: Stephan Reiter
Original Author URI: http://stephanreiter.info/

Copyright (C) 2008  Stephan Reiter <stephan.reiter@gmail.com>
Copyright (C) 2011  A. Huizinga <anton@huiz.net>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if(version_compare($wp_version, '2.9', '<')) // integration check begin
{
	// Sorry, WordPress 2.8 and lower are not supported!
	// You need Scissors v1.3.7 or you could upgrade WordPress.
	// Anton, February 2011
}
else
{

function scissors_set_memory_limit()
{
	@ini_set('memory_limit', '256M');
	//@ini_set('memory_limit', WP_MEMORY_LIMIT);
}

$scissors_dirname = plugin_basename(dirname(__FILE__));
$scissors_locale_dir = PLUGINDIR . '/' . $scissors_dirname . '/languages';
load_plugin_textdomain('scissors', $scissors_locale_dir);

function scissors_create_image($width, $height)
{
	if($width <= 0 || $height <= 0)
		return FALSE;

	$image = imagecreatetruecolor($width, $height);
	if(is_resource($image))
	{
		// set default alpha-blending mode
		imagealphablending($image, false);
		imagesavealpha($image, true);
	}
	return $image;
}

function scissors_get_file_mime_type($filename)
{
	if(!file_exists($filename)) return '';
	$size = getimagesize($filename);
	return isset($size['mime']) ? $size['mime'] : '';
}

function scissors_supports_imagetype($mime_type)
{
	if(function_exists('imagetypes'))
	{
		switch($mime_type)
		{
		case 'image/jpeg': return (imagetypes() & IMG_JPG) != 0;
		case 'image/png': return (imagetypes() & IMG_PNG) != 0;
		case 'image/gif': return (imagetypes() & IMG_GIF) != 0;
		default: return FALSE;
		}
	}
	else
	{
		switch($mime_type)
		{
		case 'image/jpeg': return function_exists('imagecreatefromjpeg');
		case 'image/png': return function_exists('imagecreatefrompng');
		case 'image/gif': return function_exists('imagecreatefromgif');
		default: return FALSE;
		}		
	}
}

function scissors_load_image($filename, $mime_type)
{
	if(!file_exists($filename))
		return FALSE;

	if(!scissors_supports_imagetype($mime_type))
		return FALSE;

	switch($mime_type)
	{
	case 'image/jpeg': $image = imagecreatefromjpeg($filename); break;
	case 'image/png': $image = imagecreatefrompng($filename); break;
	case 'image/gif': $image = imagecreatefromgif($filename); break;
	default: $image = FALSE; break;
	}

	if(is_resource($image))
	{
		// set default alpha-blending mode
		imagealphablending($image, false);
		imagesavealpha($image, true);
	}

	return $image;
}

function scissors_save_image($image, $filename, $mime_type)
{
	if(!scissors_supports_imagetype($mime_type))
		return FALSE;

	// set default alpha-blending mode
	imagealphablending($image, false);
	imagesavealpha($image, true);

	switch($mime_type)
	{
	case 'image/jpeg': return imagejpeg($image, $filename, 90);
	case 'image/png': return imagepng($image, $filename);
	case 'image/gif': return imagegif($image, $filename);
	default: return FALSE;
	}
}

// Automatic resizing functionality -------------------------------------------

function scissors_get_fullpath_from_metadata($metadata)
{
	$upload_path = trim(get_option('upload_path'));
	if(empty($upload_path)) $upload_path = WP_CONTENT_DIR . '/uploads';
	$upload_path = path_join(ABSPATH, $upload_path);
	$filename = path_join($upload_path, $metadata['file']);
	return file_exists($filename) ? $filename : FALSE;
}

function scissors_resize_auto($metadata)
{
	$srcW = intval($metadata['width']);
	$srcH = intval($metadata['height']);
	if($srcW <= 0 || $srcH <= 0)
		return $metadata; // image dimensions are fishy ...

	// skip full if temporarily disabled
	if(array_key_exists('scissorsSkipFullResize', $_REQUEST) && intval($_REQUEST['scissorsSkipFullResize']) != 0)
		$sizes = array('large', 'medium');
	else
		$sizes = array('full', 'large', 'medium');
	
	$filename = FALSE; $src = NULL; $src_mime_type = '';
	foreach($sizes as $size)
	{
		$adaptive = (get_option("{$size}_adaptive") == '1');
		if($size != 'full')
		{
			if(!$adaptive) continue; // skip, standard intermediate images have already been created ...
			if(!is_array($metadata) || !isset($metadata['sizes']) || !isset($metadata['sizes'][$size])) continue; // not available
		}

		// calculate scaled image dimensions
		$dstW = $srcW; $dstH = $srcH;
		$maxW = intval(get_option("{$size}_size_w"));
		$maxH = intval(get_option("{$size}_size_h"));
		$scaleW = (!$adaptive || $srcW >= $srcH);
		$scaleH = (!$adaptive || $srcH >= $srcW);
		if($scaleW && $maxW > 0 && $dstW > $maxW)
		{
			$dstH = $dstH * ($maxW / $dstW);
			$dstW = $maxW;
		}
		if($scaleH && $maxH > 0 && $dstH > $maxH)
		{
			$dstW = $dstW * ($maxH / $dstH);
			$dstH = $maxH;
		}
		if($dstW == $srcW && $dstH == $srcH)
			continue; // no need to resize the image

		if(!$filename)
		{
			$filename = scissors_get_fullpath_from_metadata($metadata);
			if(!$filename) return $metadata; // failed to locate the file
			scissors_set_memory_limit();

			// load the full size image
			$src_mime_type = scissors_get_file_mime_type($filename);
			$src = scissors_load_image($filename, $src_mime_type);
			if(!is_resource($src)) return $metadata; // failed to load the file
		}

		// create a resized copy, overwrite the intermediate file and update the metadata
		$dst = scissors_create_image($dstW, $dstH);
		if(is_resource($dst))
		{
			if(imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH))
			{
				if($size == 'full')
				{
					if(scissors_save_image($dst, $filename, $src_mime_type))
					{
						$metadata['width'] = $dstW; $metadata['height'] = $dstH;
						list($uwidth, $uheight) = wp_shrink_dimensions($metadata['width'], $metadata['height']);
						$metadata['hwstring_small'] = "height='$uheight' width='$uwidth'";
					}
				}
				else
				{
					$fname = path_join(dirname($filename), $metadata['sizes'][$size]['file']);
					if(scissors_save_image($dst, $fname, $src_mime_type))
					{
						$metadata['sizes'][$size]['width'] = $dstW;
						$metadata['sizes'][$size]['height'] = $dstH;
					}
				}
			}
			imagedestroy($dst);
		}
	}
	if(is_resource($src)) imagedestroy($src);
	return $metadata;	
}

add_filter('wp_generate_attachment_metadata', 'scissors_resize_auto');

function scissors_fullsize_fields()
{
?><fieldset><legend class="hidden"><?php _e('Full size', 'scissors') ?></legend>
<label for="full_size_w"><?php _e('Max Width'); ?></label>
<input name="full_size_w" type="text" id="full_size_w" value="<?php form_option('full_size_w'); ?>" class="small-text" />
<label for="full_size_h"><?php _e('Max Height'); ?></label>
<input name="full_size_h" type="text" id="full_size_h" value="<?php form_option('full_size_h'); ?>" class="small-text" />
<span class="setting-description"><?php _e('Specify 0 to disable resizing in a particular dimension.', 'scissors') ?></span><br />
<input name="full_adaptive" type="checkbox" id="full_adaptive" value="1" <?php checked('1', get_option('full_adaptive')); ?>/>
<label for="full_adaptive"><?php _e('Adaptive mode: Limit width of landscape images and height of portrait images.', 'scissors') ?></label>
</fieldset>

<script type="text/javascript">
function extendWithCropCheckbox(size, checked, desc) {
	var cur = jQuery('#'+size+'_size_h').parent('fieldset').html();
	var txt = '<br/><input name="'+size+'_crop" type="checkbox" id="'+size+'_crop" value="1" ' + (checked ? 'checked="checked"' : '') + '/>';
	txt += ' <label for="'+size+'_crop">'+desc+'</label>';
	jQuery('#'+size+'_size_h').parent('fieldset').html(cur+txt);
}
extendWithCropCheckbox('medium', <?php echo (get_option('medium_crop') == '1') ? 'true' : 'false'; ?>,
	"<?php _e('Crop medium images to exact dimensions (normally medium images are proportional)', 'scissors'); ?>");
extendWithCropCheckbox('large', <?php echo (get_option('large_crop') == '1') ? 'true' : 'false'; ?>,
	"<?php _e('Crop large images to exact dimensions (normally large images are proportional)', 'scissors'); ?>");

function extendWithAdaptiveCheckbox(size, checked) {
	var desc = "<?php _e('Adaptive mode: Limit width of landscape images and height of portrait images.', 'scissors') ?>";
	var cur = jQuery('#'+size+'_size_h').parent('fieldset').html();
	var txt = '<br/><input name="'+size+'_adaptive" type="checkbox" id="'+size+'_adaptive" value="1" ' + (checked ? 'checked="checked"' : '') + '/>';
	txt += ' <label for="'+size+'_adaptive">'+desc+'</label>';
	jQuery('#'+size+'_size_h').parent('fieldset').html(cur+txt);
}
extendWithAdaptiveCheckbox('medium', <?php echo (get_option('medium_adaptive') == '1') ? 'true' : 'false'; ?>);
extendWithAdaptiveCheckbox('large', <?php echo (get_option('large_adaptive') == '1') ? 'true' : 'false'; ?>);
</script><?php
}

function scissors_autosize_activation()
{
	add_option('full_size_w', '0');
	add_option('full_size_h', '0');
	add_option('full_adaptive', '0');
	add_option('medium_crop', '0');
	add_option('medium_adaptive', '0');
	add_option('large_crop', '0');
	add_option('large_adaptive', '0');
}

function scissors_autosize_add_settings()
{
	register_setting('media', 'full_size_w', 'intval');
	register_setting('media', 'full_size_h', 'intval');
	register_setting('media', 'full_adaptive', 'intval');
	register_setting('media', 'medium_crop', 'intval');
	register_setting('media', 'medium_adaptive', 'intval');
	register_setting('media', 'large_crop', 'intval');
	register_setting('media', 'large_adaptive', 'intval');

	add_settings_field('scissors_fullsize_fields', __('Full size', 'scissors'), 'scissors_fullsize_fields', 'media', 'default');
}

add_action('admin_init', 'scissors_autosize_add_settings');
register_activation_hook(__FILE__, 'scissors_autosize_activation'); 


function scissors_cropping()
{
?><p><?php _e('The Scissors image cropping functionality can be configured here ...', 'scissors') ?></p><?php
}

function scissors_cropping_aspectmode()
{
?><fieldset><legend class="hidden"><?php _e('Default aspect ratio', 'scissors') ?></legend>
<input id="scissors_crop_defaultaspect_image" type="radio" value="0" name="scissors_crop_defaultaspect" <?php checked('0', get_option('scissors_crop_defaultaspect')); ?>/>
<label for="scissors_crop_defaultaspect_image"><?php _e('Maintain original aspect ratio.', 'scissors'); ?></label><br />
<input id="scissors_crop_defaultaspect_user" type="radio" value="1" name="scissors_crop_defaultaspect" <?php checked('1', get_option('scissors_crop_defaultaspect')); ?>/>
<?php
$field = "<input style='width:2em;text-align:center;' class='small-text' type='text' name='scissors_crop_useraspectx' value='" . attribute_escape(get_option('scissors_crop_useraspectx')) . "' />:<input style='width:2em;text-align:center;' class='small-text' type='text' name='scissors_crop_useraspecty' value='" . attribute_escape(get_option('scissors_crop_useraspecty')) . "' />";
echo sprintf(__('Lock aspect ratio to %s.', 'scissors'), $field);
?><br />
<input id="scissors_crop_defaultaspect_none" type="radio" value="2" name="scissors_crop_defaultaspect" <?php checked('2', get_option('scissors_crop_defaultaspect')); ?>/>
<label for="scissors_crop_defaultaspect_none"><?php _e('Do not lock aspect ratio.', 'scissors'); ?></label>
</fieldset><?php
}

function scissors_cropping_reir()
{
?><fieldset><legend class="hidden"><?php _e('Default REIR state', 'scissors') ?></legend>
<input id="scissors_crop_defaultreir" type="checkbox" value="1" name="scissors_crop_defaultreir" <?php checked('1', get_option('scissors_crop_defaultreir')); ?>/>
<label for="scissors_crop_defaultreir"><?php _e('enabled', 'scissors'); ?></label>
</fieldset><?php
}

function scissors_cropping_activation()
{
	add_option('scissors_crop_defaultaspect', '0'); // 0 ... image, 1 ... user-supplied, 2 ... disabled
	add_option('scissors_crop_useraspectx', '4');
	add_option('scissors_crop_useraspecty', '3');
	add_option('scissors_crop_defaultreir', '0'); // 0 ... disabled, 1 ... enabled
}

function scissors_cropping_add_settings()
{
	register_setting('media', 'scissors_crop_defaultaspect', 'intval');
	register_setting('media', 'scissors_crop_useraspectx', 'intval');
	register_setting('media', 'scissors_crop_useraspecty', 'intval');
	register_setting('media', 'scissors_crop_defaultreir', 'intval');

	add_settings_section('cropping', __('Cropping', 'scissors'), 'scissors_cropping', 'media');
	add_settings_field('scissors_cropping_aspectmode', __('Default aspect ratio', 'scissors'), 'scissors_cropping_aspectmode', 'media', 'cropping');
	add_settings_field('scissors_cropping_reir', __('Default REIR state', 'scissors') . " <a href=\"http://www.useit.com/alertbox/9611.html\" target=\"_blank\">[?]</a>", 'scissors_cropping_reir', 'media', 'cropping');
}

add_action('admin_init', 'scissors_cropping_add_settings');
register_activation_hook(__FILE__, 'scissors_cropping_activation');

// Watermarking support -------------------------------------------------------

function scissors_get_global_watermarking_path()
{
	if(is_dir(WP_CONTENT_DIR . '/mu-plugins'))
	{
		$upload_path = trim(get_option('upload_path'));
		if(empty($upload_path)) $upload_path = WP_CONTENT_DIR . '/uploads';
		$upload_path = path_join(ABSPATH, $upload_path);
		return path_join($upload_path, get_option('scissors_watermark_path'));
	}
	else
		return path_join(ABSPATH, get_option('scissors_watermark_path'));
}

function scissors_get_watermark_configuration()
{
	$filename = scissors_get_global_watermarking_path();
	list($ww, $wh) = file_exists($filename) ? getimagesize($filename) : array(0, 0);
	return array('ver' => 1, // NOTE: increment this if features are added and add appropriate checks to load_configuration
		'ww' => $ww, 'wh' => $wh,
		'doscale' => get_option('scissors_watermark_size'),
		'scale' => get_option('scissors_watermark_size_relative'),
		'horz' => get_option('scissors_watermark_halign'),
		'vert' => get_option('scissors_watermark_valign'));
}

function scissors_get_relative_filepath($filename)
{
	$upload_path = trim(get_option('upload_path'));
	if(empty($upload_path)) $upload_path = WP_CONTENT_DIR . '/uploads';
	$upload_path = path_join(ABSPATH, $upload_path);
	return str_replace($upload_path . '/', '', $filename);
}

function scissors_get_absolute_filepath($filename)
{
	$upload_path = trim(get_option('upload_path'));
	if(empty($upload_path)) $upload_path = WP_CONTENT_DIR . '/uploads';
	$upload_path = path_join(ABSPATH, $upload_path);
	return path_join($upload_path, $filename);
}

function scissors_get_postid_from_fullfilename($filename)
{
	global $wpdb;
	$relfile = scissors_get_relative_filepath($filename);
	$post = $wpdb->get_row($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_value = %s LIMIT 1", $relfile));
	return ($post != null) ? $post->post_id : FALSE;
}

function scissors_save_watermark_configuration($filename, $rectfilename)
{
	$postId = scissors_get_postid_from_fullfilename($filename);
	if(!$postId) return FALSE;

	$config = scissors_get_watermark_configuration();
	$config['rectfilename'] = addslashes(scissors_get_relative_filepath($rectfilename));

	return add_post_meta($postId, "_scissors_watermarking", $config, true) or
		update_post_meta($postId, "_scissors_watermarking", $config);
}

function scissors_load_watermark_configuration($postId)
{
	$config = get_post_meta($postId, '_scissors_watermarking', true);
	if($config == '') return FALSE;
	$config['rectfilename'] = scissors_get_absolute_filepath($config['rectfilename']);
	return $config;
}

function scissors_get_watermarking_state($postId = '')
{
	if($postId != '')
	{
		$state = get_post_meta($postId, '_scissors_watermarking_state', true);
		if($state == 'disabled') $state = '0'; // legacy value handling
		if($state != '') return $state;
	}
	return get_option('scissors_watermark_enabled'); // global configuration
}

function scissors_set_watermarking_state($postId, $state)
{
	return add_post_meta($postId, '_scissors_watermarking_state', $state, true) or
		update_post_meta($postId, '_scissors_watermarking_state', $state);
}

function scissors_is_watermarking_enabled($size = '*', $postId = '', $checkFile = FALSE)
{
	if($size == '*' || $size == '+')
	{
		if($postId != '')
		{
			$sizes = array();
			$metadata = wp_get_attachment_metadata($postId);
			if(is_array($metadata) && isset($metadata['sizes']) && count($metadata['sizes']) > 0)
			{
				foreach($metadata['sizes'] as $s => $value)
					$sizes[] = $s;
			}
		}
		else
			$sizes = apply_filters('intermediate_image_sizes', array('large', 'medium', 'thumbnail'));
		
		$sizes[] = 'full';
		if($size == '*') $sizes[] = 'custom'; // '+' ignores custom

		foreach($sizes as $s)
		{
			if(scissors_is_watermarking_enabled($s, $postId, $checkFile))
				return TRUE;
		}
		return FALSE;
	}

	$str = scissors_get_watermarking_state($postId);
	$enabled = (strlen($str) > 0 && $str[0] == '1'); // legacy value handling (was 0 for none, 1 for all).
	$enabled |= strstr($str, "+$size") != FALSE; // size added?
	$enabled &= strstr($str, "-$size") == FALSE; // size not removed?
	if(!$enabled || !$checkFile) return $enabled;

	$filename = scissors_get_global_watermarking_path();
	list($ww, $wh) = file_exists($filename) ? getimagesize($filename) : array(0, 0);
	return ($ww > 0 && $wh > 0); // make sure the watermark image is valid!
}

function scissors_find_rect_filename($filename)
{
	$rectfilename = FALSE;
	if(file_exists($filename))
	{
		$path_parts = pathinfo($filename);
		$sansext = substr($path_parts['basename'], 0, -(strlen($path_parts['extension']) + 1)); // +1 for the dot
		$pattern = '!' . preg_quote($sansext) . '-[0-9]+-rect' . preg_quote('.' . $path_parts['extension']) . '$!';
		$dir = opendir($path_parts['dirname']);
		while($file = readdir($dir))
		{
			if(preg_match($pattern, $file) == 1)
			{
				$rectfilename = $path_parts['dirname'] . '/' . $file;
				break;
			}					
		}
		closedir($dir);
	}
	return $rectfilename;
}

function scissors_delete_watermark_meta($filename)
{
	$postId = scissors_get_postid_from_fullfilename($filename);
	$config = $postId ? get_post_meta($postId, '_scissors_watermarking', true) : '';
	if($config != '') @unlink(scissors_get_absolute_filepath($config['rectfilename']));
	if($postId) delete_post_meta($postId, '_scissors_watermarking');

	if($config == '')
	{
		// try to find the file manually ... it seems that the
		// metadata in the db is already gone when delete_file is invoked
		$rectfilename = scissors_find_rect_filename($filename);
		if($rectfilename) @unlink($rectfilename);
	}

	return $filename; // needs this to support WP filter chain
}

function scissors_place_watermark($image, $config = '')
{
	if($config == '')
		$config = scissors_get_watermark_configuration();

	$iw = imagesx($image); $ih = imagesy($image);
	$ww = $config['ww']; $wh = $config['wh'];

	// scale down the watermark to occupy the desired area if requested
	if($config['doscale'] == 1)
	{
		$desiredarea = $iw * $ih * ($config['scale'] / 100.0);
		// ($ww * $s) * ($wh * $s) = $desiredArea;
		$s = sqrt($desiredarea / ($ww * $wh));
		if($s < 1) { $ww = $ww * $s; $wh = $wh * $s; }
	}

	// make sure that the watermark fits onto the original image, scale it down if necessary
	if($ww > $iw) { $wh = $wh * ($iw / $ww); $ww = $iw; }
	if($wh > $ih) { $ww = $ww * ($ih / $wh); $wh = $ih; }

	switch($config['horz'])
	{
	default: case 0: $l = 0; break; // left
	case 1: $l = ($iw - $ww) / 2; break; // center
	case 2: $l = $iw - $ww; break; // right
	}

	switch($config['vert'])
	{
	default: case 0: $t = 0; break; // top
	case 1: $t = ($ih - $wh) / 2; break; // middle
	case 2: $t = $ih - $wh; break; // bottom
	}

	return array($l, $t, $ww, $wh);
}

function scissors_build_rect_filename($filename)
{
	$path_parts = pathinfo($filename);
	$sansext = substr($path_parts['basename'], 0, -(strlen($path_parts['extension']) + 1)); // +1 for the dot
	$randid = time(); // add a random number to make access to the rect more difficult (would allow watermark-removal)
	$path_parts['basename'] = "{$sansext}-{$randid}-rect." . $path_parts['extension'];
	return $path_parts["dirname"] . '/' . $path_parts['basename'];
}

// the following functions are referenced by the resampling and resizing/cropping functionality ---

function scissors_remove_watermark($image, $postId)
{
	$config = scissors_load_watermark_configuration($postId);
	if($config)
	{
		$mime_type = scissors_get_file_mime_type($config['rectfilename']);
		$rect = scissors_load_image($config['rectfilename'], $mime_type);
		if(is_resource($rect))
		{
			imagealphablending($image, false);
			imagesavealpha($image, true);
			list($left, $top, $width, $height) = scissors_place_watermark($image, $config);
			imagecopy($image, $rect, $left, $top, 0, 0, $width, $height);
			imagedestroy($rect);
		}
	}
}

function scissors_watermark_image($image)
{
	$result = FALSE;
	$watermark_path = scissors_get_global_watermarking_path();
	$watermark_mime_type = scissors_get_file_mime_type($watermark_path);
	$watermark = scissors_load_image($watermark_path, $watermark_mime_type);
	if(is_resource($watermark))
	{
		imagesavealpha($image, false);
		imagealphablending($image, true);
		list($l, $t, $ww, $wh) = scissors_place_watermark($image);
		$result = imagecopyresampled($image, $watermark, $l, $t, 0, 0, $ww, $wh, imagesx($watermark), imagesy($watermark));
		imagedestroy($watermark);
	}
	return $result;
}

function scissors_watermark_file($filename)
{
	$result = FALSE;
	$mime_type = scissors_get_file_mime_type($filename);
	$image = scissors_load_image($filename, $mime_type);
	if(is_resource($image))
	{
		if(scissors_watermark_image($image))
			$result = scissors_save_image($image, $filename, $mime_type);
		imagedestroy($image);
	}
	return $result;
}

function scissors_rebuild_watermark_meta($image, $mime_type, $postId)
{
	$fullfilename = get_attached_file($postId);

	$done = FALSE;
	if(scissors_is_watermarking_enabled('full', $postId, TRUE)) // only need meta if full image is modified, because all image operations use it as the base
	{
		// determine the place and the size of the watermark if applied to this image
		list($left, $top, $width, $height) = scissors_place_watermark($image);

		// back up the part of the image that will be overlaid with the watermark
		$rect = scissors_create_image($width, $height);
		if(is_resource($rect))
		{
			if(imagecopy($rect, $image, 0, 0, $left, $top, $width, $height))
			{
				$oldconfig = scissors_load_watermark_configuration($postId);
				$rectfilename = ($oldconfig == FALSE) ? scissors_build_rect_filename($fullfilename) : $oldconfig['rectfilename'];
				if(scissors_save_image($rect, $rectfilename, $mime_type))
					$done = scissors_save_watermark_configuration($fullfilename, $rectfilename);
			}
			imagedestroy($rect);
		}
	}

	if(!$done)
		scissors_delete_watermark_meta($fullfilename);						
}

// ------------------------------------------------------------------------------------------------

function scissors_get_postid_from_metadata($metadata)
{
	global $wpdb;
	$post = $wpdb->get_row($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_value = %s LIMIT 1", $metadata['file']));
	return ($post != null) ? $post->post_id : FALSE;
}

function scissors_apply_initial_watermarks($metadata)
{
	// skip if temporarily disabled
	if(array_key_exists('scissorsSkipWatermarking', $_REQUEST) && intval($_REQUEST['scissorsSkipWatermarking']) != 0)
		return $metadata;
	
	$postId = scissors_get_postid_from_metadata($metadata);
	if($postId)
	{
		$fullfilename = get_attached_file($postId);
		if($fullfilename && file_exists($fullfilename))
		{
			scissors_set_watermarking_state($postId, scissors_get_watermarking_state()); // initialize local watermarking state
			if(scissors_is_watermarking_enabled('*', $postId, TRUE))
			{
				// load the full size image
				scissors_set_memory_limit();
				$mime_type = scissors_get_file_mime_type($fullfilename);
				$image = scissors_load_image($fullfilename, $mime_type);
				if(is_resource($image))
				{
					scissors_rebuild_watermark_meta($image, $mime_type, $postId);

					// apply the watermark to the full size image
					if(scissors_is_watermarking_enabled('full', $postId, TRUE))
					{
						if(scissors_watermark_image($image))
							scissors_save_image($image, $fullfilename, $mime_type);
					}

					imagedestroy($image);

					// apply the watermark to the intermediate images
					if(is_array($metadata) && isset($metadata['sizes']) && count($metadata['sizes']) > 0)
					{
						foreach($metadata['sizes'] as $size => $data)
						{
							if(scissors_is_watermarking_enabled($size, $postId, TRUE))
							{
								$filename = path_join(dirname($fullfilename), $data['file']);
								$mime_type = scissors_get_file_mime_type($filename);
								$image = scissors_load_image($filename, $mime_type);
								if(is_resource($image))
								{
									if(scissors_watermark_image($image))
										scissors_save_image($image, $filename, $mime_type);
									imagedestroy($image);
								}
							}
						}
					}
				}
			}
		}
	}
	return $metadata;
}

add_filter('wp_generate_attachment_metadata', 'scissors_apply_initial_watermarks');
add_filter('wp_delete_file', 'scissors_delete_watermark_meta');

function scissors_watermarking()
{
?><p><?php _e('The Scissors plugin supports automatic watermarking of images. Please configure this feature here ...', 'scissors') ?></p><?php
}

// never called, used to make sure that image sizes show up in localization tools
function scissors_dummy()
{
	__('large', 'scissors');
	__('medium', 'scissors');
	__('thumbnail', 'scissors');
	__('full', 'scissors');
	__('custom', 'scissors');
}

function scissors_watermarking_switch()
{
	$enabled = get_option('scissors_watermark_enabled');
	echo "<input name='scissors_watermark_enabled' id='scissors_watermark_enabled' type='hidden' value='$enabled'/>";
	$sizes = apply_filters('intermediate_image_sizes', array('large', 'medium', 'thumbnail'));
	$sizes[] = 'full'; $sizes[] = 'custom';
	foreach($sizes as $size)
	{
		$checked = scissors_is_watermarking_enabled($size) ? "checked" : "";
		echo " <input type='checkbox' id='scissors_watermark_target_$size' $checked";
		echo " onchange=\"scissorsConfigEnableChanged('$size')\"";
		echo "> <label class='align_watermark' for='scissors_watermark_target_$size'>" . __($size, 'scissors') . "</label>";
	}
}

function scissors_watermarking_path()
{
?><fieldset><legend class="hidden"><?php _e('Image', 'scissors') ?></legend>
<input name="scissors_watermark_path" type="text" id="scissors_watermark_path" value="<?php form_option('scissors_watermark_path'); ?>" class="regular-text code" />
<input type="button" class="button" value="<?php _e('Select Image', 'scissors') ?>" onclick="tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true');"/>
<br /><span class="setting-description"><?php _e('Click the button, pick an image in the opening dialog, and use "Insert into Post" to make the selection.', 'scissors') ?></span>
</fieldset><?php
}

function scissors_watermarking_size()
{
?><fieldset><legend class="hidden"><?php _e('Size', 'scissors') ?></legend>
<input id="scissors_watermark_size_absolute" type="radio" value="0" name="scissors_watermark_size" <?php checked('0', get_option('scissors_watermark_size')); ?>/>
<label for="scissors_watermark_size_absolute"><?php _e('Do not resize the watermark.', 'scissors'); ?></label><br />
<input id="scissors_watermark_size_relative" type="radio" value="1" name="scissors_watermark_size" <?php checked('1', get_option('scissors_watermark_size')); ?>/>
<?php
$field = '<input name="scissors_watermark_size_relative" type="text" value="' . attribute_escape(get_option('scissors_watermark_size_relative')) . '" class="small-text" />';
echo sprintf(__('Limit the watermark to %s percent of the destination image area.', 'scissors'), $field);
?>
</fieldset><?php
}

function scissors_watermarking_halign()
{
?><fieldset><legend class="hidden"><?php _e('Horizontal alignment', 'scissors') ?></legend>
<input id="scissors_watermark_halign_left" type="radio" value="0" name="scissors_watermark_halign" <?php checked('0', get_option('scissors_watermark_halign')); ?> />
<label class="align_watermark" for="scissors_watermark_halign_left"><?php _e('Left'); ?></label>
<input id="scissors_watermark_halign_center" type="radio" value="1" name="scissors_watermark_halign" <?php checked('1', get_option('scissors_watermark_halign')); ?> />
<label class="align_watermark" for="scissors_watermark_halign_center"><?php _e('Center'); ?></label>
<input id="scissors_watermark_halign_right" type="radio" value="2" name="scissors_watermark_halign" <?php checked('2', get_option('scissors_watermark_halign')); ?> />
<label class="align_watermark" for="scissors_watermark_halign_right"><?php _e('Right'); ?></label>
</fieldset><?php
}

function scissors_watermarking_valign()
{
?><input id="scissors_watermark_valign_top" type="radio" value="0" name="scissors_watermark_valign" <?php checked('0', get_option('scissors_watermark_valign')); ?> />
<label class="align_watermark" for="scissors_watermark_valign_top"><?php _e('Top'); ?></label>
<input id="scissors_watermark_valign_middle" type="radio" value="1" name="scissors_watermark_valign" <?php checked('1', get_option('scissors_watermark_valign')); ?> />
<label class="align_watermark" for="scissors_watermark_valign_middle"><?php _e('Middle'); ?></label>
<input id="scissors_watermark_valign_bottom" type="radio" value="2" name="scissors_watermark_valign" <?php checked('2', get_option('scissors_watermark_valign')); ?> />
<label class="align_watermark" for="scissors_watermark_valign_bottom"><?php _e('Bottom'); ?></label>
</fieldset><?php
}

function scissors_admin_head_watermark()
{
	global $scissors_dirname;
	if(strstr($_SERVER['REQUEST_URI'], 'options-media'))
	{
		$wpurl = get_bloginfo('wpurl');
		if(is_dir(WP_CONTENT_DIR . '/mu-plugins')) $wpurl .= 'files/'; // special treatment of WPMU installations
		echo "<!-- JS loaded for Scissors in options-media -->\n";
		echo "<script type='text/javascript'>\n/* <![CDATA[ */\n";
		echo "scissors2 = {\n";
		echo "wpUrl: '$wpurl/'\n";
		echo "}\n";
		echo "/* ]]> */\n</script>\n";
		echo "<!-- End of JS loaded for Scissors in options-media -->\n";

		wp_enqueue_script('thickbox');
		wp_enqueue_script('scissors_watermark_select_js', '/' . PLUGINDIR . '/'.$scissors_dirname.'/js/watermark-select.js', array('jquery') );
	}
}

function scissors_watermarking_activation()
{
	add_option('scissors_watermark_enabled', '0');
	add_option('scissors_watermark_path', '');
	add_option('scissors_watermark_size', '1');
	add_option('scissors_watermark_size_relative', '20');
	add_option('scissors_watermark_halign', '2');
	add_option('scissors_watermark_valign', '2');
}

function scissors_watermarking_add_settings()
{
	register_setting('media', 'scissors_watermark_enabled');
	register_setting('media', 'scissors_watermark_path', 'trim');
	register_setting('media', 'scissors_watermark_size');
	register_setting('media', 'scissors_watermark_size_relative', 'intval');
	register_setting('media', 'scissors_watermark_halign');
	register_setting('media', 'scissors_watermark_valign');

	add_settings_section('watermarking', __('Watermarking', 'scissors'), 'scissors_watermarking', 'media');
	add_settings_field('scissors_watermarking_switch', __('Enable', 'scissors'), 'scissors_watermarking_switch', 'media', 'watermarking');
	add_settings_field('scissors_watermarking_path', __('Image', 'scissors'), 'scissors_watermarking_path', 'media', 'watermarking');
	add_settings_field('scissors_watermarking_size', __('Size', 'scissors'), 'scissors_watermarking_size', 'media', 'watermarking');
	add_settings_field('scissors_watermarking_halign', __('Horizontal alignment', 'scissors'), 'scissors_watermarking_halign', 'media', 'watermarking');
	add_settings_field('scissors_watermarking_valign', __('Vertical alignment', 'scissors'), 'scissors_watermarking_valign', 'media', 'watermarking');
}

add_action('admin_print_scripts', 'scissors_admin_head_watermark'); 
add_action('admin_init', 'scissors_watermarking_add_settings');
register_activation_hook(__FILE__, 'scissors_watermarking_activation'); 

function scissors_plugin_settings_link($links)
{
	$settings_link = '<a href="options-media.php">' . __('Settings') . '</a>';
	array_unshift($links, $settings_link);
	return $links;
}

$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'scissors_plugin_settings_link' );

function scissors_post_upload_ui()
{
	$watermarking = scissors_is_watermarking_enabled('+');
	$fullresizing = (intval(get_option("full_size_w")) > 0 || intval(get_option("full_size_h")) > 0);
	if(!$watermarking && !$fullresizing)
		return;
	
	echo '<div style="padding:4px 0px">';
	if($watermarking) {
?>
<input name="scissorsSkipWatermarking" id="scissorsSkipWatermarking" type="checkbox" value="1" onchange="updateSwfuPostParams(swfu, 'scissorsSkipWatermarking');" /><label style="display:inline;font-size:12px" for="scissorsSkipWatermarking"><?php _e('Skip watermarking of images', 'scissors'); ?></label>
<?php }
	if($watermarking && $fullresizing) echo "<br />";
	if($fullresizing) {
?>
<input name="scissorsSkipFullResize" id="scissorsSkipFullResize" type="checkbox" value="1" onchange="updateSwfuPostParams(swfu, 'scissorsSkipFullResize');" /><label style="display:inline;font-size:12px" for="scissorsSkipFullResize"><?php _e('Skip scaling of full-size images', 'scissors'); ?></label>
<?php }
	echo "</div>";
}

add_action('post-flash-upload-ui', 'scissors_post_upload_ui');
add_action('post-html-upload-ui', 'scissors_post_upload_ui');

// Manual cropping and resizing functionality ---------------------------------

function scissors_admin_head()
{
	if(strstr($_SERVER['REQUEST_URI'], 'media'))
	{
		global $scissors_dirname;
		
		wp_enqueue_script('scissors_crop', '/' . PLUGINDIR . '/'.$scissors_dirname.'/js/jquery.Jcrop.js', array('jquery') );
		wp_enqueue_script('scissors_js', '/' . PLUGINDIR . '/'.$scissors_dirname.'/js/scissors.js' );
		
		$thisUrl = admin_url('admin-ajax.php');
		echo "<!-- JS loaded for Scissors in media library -->\n";
		echo "<script type='text/javascript'>\n/* <![CDATA[ */\n";
		echo "scissors = {\n";
		echo "ajaxUrl: '$thisUrl'";
		foreach(array('large', 'medium', 'thumbnail') as $size)
		{
			$width = intval(get_option("{$size}_size_w"));
			$height = intval(get_option("{$size}_size_h"));
			$aspectRatio = max(1, $width) / max(1, $height);
			if(!get_option("{$size}_crop")) $aspectRatio = 0;
			echo ",\n{$size}AspectRatio: $aspectRatio";
		}
		echo "\n}\n";
		echo "/* ]]> */\n</script>\n";
		echo "<!-- End of JS loaded for Scissors in media library -->\n";
	}
}

function scissors_styles()
{
	global $scissors_dirname;
	wp_register_style('jcrop_style', '/' .PLUGINDIR . "/$scissors_dirname/css/jquery.Jcrop.css");
	wp_register_style('scissors_style', '/' .PLUGINDIR . "/$scissors_dirname/css/scissors.css");

	wp_enqueue_style('jcrop_style');
	wp_enqueue_style('scissors_style');
	wp_enqueue_style('thickbox');
}

function gcd($a, $b)
{
	if ($a == 0 || $b == 0)
		return 0;
	else if ($a == $b)
		return $a;
	else
	{
		do
		{
			$r = $a % $b;
			$a = $b; $b = $r;
		} while($r != 0);
		return $a;
	}
}

function scissors_media_meta($string, $post)
{

  // From v2.9 the media box is different, there is one table cell more
  global $wp_version;
  if(version_compare($wp_version, '2.9', '>=')) 
  {
    $scissors_extra_cell = "</tr><tr>";
    $scissors_hide_editbutton = "<style type='text/css'>input#imgedit-open-btn-".$post->ID."{display:none;}</style>";
  }
  else
  {
    $scissors_extra_cell = "";
    $scissors_hide_editbutton = "";
  }


	$errstr = "";
	
	if(!scissors_supports_imagetype($post->post_mime_type))
		$errstr = sprintf(__('Failed to load image. Image type %s not supported.', 'scissors'), $post->post_mime_type);
	{
		$postId = $post->ID;
		$filename = get_attached_file($postId);
		if(!$filename || !file_exists($filename))
			$errstr = __('Failed to load image. File not found.', 'scissors');
		else
		{
			list($width, $height) = getimagesize($filename);
			if($width <= 0 || $height <= 0)
				$errstr = __('Failed to load image. Invalid image dimensions.', 'scissors');
		}
	}
	
	if($errstr != "")
	{
		$string .= "</td></tr>\n\t\t<tr>";
		$string .= "<th valign='top' scope='row' class='label'><label>Scissors</label></th><td class='field'>";
		$string .= "<p class='help'>$errstr</p>";
	}
	else
	{
		$text_crop = __('Crop', 'scissors');
		$text_resize = __('Resize', 'scissors');
		$text_rotate = __('Rotate', 'scissors');
		$text_watermarks = __('Watermarks', 'scissors');
		$text_apply = __('Apply', 'scissors');
		$text_abort = __('Abort', 'scissors');
		$text_crop2 = __('Crop %s.', 'scissors');
		$text_crop3 = __('Lock aspect ratio to %s.', 'scissors');
		$text_width = __('width', 'scissors');
		$text_height = __('height', 'scissors');
		$text_longside = __('long side', 'scissors');
		$text_shortside = __('short side', 'scissors');
		$text_reir = __('Use automatic relevance-enhanced image reduction.', 'scissors');

		$image_url = wp_get_attachment_url($post->ID);
		$nonce = wp_create_nonce("scissors-$postId");
		$img_size = $width . '&nbsp;&times;&nbsp;' . $height;
    
    $string .= "\n\n".$scissors_hide_editbutton."\n\n";
		$string .= "<span id='scissorsSize-$postId'>($img_size - ".__('just edited', 'scissors').")</span></td></tr>\n\t\t<tr>".$scissors_extra_cell;
		$string .= "<th valign='top' scope='row' class='label'><label>Scissors</label></th><td class='field'>";
		$string .= "<div id='scissorsShowBtn-$postId'>";
		$string .= "<button class='button' onclick=\"return scissorsShowCrop($postId, '$image_url')\">$text_crop</button>&nbsp;";
		$string .= "<button class='button' onclick=\"return scissorsShowResize($postId)\">$text_resize</button>&nbsp;";
		if(function_exists('imagerotate')) $string .= "<button class='button' onclick=\"return scissorsShowRotate($postId)\">$text_rotate</button>&nbsp;";
		$string .= "<button class='button' onclick=\"return scissorsShowWatermarks($postId)\">$text_watermarks</button>";
		$string .= "</div>";

		$cropTargets = "<select id='scissorsCropTarget-$postId' onchange=\"scissorsCropTargetChange($postId)\">";
		$cropTargets .= "<option value='chain' selected='selected'>" . __('full chain', 'scissors') . "</option>";
		$cropTargets .= "<option value='full'>" . __('full', 'scissors') . "</option>";
  		$metadata = wp_get_attachment_metadata($postId);
		if(is_array($metadata) && isset($metadata['sizes']) && count($metadata['sizes']) > 0)
		{
			foreach($metadata['sizes'] as $size => $value)
				$cropTargets .= "<option value='$size'>" . __($size, 'scissors') . "</option>";
		}
		$cropTargets .= "</select>";

		$aspectMode = get_option('scissors_crop_defaultaspect');
		$aspectChecked = ($aspectMode != 2) ? "checked='checked'" : "";
		if($aspectMode == 1)
		{
			$aspectX = intval(get_option('scissors_crop_useraspectx'));
			$aspectY = intval(get_option('scissors_crop_useraspecty'));
		}
		if($aspectMode != 1 || $aspectX <= 0 || $aspectY <= 0)
		{
			$aspectX = $width; $aspectY = $height;
			while(($g = gcd($aspectX, $aspectY)) > 1)
			{
				$aspectX = intval($aspectX / $g);
				$aspectY = intval($aspectY / $g);
			}
		}
		
		$cropAspect  = "<input style='width:2em;text-align:center;' type='text' onchange=\"scissorsManualAspectChange($postId)\" id='scissorsLockX-$postId' value='$aspectX' />:";
		$cropAspect .= "<input style='width:2em;text-align:center;' type='text' onchange=\"scissorsManualAspectChange($postId)\" id='scissorsLockY-$postId' value='$aspectY' />";

		$reirChecked = get_option('scissors_crop_defaultreir') ? "checked='checked'" : "";

		$string .= "<div class='scissorsPane' id='scissorsCropPane-$postId'>";
		$string .= sprintf($text_crop2, $cropTargets);
		$string .= "&nbsp;<button class='button' onclick=\"return scissorsCrop($postId, '$nonce')\">$text_crop</button>";
		$string .= "&nbsp;<button class='button' onclick=\"return scissorsAbortCrop($postId)\">$text_abort</button>";
		$string .= "<div class='scissorsImgHost' id='scissorsImgHost-$postId'><img id='scissorsImg-$postId' /></div>";
		$string .= "<input type='hidden' id='scissorsX-$postId' /><input type='hidden' id='scissorsY-$postId' />";
		$string .= "<input type='hidden' id='scissorsW-$postId' /><input type='hidden' id='scissorsH-$postId' />";
		$string .= "<span id='scissorsSel-$postId'></span>";
		$string .= "<div id='scissorsAspect-$postId'><input type='checkbox' onchange=\"scissorsAspectChange($postId)\" id='scissorsLockBox-$postId' $aspectChecked />&nbsp;";
		$string .= sprintf($text_crop3, $cropAspect);
		$string .= " <button style='margin-left:1em;' class='button' onclick=\"scissorsStdImgAspectRatio($postId); return false;\">" . __('current', 'scissors') . "</button>";
		$string .= "</div><div id='scissorsReir-$postId'><input type='checkbox' id='scissorsReirEnable-$postId' $reirChecked />&nbsp;$text_reir&nbsp;<a href=\"http://www.useit.com/alertbox/9611.html\" target=\"_blank\">[?]</a></div>";
		$string .= "</div>";

		$fields = "<input style='width:4em;text-align:center;' type='text' id='scissorsWidth-$postId' value='$width' onkeyup='scissorsResizeChanged($postId, 0)' />&nbsp;&times;&nbsp;";
		$fields .= "<input style='width:4em;text-align:center;' type='text' id='scissorsHeight-$postId' value='$height' onkeyup='scissorsResizeChanged($postId, 1)' />";
		$string .= "<div class='scissorsPane' id='scissorsResizePane-$postId'>";
		$string .= sprintf(__('Resize to %s pixels.', 'scissors'), $fields);
		$string .= "&nbsp;<button class='button' onclick=\"return scissorsResize($postId, '$nonce')\">$text_resize</button>";
		$string .= "&nbsp;<button class='button' onclick=\"return scissorsAbortResize($postId)\">$text_abort</button><br/>";
		$string .= "<input type='checkbox' id='scissorsMaintainAspect-$postId' checked='checked' onchange='scissorsResizeChanged($postId, 0)' />";
		$string .= "<input type='hidden' id='scissorsCurWidth-$postId' value='$width' />";
		$string .= "<input type='hidden' id='scissorsCurHeight-$postId' value='$height' />";
		$string .= "<input type='hidden' id='scissorsUserAspect-$postId' value='" . (($aspectMode == 1) ? "1" : "0") . "' />";
		$string .= "<label style='display:inline;font-size:11px' for='scissorsMaintainAspect-$postId'>" . __('Maintain aspect ratio.', 'scissors') . "</label>";
		$string .= "</div>";

		$text_rotate2 = __('Rotate by', 'scissors');
		$text_left90 = __('90 degrees left', 'scissors');
		$text_right90 = __('90 degrees right', 'scissors');
		$text_180 = __('180 degrees', 'scissors');
		if(function_exists('imagerotate'))
		{
			$string .= "<div class='scissorsPane' id='scissorsRotatePane-$postId'>";
			$string .= "$text_rotate2 <select id='scissorsRotateAngle-$postId'><option value='90' selected='selected'>$text_left90</option><option value='-90'>$text_right90</option><option value='180'>$text_180</option></select>.";
			$string .= "&nbsp;<button class='button' onclick=\"return scissorsRotate($postId, '$nonce')\">$text_rotate</button>";
			$string .= "&nbsp;<button class='button' onclick=\"return scissorsAbortRotate($postId)\">$text_abort</button>";
			$string .= "</div>";
		}

		$string .= "<div class='scissorsPane' id='scissorsWatermarkPane-$postId'>";		
		$wstate = scissors_get_watermarking_state($postId);
		$string .= "<input id='scissors_watermarking_state-$postId' type='hidden' value='$wstate'/>";
		$sizes = (is_array($metadata) && isset($metadata['sizes']) && count($metadata['sizes']) > 0) ? array_keys($metadata['sizes']) : array();
		$sizes[] = 'full'; $sizes[] = 'custom';
		foreach($sizes as $size)
		{
			$checked = scissors_is_watermarking_enabled($size, $postId, TRUE) ? "checked" : "";
			$string .= " <input type='checkbox' id='scissors_watermark_target_$size-$postId' $checked";
			$string .= " onchange=\"scissorsWatermarkStateChanged($postId, '$size')\"";
			$string .= "><label style='display:inline;font-size:11px' for='scissors_watermark_target_$size-$postId'>" . __($size, 'scissors') . "</label>";
		}
		$string .= " <button class='button' onclick=\"return scissorsWatermark($postId, '$nonce')\">$text_apply</button>";
		$string .= "&nbsp;<button class='button' onclick=\"return scissorsAbortWatermark($postId)\">$text_abort</button>";
		$string .= "</div>";

		$string .= "<div class='scissorsPane scissorsWaitFld' id='scissorsWaitFld-$postId'></div>";
	}
	return $string;
}

function scissors_image_make_intermediate_size($file, $width, $height, $crop=false, $adaptive=false)
{
	if($adaptive == '1')
	{
		$fullsize = getimagesize($file);
		$width = ($fullsize[0] >= $fullsize[1]) ? $width : 0;
		$height = ($fullsize[1] >= $fullsize[0]) ? $height : 0;
	}

	$intermediate_result = image_make_intermediate_size($file, $width, $height, $crop);
	if(!$intermediate_result)
	{
		// simply use the specified file as the new intermediate albeit with smaller dimensions
		$suffix = "{$width}x{$height}";
		$info = pathinfo($file);
		$dir = $info['dirname'];
		$ext = $info['extension'];
		$name = basename($file, ".{$ext}");
		$destfilename = "{$dir}/{$name}-{$suffix}.{$ext}";
		copy($file, $destfilename);
		
		list($realW, $realH) = getimagesize($file);		
		$intermediate_result = array('file' => basename($destfilename), 'width' => $realW, 'height' => $realH);
	}
	return $intermediate_result;
}

function scissors_has_thumbnail($metadata)
{
	return is_array($metadata) && isset($metadata['sizes']) && count($metadata['sizes']) > 0 &&
		isset($metadata['sizes']['thumbnail']) && isset($metadata['sizes']['thumbnail']);
}

function scissors_crop($post, $srcfile, $src)
{
	$x = intval($_POST['x']); $y = intval($_POST['y']);
	$w = intval($_POST['w']); $h = intval($_POST['h']);
	if($x >= 0 && $y >= 0 && $w > 0 && $h > 0)
	{
		$fullfile = get_attached_file($post->ID);
		if($_POST['target'] == 'chain' || $_POST['target'] == 'full')
			$dstfile = $fullfile;
		else if($intermediate = image_get_intermediate_size($post->ID, $_POST['target']))
			$dstfile = tempnam(dirname($fullfile), 'scissors');
		else
			return __('Invalid target.', 'scissors');

		// TODO: respect auto-size settings for full-sized image? cropping is no problem, but rotation ...

		$dst = scissors_create_image($w, $h);
		if(is_resource($dst))
		{
			if(imagecopy($dst, $src, 0, 0, $x, $y, $w, $h))
			{
				if(scissors_save_image($dst, $dstfile, $post->post_mime_type))
				{
					if($dstfile == $fullfile)
						scissors_rebuild_watermark_meta($dst, $post->post_mime_type, $post->ID);

					$metadata = wp_get_attachment_metadata($post->ID);
					if($_POST['target'] == 'chain' || $_POST['target'] == 'full')
					{
						// update meta data for the full-size image
						$metadata['width'] = $w; $metadata['height'] = $h;
						list($uwidth, $uheight) = wp_shrink_dimensions($metadata['width'], $metadata['height']);
						$metadata['hwstring_small'] = "height='$uheight' width='$uwidth'";

						$msg = "done;full,{$w},{$h}"; // signal change of the full-size image
						if(!scissors_has_thumbnail($metadata)) $msg .= "+thumbnail,{$w},{$h}"; // full size image is used as thumbnail, force GUI updated

						// update existing intermediate images if requested
						if($_POST['target'] == 'chain' && is_array($metadata) && isset($metadata['sizes']) && count($metadata['sizes']) > 0)
						{
							foreach($metadata['sizes'] as $size => $data)
							{
								$srcfile = $fullfile;
								$w2 = get_option("{$size}_size_w");
								$h2 = get_option("{$size}_size_h");
								if($_POST['reir'] == '1')
								{
									$fx = $w2 / $w; $fy = $h2 / $h;
									$factor = sqrt($fx > $fy ? $fx : $fy);
									$cw = ceil($w * $factor); $ch = ceil($h * $factor);
									if($cw != $w || $ch != $h)
									{
										$cx = floor(($w - $cw) / 2); $cy = floor(($h - $ch) / 2);
										$interfile = tempnam(dirname($fullfile), 'scissors');

										// crop $fullfile to ($cx,$cy) [$cw,$ch] and store to $interfile
										$tmp = scissors_create_image($cw, $ch);
										if(is_resource($tmp))
										{
											if(imagecopy($tmp, $dst, 0, 0, $cx, $cy, $cw, $ch))
											{
												if(scissors_save_image($tmp, $interfile, $post->post_mime_type))
													$srcfile = $interfile;
											}
											imagedestroy($tmp);
										}

										if($srcfile != $interfile)
											unlink($interfile);
									}
								}
	
								$resized = scissors_image_make_intermediate_size($srcfile, $w2, $h2, get_option("{$size}_crop"), get_option("{$size}_adaptive"));
								if($resized)
								{
									$oldfile = path_join(dirname($fullfile), $data['file']);

									if($resized['file'] != $data['file'])
									{
										// overwrite the old file with the newly created image
										$resizedfile = path_join(dirname($srcfile), $resized['file']);
										copy($resizedfile, $oldfile);
										unlink($resizedfile);
										$resized['file'] = $data['file'];
									}
									$metadata['sizes'][$size] = $resized;
									$msg .= "+$size," . $resized['width'] . ',' . $resized['height']; // signal change

									if(scissors_is_watermarking_enabled($size, $post->ID, TRUE))
										scissors_watermark_file($oldfile);
								}

								if($srcfile != $fullfile)
									unlink($srcfile); // clean up reir temporary
							}
						}

						wp_update_attachment_metadata($post->ID, $metadata);

						if(scissors_is_watermarking_enabled('full', $post->ID, TRUE))
							scissors_watermark_file($dstfile);
					}
					else
					{
						$size = $_POST['target'];
						$resized = scissors_image_make_intermediate_size($dstfile, get_option("{$size}_size_w"), get_option("{$size}_size_h"), get_option("{$size}_crop"), get_option("{$size}_adaptive"));
						if($resized)
						{
							// overwrite the specified destination
							$resizedfile = path_join(dirname($dstfile), $resized['file']);
							$oldfile = path_join(dirname($fullfile), $intermediate['file']);
							if(copy($resizedfile, $oldfile))
							{
								$resized['file'] = $intermediate['file'];
								$metadata['sizes'][$size] = $resized;
								$msg = "done;$size," . $resized['width'] . ',' . $resized['height']; // signal change
								if(scissors_is_watermarking_enabled($size, $post->ID, TRUE))
									scissors_watermark_file($oldfile);
							}
							else
								$msg = __('Failed to save new image.', 'scissors');

							unlink($resizedfile);
						}
						else
							$msg = __('Failed to save new image.', 'scissors');

						wp_update_attachment_metadata($post->ID, $metadata);
					}
				}
				else
					$msg = __('Failed to save new image.', 'scissors');
			}
			else
				$msg = __('Failed to crop image.', 'scissors');
	
			imagedestroy($dst);
		}
		else
			$msg = __('Failed to allocate memory.', 'scissors');

		// cleanup temporary file
		if($intermediate)
			unlink($dstfile);
		return $msg;
	}
	else
		return __('Invalid selection.', 'scissors');
}

function scissors_resize($post, $filename, $src)
{
	$w = intval($_POST['width']); $h = intval($_POST['height']);
	if($w > 0 && $h > 0)
	{
		$srcW = imagesx($src); $srcH = imagesy($src);
		if($w != $srcW || $h != $srcH)
		{
			$dst = scissors_create_image($w, $h);
			if(is_resource($dst))
			{
				if(imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, $srcW, $srcH))
				{
					scissors_rebuild_watermark_meta($dst, $post->post_mime_type, $post->ID);
					if(scissors_is_watermarking_enabled('full', $post->ID, TRUE))
						scissors_watermark_image($dst);

					if(scissors_save_image($dst, $filename, $post->post_mime_type))
					{
						// update meta data, no need to rebuild intermediate images because the aspect ratio always stays the same
						$metadata = wp_get_attachment_metadata($post->ID);
						$metadata['width'] = $w; $metadata['height'] = $h;
						list($uwidth, $uheight) = wp_shrink_dimensions($metadata['width'], $metadata['height']);
						$metadata['hwstring_small'] = "height='$uheight' width='$uwidth'";
						wp_update_attachment_metadata($post->ID, $metadata);
						$msg = "done;full,{$w},{$h}"; // signal change of the full-size image
						if(!scissors_has_thumbnail($metadata)) $msg .= "+thumbnail,{$w},{$h}"; // full size image is used as thumbnail, force GUI updated
					}
					else
						$msg = __('Failed to save new image.', 'scissors');
				}
				else
					$msg = __('Failed to resize image.', 'scissors');

				imagedestroy($dst);
				return $msg;
			}
			else
				return __('Failed to allocate memory.', 'scissors');
		}
		else
			return 'done;*'; // signal no change
	}
	else
		return __('Invalid destination dimensions.', 'scissors');
}

function scissors_per_image_watermarks($post, $srcfile, $src)
{
	scissors_set_watermarking_state($post->ID, $_POST['state']);

	// reuse the cropping functionality to rebuild the image chain with/without watermarking
	// TODO: this does a lot more work than what is necessary ... OPTIMIZE ME!
	$_POST['x'] = 0; $_POST['y'] = 0;
	$_POST['w'] = imagesx($src); $_POST['h'] = imagesy($src);
	$_POST['target'] = 'chain'; $_POST['reir'] = 0;
	return scissors_crop($post, $srcfile, $src);
}

function scissors_rotate($post, $srcfile, $src)
{
	$angle = intval($_POST['angle']);
	switch($angle)
	{
	case -90: case 90: case 180: $rotated = imagerotate($src, $angle, 0); break;
	default: return __('Invalid query.', 'scissors');
	}

	// reuse the cropping functionality to rebuild the image chain after rotation
	$_POST['x'] = 0; $_POST['y'] = 0;
	$_POST['w'] = imagesx($rotated); $_POST['h'] = imagesy($rotated);
	$_POST['target'] = 'chain'; $_POST['reir'] = 0;
	return scissors_crop($post, $srcfile, $rotated);
}

function scissors_action()
{
	$postId = intval($_POST['post_id']); // perform security checks
	if (wp_verify_nonce($_POST['_wpnonce'], "scissors-$postId") && current_user_can('upload_files'))
	{
		// retrieve post data and load the full-size image
		$post = get_post($postId);
		$filename = get_attached_file($postId);
		if($post && $filename && file_exists($filename))
		{
			scissors_set_memory_limit();
			$src = scissors_load_image($filename, $post->post_mime_type); // load the source image			
			if(is_resource($src))
			{
				// if the full image contains a watermark we have to remove it first ...
				scissors_remove_watermark($src, $postId);

				if($_POST['action'] == 'scissorsCrop')
					$msg = scissors_crop($post, $filename, $src);
				else if($_POST['action'] == 'scissorsResize')
					$msg = scissors_resize($post, $filename, $src);
				else if($_POST['action'] == 'scissorsRotate')
					$msg = scissors_rotate($post, $filename, $src);
				else if($_POST['action'] == 'scissorsWatermark')
					$msg = scissors_per_image_watermarks($post, $filename, $src);
				else
					$msg = __('Invalid query.', 'scissors');

				imagedestroy($src);
			}
			else
				$msg = __('Failed to load image.', 'scissors');
		}
		else
			$msg = __('Invalid post-id.', 'scissors');
	}
	else
		$msg = __('Unauthorized query.', 'scissors');
	die($msg);
}

add_filter('media_meta', 'scissors_media_meta', 99, 2);
add_action('admin_print_scripts', 'scissors_admin_head');
add_action('admin_print_styles', 'scissors_styles');
add_action('wp_ajax_scissorsCrop', 'scissors_action');
add_action('wp_ajax_scissorsResize', 'scissors_action');
add_action('wp_ajax_scissorsRotate', 'scissors_action');
add_action('wp_ajax_scissorsWatermark', 'scissors_action');

// On-the-fly image resampling functionality ----------------------------------

class Scissors
{
	function Scissors()
	{
		add_filter('mce_external_plugins', array(&$this, 'mce_external_plugins'));
		add_filter('content_save_pre', array(&$this, 'content_save_pre'));
		add_action('save_post', array(&$this, 'save_post'));
		add_action('delete_post', array(&$this, 'delete_post'));

		global $wp_version;
		if(version_compare($wp_version, '2.8', '>='))
			add_action('delete_attachment', array(&$this, 'delete_attachment'));
		else
		{
			// NOTE: We cannot use the delete_attachment-action because at the time it is invoked the metadata
			// associated with the attachment is already gone. 
			add_filter('wp_delete_file', array(&$this, 'wp_delete_file'));
		}
	}

	function mce_external_plugins($plugins)
	{
		$plugins['scissors'] = plugins_url('/scissors-continued/mce/editor_plugin.js');
		return $plugins;
	}

	function _getImagePostIdFromUrl($url)
	{
		$wud = wp_upload_dir(); // remove the base url
		$url = str_replace($wud['baseurl'] . '/', '', $url);

		// remove -(width)x(height) and -custom
		if(preg_match('!(-[0-9]+x[0-9]+(-custom)?)([^/\\\]+)$!', $url, $match) == 1)
			$url = str_replace($match[1], '', $url);

		global $wpdb;
		$post = $wpdb->get_row($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_value = %s LIMIT 1", $url));
		return ($post != null) ? $post->post_id : FALSE;
	}

	function _createResampledImage($imgurl, $destWidth, $destHeight)
	{
		$retval = FALSE;
		$postId = $this->_getImagePostIdFromUrl($imgurl);
		if($postId != FALSE)
		{
			// retrieve post data and load the full-size image
			$post = get_post($postId);
			$filename = get_attached_file($postId);
			if($post && $filename && file_exists($filename))
			{
				$url = wp_get_attachment_url($postId);
				list($fullWidth, $fullHeight) = getimagesize($filename);
				if($destWidth < $fullWidth || $destHeight < $fullHeight)
				{
					$path_parts = pathinfo($filename);
					$sansext = substr($path_parts['basename'], 0, -(strlen($path_parts['extension']) + 1)); // +1 for the dot
					$path_parts['basename'] = "{$sansext}-{$destWidth}x{$destHeight}-custom." . $path_parts['extension'];
					$url = str_replace(basename($url), $path_parts['basename'], $url);							
					$destfilename = $path_parts["dirname"] . '/' . $path_parts['basename'];
					if(!file_exists($destfilename))
					{
						scissors_set_memory_limit();
						$src = scissors_load_image($filename, $post->post_mime_type); // load the source image
						if(is_resource($src))
						{
							$dst = scissors_create_image($destWidth, $destHeight);
							if(is_resource($dst))
							{
								// if the full image contains a watermark we have to remove it first ...
								scissors_remove_watermark($src, $postId);

								if(imagecopyresampled($dst, $src, 0, 0, 0, 0, $destWidth, $destHeight, imagesx($src), imagesy($src)))
								{
									// add the watermark to the resampled image
									if(scissors_is_watermarking_enabled('custom', $postId, TRUE))
										scissors_watermark_image($dst);

									if(scissors_save_image($dst, $destfilename, $post->post_mime_type))
									{
										$key = basename($url);

										add_post_meta($postId, "_scissors_custom_images", array(), true); // initialize if non-existant
										$meta = get_post_meta($postId, '_scissors_custom_images', true);
										$meta[$key] = array('ref' => '');
										update_post_meta($postId, "_scissors_custom_images", $meta);

										$retval = $url;
									}
								}
								imagedestroy($dst);
							}
							imagedestroy($src);
						}
					}
					else
						$retval = $url;
				}
				else
					$retval = $url;
			}
		}
		return $retval;
	}

	function content_save_pre($content)
	{
		// find images with class 'scissors-resample' and resample the image to the specified size
		$pattern = '!(<)img[^>]+class=\\\"[^">]*scissors-resample[^">]*\\\"[^>]*(>)!is';
		$count = preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE | PREG_PATTERN_ORDER);
		if($count > 0)
		{
			$newcontent = ''; $last = 0;
			for($i = 0; $i < $count; $i++)
			{
				$tagStart = $matches[1][$i][1]; $tagEnd = $matches[2][$i][1];
				$newcontent .= substr($content, $last, $tagStart - $last); // append the content up to the tag
				$last = $tagEnd + 1; // skip over the tag itself

				$tag = substr($content, $tagStart, $tagEnd - $tagStart + 1);				
				$tag = str_replace('scissors-resample', '', str_replace(' scissors-resample', '', $tag)); // remove the class

				$width = -1; $height = -1; $url = '';
				if(preg_match('!width=\\\"([0-9]+)\\\"!is', $tag, $widthmatch) == 1)
					$width = ($widthmatch[1]);
				if(preg_match('!height=\\\"([0-9]+)\\\"!is', $tag, $heightmatch) == 1)
					$height = ($heightmatch[1]);
				if(preg_match('!src=\\\"([^">]*)\\\"!is', $tag, $urlmatch, PREG_OFFSET_CAPTURE) == 1)
					$url = $urlmatch[1][0];

				if($width > 0 && $height > 0 && $url != '')
				{
					$newurl = $this->_createResampledImage($url, $width, $height);
					if($newurl != FALSE)
						$tag = str_replace($url, $newurl, $tag);
				}

				$newcontent .= $tag; // append the modified tag
			}
			$newcontent .= substr($content, $last);
			return $newcontent;
		}
		else
			return $content;
	}

	function _deleteReferenceToCustomImage($imgPostId, $key, $postId)
	{
		$meta = get_post_meta($imgPostId, '_scissors_custom_images', true);
		if($meta != FALSE)
		{
			if(strstr($meta[$key]['ref'], "[$postId]") != FALSE)
			{
				$meta[$key]['ref'] = str_replace("[$postId]", '', $meta[$key]['ref']);
				if($meta[$key]['ref'] == '')
				{
					$filename = get_attached_file($imgPostId);
					$filename = str_replace(basename($filename), $key, $filename);
					@unlink($filename);						
					unset($meta[$key]);
				}
	
				if(count($meta) > 0)
					update_post_meta($imgPostId, "_scissors_custom_images", $meta);
				else
					delete_post_meta($imgPostId, "_scissors_custom_images");
			}
		}
	}

	function save_post($postId)
	{
		$post = get_post($postId);

		$newimglist = array();

		// find references to custom images
		$pattern = '!<img[^>]+src="([^">]+-[0-9]+x[0-9]+-custom[^">/\\\]+)"[^>]*>!is';
		$count = preg_match_all($pattern, $post->post_content, $matches, PREG_PATTERN_ORDER);
		foreach($matches[1] as $url)
		{
			$imgPostId = $this->_getImagePostIdFromUrl($url);
			if($imgPostId != FALSE)
			{
				$meta = get_post_meta($imgPostId, '_scissors_custom_images', true);
				if($meta != FALSE)
				{
					$key = basename($url);
					$newimglist[] = "$imgPostId:$key";
					if(strstr($meta[$key]['ref'], "[$postId]") == FALSE)
					{
						$meta[$key]['ref'] .= "[$postId]";
						update_post_meta($imgPostId, "_scissors_custom_images", $meta);
					}
				}
			}
		}

		$newimglist = array_unique($newimglist);

		// if this post gets edited, make sure to remove references to images
		// that are no longer present in the new version
		$oldimglist = get_post_meta($postId, '_scissors_custom_images', true);
		if(is_array($oldimglist))
		{
			$oldimglist = array_diff($oldimglist, $newimglist);
			foreach($oldimglist as $elem)
			{
				list($imgPostId, $key) = explode(':', $elem);
				$this->_deleteReferenceToCustomImage($imgPostId, $key, $postId);
			}
		}

		if(count($newimglist) > 0)
		{
			add_post_meta($postId, "_scissors_custom_images", $newimglist, true) or
				update_post_meta($postId, "_scissors_custom_images", $newimglist);
		}
		else
			delete_post_meta($postId, "_scissors_custom_images");
	}

	function delete_post($postId)
	{
		$post = get_post($postId);

		// find references to custom images
		$pattern = '!<img[^>]+src="([^">]+-[0-9]+x[0-9]+-custom[^">/\\\]+)"[^>]*>!is';
		$count = preg_match_all($pattern, $post->post_content, $matches, PREG_PATTERN_ORDER);
		foreach($matches[1] as $url)
		{
			$imgPostId = $this->_getImagePostIdFromUrl($url);
			if($imgPostId != FALSE)
			{
				$key = basename($url);
				$this->_deleteReferenceToCustomImage($imgPostId, $key, $postId);
			}
		}
	}

	function delete_attachment($postId) // proper semantics in WP >= 2.8
	{
		$meta = get_post_meta($postId, '_scissors_custom_images', true);
		if($meta != FALSE)
		{
			$filename = get_attached_file($postId);
			$path_parts = pathinfo($filename);
			foreach($meta as $key => $value)
				@unlink($path_parts["dirname"] . '/' . $key);	
		}
	}

	function wp_delete_file($filename)
	{
		if(file_exists($filename))
		{
			$path_parts = pathinfo($filename);
			$sansext = substr($path_parts['basename'], 0, -(strlen($path_parts['extension']) + 1)); // +1 for the dot
			$pattern = '!' . preg_quote($sansext) . '-[0-9]+x[0-9]+-custom' . preg_quote('.' . $path_parts['extension']) . '$!';

			$dir = opendir($path_parts['dirname']);
			while($file = readdir($dir))
			{
				if(preg_match($pattern, $file) == 1)
					@unlink($path_parts['dirname'] . '/' . $file);						
			}
			closedir($dir);
		}
		return $filename;
	}
}

$ScissorsInstance = new Scissors();

} // integration check end

?>
