<?php

add_shortcode('ilmomasiina' , 'ilmomasiinan_kayttoliittyma');


// VARSINAINEN OHJELMA

function ilmomasiinan_kayttoliittyma() {
	$tuloste = '';
	
	if (isset($_GET['tapahtuma'])) {
		$tuloste .= tulosta_tapahtuma($_GET['tapahtuma']);
	} else {
		$tuloste .= tulosta_tapahtumalista();
	}
	
	return $tuloste;
}



// TAPAHTUMALISTAUS
function tulosta_tapahtumalista() {
	$tuloste = '';
	
	// Uusi tapahtuma -linkki
	if (current_user_can('edit_posts')) {
		$tuloste .= '<a href="'.admin_url('post-new.php?post_type=tapahtumat').'" target="_blank">Luo uusi tapahtuma</a><br />';
		$tuloste .= '<a href="'.admin_url('edit.php?post_type=tapahtumat').'" target="_blank">Muokkaa tapahtumiasi</a><br /><br />';
	}
	
	$tapahtumat = hae_tapahtumat();
	
	$ilmoauki = array();
	$ilmotulossa = array();
	$ilmokiinni = array();
	// Lopulta alkuperäseen arrayhin jää jäljelle menneet tapahtumat
	
	foreach ($tapahtumat as $key => $tapahtuma) {
		$id = $tapahtuma->ID;
		$ilmoaika = get_post_meta($id, '_ilmoaika', true);
		$ilmonloppuaika = get_post_meta($id, '_ilmonloppuaika', true);
		$tapahtumanaika = get_post_meta($id, '_tapahtumanaika', true);
		$yksityinen = get_post_meta($id, '_yksityinen_tapahtuma', true);
    if ( $yksityinen ) {
			unset($tapahtumat[$key]);
      continue;
    }
    
    
		$nyt = current_time('timestamp');
		
		if ($ilmoaika > $nyt) {
			$ilmotulossa[] = $tapahtuma;
			unset($tapahtumat[$key]);
			continue;
		}
		
		if ($ilmoaika < $nyt && $ilmonloppuaika > $nyt && $tapahtumanaika > $nyt) {
			$ilmoauki[] = $tapahtuma;
			unset($tapahtumat[$key]);
			continue;
		}
		
		if ($ilmoaika < $nyt && $ilmonloppuaika < $nyt && $tapahtumanaika > $nyt) {
			$ilmokiinni[] = $tapahtuma;
			unset($tapahtumat[$key]);
			continue;
		}
	}
	
	if ($ilmoauki) {
		$tuloste .= '<h3>Ilmo auki:</h3>';
		$tuloste .= tulosta_tapahtuman_alilista($ilmoauki);
	}
	
	if ($ilmotulossa) {
	$tuloste .= '<h3>Ilmo tulossa:</h3>';
	$tuloste .= tulosta_tapahtuman_alilista($ilmotulossa);
	}
	
	if ($ilmokiinni) {
	$tuloste .= '<h3>Ilmo sulkeutunut:</h3>';
	$tuloste .= tulosta_tapahtuman_alilista($ilmokiinni);
	}
	
	if ($tapahtumat) {
	$tuloste .= '<h3>Menneet tapahtumat:</h3>';
	$tuloste .= tulosta_tapahtuman_alilista($tapahtumat);
	}
	
	return $tuloste;
}

// Tulosta listana tapahtumat
function tulosta_tapahtuman_alilista($tapahtumat) {
	$tuloste = '';
	
	foreach ($tapahtumat as $tapahtuma) {
		$tuloste .= '<a href="'.get_permalink($tapahtuma->ID).'">'.$tapahtuma->post_title.'</a>';
		
		if (get_post_meta($tapahtuma->ID, '_maxosallistujat', true) <= count(get_post_meta($tapahtuma->ID, '_ilmot', true)) && get_post_meta($tapahtuma->ID, '_maxosallistujat', true)>0 && current_time('timestamp') < get_post_meta($tapahtuma->ID, '_tapahtumanaika', true)) {
			$tuloste .= ' <span style="font-weight:bold;">Täynnä!</span>';
		}
		
		if ($tapahtuma->post_author == get_current_user_id()) {
			$tuloste .= '<a style="float: right;" target="_blank" href="'.get_edit_post_link($tapahtuma->ID).'">Muokkaa tapahtumaa</a>';
		}
		$tuloste .= '<br />';
	}
	
	return $tuloste;
}


// HAE TAPAHTUMAT
function hae_tapahtumat() {
	$args = array(
		'post_type'        => 'tapahtumat',
		'post_status'      => 'publish',
		'meta_key'         => '_tapahtumanaika',
		'orderby'          => 'meta_value',
		'order'            => 'DESC',
		'posts_per_page'   => 500,
	);
	
	$tapahtumat = get_posts($args);
	
	return $tapahtumat;
}


add_filter('the_content', 'tulosta_tapahtuma' , 11, 1);

// YKSITTÄINEN TAPAHTUMA
function tulosta_tapahtuma($content) {
	global $post;
  
  if ($post->post_type != 'tapahtumat') return $content;
  
  $id = $post->ID;
	$tuloste = '<div class="tapahtuma">';
	
	if ($post->post_author == get_current_user_id()) {
		$tuloste .= '<p><a target="_blank" href="'.get_edit_post_link($post->ID).'">Muokkaa tapahtumaa tai katsele osallistujia</a></p>';
	}
	
	
	$tuloste .= get_the_post_thumbnail( $post->ID, 'large' ).'<br /><br />';
	
	
	$tuloste .= '<table>

<tr>
<td>Päivämäärä:</td>
<td>'.date('d.m.Y H:i',get_post_meta($id, '_tapahtumanaika', true)).'</td>
</tr>

<tr>
<td>Ilmoittautuminen auki:</td>
<td>'.date('d.m. H:i',get_post_meta($id, '_ilmoaika', true));
	
	if (current_time('timestamp')<get_post_meta($id, '_ilmoaika', true)) {
		$ero = get_post_meta($id, '_ilmoaika', true) - current_time('timestamp');
		$paivat = floor($ero/60/60/24);
		$tunnit = floor(($ero-$paivat*60*60*24)/60/60);
		$minuutit = floor(($ero-$paivat*60*60*24-$tunnit*60*60)/60);
		$sekunnit = floor($ero-$paivat*60*60*24-$tunnit*60*60-$minuutit*60);
    $tuloste .= ' <i>Aikaa ilmon alkuun: </i>' .($paivat?$paivat.'d, ':'').($tunnit?$tunnit.'h, ':'').($minuutit?$minuutit.'min':'').($ero<(60*60)?', '.$sekunnit.'s' : '' );
  }
  
  if (get_post_meta($id, '_tapahtumanaika', true) != get_post_meta($id, '_ilmonloppuaika', true)) {
    $tuloste .= '<br /> - <br />'.date('d.m. H:i',get_post_meta($id, '_ilmonloppuaika', true));
  }
	
	$tuloste .= '
</td>
</tr>';
	
	
	if (get_post_meta($id, '_maxosallistujat', true)) {
		$tuloste .= '
  <tr>
	<td>Osallistujaraja:</td>
	<td>'.get_post_meta($id, '_maxosallistujat', true).'</td>
	</tr>';
	}
	
	if (get_post_meta($id, '_maxosallistujat', true)) {
		$tuloste .= '
	<tr>
	<td>Varasijat käytössä:</td>
	<td>'.(get_post_meta($id, '_varasijat', true)?'Kyllä':'Ei').'</td>
	</tr>';
	}
	
	$tuloste .= '

</table><br />';
	
	
	$tuloste .= $content;
	
	$tuloste .= '<hr>';
	
	if (isset($_POST['ilmo_nonce'])) {
		$tuloste .= '<p>'.tallenna_ilmo($id).'</p>';
	}
	
	if (isset($_GET['peru'])) {
		$tuloste .= peruuta_osallistuminen($id, $_GET['peru']);
	}
	
	if (get_post_meta($id, '_tapahtumanaika', true) - current_time('timestamp') > 0 ) {
		$tuloste .= tapahtuman_osallistujalista($id);
	}
	
	
	if (!isset($_POST['ilmo_nonce'])) {	
		
		if ( // Hemmetin pitkä if klausuuli alkaa
			current_time('timestamp') > get_post_meta($id, '_ilmoaika', true) && 
			(
				current_time('timestamp') < get_post_meta($id, '_ilmonloppuaika', true) || 
				isset($_GET['paivitys'])
			)  &&  (
				get_post_meta($id, '_varasijat', true) || 
				get_post_meta($id, '_maxosallistujat', true) > count(get_post_meta($id, '_ilmot', true)) || 
				!(get_post_meta($id, '_maxosallistujat', true)>0)
			) 
		) { // Hemmetin pitkä if klausuuli loppuu, ja itse if sisältö alkaa
			$tuloste .= ilmottaudu_tapahtumaan($id);
		} else {
			
			if ($post->post_author == get_current_user_id() && get_post_meta($id, '_tapahtumanaika', true) - time() > 0 ) {
				$tuloste .= '<p>Ilmo on kiinni, mutta koska olet tapahtuman moderaattori, niin tässä kuitenkin sinulle ilmolomake:</p>';
				$tuloste .= ilmottaudu_tapahtumaan($id);
			}
			
		}
	}
	$tuloste .= '</div>';
  
  
  if ($post->post_author == get_current_user_id()) {
    ob_start();
    echo '<h1>Osallistujat:</h1>Tämä näkyy vain tapahtuman ylläpitäjälle.<br /><br />';
    tapahtumaan_ilmonneet_metabox();
    $tuloste .= ob_get_clean();
  }
  
	return $tuloste;
}



// Listataan jo ilmottautuneet
function tapahtuman_osallistujalista($id) {
	$tuloste = 'Ilmoittautuneet: ';
  $ilmot = get_post_meta($id, '_ilmot', true);
  $ilmot = jarjesta_ilmot_aika($ilmot);
  
  if (get_post_meta($id, '_piilota_ilmolista', true)) return $tuloste. '<br />'.count($ilmot).' kpl <br />';
  if (is_array($ilmot)) {
    
    
    $tuloste .= '<ol>';
    $i = 0;
    foreach ($ilmot as $md5 => $ilmo) {
      $tuloste .= '<li>'.(isset($ilmo['anonyymi']) && $ilmo['anonyymi']==true?'<i>Anonyymi</i>':$ilmo['nimi']);
      $tuloste .= ' <a href="?tapahtuma='.$id.'&muokkaa='.$md5.'">Muokkaa</a>';
      $i++;
      if ($i == get_post_meta($id, '_maxosallistujat', true)) {
        $tuloste .= '</li></ol><hr /><ol>';
      } else {
        $tuloste .= '</li>';
      }
    }
    $tuloste .= '</ol>';
  }
  
  return $tuloste;
}

// Ilmottautumislomake
function ilmottaudu_tapahtumaan($id, $paivitys=false) {
	$tuloste = '';
	$paivitys = (isset($_GET['muokkaa']) ? true: false);
	if ($paivitys==false) {
		$tuloste .= '<h3>Ilmoittautumislomake:</h3>';
	} else {
		$tuloste .= '<h3>Päivitä aiempaa vastausta</h3>';
		$tuloste .= '<p>Myös vanhat tiedot säilyvät tietokannassa väärinkäytösten ja virheiden estämiseksi.</p>';
		$tuloste .= '<p><a href="?tapahtuma='.$id.'&peru='.$_GET['muokkaa'].'">Peru ilmottautuminen</a></p>';
	}
	
	$tuloste .= '<form method="post" action="">';
	
  $ilmoaika = current_time('timestamp');
  
	$tuloste .= '<input type="hidden" name="ilmo_nonce" id="ilmo_nonce" value="' . wp_create_nonce( plugin_basename(__FILE__).'ilmon_nonce'.$ilmoaika ) . '" />';
	$tuloste .= '<input type="hidden" name="ilmo_aika" id="ilmo_aika" value="'. $ilmoaika .'" />';
	$tuloste .= ( $paivitys ? '<input type="hidden" name="paivitys" value="'.$_GET['muokkaa'].'">' : '' );
	
	$tuloste .= '<label class="ilmo_ohje" for="nimi">Nimi: *</label><br />';
	$tuloste .= '<input class="ilmoteksti" type="text" name="nimi" id="nimi" required/><br />';
	if (!get_post_meta($id, '_piilota_ilmolista', true)) {
		$tuloste .= '<input class="ilmomonivalinta" type="checkbox" name="anonyymi" id="anonyymi" value="1"/>';
		$tuloste .= ' <label class="anonyymi" for="anonyymi">Piilota nimi julkisesta listasta</label><br /><br />';
	}
	
  $kentat = get_post_meta($id, '_kentat', true);
  
  foreach ($kentat as $key => $kentta) {
    if ($kentta['tyyppi'] == 'ohje') {
      $tuloste .= '<p>'.$kentta['ohje'].'</p>';
    }
    
    if ($kentta['tyyppi'] == 'teksti') {
      $tuloste .= '<p><label class="ilmo_ohje" for="'.$key.'_kentta">'.$kentta['ohje'].' '.($kentta['pakollinen']?'*':'').'</label><br />';
      $tuloste .= '<input class="ilmoteksti" type="text" name="'.$key.'" id="'.$key.'_kentta" '.($kentta['pakollinen']?'required':'').' /></p>';
    }
    
    if ($kentta['tyyppi'] == 'isoteksti') {
      $tuloste .= '<p><label class="ilmo_ohje" for="'.$key.'_kentta">'.$kentta['ohje'].' '.($kentta['pakollinen']?'*':'').'</label><br />';
      $tuloste .= '<textarea style="min-width: 300px; min-height: 150px;" class="ilmoisoteksti" type="text" name="'.$key.'" id="'.$key.'_kentta" '.($kentta['pakollinen']?'required':'').'> </textarea></p>';
    }
    
    if ($kentta['tyyppi'] == 'monivalinta') {
      $tuloste .= '<p>'.$kentta['ohje'].($kentta['pakollinen']?'*':'').'<br />';
      $i = 0;
      foreach ($kentta['vaihtoehdot'] as $vaihtoehto) {
        $tuloste .= '<input type="radio" class="ilmomonivalinta" name="'.$key.'" id="'.$key.'_kentta_'.$i.'" value="'.$i.'" '.($kentta['pakollinen']?'required':'').'>';
        $tuloste .= '<label for="'.$key.'_kentta_'.$i.'">'.$vaihtoehto.'</label><br />';
        $i++;
      }
      $tuloste .= '</p>';
    }
    
    if ($kentta['tyyppi'] == 'valinta') {
      $tuloste .= '<p>'.$kentta['ohje'].($kentta['pakollinen']?'*':'').'<br />';
      $i = 0;
      foreach ($kentta['vaihtoehdot'] as $vaihtoehto) {
        $tuloste .= '<input type="checkbox" class="ilmovalinta" name="'.$key.'_'.$i.'" id="'.$key.'_kentta_'.$i.'" value="kylla" >';
        $tuloste .= '<label for="'.$key.'_kentta_'.$i.'">'.$vaihtoehto.'</label><br />';
        $i++;
      }
      $tuloste .= '</p>';
    }
  }
  
  
	$tuloste .= '<input type="submit" value="Lähetä" />';
	$tuloste .= '</form>';
	return $tuloste;
  
  
	
}


// TALLENNA ILMO 

function tallenna_ilmo($id) {
	
	if (   ( (current_time('timestamp') < get_post_meta($id, '_ilmoaika', true)   ||   (current_time('timestamp') > get_post_meta($id, '_ilmonloppuaika', true) && !isset($_POST['paivitys'])) ) && !get_post_field( 'post_author', $id ) == get_current_user_id()   )   || !wp_verify_nonce( $_POST['ilmo_nonce'], plugin_basename(__FILE__).'ilmon_nonce' . $_POST['ilmo_aika'] )) return 'Tallennus epäonnistui.';
	
	
	
	$ilmot = get_post_meta($id, '_ilmot', true);
  $kentat = get_post_meta($id, '_kentat', true);
	
	
	$paivitys = (isset($_POST['paivitys']) && isset($ilmot[$_POST['paivitys']])? $_POST['paivitys'] : false );
	
	$vastaus = array();
	
	$vastaus['nimi'] = sanitize_text_field($_POST['nimi']);
	$vastaus['anonyymi'] = ($_POST['anonyymi']==1?true:false);
	
	$ilmoaika = sanitize_text_field($_POST['ilmo_aika']);
	$vastaus['ilmoaika'] = $ilmoaika;
	
	if ($paivitys == false ) {
		$md5 = md5($ilmoaika.$vastaus['nimi']);
	} else {
		$md5 = $paivitys;
	}
	
  foreach ($kentat as $key => $kentta) {
    if ($kentta['tyyppi']=='ohje') continue;
    $vastaus[$kentta['ohje']] = sanitize_text_field($_POST[$key]);
    
    if ($kentta['tyyppi']=='valinta') {
      $vastaus[$kentta['ohje']] = '';
      foreach ($kentta['vaihtoehdot'] as $vaihtoehtokey => $vaihtoehto) {
        $vastaus[$kentta['ohje']] .= ($_POST[$key.'_'.$vaihtoehtokey] == 'kylla' ? $vaihtoehto.', ' : '');
      }
    }
    
    if ($kentta['tyyppi']=='monivalinta') {
      $vastaus[$kentta['ohje']] = $kentta['vaihtoehdot'][$_POST[$key]];
    }
  }
    
	/*$tekstikentat = get_the_terms($id,'tapahtuman_tekstikentat');
	
	if (is_array($tekstikentat)) {
		foreach ($tekstikentat as $kenttaobjekti) {
			$slugi = $kenttaobjekti->slug;
			$nimi = $kenttaobjekti->name;
			if ($slugi == 'nimi') continue; // Estetään nimen tuplakysely
			
			$vastaus[$slugi] = sanitize_text_field($_POST[$slugi]);
		}
	}
	
	$monivalinnat = get_the_terms($id,'tapahtuman_monivalinnat');
	
	if (is_array($monivalinnat)) {
		foreach ($monivalinnat as $monivalinta) {
			$slugi = $monivalinta->slug;
			
			$vastaus[$slugi] = ($_POST[$slugi] == 1 ? 'Kyllä' : 'Ei');
		}
	}
	
	$valinnat = get_the_terms($id,'tapahtuman_valinnat');
	
	if (is_array($valinnat)) {
		foreach ($valinnat as $valinta) {
			$slugi = $valinta->slug;
			
			$vastaus[$slugi] = sanitize_text_field($_POST[$slugi]);
			$vastaus[$slugi] = ($vastaus[$slugi] == 'kylla' ? 'Kyllä' : $vastaus[$slugi]);
			$vastaus[$slugi] = ($vastaus[$slugi] == 'ei' ? 'Ei' : $vastaus[$slugi]);
		}
		
	}*/
	
	if (isset($ilmot[$md5]) && $ilmot[$md5]['nimi'] == $vastaus['nimi']) return 'Lähettämästi tiedot löytyvät jo tietokannasta..';
	
	if ($paivitys) {
		$vastaus['ennenmuokkausta'] = $ilmot[$md5];
	}
	
	$ilmot[$md5] = $vastaus;
	
	
	if (get_post_meta($id, '_ilmot', false)) {
		$onnistuko = update_post_meta($id, '_ilmot', $ilmot);
	} else {
		$onnistuko = add_post_meta($id, '_ilmot', $ilmot);
	}
	
	$tuloste .= ($onnistuko? 'Tiedot lisätty onnistuneesti!' : 'Ilmottautuminen epäonnistui.');
	
	return $tuloste;
}

// PERUTAAN OSALLISTUMINEN
function peruuta_osallistuminen($id, $md5) {
	$ilmot = get_post_meta($id, '_ilmot', true);
	$peruneet = get_post_meta($id, '_peruneet', true);
	if ( isset($ilmot[$md5]) ) {
		
		$peruneet[$md5] = $ilmot[$md5];
		unset($ilmot[$md5]);
		
		if (get_post_meta($id, '_ilmot', false)) {
			update_post_meta($id, '_ilmot', $ilmot);
		} else {
			add_post_meta($id, '_ilmot', $ilmot);
		}
		
		if (get_post_meta($id, '_peruneet', false)) {
			update_post_meta($id, '_peruneet', $peruneet);
		} else {
			add_post_meta($id, '_peruneet', $peruneet);
		}
		$tuloste = '<p>Ilmottautuminen peruttu.</p>';
	} else {
		$tuloste = '<p>Ilmottautumista ei löydetty, eikä sitä poistettu.</p>';
	}
	return $tuloste;
}