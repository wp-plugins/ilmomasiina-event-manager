<?php
/*
Plugin Name: Ilmomasiina
Plugin URI: https://wordpress.org/plugins/ilmomasiina-event-manager/
Author: Tomi Ylä-Soininmäki
Author email: tomi.yla-soininmaki@fimnet.fi
Description: Ilmomasiina tapahtumien luomiseen ja ilmottautumiseen
Version: 0.2
*/

include_once( plugin_dir_path( __FILE__ ) . 'kayttoliittyma.php'); // Linkitetään UI:n luova php

add_action( 'init', 'alusta_tapahtumapostityyppi' );

// ALUSTETAAN TAPAHTUMA -POSTITYYPPI

function alusta_tapahtumapostityyppi() {
	
	register_post_type( 'tapahtumat', array(
		'labels' => 
		array(
			'name' => __( 'Tapahtumat' ),
			'singular_name' => __( 'Tapahtuma' )
		),
		'public' => false,
		'publicly_queryable' => false,
		'show_ui' => (current_user_can( 'edit_posts' ) ? true : false),
		'show_in_admin_bar' => true,
		'hierarchial' => false,
		'supports' => array(  'title', 'author', 'editor', 'thumbnail' ),
		'has_archive' => false,
		'query_var' => false,
		'menu_position' => 5,
		'register_meta_box_cb' => 'tapahtuman_metaboksit',
		'taxonomies' => array('tapahtuman_tekstikentat', 'tapahtuman_valinnat', 'tapahtuman_monivalinnat'),
		'capabilities' => array(
			'publish_posts' => 'edit_posts',
			'edit_posts' => 'edit_posts',
			'edit_post' => 'edit_posts',
			/*'delete_posts' => 'edit_posts',
			'delete_post' => 'edit_posts',*/
		)
	));
	
}




// TAKSONOMIAT

add_action('init', 'rekisteroi_tapahtuman_tekstikentat_taksonomia');
add_action('init', 'rekisteroi_tapahtuman_valinnat_taksonomia');
add_action('init', 'rekisteroi_tapahtuman_monivalinnat_taksonomia');


function rekisteroi_tapahtuman_tekstikentat_taksonomia() {
	
	$argumentit = array(
		'labels' => array(
			'name' => 'Tekstikentät',
			'menu_name' => 'Tekstikentät',
			'separate_items_with_commas' => '\'-pakollinen\' -pääte tekee kentästä pakollisen',
		),
		'hierarchical' => false,
		'public' => true,
		'sort' =>  true,
	);
	
	register_taxonomy('tapahtuman_tekstikentat', 'tapahtumat', $argumentit);
}

function rekisteroi_tapahtuman_valinnat_taksonomia() {
  
  $argumentit = array(
		'labels' => array(
			'name' => 'Valintakentät (<input type="radio" checked>)',
			'menu_name' => 'Valintakentät',
			'separate_items_with_commas' => 'Oletuksena Kyllä-Ei vaihtoehdot. Luo lisää vaihtoehtoja "//" -merkillä, esim Valkoviini // Punaviini // Kuohuviini',
		),
    'hierarchical' => false,
		'public' => true,
		'sort' =>  true,
	);
	
	register_taxonomy('tapahtuman_valinnat', 'tapahtumat', $argumentit);
}

function rekisteroi_tapahtuman_monivalinnat_taksonomia() {
	
	$argumentit = array(
		'labels' => array(
			'name' => 'Ruksittavat kohdat (<input type="checkbox" checked>)',
			'menu_name' => 'Ruksittavat kohdat',
			'separate_items_with_commas' => ' ',
		),
		'hierarchical' => false,
		'public' => true,
	);
	
	register_taxonomy('tapahtuman_monivalinnat', 'tapahtumat', $argumentit);
}

add_action('admin_menu', 'poista_taxonomia_menut' );

function poista_taxonomia_menut() {
	remove_submenu_page( 'edit.php?post_type=tapahtumat','edit-tags.php?taxonomy=tapahtuman_tekstikentat&amp;post_type=tapahtumat');
	remove_submenu_page( 'edit.php?post_type=tapahtumat','edit-tags.php?taxonomy=tapahtuman_monivalinnat&amp;post_type=tapahtumat');
	remove_submenu_page( 'edit.php?post_type=tapahtumat','edit-tags.php?taxonomy=tapahtuman_valinnat&amp;post_type=tapahtumat');
}






// METABOKSIT

function tapahtuman_metaboksit() {
	add_meta_box('ilmo_ohjeet', 'Ohjeet tapahtuman järjestäjälle', 'tapahtuman_ohjeet', 'tapahtumat', 'normal', 'high');
	add_meta_box('tapahtuman_paivat', 'Tapahtuman päivämäärät', 'tapahtuman_paivamaarat_metabox', 'tapahtumat', 'normal', 'high');
	add_meta_box('osallistujat', 'Ilmoittautuneet', 'tapahtumaan_ilmonneet_metabox', 'tapahtumat', 'normal', 'default');
	add_meta_box('ilmo_tietoja', 'Ilmottautumisen tietoja', 'tapahtuman_ilmotietoja_metabox', 'tapahtumat', 'side', 'high');
	
	remove_meta_box('gos_simple_redirect', 'tapahtumat', 'side');
	remove_meta_box('slugdiv', 'tapahtumat', 'normal');
	remove_meta_box('authordiv', 'tapahtumat', 'normal');
}


// Poistetaan v-mäinen revolution slider, joka ei poistu helpolla..
add_action( 'do_meta_boxes' , 'poista_revolution_slider_tapahtumista' , 999999999);
function poista_revolution_slider_tapahtumista() {
	remove_meta_box( 'mymetabox_revslider_0', 'tapahtumat', 'normal' );
}


function tapahtuman_ohjeet() {
	echo '
<p><b>Tekstikentät</b><br />
Nimi-kenttää ei tarvitse lisätä. Muita tekstikenttiä voi lisätä oikealla. Lisäämällä loppuun tähden \'-pakollinen\' -päätteen pystyt tekemään siitä pakollisen kentät.<br />
Esimerkiksi "Allergiat -pakollinen" tuottaa seuraavanlaisen kohdan:<br />
<label for="allergiat">Allergiat: *</label><br />
<input type="text" id="allergiat"></p>


<p><b>Valintakentät</b><br />
Valintakentät ovat pakollisia kohtia, joissa valitaan yksi vaihtoehto monesta.<br />
Oletuksena valintakenttä on Kyllä/Ei -tyyppinen kysymys. <br/>Esimerkiksi "Alkoholiton" tuottaa seuraavanlaisen kohdan:<br />
Alkoholiton:<br />
<input type="radio" name="alkoholiton" id="holiton"><label for="holiton"> Kyllä </label><input type="radio" name="alkoholiton" id="holillinen"><label for="holillinen"> Ei </label></p>
<p>Lisäämällä kaksoiskauttamerkit pystyt luomaan lisävaihtoehtoja, ja nimeämään niitä. Tällöin myöskään "otsikkoa" ei ole. <br/>Esimerkiksi "Punaviini // Valkoviini // Kuohuviini" tuottaa:<br />
<input type="radio" name="viini" id="puna"><label for="puna"> Punaviini </label><input type="radio" name="viini" id="valkoviini"><label for="valkoviini"> Valkoviini </label><input type="radio" name="viini" id="kuohu"><label for="kuohu"> Kuohuviini </label></p>


<p><b>Ruksittavat</b><br />
Ruksittavat ovat tyypillisiä checkboxeja. Ne ovat vapaavalintaisia lisätietoja. Esimerkiksi "Perjantai" "Lauantai" ja "Sunnuntai" tuottavat:<br />
<input type="checkbox" id="perjantai"><label for="perjantai"> Perjantai</label><br />
<input type="checkbox" id="lauantai"><label for="lauantai"> Lauantai</label><br />
<input type="checkbox" id="sunnuntai"><label for="sunnuntai"> Sunnuntai</label><br />
</p>


';
}


// Päivämäärät -metaboksi

function tapahtuman_paivamaarat_metabox() {
	
	global $post;
	
	
	echo 'Kello on palvelimen ja wordpressin mukaan nyt <span style="text-decoration: underline;">' . date('d.m.Y H:i:s',current_time('timestamp')) . '</span><br />
Huomioithan ajassasi jos tämä on väärin, ja ilmoita asiasta sivuston ylläpitäjälle!<br /><br />';
	
	echo '<input type="hidden" name="tapahtuman_muokkaus" id="tapahtuma_nonce" value="' . wp_create_nonce( plugin_basename(__FILE__).'tapahtuman_muokkaus' ) . '" />';
	
	
	$tapahtumanaika = get_post_meta($post->ID, '_tapahtumanaika', true);
	if ($tapahtumanaika) {
	$tapahtumanpaiva = date('d.m.Y' , $tapahtumanaika);
	$tapahtumankello = date('H:i' , $tapahtumanaika);
	}
	
	echo '<b>Tapahtuman päivämäärä:</b><br /><label for="tapahtumanpaiva">Päivä: <i>(Muodossa '.date('d.m.Y',current_time('timestamp')).')</i></label><br />';
	echo '<input type="text" name="tapahtumanpaiva" id="tapahtumanpaiva" value="'.$tapahtumanpaiva.'"/><br />';
	echo '<label for="tapahtumankello">Kello: <i>(Muodossa 09:00)</i></label><br />';
	echo '<input type="text" name="tapahtumankello" id="tapahtumankello" value="'.$tapahtumankello.'"/><br /><br /><br />';
	
	
	
	
	
	$ilmoaika = get_post_meta($post->ID, '_ilmoaika', true);
	if (!$ilmoaika) {
		$ilmoaika = time();
	}
	$ilmopaiva = date('d.m.Y' , $ilmoaika);
	$ilmokello = date('H:i' , $ilmoaika);
	
	
	echo '<b>Ilmottautuminen aukeaa:</b><br /><label for="ilmopaiva">Päivä: <i>(Muodossa '.date('d.m.Y',current_time('timestamp')).')</i></label><br />';
	echo '<input type="text" name="ilmopaiva" id="ilmopaiva" value="'.$ilmopaiva.'"/><br />';
	echo '<label for="ilmokello">Kello: <i>(Muodossa 09:00)</i></label><br />';
	echo '<input type="text" name="ilmokello" id="ilmokello" value="'.$ilmokello.'"/><br />';
	
	echo '<br />';
	
	
	
	
	$ilmonloppuaika = get_post_meta($post->ID, '_ilmonloppuaika', true);
	if ($ilmonloppuaika) {
	$ilmonloppupaiva = date('d.m.Y' , $ilmonloppuaika);
	$ilmonloppukello = date('H:i' , $ilmonloppuaika);
	}
	
	echo '<b>Ilmon viimeinen päivämäärä:</b> <br /><i>Oletuksena tapahtuman päiväys</i><br /><label for="ilmonloppupaiva">Päivä: <i>(Muodossa '.date('d.m.Y',current_time('timestamp')).')</i></label><br />';
	echo '<input type="text" name="ilmonloppupaiva" id="ilmonloppupaiva" value="'.$ilmonloppupaiva.'"/><br />';
	echo '<label for="ilmonloppukello">Kello: <i>(Muodossa 09:00)</i></label><br />';
	echo '<input type="text" name="ilmonloppukello" id="ilmonloppukello" value="'.$ilmonloppukello.'"/><br />';
	
	echo '<br />';
	
}


// Ilmon tietoja -metaboxi
function tapahtuman_ilmotietoja_metabox() {
	global $post;
	echo '<label for="maxosallistujat">Max osallistujamäärä:</label><br />';
	echo '<input type="number" name="maxosallistujat" id="maxosallistujat" value="'.get_post_meta($post->ID, '_maxosallistujat', true).'"/><br />';
	echo '<i>Tyhjä ruutu tarkoittaa rajatonta osallistujamäärää</i><br /><br />';
	
	echo '<input type="checkbox" name="varasijat" id="varasijat" value=1 '.(get_post_meta($post->ID, '_varasijat', false)?'checked ':'').'/>';
	echo '<label for="varasijat">Salli varasijoille ilmottautuminen?</label><br />';
	
	echo '<input type="checkbox" name="piilota_ilmolista" id="piilota_ilmolista" value=1 '.(get_post_meta($post->ID, '_piilota_ilmolista', false)?'checked ':'').'/>';
	echo '<label for="piilota_ilmolista">Piilota julkinen nimilista?</label><br />';
	
}



// Ilmoittautuneet -metaboxi

function tapahtumaan_ilmonneet_metabox() {
	global $post;
	
	
	
	// Haetaan kaikki kentät. Tästä vois toki tehä oman funktionkin..
	$kentat = array('nimi' => 'Nimi');
	
	$tekstikentat = get_the_terms($post->ID,'tapahtuman_tekstikentat');
	
	if (is_array($tekstikentat)) {
		foreach ($tekstikentat as $kenttaobjekti) {
			if ($kenttaobjekti->slug == 'nimi') continue; // Estetään nimen tuplakysely
			$kentat[$kenttaobjekti->slug] = $kenttaobjekti->name;
		}
	}
	
	$monivalinnat = get_the_terms($post->ID,'tapahtuman_monivalinnat');
	
	if (is_array($monivalinnat)) {
		foreach ($monivalinnat as $monivalinta) {
			$kentat[$monivalinta->slug] = $monivalinta->name;
		}
	}
	
	$valinnat = get_the_terms($post->ID,'tapahtuman_valinnat');
	
	if (is_array($valinnat)) {
		foreach ($valinnat as $valinta) {
			$kentat[$valinta->slug] = $valinta->name;
		}
	}
	
	
	$ilmot = get_post_meta($post->ID, '_ilmot', true);
	$peruneet = get_post_meta($post->ID, '_peruneet', true);
	
	echo '<style>th { text-align: left; }</style>';
	echo '<div style="overflow-x: auto;"><table>';
	
	echo '<tr><th>n</th>';
	foreach ($kentat as $slugi => $nimi) {
		echo '<th>'.$nimi.'</th>';
	}
	echo '</tr>';
	$i = 1;
	foreach ($ilmot as $ilmo) {
		echo '<tr><td>'.$i.'.</td>';
		$i++;
		foreach ($kentat as $slugi => $nimi) {
			echo '<td>'.$ilmo[$slugi].'</td>';
		}
		echo '</tr>';
	}
	
	echo '</table></div><hr /><br /><br />';
	
	echo 'Sössitkö? Jos poistit kyseltäviä asioita välissä etkä onnistu lisäämään niitä takaisin niin alla on vielä kaikki ilmot. <br /><a onclick="var a = document.getElementById(\'raakadata\'); a.style.display = \'block\'">Näytä raakadata</a><br />';
	echo '<div id="raakadata" style="display:none; overflow-x: auto;">';
	
	echo '<table>';
	foreach ($ilmot as $ilmo) {
		echo '<tr>';
		foreach ($ilmo as $key => $data) {
			echo '<td>'.$key.': </td><td>'.$data.'</td>';
		}
		echo '</tr>';
	}
	echo '</table>';
	echo '<br />Ihan oikeasti raakana kaikki data:<br /> <pre>';
	var_dump($ilmot);
	echo '</pre>';
	echo '<hr /> Alla vielä peruuttaneet: <br /> <pre>';
	var_dump($peruneet);
	echo '</pre>';
	echo '</div>';
}




// METABOKSIEN TALLENNUS

add_action( 'save_post', 'tallenna_tapahtuman_metaboksit', 1, 2);

function tallenna_tapahtuman_metaboksit($post_id, $post) {
  if ( !wp_verify_nonce( $_POST['tapahtuman_muokkaus'], plugin_basename(__FILE__).'tapahtuman_muokkaus' ) || !current_user_can('edit_post',$post->ID)) {
		return $post->ID;
	}
	
  $tapahtumameta = array();
  
	$tapahtumameta['_tapahtumanaika'] = ( $_POST['tapahtumanpaiva'] ? strtotime($_POST['tapahtumanpaiva'].' '.$_POST['tapahtumankello']) : null);
	
	$tapahtumameta['_ilmoaika'] = ($_POST['ilmopaiva'] ? strtotime($_POST['ilmopaiva'].' '.$_POST['ilmokello']) : null);
	
	$tapahtumameta['_ilmonloppuaika'] = ($_POST['ilmonloppupaiva'] ?  strtotime($_POST['ilmonloppupaiva'].' '.$_POST['ilmonloppukello']) : $tapahtumameta['_tapahtumanaika']);
	
	
	$tapahtumameta['_maxosallistujat'] = intval($_POST['maxosallistujat']);
	$tapahtumameta['_varasijat'] = ($_POST['varasijat']? true : false) ;
	$tapahtumameta['_piilota_ilmolista'] = ($_POST['piilota_ilmolista']? true : false) ;
	
	
	//$tapahtumameta['_'] = $_POST[''];
	//$tapahtumameta['_'] = $_POST[''];
  
  foreach ($tapahtumameta as $key => $arvo) {
    		if ($post->post_type == 'revision') return; // Estää jotenkin tuplatallennusta..??
		$arvo = implode(',', (array)$arvo); //Tehdään mahdollisesta arraysta CSV tyyppinen array
		if (get_post_meta($post->ID, $key, FALSE)) {
			update_post_meta($post->ID, $key, $arvo); // Jos meta löytyy jo niin päivitetään
		} else {
			add_post_meta($post->ID, $key, $arvo); // Jos ei löydy, niin luodaan.
		}
		if(!$arvo) delete_post_meta($post->ID,$key); // Jos lomake tyhjä, niin poistetaan myös meta.
  }
  
}