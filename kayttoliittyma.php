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
		$nyt = time();
		
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
		$tuloste .= '<a href="?tapahtuma='.$tapahtuma->ID.'">'.$tapahtuma->post_title.'</a>';
		
		if (get_post_meta($tapahtuma->ID, '_maxosallistujat', true) <= count(get_post_meta($tapahtuma->ID, '_ilmot', true)) && get_post_meta($tapahtuma->ID, '_maxosallistujat', true)>0 && time() < get_post_meta($tapahtuma->ID, '_tapahtumanaika', true)) {
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



// YKSITTÄINEN TAPAHTUMA
function tulosta_tapahtuma($id) {
	$post = get_post($id);
	setup_postdata($post);
	$tuloste = '<div class="tapahtuma">';
	
	if ($post->post_author == get_current_user_id()) {
		$tuloste .= '<p><a target="_blank" href="'.get_edit_post_link($post->ID).'">Muokkaa tapahtumaa</a></p>';
	}
	
	
	$tuloste .= get_the_post_thumbnail( $post->ID, 'large' ).'<br /><br />';
	
	$tuloste .= '<h2>'.$post->post_title.'</h2>';
	
	$tuloste .= '<table>

<tr>
<td>Päivämäärä:</td>
<td>'.date('d.m.Y H:i',get_post_meta($id, '_tapahtumanaika', true)).'</td>
</tr>

<tr>
<td>Ilmoittautuminen auki:</td>
<td>'.date('d.m. H:i',get_post_meta($id, '_ilmoaika', true));
	
	if (time()<get_post_meta($id, '_ilmoaika', true)) {
		$ero = get_post_meta($id, '_ilmoaika', true) - time();
		$paivat = floor($ero/60/60/24);
		$tunnit = floor(($ero-$paivat*60*60*24)/60/60);
		$minuutit = floor(($ero-$paivat*60*60*24-$tunnit*60*60)/60);
		$tuloste .= ' <i>Aikaa ilmon alkuun: </i>' .($paivat?$paivat.'d, ':'').($tunnit?$tunnit.'h, ':'').($minuutit?$minuutit.'min ':'');
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
	
	
	$tuloste .= apply_filters( 'the_content', $post->post_content );
	
	$tuloste .= '<hr>';
	
	if (isset($_POST['ilmo_nonce'])) {
		$tuloste .= '<p>'.tallenna_ilmo($id).'</p>';
	}
	
	if (get_post_meta($id, '_tapahtumanaika', true) - time() > 0 ) {
		$tuloste .= tapahtuman_osallistujalista($id);
	}
	
	
	 if (!isset($_POST['ilmo_nonce'])) {	
		 
		if (time() > get_post_meta($id, '_ilmoaika', true) && time() < get_post_meta($id, '_ilmonloppuaika', true)  && (get_post_meta($id, '_varasijat', true) || get_post_meta($id, '_maxosallistujat', true) > count(get_post_meta($id, '_ilmot', true)) || !(get_post_meta($id, '_maxosallistujat', true)>0)) /* Jo osallistuneiden määrä */  ) {
			$tuloste .= ilmottaudu_tapahtumaan($id);
		} else {
			
			if ($post->post_author == get_current_user_id() && get_post_meta($id, '_tapahtumanaika', true) - time() > 0 ) {
				$tuloste .= '<p>Ilmo on kiinni, mutta koska olet tapahtuman moderaattori, niin tässä kuitenkin sinulle ilmolomake:</p>';
				$tuloste .= ilmottaudu_tapahtumaan($id);
			}
			
		}
	}
	$tuloste .= '</div>';
	wp_reset_postdata();
	return $tuloste;
}



// Listataan jo ilmottautuneet
function tapahtuman_osallistujalista($id) {
	$tuloste = 'Ilmoittautuneet: ';
	$ilmot = get_post_meta($id, '_ilmot', true);
	
	if (get_post_meta($id, '_piilota_ilmolista', true)) return $tuloste. '<br />'.count($ilmot).' kpl <br />';
	
	$tuloste .= '<ol>';
	$i = 0;
	foreach ($ilmot as $ilmo) {
		$tuloste .= '<li>'.(isset($ilmo['anonyymi']) && $ilmo['anonyymi']==true?'<i>Anonyymi</i>':$ilmo['nimi']);
		$i++;
		if ($i == get_post_meta($id, '_maxosallistujat', true)) {
			$tuloste .= '<br /><hr /></li>';
		} else {
			$tuloste .= '</li>';
		}
	}
	$tuloste .= '</ol>';
	
	return $tuloste;
}

// Ilmottautumislomake
function ilmottaudu_tapahtumaan($id) {
	$tuloste = '<h3>Ilmottautumislomake:</h3>';
	
	$tuloste .= '<form method="post" action="">';
	
	$tuloste .= '<input type="hidden" name="ilmo_nonce" id="ilmo_nonce" value="' . wp_create_nonce( plugin_basename(__FILE__).'ilmon_nonce' ) . '" />';
	$tuloste .= '<input type="hidden" name="ilmo_aika" id="ilmo_aika" value="' . time() . '" />';
	
	$tuloste .= '<label class="ilmo_ohje" for="nimi">Nimi: *</label><br />';
	$tuloste .= '<input class="ilmoteksti" type="text" name="nimi" id="nimi" required/><br />';
	if (!get_post_meta($id, '_piilota_ilmolista', true)) {
		$tuloste .= '<input class="ilmomonivalinta" type="checkbox" name="anonyymi" id="anonyymi" value="1"/>';
		$tuloste .= ' <label class="anonyymi" for="anonyymi">Piilota nimi julkisesta listasta</label><br /><br />';
	}
	
	$tekstikentat = get_the_terms($id,'tapahtuman_tekstikentat');
	
	if (is_array($tekstikentat)) {
		foreach ($tekstikentat as $kenttaobjekti) {
			$slugi = $kenttaobjekti->slug;
			$nimi = $kenttaobjekti->name;
			$pakollinen = false;
			if (substr($nimi, -11) == '-pakollinen') {
				$pakollinen = true;
				$nimi = rtrim($nimi, '-pakollinen');
			}
			if ($slugi == 'nimi') continue; // Estetään nimen tuplakysely
			$tuloste .= '<label class="ilmo_ohje" for="'.$slugi.'">'.$nimi.': '.($pakollinen?'*':'').'</label><br />';
			$tuloste .= '<input class="ilmoteksti" type="text" name="'.$slugi.'" id="'.$slugi.'" '.($pakollinen?'required':'').' /><br />';
		}
		$tuloste .= '<br />';
	}
	
	$valinnat = get_the_terms($id,'tapahtuman_valinnat');
	
	if (is_array($valinnat)) {
		
		foreach ($valinnat as $valinta) {
			$slugi = $valinta->slug;
			$nimi = $valinta->name;
			
			$osat = explode(" // " , $nimi);
			if (!$osat) continue; // Virhetilanteessa skiptaan valinta..
			
			$tuloste .= '<p>';
			
			if (count($osat)>1) { // Vaihtoehdot määritetty!
				$tuloste .= '* ';
				foreach ($osat as $key => $osa) {
					$tuloste .= '<input type="radio" name="'.$slugi.'" id="'.$slugi.$key.'" value="'.$osa.'" required/>';
					$tuloste .= '<label class="ilmo_ohje" for="'.$slugi.$key.'">'.$osa.'</label>';
				}
				
			} else { // Nyt siis ei ole vaihtoehtoja määritetty -> Kyllä / Ei!
				$tuloste .= $nimi.' *<br />';
				$tuloste .= '<input type="radio" name="'.$slugi.'" id="'.$slugi.'kylla" value="kylla"  required/>';
				$tuloste .= '<label class="ilmo_ohje" for="'.$slugi.'kylla"> Kyllä </label>';
				$tuloste .= '<input type="radio" name="'.$slugi.'" id="'.$slugi.'ei" value="ei" required/>';
				$tuloste .= '<label class="ilmo_ohje" for="'.$slugi.'ei"> Ei </label><br />';
			}
			
			$tuloste .= '</p>';
		}
		$tuloste .= '<br />';
	}
	
	$monivalinnat = get_the_terms($id,'tapahtuman_monivalinnat');
	
	if (is_array($monivalinnat)) {
		foreach ($monivalinnat as $monivalinta) {
			$slugi = $monivalinta->slug;
			$nimi = $monivalinta->name;
			$tuloste .= '<input class="ilmomonivalinta" type="checkbox" name="'.$slugi.'" id="'.$slugi.'" value="1" />';
			$tuloste .= ' <label class="ilmo_ohje" for="'.$slugi.'">'.$nimi.'</label><br />';
		}
		$tuloste .= '<br />';
	}
	
	
	$tuloste .= '<input type="submit" value="Lähetä" />';
	$tuloste .= '</form>';
	return $tuloste;
}


// Tallenna ilmo
function tallenna_ilmo($id) {
	
	if ( ((time()<get_post_meta($id, '_ilmoaika', true) || time()>get_post_meta($id, '_ilmonloppuaika', true) ) && !get_post_field( 'post_author', $id ) == get_current_user_id()) || !wp_verify_nonce( $_POST['ilmo_nonce'], plugin_basename(__FILE__).'ilmon_nonce' )) return 'Tallennus epäonnistui.';
	
	
	$ilmot = get_post_meta($id, '_ilmot', true);
	
	
	$vastaus = array();
	
	$vastaus['nimi'] = sanitize_text_field($_POST['nimi']);
	$ilmoaika = sanitize_text_field($_POST['ilmo_aika']);
	$vastaus['anonyymi'] = ($_POST['anonyymi']==1?true:false);
	
	
	
	$tekstikentat = get_the_terms($id,'tapahtuman_tekstikentat');
	
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
		
	}
	
	if (isset($ilmot[$ilmoaika]) && $ilmot[$ilmoaika]['nimi'] == $vastaus['nimi']) return 'Lähettämästi tiedot löytyvät jo tietokannasta..';
	$ilmot[$ilmoaika] = $vastaus;
	
	
	if (get_post_meta($id, '_ilmot', false)) {
		$onnistuko = update_post_meta($id, '_ilmot', $ilmot);
	} else {
		$onnistuko = add_post_meta($id, '_ilmot', $ilmot);
	}
	
	$tuloste .= ($onnistuko? 'Tiedot lisätty onnistuneesti!' : 'Ilmottautuminen epäonnistui.');
	
	return $tuloste;
}