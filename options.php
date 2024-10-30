<?php
$pluginURL = WP_PLUGIN_URL . '/' .basename(dirname(__FILE__));
?>
<div class="wrap BI">
	<div class="head">
		<h2><a href="options-general.php?page=brog-indexor/options.php"><img src="<?php echo $pluginURL;?>/images/logo.png" alt="Brog Indexor Options" /></a></h2>
		<p><em><?php _e("Display yours posts in nice index.", 'b-indexor');?></em></p>
	</div>
	<div class="aGauche">
		<p>
			<?php _e("This plugin let you display post lists of your index in alphabetical order with the title and the vignette of each post. Vignettes size are based on thumbnails size of your wordpress installation. They match to the first image find in the post. If your post doesn't have any images in his content or specified in the custom field, it's the default vignette which is display.", 'b-indexor'); ?>
		</p>
		<p>
			<?php _e("To <strong>display an index</strong>, you just have to put the code <code><strong>[index=</strong>your_index<strong>]</strong></code> in a post or a page. All linked posts to this index will be automatically displayed.", 'b-indexor'); ?>
		</p>
	</div>
<?php 
	
//1. vérification du niveau de droits
if (!current_user_can('manage_options'))
	wp_die(__('You do not have sufficient permissions to access options of this page.', 'b-indexor'));

//2. quelques variables necessaires ou facilitant la vie
global $wpdb;
$lesIndex = @array_count_values($wpdb->get_col($wpdb->prepare("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = %s ORDER BY meta_value", '_bi_index')));
$colors = array('Gris', 'Bleu', 'Vert', 'Jaune', 'Rose', 'Blanc');
$wThumb = get_option('thumbnail_size_w');
$hThumb = get_option('thumbnail_size_h');

//3. traitement des données envoyées par le 1er formulaire, mise à jour des options
if(isset($_POST['options'])){
	//traitement du manage index
	if($lesIndex!=NULL){
		$i=0;
		foreach ($lesIndex as $index => $nbPosts){
			$i=$i+1;
			$indexId= 'index'.$i;
			if(isset($_POST["del-".$indexId])){//supprimer un index
				$wpdb->query($wpdb->prepare("DELETE FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value= %s", "_bi_index", $index));
				unset($lesIndex[$index]);
			}
			elseif(isset($_POST['manage-'.$indexId])){
				$newIndex = str_replace('"','\'\'',stripslashes(strtolower(trim($_POST['manage-'.$indexId]))));
				$doublon = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value= %s",'_bi_index', $newIndex));
				if($doublon == 0){
					$wpdb->query($wpdb->prepare("UPDATE $wpdb->postmeta SET meta_value= %s WHERE meta_key = %s AND meta_value= %s", $newIndex ,'_bi_index', $index));
					$toUp = true;
				}
			}
			if($toUp)
				$lesIndex = @array_count_values($wpdb->get_col($wpdb->prepare("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = %s ORDER BY meta_value", '_bi_index')));
		}
	}
	//mise à jour de la vignette par défaut
	if(isset($_POST['defautImage'])){
		if($_POST['defautImage']=="autre")
			update_option('bi_defVigURL',$_POST['lAutre']);
		else
			update_option('bi_defVigURL',$_POST['defautImage']);
	}
	//maj de la taille des vignettes
	if(isset($_POST['useThumbs'])){
	    update_option('bi_wVig',$wThumb);
	    update_option('bi_hVig',$hThumb);
	    update_option('bi_useThumbs', true);
	}
	else{
	    update_option('bi_wVig',$_POST['wVig']);
	    update_option('bi_hVig',$_POST['hVig']);
	    update_option('bi_useThumbs', false);
	}
	//maj de l'ordre
	if(isset($_POST['ordre'])){
		if($_POST['ordre'] == 'alpha')
			update_option('bi_ordre', NULL);
		else
			update_option('bi_ordre', $_POST['ordre']);
	}
	//maj de l'utilisation de catégorie
	if(isset($_POST['useCat']))
	    update_option('bi_useCat',$_POST['useCat']);
	else
	    update_option('bi_useCat',false);

	if(isset($_POST['useVig']))
	    update_option('bi_useVig',$_POST['useVig']);
	else
	    update_option('bi_useVig',false);
	//maj de l'utilisation des titres
	if(isset($_POST['useTitre']))
	    update_option('bi_useTit',$_POST['useTitre']);
	else
	    update_option('bi_useTit',false);
	//maj de l'utilisation des popus
	if(isset($_POST['useExtrait']))
	    update_option('bi_useExtrait',$_POST['useExtrait']);
	else
	    update_option('bi_useExtrait',false);
	//maj de l'utilisation des titres
	if(isset($_POST['useBulle']))
	    update_option('bi_useBul',$_POST['useBulle']);
	else
	    update_option('bi_useBul',false);
}
//3bis. traitement des données envoyées par le 2nd formulaire, l'importation d'anciens index
if(isset($_POST['import']) && isset($_POST['nomIndex']) && $_POST['import']!=""){
	if($_POST['nomIndex']!=""){
		$_POST['nomIndex'] = strtolower(trim($_POST['nomIndex']));?>
		<h3 style="color:red;"><?php printf(__("Importation in %s", 'b-indexor'), $_POST['nomIndex']);?></h3><ul><?php
		$import=stripslashes($_POST['import']);
// 		$permalink= get_option('permalink_structure');
// 		if(!strstr($permalink, '%postname%'))//TODO4
		    //echo 'non';
		$taille=preg_match_all('#href="(?:https?://)?(?:[a-z0-9._/-]*/)?([a-z0-9_-]+)(?:\.[a-z.]+|/)?"#i', $import, $liens);
		$nbIndexe=0;$nbDejaIndexe=0;
		for($i=0; $i<$taille; $i++){
		    $postID = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_name = %s LIMIT 1", $liens[1][$i]));
		    if ($postID!=''){
			$dejaIndexe = false;
			$indexs = get_post_meta($postID, '_bi_index');
			if ($indexs != NULL){
			    foreach($indexs as $index){
				if ($index==$_POST['nomIndex'])
				    $dejaIndexe = true;
			    }
			}
			if ($dejaIndexe){
			    echo '<li style="color:red;"><em>'.$liens[1][$i].'</em>' .__(" is already in this index.", 'b-indexor').'</li>';
			    $nbDejaIndexe++;
			}
			else{
			    add_post_meta($postID, '_bi_index', $_POST['nomIndex']);
			    echo '<li style="color:green;">'. __("Adding ", 'b-indexor').'<em>'.$liens[1][$i].'</em></li>';
			    $nbIndexe++;
			}
		    }
		}
		?></ul><p style="color:blue;"><?php
		if ($nbIndexe>0)
		    printf(__("Congratulations, <strong>%d</strong> posts have been added in the index <strong>%s</strong>.", 'b-indexor'), $nbIndexe, $_POST['nomIndex']);
		else{
		    if ($nbDejaIndexe>0)
			printf(_n("The founded post was already indexed.", "The %d founded posts were already indexed.", $nbDejaIndexe, 'b-indexor'), $nbDejaIndexe);
		    else
			_e("Sorry, no post found in the html code given. :(", 'b-indexor');
		}
		?></p><?php
	}
	else{?>
		<h3 style="color:red;"><?php _e("Importation failed", 'b-indexor'); ?></h3>
		<p style="color:blue;"><?php _e("Please put an index name to import your posts.", 'b-indexor')?></p><?php
	}
}

//4. recuperation des options enregistrées pour pouvoir les afficher
$useCat    = get_option('bi_useCat');
$useVig    = get_option('bi_useVig');
$useBulle  = get_option('bi_useBul');
$useExtrait= get_option('bi_useExtrait');
$wide      = get_option('bi_wVig');
$height    = get_option('bi_hVig');
$useThumbs = get_option('bi_useThumbs');
$useTitre  = get_option('bi_useTit');
$ordre     = get_option('bi_ordre');
$defautImageURL = get_option('bi_defVigURL');
if(preg_match('#/brog-indexor/images/logoDefaut([A-Z][a-z]{3,4}).png$#', $defautImageURL, $reg))
	$defautImageChoix = $reg[1];
else
	$defautImageChoix = 'autre';

//4. et enfin affichage des formulaires :?>

<form action="" method="post" onclick="document.getElementById('dontForgetValider').style.visibility='visible';">
	<ul><li class="manageIndex">
	<h3><?php _e("Manage your index", 'b-indexor'); ?></h3>
		<table><tbody><tr><th><?php _e("Index Name", 'b-indexor'); ?></th><th><?php _e("Posts", 'b-indexor'); ?></th><th><?php _e("Delete", 'b-indexor'); ?></th></tr>
			<?php 
			if($lesIndex==NULL)
				_e("None of your posts are indexed yet.", 'b-indexor');
			else {
				$i=0;
				foreach ($lesIndex as $nom => $nbPosts){
					$i=$i+1;
					$id= 'index'.$i;
					$nomJs=addslashes($nom);
					echo '<tr><td id="'.$id. '" class="nonEditable"><input size="26" disabled="disabled" type="text" value="'.$nom. '" name="manage-'.$id. '" id="manage-'.$id.'" /><img id="img-'.$id.'" onclick="document.getElementById(\'manage-'.$id.'\').disabled=false;document.getElementById(\''.$id.'\').className=\'editable\';document.getElementById(\'manage-'.$id.'\').focus();" src="'.$pluginURL.'/images/generic.png" alt="Modify" /></td><td>'.$nbPosts.'</td><td><input onclick="if(this.checked == true) { document.getElementById(\''.$id.'\').className=\'deletable nonEditable\'; document.getElementById(\'manage-'.$id.'\').disabled=true; document.getElementById(\'manage-'.$id.'\').value=\''.$nomJs.'\'; document.getElementById(\'img-'.$id.'\').src=\''.$pluginURL.'/images/no.png\'} else { document.getElementById(\''.$id.'\').className=\'nonEditable\'; document.getElementById(\'img-'.$id.'\').src=\''.$pluginURL.'/images/generic.png\'}" type="checkbox" name="del-'.$id.'" id="del-'.$id.'" /></td></tr>';
				}
			}?>
		</tbody></table>
		<input class="valider" type="submit" name="options" value="<?php _e("Save modifications", 'b-indexor');?>" />
		</li>
		<li class="optionsAff"><h3><?php _e("Display options", 'b-indexor'); ?></h3>
			<p><small><?php _e("If nothing checked, titles will be displayed.", 'b-indexor'); ?></small></p>
			<ul>
				<li><input type="checkbox" name="useBulle" id="useBulle" <?php if ($useBulle) echo 'checked="checked"';?>/>
				<label for="useBulle"><?php _e("Display popup vignettes.", 'b-indexor');?></label></li>
				<li><input type="checkbox" name="useExtrait" id="useExtrait" <?php if ($useExtrait) echo 'checked="checked"';?>/>
				<label for="useExtrait"><?php _e("Display popup excerpt.", 'b-indexor');?></label></li>
				<li><input type="checkbox" name="useVig" id="useVig" <?php if ($useVig) echo 'checked="checked"';?>/>
				<label for="useVig"><?php _e("Display vignettes.", 'b-indexor');?></label> <small><?php _e("Do not check this if you are concerned about optimizing your website load time.", 'b-indexor'); ?></small></li>
				<li><input type="checkbox" name="useTitre" id="useTitre" <?php if ($useTitre) echo 'checked="checked"';?>/>
				<label for="useTitre"><?php _e("Display titles.", 'b-indexor');?></label></li>
			</ul>
		</li>
		<li><h3><?php _e("Index order", 'b-indexor'); ?></h3>
			<ul class="ordreIndex">
				<li><input type="radio" name="ordre" <?php if ($ordre == NULL) echo 'checked="checked"';?> value="alpha" id="alpha" /><label for="alpha"> <?php _e("Alphabetical", 'b-indexor'); ?></label></li>
				<li><input type="radio" name="ordre" <?php if ($ordre == 'ASC') echo 'checked="checked"';?> value="ASC" id="ASC"/><label for="ASC"> <?php _e("Chronological", 'b-indexor'); ?></label></li>
				<li><input type="radio" name="ordre" <?php if ($ordre == 'DESC') echo 'checked="checked"';?> value="DESC" id="DESC"/><label for="DESC"> <?php _e("Reverse chronological", 'b-indexor'); ?></label></li>
			</ul>
		</li>
		<li><h3><?php _e("Vignettes size", 'b-indexor'); ?></h3>
			<p><small><?php _e("The plugin always try to display the wordpress thumbnails, so using the thumbnail wordpress size is very optimized.", 'b-indexor');?></small></p>
			<ul>
				<li><input onclick="document.getElementById('hVig').disabled=this.checked ? true : false; document.getElementById('wVig').disabled=this.checked ? true : false; document.getElementById('wVig').value=<?php echo $wThumb; ?>; document.getElementById('hVig').value=<?php echo $hThumb; ?>;" type="checkbox" name="useThumbs" id="useThumbs" <?php if ($useThumbs) echo 'checked="checked"';?>/>
				<label for="useThumbs"><?php _e("Use wordpress thumbnails size.", 'b-indexor');?></label></li>
				<li><label for="wVig"><?php _e("Width", 'b-indexor');?>&nbsp;</label><input type="text" value="<?php echo $wide;?>" name="wVig" id="wVig" size="3" <?php if ($useThumbs) echo 'disabled="disabled"';?> />px</li>
				<li><label for="hVig"><?php _e("Height", 'b-indexor');?>&nbsp;</label><input type="text" value="<?php echo $height;?>" name="hVig" id="hVig" size="3" <?php if ($useThumbs) echo 'disabled="disabled"';?>/>px</li>
			</ul>
		</li>
		<li><h3><?php _e("Default vignette", 'b-indexor'); ?></h3>
			<p>
				<?php _e("Choose the vignette to display when there is no image associate with a post:", 'b-indexor'); ?><br />
				<?php
				for ($i=0; $i<count($colors); $i++){
					echo '<input ';if ($defautImageChoix==$colors[$i]) echo 'checked="checked"';  echo 'type="radio" name="defautImage" value="'.$pluginURL.'/images/logoDefaut'.$colors[$i].'.png" id="'.$colors[$i].'"/><label for="'.$colors[$i].'"><img src="'.$pluginURL.'/images/logoDefaut'.$colors[$i].'.png" alt="'.__('default vignette', 'b-indexor').'" /></label>';
				}?><br />
				<input <?php if ($defautImageChoix=='autre') :?> checked="checked"<?php endif;?> type="radio" name="defautImage" value="autre" id="autre" /><label for="autre"> <?php _e('Other', 'b-indexor');?> </label><label for="lAutre"><?php printf(__("&mdash;&nbsp;image's URL, %dx%dpx preferably:", 'b-indexor'), $wide, $height);?></label>
				<input onclick="document.getElementById('autre').checked=true" type="text" value="<?php if ($defautImageChoix=='autre') echo $defautImageURL; else echo 'http://';?>" name="lAutre" id="lAutre" size="60" />
			</p>
		</li>
		<li>
			 <h3><?php _e("Categories", 'b-indexor'); ?></h3>
			 <ul>
				<li><input type="checkbox" name="useCat" id="useCat" <?php if ($useCat) echo 'checked="checked"'; ?>/> <label for="useCat"><?php _e("Use categories instead indexor system.", 'b-indexor');?></label><br />
				<small><?php _e("So, you have to use [index=category-id]. Look the <em>Categories</em> page to know your category id. And the indexor box will be useless", 'b-indexor'); ?></small>
				</li>
			</ul>
		</li>
	</ul>
	<p>
		<input class="valider" type="submit" name="options" value="<?php _e("Save modifications", 'b-indexor');?>" />
		<input id="dontForgetValider" style="visibility:hidden;" class="valider" type="submit" name="options" value="<?php _e("Save modifications", 'b-indexor');?>" />
	</p>
</form>
<form action="" method="post" class="importation">
	<h3><?php _e("Index importation", 'b-indexor'); ?></h3>
		<p class="aGauche"><?php _e("The importation recognize all URL which match <code>href=\"http://yoursite.com/your-post/\"</code> and verify if <code>your-post</code> is a real post in your blog. If it's true, your post is adding in the given index.", 'b-indexor');?></p>
		<ol class="aGauche">
			<li>
				<label for="nomIndex"><?php _e("Indicate the exact name of the index in which you want to import yours posts:", 'b-indexor'); ?></label>
				<input type="text" name="nomIndex" id="nomIndex" />
			</li>
			<li>
				<label for="import"><?php _e("Paste here the html code where are the posts you want importing", 'b-indexor'); ?></label><br />
				<textarea cols="80" rows="10" name="import" id="import"></textarea>
			</li>
			<li>
				<input type="submit" name="importer" value="Send" />
			</li>
		</ol>
</form>
</div>
