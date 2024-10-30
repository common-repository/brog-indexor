=== Plugin Name ===
Contributors: Brogol
Donate link: http://www.brogol.fr/wordpress/plugins/brog-indexor/
Tags: index, vignette, alphabetical, image, picture, thumbnail, thumbshot, categories
Requires at least: 2.8
Tested up to: 3.0.1
Stable tag: 1.3

Display your posts in nice index.

== Description ==

This plugin let you display post lists of your index in alphabetical order with the title of each post. A vignette and the excerpt of the post are displayed when you pass your mouse on its title. Vignettes size are based on thumbnails size of your wordpress installation. They match to the first image found in the post. If your post doesn't have any images in its content or specified in the custom field, it's the default vignette which is displayed.

* To display an index, you just have to put the code `[index=your_index]` in a post or a page. All linked posts to this index will be displayed.
* To link a post with a new index, you must add your index name in the new index file you find in the side column of create/edit post.
* To choose a specific vignette for a post, you must add the URL of the choosen image in the image URL field.
* To custom a title post, you must add your title in the custom title field.
* You can manage your index name in the options.

== Installation ==

1. Upload `brog-indexor` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Check wanted index in the indexor box of your post
4. Place `[index=your_name_index]` in pages'n'posts you want display it

== Frequently Asked Questions ==

= I have a vignette which it doesn't display =

You must have a wrong URL in the image URL field of the indexor box.

= The Popup does'nt appear in the right place =
If you have issues with the popup placement, it means you must have an holder object which have be place with the attribut position in CSS.
Two possibilities to correct it : delete this CSS attribut or go in the fonctions.js file of the plugin and change the 10px value until the placement satisfy you.  

== Changelog ==

= 1.3 =
* Adding the possibility of displaying vignettes and excerpt on mouse hover.
* You can now manage your index (modify the name or delete it) in the options page plugin.

= 1.2 =
* French version
* Correction of a minor bug (unselect all index of a post was impossible)

= 1.1 =
* English version only.

== Upgrade Notice ==

= 1.3 =
Go to the plugin options to enjoy the new features.

= 1.2 =
Adding pot file for translations.
 
= 1.1 =
Fixes minor bugs. To keep the french version, wait the next upgrade.