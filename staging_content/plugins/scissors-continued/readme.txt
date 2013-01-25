=== Plugin Name ===
Contributors: stephanreiter, samuelaguilera, huiz
Tags: admin, media, images, gallery, library, upload, resize, crop, watermark, rotate
Requires at least: 2.9
Tested up to: 3.1
Stable tag: 2.1

Scissors Continued enhances WordPress' handling of images by introducing cropping, resizing, rotating, and watermarking functionality.

== Description ==

This plugin adds cropping, resizing, and rotating functionality to Wordpress' image upload and management dialogs. Scissors also allows automatic resizing of images when they are uploaded and supports automatic and manual watermarking of images. Additionally, images that are resized in the post editor are automatically resampled to the requested size using bilinear filtering when a post is saved, which improves the perceived image quality while reducing the amount of data transferred at the same time.

Please note that WordPress versions 2.8 and older are not supported! Scissors Continued is an edited copy of Scissors v1.3.7 which stopped working in v2.9 and up.

== Installation ==

Scissors Continued is available in the official plugin repository. To make a manual installation, follow these steps:

1. Extract the contents of the Scissors zip-archive you downloaded.
1. Upload the extracted `scissors` folder to your `/wp-content/plugins/` directory.
1. Activate the plugin in the 'Plugins' menu in WordPress.
1. Configure the plugin in WordPress' media settings.

Automatic resizing of the full-size image at upload-time can be configured in WordPress' media settings. You can specify a maximum width and a maximum height in pixels for this to take effect (a width/height of 0 disables image resizing in that particular dimension).

Watermarking can also be configured and enabled in WordPress' media settings: Supply an image that you want to be embedded into newly uploaded images, specify its alignment, size, and margin, and you're all set!

== Screenshots ==

1. Cropping a picture from within WordPress
2. Extended media settings for automatic resizing, cropping, and watermarking

== Changelog ==

**Version 2.1**
There was a little bug that caused a MCE editor without buttons.

**Version 2.0**
Updated code from the latest Scissors v1.3.7 and altered to work under WordPress 3.1.

**Version 1.0**
A first running Scissors under WordPress v2.9 and up.