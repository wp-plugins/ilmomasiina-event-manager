<?php
/*
Plugin Name: Ilmomasiina
Plugin URI: https://wordpress.org/plugins/ilmomasiina-event-manager/
Author: Tomi Ylä-Soininmäki
Author email: tomi.yla-soininmaki@fimnet.fi
Description: Ilmomasiina tapahtumien luomiseen ja ilmottautumiseen
Version: 0.3
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
		'public' => true,
    'show_in_search' => false,
		'publicly_queryable' => true,
		'show_ui' => (current_user_can( 'edit_posts' ) ? true : false),
		'show_in_admin_bar' => true,
		'hierarchial' => false,
		'supports' => array(  'title', 'author', 'editor', 'thumbnail' ),
		'has_archive' => false,
		'query_var' => false,
		'menu_position' => 5,
		'register_meta_box_cb' => 'tapahtuman_metaboksit',
		//'taxonomies' => array('tapahtuman_tekstikentat', 'tapahtuman_valinnat', 'tapahtuman_monivalinnat'),
		'capabilities' => array(
			'publish_posts' => 'edit_posts',
			'edit_posts' => 'edit_posts',
			'edit_post' => 'edit_posts',
			//'delete_posts' => 'edit_posts',
			'delete_post' => 'edit_posts',
		)
	));
	
}


// FLUSH mm. OSOITERAKENNE AKTIVOITAESSA
register_activation_hook(__FILE__, 'ilmomasiinan_aktivoituessa');
function ilmomasiinan_aktivoituessa() {
  add_option('ilmomasiinan_ensiajo', 'totta');
}
add_action('admin_init', 'ilmomasiinan_init');

function ilmomasiinan_init() {
  if ( is_admin() && get_option( 'ilmomasiinan_ensiajo' ) == 'totta' ) {
    delete_option( 'ilmomasiinan_ensiajo' );
    flush_rewrite_rules();
    
  }
  
}




// METABOKSIT

function tapahtuman_metaboksit() {
	add_meta_box('tapahtuman_paivat', 'Tapahtuman päivämäärät', 'tapahtuman_paivamaarat_metabox', 'tapahtumat', 'normal', 'high');
	add_meta_box('ilmo_kentat', 'Ilmottautumislomake', 'tapahtuman_kentat', 'tapahtumat', 'normal', 'high');
	add_meta_box('ilmo_ohjeet', 'Ohjeet tapahtuman järjestäjälle', 'tapahtuman_ohjeet', 'tapahtumat', 'normal', 'high');
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
<h1>Rivi-/Kenttätyypit</h1>
<p>Teksti-kohdat ovat <b>pakollisia</b> kyseisen kohdan ohjeita ja samalla kentän yksilöiviä nimiä. NIIDEN PITÄÄ OLLA UNIIKKEJA, eli esim. älä lisää useampaa "email" -kohtaa, vaan tarvittaessa esim "email 1", "email 2".</p>


<h2>Tekstikentät</h2>
<p><b>Nimi-kenttää ei tarvitse lisätä.</b><br />
Tekstillä "Allergiat:" syntyy seuraavanlainen kenttä:<br />
<label for="allergiat">Allergiat: </label><br />
<input type="text" id="allergiat"></p>


<h2>Valinta</h2>
<p>Valintakentät ovat kohtia, joissa valitaan yksi vaihtoehto monesta.<br />
Esimerkiksi ohje "Alkoholiton" vaihtoehdoilla "Kyllä // Ei" tuottaa seuraavanlaisen kohdan:<br />
Alkoholiton:<br />
<input type="radio" name="alkoholiton" id="holiton"><label for="holiton"> Kyllä</label><br />
<input type="radio" name="alkoholiton" id="holillinen"><label for="holillinen"> Ei</label></p>


<h2>Monivalinta</h2>
<p>Monivalintakentät ovat kohtia, joissa valitaan monta vaihtoehtoa.<br />
Esimerkiksi ohje "Osallistumispäivät" vaihtoehdoilla "Perjantai // Lauantai // Sunnuntai" tuottaa seuraavanlaisen kohdan:<br />
Osallistumispäivät:<br />
<input type="checkbox" id="perjantai"><label for="perjantai"> Perjantai</label><br />
<input type="checkbox" id="lauantai"><label for="lauantai"> Lauantai</label><br />
<input type="checkbox" id="sunnuntai"><label for="sunnuntai"> Sunnuntai</label><br />


<h2>Ohje</h2>
<p>Ohje-kentät tuottavat tämän tekstin kaltaista tekstiä.</p>

<h2>Iso tekstikenttä</h2>
<p>Vastaava kuin tekstikenttä, mutta isommalla laatikolla:</p>
<textarea style="min-width: 300px; min-height: 150px;" class="ilmoisoteksti"> </textarea>


';
}


// KENTÄT / VALINTARIVIT -metaboksi

function tapahtuman_kentat() {
  global $post;
  
  echo '<p>Ilmottautumislomakkeeseen lisätään automaattisesti Nimi-kenttä. Voit lisätä tässä lisäkenttiä. Älä muokkaa vanhoja kenttiä enää ensimmäisen ilmoittautumisen jälkeen.</p>';
  
  echo (get_post_meta($post->ID, '_ilmot', true)!=false && count(get_post_meta($post->ID, '_ilmot', true))>0 ? '<h1 style="color:red">Ilmottautumisia on jo tullut. Älä enää muokkaa kenttiä!</h1>Voit kyllä lisätä uusia loppuun.<br /><br />' : '');
  
  $kentat = get_post_meta($post->ID, '_kentat', true);
  $kentat = (is_array($kentat) && !empty($kentat) ? $kentat : array() ) ;
  
  /*
  echo '<pre>';
  var_dump($kentat);
  echo '</pre>';
  */

  end($kentat);
  $montako = key($kentat) + 3;
  $montako = ($montako<5?5:$montako);
  $montako = ($montako>99?99:$montako);
  reset($kentat);
  
  echo '<style>
.valintarivi {margin-bottom: 10px;}
.rivivalinta {display: inline-block; margin-right: 10px;} 
.rivintyyppi {width:15%; min-width: 60px; max-width: 130px;}
.vaihtoehdot, .ohjeenteksti {width: 40%; min-width: 200px;} 
.ohjekentta, .vaihtoehdotkentta {width: 100%;; min-width: 200px;}
</style>';
  
  for ($i = 0 ; $i < 99; $i++) {
    echo '<div class="valintarivi" id="'.$i.'_rivi" '.($i+1>$montako?'style="display:none;"':'').'>';
    
    echo '<div class="rivivalinta rivintyyppi" id="'.$i.'_rivintyyppi">';
    echo '	Rivin tyyppi: <br />';
    echo '	<select class="tyyppi" id="'.$i.'_tyyppi" name="'.$i.'_tyyppi" onchange="valittu('.$i.')">';
    echo '		<option value="tyhja"></option>';
    echo '    <option value="teksti"'.($kentat[$i]['tyyppi']=='teksti'?'selected':'').'>Tekstikenttä</option>';
    echo '    <option value="valinta"'.($kentat[$i]['tyyppi']=='valinta'?'selected':'').'>Valinta</option>';
    echo '    <option value="monivalinta"'.($kentat[$i]['tyyppi']=='monivalinta'?'selected':'').'>Monivalinta</option>';
    echo '		<option value="ohje"'.($kentat[$i]['tyyppi']=='ohje'?'selected':'').'>Ohjeteksti</option>';
    echo '    <option value="isoteksti"'.($kentat[$i]['tyyppi']=='isoteksti'?'selected':'').'>Iso tekstikenttä</option>';
    echo '	</select>';
    echo '</div>';
    
    echo '<div class="rivivalinta ohjeenteksti" id="'.$i.'_ohjeenteksti">';
    echo '	Teksti: <br />';
    echo '	<input type="text" name="'.$i.'_ohje" id="'.$i.'_ohje" class="ohjekentta" value="'.$kentat[$i]['ohje'].'"/>';
    echo '</div>';
    
    echo '<div class="rivivalinta vaihtoehdot" id="'.$i.'_vaihtoehdot">';
    echo '	Vaihtoehdot kauttamerkeillä (\' // \') eroteltuna: <br />';
    $vaihtoehdot = (is_array($kentat[$i]['vaihtoehdot']) ? implode(' // ',$kentat[$i]['vaihtoehdot']) : '');
    echo '	<input type="text" name="'.$i.'_vaihtoehdot" id="'.$i.'_vaihtoehdot" class="vaihtoehdotkentta" value="'.$vaihtoehdot.'" placeholder="Vaihtoehto 1 // Vaihtoehto 2 // Vaihtoehto 3"/>';
    echo '</div>';
    
    echo '<div class="rivivalinta pakollinen" id="'.$i.'_pakollinen">';
    echo '	<label for="'.$i.'_pakollinen">Pakollinen: </label><br />';
    echo '	<input type="checkbox" name="'.$i.'_pakollinen" id="'.$i.'_pakollinen" value="1" '.($kentat[$i]['pakollinen']?'checked':'').'/>';
    echo '</div>';
    
    echo '</div>';
  }
  
  echo '<button type="button" onclick="lisaa5rivia()">Lisää 5 riviä</button>';
  
  echo '<script>
var montakonakyy = '.$montako.'

function lisaa5rivia() {

maksimi = montakonakyy + 5;
for (var i = montakonakyy ; i < maksimi ; i++) {

var rivi = document.getElementById(montakonakyy+"_rivi");
rivi.style.display = "block";
valittu(montakonakyy);

montakonakyy = montakonakyy + 1;
}

}

function valittu(i) {

var rivi = document.getElementById(i+"_rivi");
var tyyppi = document.getElementById(i+"_tyyppi");
var ohjeenteksti = document.getElementById(i+"_ohjeenteksti");
var vaihtoehdot = document.getElementById(i+"_vaihtoehdot");
var pakollinen = document.getElementById(i+"_pakollinen");

vaihtoehdot.style.display = "none";
pakollinen.style.display = "none";

if (tyyppi.value == "monivalinta" ) {
vaihtoehdot.style.display = "inline-block";
pakollinen.style.display = "inline-block";
}

if (tyyppi.value == "valinta" ) {
vaihtoehdot.style.display = "inline-block";
}

if (tyyppi.value == "teksti" ) {
pakollinen.style.display = "inline-block";
}

}

for (i=0 ; i<'.($montako+1).' ; i++) {
valittu(i);
}

</script>';
  
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
	
  echo '<style> .paivaboksi {float:left; margin: 0 10px 10px 0;} </style>';
  echo '<div class="paivaboksi">';
	echo '<b>Tapahtuman päivämäärä:</b><br /><label for="tapahtumanpaiva">Päivä: <i>(Muodossa '.date('d.m.Y',current_time('timestamp')).')</i></label><br />';
	echo '<input type="text" name="tapahtumanpaiva" id="tapahtumanpaiva" value="'.$tapahtumanpaiva.'"/><br />';
	echo '<label for="tapahtumankello">Kello: <i>(Muodossa 09:00)</i></label><br />';
	echo '<input type="text" name="tapahtumankello" id="tapahtumankello" value="'.$tapahtumankello.'"/><br /><br /><br />';
	echo '</div>';
	
	
	
	
	$ilmoaika = get_post_meta($post->ID, '_ilmoaika', true);
	if (!$ilmoaika) {
		$ilmoaika = current_time('timestamp');
	}
	$ilmopaiva = date('d.m.Y' , $ilmoaika);
	$ilmokello = date('H:i' , $ilmoaika);
	
	
  echo '<div class="paivaboksi">';
	echo '<b>Ilmottautuminen aukeaa:</b><br /><label for="ilmopaiva">Päivä: <i>(Muodossa '.date('d.m.Y',current_time('timestamp')).')</i></label><br />';
	echo '<input type="text" name="ilmopaiva" id="ilmopaiva" value="'.$ilmopaiva.'"/><br />';
	echo '<label for="ilmokello">Kello: <i>(Muodossa 09:00)</i></label><br />';
	echo '<input type="text" name="ilmokello" id="ilmokello" value="'.$ilmokello.'"/><br />';
	echo '</div>';
	
	
	
	
	$ilmonloppuaika = get_post_meta($post->ID, '_ilmonloppuaika', true);
	if ($ilmonloppuaika) {
	$ilmonloppupaiva = date('d.m.Y' , $ilmonloppuaika);
	$ilmonloppukello = date('H:i' , $ilmonloppuaika);
	}
	
  echo '<div class="paivaboksi">';
	echo '<b>Ilmon viimeinen päivämäärä:</b> <br /><i>Oletuksena tapahtuman päiväys</i><br /><label for="ilmonloppupaiva">Päivä: <i>(Muodossa '.date('d.m.Y',current_time('timestamp')).')</i></label><br />';
	echo '<input type="text" name="ilmonloppupaiva" id="ilmonloppupaiva" value="'.$ilmonloppupaiva.'"/><br />';
	echo '<label for="ilmonloppukello">Kello: <i>(Muodossa 09:00)</i></label><br />';
	echo '<input type="text" name="ilmonloppukello" id="ilmonloppukello" value="'.$ilmonloppukello.'"/><br />';
	echo '</div>';
  
  echo '<div style="clear:both;"> </div>';
	
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
	
	echo '<br /><input type="checkbox" name="yksityinen_tapahtuma" id="yksityinen_tapahtuma" value=1 '.(get_post_meta($post->ID, '_yksityinen_tapahtuma', false)?'checked ':'').'/>';
	echo '<label for="yksityinen_tapahtuma">Yksityinen tapahtuma (tapahtuma löytyy vain linkin tietämällä)</label><br />';
	
}



// Ilmoittautuneet -metaboxi

function tapahtumaan_ilmonneet_metabox() {
	global $post;
	
	
	$kentat = get_post_meta($post->ID,'_kentat', true);
  if ($kentat == false) return;
  
	foreach ($kentat as $key => $kentta) {
    if ($kentta['tyyppi'] == 'ohje' ) {
      unset($kentat[$key]);
    }
	}
	
	$ilmot = get_post_meta($post->ID, '_ilmot', true);
  $ilmot = jarjesta_ilmot_aika($ilmot);
	$peruneet = get_post_meta($post->ID, '_peruneet', true);
	if ($ilmot == false) return;
  
  $csvdata = 'sep=,%0A';
  
	echo '<style>th { text-align: left; }</style>';
	echo '<div style="overflow-x: auto;"><table>';
	
	echo '<tr><th>n</th><th>Nimi</th>';
  $csvdata .= 'n%2CNimi%2C';
  
	foreach ($kentat as $kentta) {
    echo '<th>'.$kentta['ohje'].'</th>';
  $csvdata .= '%22'.$kentta['ohje'].'%22'.'%2C';
	}
	echo '</tr>';
  $csvdata .= '%0A';
  
	$i = 0;
	foreach ($ilmot as $ilmo) {
		$i++;
		echo '<tr><td>'.$i.'.</td><td>'.$ilmo['nimi'].'</td>';
    $csvdata .= $i.'%2C' . '%22'.$ilmo['nimi'].'%22'.'%2C';
		foreach ($kentat as $key => $kentta) {
			echo '<td>'.$ilmo[$kentta['ohje']].'</td>';
      $csvdata .= '%22'.$ilmo[$kentta['ohje']].'%22'.'%2C';
		}
    if ($i == get_post_meta($post->ID, '_maxosallistujat', true) && get_post_meta($post->ID, '_varasijat', false)) {
      echo '</tr><tr><td colspan="'.(2+count($kentat)).'" style="border-top: 1px solid #ccc"><h3>VARALLA:</h3></td></tr>';
      $csvdata .= '%0AVaralla:%0A';
    } else {
      echo '</tr>';
      $csvdata .= '%0A';
    }
  }
	
	echo '</table></div><hr /><br /><br />';
  
  echo '<p><a download="'.$post->post_title.'.csv" href="data:application/csv;charset=utf-8,'.$csvdata.'" style="font-size:2em; font-weight:bold;">Lataa osallistujat</a> (.csv)</p>';
	
	echo '<p>Sössitkö? Tai haluatko tarkistaa peruutetut tai muokatut ilmoittautumiset? <br />Jos muutit kyseltäviä asioita ilmoittautumisen saavuttua niin alla on vielä kaikki ilmot. </p><p><a onclick="var a = document.getElementById(\'raakadata\'); a.style.display = \'block\'">Näytä raakadata</a></p>';
	echo '<div id="raakadata" style="display:none; overflow-x: auto;">';
	
  echo '<table>';
  foreach ($ilmot as $ilmo) {
    echo '<tr>';
    foreach ($ilmo as $key => $kentta) {
      echo'<td style="font-weight:bold;">'. $key .'</td>';
      echo'<td style="">'. $kentta .'</td>';
    }
    echo '</tr>';
  }
  echo '</table>';
  
	echo '<pre>';
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
	$tapahtumameta['_yksityinen_tapahtuma'] = ($_POST['yksityinen_tapahtuma']? true : false) ;
	
  $tapahtumameta['_kentat'] = tallenna_tapahtuman_kentat_metaboksi($post_id);
	
	//$tapahtumameta['_'] = $_POST[''];
	//$tapahtumameta['_'] = $_POST[''];
  
  foreach ($tapahtumameta as $key => $arvo) {
    		if ($post->post_type == 'revision') return; // Estää jotenkin tuplatallennusta..??
		if (get_post_meta($post->ID, $key, FALSE)) {
			update_post_meta($post->ID, $key, $arvo); // Jos meta löytyy jo niin päivitetään
		} else {
			add_post_meta($post->ID, $key, $arvo); // Jos ei löydy, niin luodaan.
		}
		if(!$arvo) delete_post_meta($post->ID,$key); // Jos lomake tyhjä, niin poistetaan myös meta.
  }
  
}

// Palautetaan array täytetyistä kentistä
function tallenna_tapahtuman_kentat_metaboksi($post_id) {
  $kentat = array();
  
  for ($i = 0 ; $i<99 ; $i++) {
    if ($_POST[$i.'_tyyppi'] != 'tyhja') {
      $kentta = array();
      $kentta['tyyppi'] = $_POST[$i.'_tyyppi'];
      $kentta['ohje'] = $_POST[$i.'_ohje'];
      $kentta['pakollinen'] = ($_POST[$i.'_pakollinen']=='1'?true:false);
      $_POST[$i.'_vaihtoehdot'] = str_replace(' // ', '//', $_POST[$i.'_vaihtoehdot']);
      $kentta['vaihtoehdot'] = explode('//', $_POST[$i.'_vaihtoehdot']);
      $kentat[$i] = $kentta;
    }
  }
 	
  return $kentat;
}

function jarjesta_array_sisemman_arvon_mukaan($array, $key2) {
  $uusiarray = array();
  $keyt = array();
  foreach ($array as $key1 => $arvo1 ) {
    $keyt[$key1] = $arvo1[$key2];
  }
  
  asort($keyt);
  
  foreach ($keyt as $key1 => $arvo2) {
    $uusiarray[$key1] = $array[$key1];
  }
  return $uusiarray;
}

function jarjesta_ilmot_aika($ilmot) {
  $ilmot = jarjesta_array_sisemman_arvon_mukaan($ilmot, 'ilmoaika');
  return $ilmot;
}