<?php
/*
Plugin Name: Brog Indexor
Plugin URI: http://www.brogol.fr/wordpress/plugins/brog-indexor/
Description: Présentez vos articles dans de jolis index automatisés permettant de s'y retrouver facilement notamment grâce à un système de vignettes personnalisables. <a href="options-general.php?page=brog-indexor/options.php">Voir les options</a>.
Author: Brogol
Version: 1.3
Author URI: http://www.brogol.fr/
Text Domain: b-indexor 
*/
/*
*TODO*
1. faire un css standard
2. faire l'importation avec des url numeriques %postname%
*/

//variables
$inserScripts = false;
$pluginURL = WP_PLUGIN_URL . '/' .basename(dirname(__FILE__));

//options
add_option('bi_useCat', false); //utilisation des categories
add_option('bi_useVig', true); //affichage des vignettes
add_option('bi_useTit', true); //affichage des titres
add_option('bi_useBul', true); //affichage des vignettes dans les popups
add_option('bi_useExtrait', true); //affichage des extraits dans les popups
add_option('bi_defVigURL', $pluginURL.'/images/logoDefautGris.png'); //url de la vignette par defaut
$wide   = get_option('thumbnail_size_w');
$height = get_option('thumbnail_size_h');
add_option('bi_useThumbs', true);
add_option('bi_wVig', $wide); //wide vignette
add_option('bi_hVig', $height); //height vignette
add_option('bi_ordre', NULL); //ordre d'affichage des vignettes alpha si NULL sinon DESC ou ASC

//filtres
add_filter('the_content', 'indexor');

//actions
add_action('admin_menu', 'BImenu'); // menu d'options du plugin
add_action('admin_menu', 'addBiBox'); //ajout des champs pour gerer les index dans les articles/pages

function BImenu() {//ajout du menu d'options
	add_options_page('Brog Indexor Options', 'Brog Indexor', 'manage_options', 'brog-indexor/options.php');
	add_action("admin_head-brog-indexor/options.php", 'optionsCSS' );
}
function optionsCSS(){
	global $pluginURL;
	echo '<link href="'.$pluginURL.'/options.css" rel="stylesheet" type="text/css" />'; 
}
//supprimer les options à la desintallation
function bi_deleteOptions(){
	$bi_options = array('useCat', 'useVig', 'useTit', 'defVigURL', 'useCSS', 'useThumbs', 'wVig', 'hVig');
	for ($i=0; $i<count($bi_options); $i++)
		delete_option('bi_'.$bi_options[$i]);
}
register_uninstall_hook(__FILE__, 'bi_deleteOptions');

//traductions domaine b-indexor
load_plugin_textdomain('b-indexor', false, basename(dirname(__FILE__)).'/languages');

//remplace [index=truc] par l'affichage de l'index de truc
function indexor($content){
	$taille = preg_match_all('#\[index=(.+)\]#iU', $content, $regs); //on regarde si un marqueur est mis dans le contenu
	
	if ($taille>0){
		global $wpdb, $inserScripts, $pluginURL;
		$useTitre = get_option('bi_useTit');
		$useVig = get_option('bi_useVig');
		$useCat = get_option('bi_useCat');
		$ordre = get_option('bi_ordre');
		$useBulle = get_option('bi_useBul');
		$useExtrait = get_option('bi_useExtrait');

		for($i=0; $i<$taille; $i++){ //boucle pour chaque marqueur
			$identifiant = $regs[1][$i]; //on recupere le nom du marqueur
			if($useCat) //on adapte la requete selon l'utilisation des categories ou des champs persos
				$articles = $wpdb->get_results($wpdb->prepare("SELECT p.post_title AS title, p.post_excerpt AS extrait, p.ID AS id FROM $wpdb->term_relationships tr, $wpdb->terms t, $wpdb->posts p WHERE t.slug = %s AND tr.term_taxonomy_id = t.term_id AND p.post_status = %s AND p.ID = tr.object_id ORDER BY p.post_date $ordre", $identifiant, 'publish'), ARRAY_A);
			else
				$articles = $wpdb->get_results($wpdb->prepare("SELECT p.post_title AS title, p.post_excerpt AS extrait, m.post_id AS id FROM $wpdb->postmeta m, $wpdb->posts p WHERE m.meta_key = %s AND m.meta_value = %s AND m.post_id = p.ID AND p.post_status = %s ORDER BY p.post_date $ordre", '_bi_index', $identifiant, 'publish'), ARRAY_A);

			if($articles){ //si on trouves des articles dans l'index courant
				foreach($articles as $k => $v){ //on change le titre si la meta index-title existe dans l'article
					$indexTitle = get_post_meta($v['id'], '_bi_index_title', true);
					if($indexTitle != '')
						$articles[$k] = array('title' => $indexTitle,'id' => $v['id'], 'extrait' => $v['extrait']);
				}
				if($ordre == NULL)
					sort($articles); //on tri par ordre alphabetique
				
				//insertion du HTML :
				if ($useBulle || $useExtrait && !$inserScripts){
					$insertion = '<script type="text/javascript" src="'.$pluginURL.'/fonctions.js" ></script>';
					$insertion .= '<div id="bi_curseur" class="bi_infobulle" style="-moz-border-radius:5px;background:white;border:1px solid silver;font-size:0.9em;line-height:1.5;opacity:0.9;padding:10px;position:absolute;visibility:hidden;"></div>';
					$inserScripts = true;
				}
				$insertion .= '<ul class="indexor">'; //et on insere du html
				foreach ($articles as $a){
					$id = $a['id'];
					if($useBulle || $useVig)
						$imgHTML = '<img src=&quot;' . imgURL($id) . '&quot; height=&quot;'.get_option('bi_hVig').'&quot; width=&quot;'.get_option('bi_wVig').'&quot; />';

					$insertion .= '<li><a class="BIvignette" href="'.get_permalink($id) .'"';
                    if($useBulle || $useExtrait){
						$insertion .= ' onmouseover="montre(\'';
						if($useBulle)
							$insertion .= '<span style=&quot;display:block;float:left;margin-right:1em;overflow:hidden;&quot;>'.$imgHTML.'</span>';
						if($useExtrait){
							if($a['extrait'] == NULL){
								$a['extrait'] =  extrait2screen($wpdb->get_var($wpdb->prepare("SELECT post_content FROM $wpdb->posts WHERE ID=$id; ")));
							}
							$insertion .= '<p style=&quot;width:38em;&quot;>'. str_replace('"','&quot;',addslashes($a['extrait'])).'</p>';
						}
						$insertion .= '\');" onmouseout="cache();"';
					}
					$insertion .= '>';
					if($useVig){
						$imgHTML = str_replace('&quot;','"', $imgHTML);
						$insertion .= $imgHTML;
					}
					if(!$useVig OR $useTitre)
						$insertion .= '<span class="titre">'. $a['title'] .'</span>';
					$insertion .= '</a></li>';
				}
			$insertion .= '</ul>';//fin insertion du HTML
			$content = str_replace($regs[0][$i], $insertion, $content);
			$insertion = NULL;
			}
		}
	}
	return $content;
}

//récupère l'URL de l'image à afficher pour l'article $postID
function imgURL($postID){
	$p=get_post($postID);
	$img = get_post_meta($p->ID, '_bi_vignette', true);
	
	if($img == ''){//si il n'y a pas de vignette enregistré dans les champs perso on cherche des images dans l'article
		global $wpdb;
		$noImg = true;
		$attachID = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_parent = %d AND post_type = %s AND post_mime_type REGEXP %s LIMIT 1", $p->ID, 'attachment', '^image'));
		if ($attachID){
			$imgSRC = wp_get_attachment_image_src($attachID, 'thumbnail');
			$img = $imgSRC[0];
			if(@getimagesize($img))
				$noImg = false;
		}
		if($noImg){
			$image = '#<img.+src=[\'"]([^\'"]+)[\'"].*>#iU';
			preg_match_all( $image, $p->post_content, $pics );
			$nb = count($pics[0]);
			if ( $nb > 0 ) 	//s'il a trouve au moins une image, on choisit la premiere
				$img = $pics[1][0];
			else 			//image par defaut si il n'y a pas d'image du tout dans l'article
				$img= get_option('bi_defVigURL');
		}
	}
	return $img;
}
//rend l'affichage d'un extrait possible à l'écran, 
function extrait2screen($text, $len=255) {
	$text = strip_tags(str_replace('<!--more-->', '[!--more--]',$text));
	$pos = strpos($text, '[!--more--]');
	if ($pos !== FALSE)
		$text = substr($text, 0, $pos);
	else{ //substr sans couper de mots
		if( (strlen($text) > $len) ) {
			$pos = strpos($text," ",$len)-1;
			if( $pos > 0 )
				$text = substr($text, 0, ($pos+1)) .' [&hellip;]';
		}
	}
    return str_replace(array("\t","\n","\r"), " ", $text);
} 
/* Adds a custom section to the "advanced" Post and Page edit screens */
function addBiBox() {
	add_meta_box( 'bi_box', 'Indexor', 'displayBiBox', 'post', 'side');
	add_meta_box( 'bi_box', 'Indexor', 'displayBiBox', 'page', 'side');
	add_action('save_post','saveBiBox');
}
function displayBiBox(){
    global $wpdb, $post;
    $indexDispo = $wpdb->get_col($wpdb->prepare("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = %s ORDER BY meta_value", '_bi_index'));
    $indexDispo = array_unique($indexDispo);
    $selectionIndex = get_post_meta($post->ID, '_bi_index');
    if($selectionIndex == '')
	$selectionIndex = array();
// 	print_r($selectionIndex);
    $customImg = get_post_meta($post->ID, '_bi_vignette', true);
    $customTit = get_post_meta($post->ID, '_bi_index_title', true);

    $html = '<ul><li><ul style="overflow:auto; max-height:300px;">';
    foreach($indexDispo as $iNom){
	$i++;
	$html .= '<li><input type="checkbox" name="bi_index'.$i.'"  id="bi_index'.$i.'" ';
	if (in_array($iNom, $selectionIndex))
		$html .= 'checked="checked" ';
	$html .= '/><label for="bi_index'.$i.'"> '.$iNom. '</label></li>';
    }

    $html .= '<input type="hidden" name="bi_nonce" id="bi_nonce" value="'.wp_create_nonce(plugin_basename(__FILE__)) . '" />';
    $html .= '<li><label for="bi_newIndex">'.__('New index:', 'b-indexor').'<input type="text" name="bi_newIndex" id="bi_newIndex" /><br /><small>'.__('To add several index, separate them with semicolons (ex: index1<strong>;</strong> index2).', 'b-indexor').'</small></li></ul></li>';
    if ($customImg != '')
	$html .= '<p><img src="'.$customImg.'" alt="vignette de l\'article" width="'.get_option('bi_wVig').'" height="'.get_option('bi_hVig').'"/></p>';
    $html .= '<li><label for="bi_vignette"><strong>'.__('Vignette\'s URL', 'b-indexor').'</strong></label><input type="text" name="bi_vignette" id="bi_vignette" value="'.$customImg.'" style="width:98%;" /></li>';
    $html .= '<li><label for="bi_titre"><strong>'.__('Custom title', 'b-indexor').'</strong></label><input type="text" name="bi_titre" id="bi_titre" value="'.$customTit.'" style="width:98%;"/></li></ul>';
    echo $html;  
}

function saveBiBox($post_id){
  // verify this came from the our screen and with proper authorization, because save_post can be triggered at other times
  if ( !wp_verify_nonce( $_POST['bi_nonce'], plugin_basename(__FILE__) )) {
    return $post_id;
  }

  // verify if this is an auto save routine. If it is our form has not been submitted, so we dont want to do anything
  if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
    return $post_id;
  
  // Check permissions
  if ( 'page' == $_POST['post_type'] ) {
    if ( !current_user_can( 'edit_page', $post_id ) )
      return $post_id;
  } else {
    if ( !current_user_can( 'edit_post', $post_id ) )
      return $post_id;
  }

  // OK, we're authenticated: we save the data
	global $wpdb;
	$indexDispo = array_unique($wpdb->get_col($wpdb->prepare("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = %s ORDER BY meta_value", '_bi_index')));
	$newIndex = array();
	foreach($indexDispo as $iNom){
		$i++;
		if(isset($_POST['bi_index'.$i]))
			$newIndex[] = $iNom;
	}

	if(isset($_POST['bi_newIndex']) && ($_POST['bi_newIndex'] != '')){
		$news = explode(';', $_POST['bi_newIndex']);
		for($i=0; $i<count($news); $i++){
			$news[$i] = str_replace('"','\'\'', strtolower(trim($news[$i])));
			$newIndex[] = $news[$i];
		}
	}
	delete_post_meta($post_id, '_bi_index'); //on nettoie les index, puis s'il y en a de selectionné :
	if ($newIndex != NULL){
		$newIndex = array_unique($newIndex);
		foreach($newIndex as $ni)
			add_post_meta($post_id, '_bi_index', $ni);
	}
	
	if(isset($_POST['bi_titre'])){
		if(trim($_POST['bi_titre']) == '')
			delete_post_meta($post_id, '_bi_index_title');
		else
			update_post_meta($post_id, '_bi_index_title', trim($_POST['bi_titre']));
	}
	if(isset($_POST['bi_vignette'])){
		if(trim($_POST['bi_vignette']) == '')
			delete_post_meta($post_id, '_bi_vignette');
		else
			update_post_meta($post_id, '_bi_vignette', trim($_POST['bi_vignette']));
	}
}

?>
