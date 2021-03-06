<?php
session_start();

ini_set("memory_limit","1024M");

class Mining{
	
	public $slova = array();	
	public $slova_docFreq = array();
	
	function __construct(){
		
		if(!isset($_SESSION['dir']))
			$_SESSION['dir'] = '';
	
	}
	
	function __destruct(){
		
	}
	
	public function vypis_kategorie()
	{
		$html = '';
		
		$html .= '
		<div class="kategorie">
			<a href="index.php?modul=mining&vypis_clanky=b&dir=business"><h2>Business</h2></a>
		</div>';
		
		$html .= '
		<div class="kategorie">
			<a href="index.php?modul=mining&vypis_clanky=e&dir=entertainment"><h2>Entertainment</h2></a>
		</div>';
		
		$html .= '
		<div class="kategorie">
			<a href="index.php?modul=mining&vypis_clanky=p&dir=politics"><h2>Politics</h2></a>
		</div>';
		
		$html .= '
		<div class="kategorie">
			<a href="index.php?modul=mining&vypis_clanky=s&dir=sport"><h2>Sport</h2></a>
		</div>';
		
		$html .= '
		<div class="kategorie">
			<a href="index.php?modul=mining&vypis_clanky=t&dir=tech"><h2>Tech</h2></a>
		</div>';
		
		return $html;
	}
	
	public function vypis_podkategorie($typ)
	{
		$html = '';
		
		$this->nacti_soubor_docFreq($typ);
		//seradim od nejvetsiho dolu
		arsort($this->slova_docFreq);
		
		$i = 0;
		//vypisu ty nejvic
		foreach($this->slova_docFreq as $klic => $pocet)
		{
			if($i < 31)
			{
				if($i > 0)
					$html .= ' | ';
				$html .= '<a href="index.php?modul=mining&hledej_s_bonusem&typ='.$typ.'&hledat='.$klic.'" title="Show by category">'.$klic.'</a>';
			}
			$i++;
		}
		$html .= '<br />';
		
		return $html;
	}
	
	public function vypis_clanky($typ, $dir)
	{
		$html = '';
		
		$_SESSION['dir'] = $dir;
		
		$html .= '<h3>'.$_SESSION['dir'].'</h3>';
		
		$html .= $this->hledej_radek_komplet($typ);
// 		$html .= $this->hledej_radek($typ);
// 		$html .= $this->hledej_radek_bonus($typ);
// 		$html .= $this->hledej_radek_bonus_ngram($typ);
		
		$dir = 'data/'.$dir.'/';
		$i = 1;
		if(is_dir($dir))
		{
			if($open = opendir($dir))
			{
				while(($file = readdir($open)) !== false)
				{
					if($file != '.' && $file != '..')
					{
						$html .= '<div class="clanek">';
						$html .= $i.'.<br />';
						$html .= $this->nacti_text($dir.$file);
						$html .= '</div>';
						$html .= '<br />';
						$i++;
					}
				}
			}
		}
		
		return $html;
	}
	
	public function vypis_clanky_bonus($typ, $dir)
	{
		$html = '';
		
		$_SESSION['dir'] = $dir;
		
		$html .= '<h3>'.$_SESSION['dir'].'</h3>';
		
		$html .= $this->hledej_radek_komplet($typ);
		$html .= $this->vypis_podkategorie($typ);
// 		$html .= $this->hledej_radek($typ);
// 		$html .= $this->hledej_radek_bonus($typ);
// 		$html .= $this->hledej_radek_bonus_ngram($typ);
		
		$dir = 'data/'.$dir.'/';
		
		//naplnim si matici
		$this->nacti_soubor_vsechno($typ);
		//projdu matici a vyberu slovo s nejlepsi docFreq
		$max = 0;
		$max_slovo = '';
		$max_klic = 0;
		for($s=0; $s<count($this->slova); $s++)
		{
			if($this->slova[$s]['docFreq'] > $max)
			{
				$max = $this->slova[$s]['docFreq'];
				$max_slovo = $this->slova[$s]['slovo'];
				$max_klic = $s;
			}
		}
		
		//projdu si ted jednotlive dokumenty pro slovo a naplnim si pomocnou pro razeni
		//projdu jeho vyskyty a ulozim si je
		foreach($this->slova[$max_klic] as $dokument => $kolik)
		{
			if($dokument != 'sum' && $dokument != 'docFreq' && $dokument != 'slovo')
			{
					$vyskyty[$dokument] = $kolik;
			}
		}
		
		//seradim od nejvetsiho dolu
		arsort($vyskyty);
		
		//vypis
		foreach($vyskyty as $klic => $pocet)
		{
			$html .= '<div class="clanek">';
			if(strlen($klic) == 1)
				$soubor = '00'.$klic.'.txt';
			else if(strlen($klic) == 2)
				$soubor = '0'.$klic.'.txt';
			else
				$soubor = $klic.'.txt';
			$html .= $klic.'.<br />';	
			$html .= $this->nacti_text($dir.$soubor);
			$html .= '</div>';
			$html .= '<br />';
		}
		
		return $html;
	}
	
	public function hledej_radek($typ)
	{
		$html = '';
		
		$html .= '
			<form action="index.php?modul=mining&hledej&typ='.$typ.'" method="post" onsubmit="" enctype="multipart/form-data">
				<input type="text" name="hledat" id="hledat" value="" placeholder="Search" />
				<input type="submit" name="hledat_button" id="hledat_button" value="Search" />
			</form><br />
		';
		
		return $html;
	}
	
	public function hledej_radek_bonus($typ)
	{
		$html = '';
		
		$html .= '
			<form action="index.php?modul=mining&hledej_s_bonusem&typ='.$typ.'" method="post" onsubmit="" enctype="multipart/form-data">
				<input type="text" name="hledat" id="hledat" value="" placeholder="Search with bonus" />
				<input type="submit" name="hledat_button" id="hledat_button" value="Search" />
			</form><br />
		';
		
		return $html;
	}
	
	public function hledej_radek_bonus_ngram($typ)
	{
		$html = '';
		
		$html .= '
			<form action="index.php?modul=mining&hledej_s_bonusem_ngram&typ='.$typ.'" method="post" onsubmit="" enctype="multipart/form-data">
				<input type="text" name="hledat" id="hledat" value="" placeholder="Search with bonus and n-grams (max 5 words)" />
				<input type="submit" name="hledat_button" id="hledat_button" value="Search" />
			</form><br />
		';
		
		return $html;
	}
	
	public function hledej_radek_komplet($typ)
	{
		$html = '';
		
		$html .= '
			<form action="index.php?modul=mining&hledej_komplet&typ='.$typ.'" method="post" onsubmit="" enctype="multipart/form-data">
				<input type="text" name="hledat" id="hledat" value="" placeholder="Search" />
				<input type="submit" name="hledat_button" id="hledat_button" value="Search" />
			</form><br />
		';
		
		return $html;
	}
	
	/**********************************************************/
	/*		Vypisova funkce																			*/
	/**********************************************************/
	public function nacti_text($soubor)
	{	
		$html = '';
		
		$textak = fopen($soubor, 'r');
		$i = 0;
		while(!feof($textak))
		{
			$radek = fgets($textak);
			if($radek != "\n")
			{
				if($i == 0)
					$html .= '<b>';
					
				$html .= $radek;
				
				if($i == 0)
					$html .= '</b><br />';
					
				$html .= '<br />';
				$i++;
			}
		}
		fclose($textak);
		
		return $html;
	}
	
	/**********************************************************/
	/*		Funkce na ostrizeni prazdnych znaku									*/
	/**********************************************************/
	public function ostrihni($pole)
	{
		for($i=0; $i<count($pole); $i++)
			$pole[$i] = trim($pole[$i]);
			
		return $pole;
	}
	
	/**********************************************************/
	/*	Funkce pro nacteni vstupu a naplneni matice a popisku	*/
	/**********************************************************/
	public function nacti_soubor($typ)
	{
		//otevreni souboru
		$soubor = fopen("data/matrix_".$typ.".csv", "r") or die("Nelze otevrit soubor");		
		//promenna pro zjisteni radku
		$akt_radek = 0;
		$p = 0;
		//pokud neni konec souboru
		while(!feof($soubor))
		{
// 			$radek = fgets($soubor);
			$radek = fgetcsv($soubor, 0, ';');
			if(!empty($radek))
			{		
				if($p != 0)
				{
// 					$rozdeleni = explode(';', $radek);
// 					$rozdeleni = $this->ostrihni($rozdeleni);
					
					$this->slova[$akt_radek]['slovo'] = $radek[0];
					
					$pocet = count($radek);
					//projdu si pocty slov v jednotlivych clancich
					for($i=1; $i<$pocet; $i++)
					{
						if($i == $pocet-2)
							$this->slova[$akt_radek]['sum'] = $radek[$i];
						else if($i == $pocet-1)
							$this->slova[$akt_radek]['docFreq'] = $radek[$i];
						else if($radek[$i] != 0)
							$this->slova[$akt_radek][$i] = $radek[$i];
					}
					
					$akt_radek++;
				}
				$p++;
			}
		}
		//zavreni souboru
		fclose($soubor);
	}
	
	/**********************************************************/
	/*	Funkce pro nacteni vstupu a naplneni docFreq z matice	*/
	/**********************************************************/
	public function nacti_soubor_docFreq($typ)
	{
		//otevreni souboru
		$soubor = fopen("data/matrix_".$typ.".csv", "r") or die("Nelze otevrit soubor");		
		//promenna pro zjisteni radku
		$akt_radek = 0;
		$p = 0;
		//pokud neni konec souboru
		while(!feof($soubor))
		{
// 			$radek = fgets($soubor);
			$radek = fgetcsv($soubor, 0, ';');
			if(!empty($radek))
			{		
				if($p != 0)
				{
					$pocet = count($radek);
					$this->slova_docFreq[$radek[0]] = $radek[$pocet-2];
					
					$akt_radek++;
				}
				$p++;
			}
		}
		//zavreni souboru
		fclose($soubor);
	}
	
	public function nacti_soubor_vsechno($typ)
	{
		//otevreni souboru
		$soubor = fopen("data/matrix_".$typ.".csv", "r") or die("Nelze otevrit soubor");		
		//promenna pro zjisteni radku
		$akt_radek = 0;
		$p = 0;
		//pokud neni konec souboru
		while(!feof($soubor))
		{
// 			$radek = fgets($soubor);
			$radek = fgetcsv($soubor, 0, ';');
			if(!empty($radek))
			{		
				if($p != 0)
				{
// 					$rozdeleni = explode(';', $radek);
// 					$rozdeleni = $this->ostrihni($rozdeleni);
					
					$this->slova[$akt_radek]['slovo'] = $radek[0];
					
					$pocet = count($radek);
					//projdu si pocty slov v jednotlivych clancich
					for($i=1; $i<$pocet; $i++)
					{
						if($i == $pocet-2)
							$this->slova[$akt_radek]['sum'] = $radek[$i];
						else if($i == $pocet-1)
							$this->slova[$akt_radek]['docFreq'] = $radek[$i];
						else
							$this->slova[$akt_radek][$i] = $radek[$i];
					}
					
					$akt_radek++;
				}
				$p++;
			}
		}
		//zavreni souboru
		fclose($soubor);
	}
	
	/**********************************************************/
	/*	Funkce pro nacteni vstupu a naplneni matice a popisku	*/
	/**********************************************************/
	public function nacti_soubor_ngram($typ, $delka, $slovo)
	{
		//otevreni souboru
		$dir = 'data/';
		if($delka == 2)
			$dir .= 'ngrams2/';
		else if($delka == 3)
			$dir .= 'ngrams3/';
		else if($delka == 4)
			$dir .= 'ngrams4/';
		else if($delka == 5)
			$dir .= 'ngrams5/';
		else
		{
			$this->nacti_soubor($typ);
			echo $this->hledej_s_bonusem($slovo, $typ);
			exit;
		}
		
		$soubor = fopen($dir."matrix_".$typ.".csv", "r") or die("Nelze otevrit soubor");		
		//promenna pro zjisteni radku
		$akt_radek = 0;
		$p = 0;
		$hlavicka = array();
		//pokud neni konec souboru
		while(!feof($soubor))
		{
// 			$radek = fgets($soubor);
			$radek = fgetcsv($soubor, 0, ';');
			if(!empty($radek))
			{		
				if($p != 0)
				{
// 					$rozdeleni = explode(';', $radek);
// 					$rozdeleni = $this->ostrihni($rozdeleni);
					
					$this->slova[$akt_radek]['slovo'] = $radek[0];
					
					$pocet = count($radek);
					//projdu si pocty slov v jednotlivych clancich
					for($i=1; $i<$pocet; $i++)
					{
						if($i == $pocet-2)
							$this->slova[$akt_radek]['sum'] = $radek[$i];
						else if($i == $pocet-1)
							$this->slova[$akt_radek]['docFreq'] = $radek[$i];
						else if($radek[$i] != 0)
							$this->slova[$akt_radek][intval($hlavicka[$i])] = $radek[$i];
					}
					
					$akt_radek++;
				}
				else
				{	
					$pocet = count($radek);
					for($i=1; $i<$pocet; $i++)
					{
						if($radek[$i] != 'sum' && $radek[$i] != 'docFreq')
						{
							$rozdelene = explode($typ,$radek[$i]);
							$hlavicka[$i] = $rozdelene[1];
						}
					}
				}
				$p++;
			}
		}
		//zavreni souboru
		fclose($soubor);
	}
	
	/**********************************************************/
	/*	Funkce hledani slova a jeho poctu											*/
	/**********************************************************/
	public function hledej($slovo, $typ)
	{
		$html = '';
		
		$dir = '';
		
		if($typ == 'b')
			$dir = 'business';
		else if($typ == 'e')
			$dir = 'entertainment';
		else if($typ == 'p')
			$dir = 'politics';
		else if($typ == 's')
			$dir = 'sport';
		else if($typ == 't')
			$dir = 'tech';	
		
		$html .= '<h3>'.$_SESSION['dir'].'</h3>';
		
		$html .= $this->hledej_radek_komplet($typ);
// 		$html .= $this->hledej_radek($typ);
// 		$html .= $this->hledej_radek_bonus($typ);
// 		$html .= $this->hledej_radek_bonus_ngram($typ);
		
		$slova = array();
		$vyskyty = array();
		//rozdelim slova podle mezer
		$slova = explode(' ', $slovo);
		
		for($p=0; $p<count($slova); $p++)
		{
			if($slova[$p] != '')
			{
				for($i=0; $i<count($this->slova); $i++) 
				{
					//pokud najdu slovo
					if($slova[$p] == $this->slova[$i]['slovo'])
					{
						//projdu jeho vyskyty a ulozim si je
						foreach($this->slova[$i] as $dokument => $kolik)
						{
							if($dokument != 'sum' && $dokument != 'docFreq' && $dokument != 'slovo')
							{
								if(array_key_exists($dokument, $vyskyty))
									$vyskyty[$dokument] = $vyskyty[$dokument]+$kolik;
								else
									$vyskyty[$dokument] = $kolik;
							}
						}
					}
				}
			}
		}
		//seradim od nejvetsiho dolu
		arsort($vyskyty);
		
		//vypis
		$html .= '<b>'.$slovo.'</b> is in documents:<br /><br />';
		foreach($vyskyty as $klic => $pocet)
		{
			$html .= $pocet.'x in document '.$klic.'<br />';
			$html .= '<div class="clanek">';
			if(strlen($klic) == 1)
				$soubor = '00'.$klic.'.txt';
			else if(strlen($klic) == 2)
				$soubor = '0'.$klic.'.txt';
			else
				$soubor = $klic.'.txt';
			$html .= $klic.'.<br />';
			$html .= $this->nacti_text('data/'.$dir.'/'.$soubor);
			$html .= '</div>';
			$html .= '<br />';
		}
		return $html;
		
// 		$html = 'Slovo jsem nenasel';
// 		return $html;
	}
	
	/**********************************************************/
	/*	Funkce hledani slova a jeho poctu											*/
	/**********************************************************/
	public function hledej_s_bonusem($slovo, $typ)
	{
		$html = '';
		
		$dir = '';
		
		if($typ == 'b')
			$dir = 'business';
		else if($typ == 'e')
			$dir = 'entertainment';
		else if($typ == 'p')
			$dir = 'politics';
		else if($typ == 's')
			$dir = 'sport';
		else if($typ == 't')
			$dir = 'tech';	
		
		$html .= '<h3>'.$_SESSION['dir'].'</h3>';
		
		$html .= $this->hledej_radek_komplet($typ);
// 		$html .= $this->hledej_radek($typ);
// 		$html .= $this->hledej_radek_bonus($typ);
// 		$html .= $this->hledej_radek_bonus_ngram($typ);
		
		$slova = array();
		$vyskyty = array();
		//rozdelim slova podle mezer
		$slova = explode(' ', $slovo);
		
		for($p=0; $p<count($slova); $p++)
		{
			if($slova[$p] != '')
			{
				for($i=0; $i<count($this->slova); $i++) 
				{
					//pokud najdu slovo
					if($slova[$p] == $this->slova[$i]['slovo'])
					{
						$koeficient = 1+(1/$this->slova[$i]['docFreq']);
						//projdu jeho vyskyty a ulozim si je
						foreach($this->slova[$i] as $dokument => $kolik)
						{
							if($dokument != 'sum' && $dokument != 'docFreq' && $dokument != 'slovo')
							{
								if(array_key_exists($dokument, $vyskyty))
									$vyskyty[$dokument] = $vyskyty[$dokument]+$koeficient;
								else
									$vyskyty[$dokument] = $koeficient;
							}
						}
					}
				}
			}
		}
		//seradim od nejvetsiho dolu
		arsort($vyskyty);
		
		//vypis
		$html .= '<b>'.$slovo.'</b> is in documents:<br /><br />';
		foreach($vyskyty as $klic => $pocet)
		{
			$html .= $pocet.'x in document '.$klic.'<br />';
			$html .= '<div class="clanek">';
			if(strlen($klic) == 1)
				$soubor = '00'.$klic.'.txt';
			else if(strlen($klic) == 2)
				$soubor = '0'.$klic.'.txt';
			else
				$soubor = $klic.'.txt';
			$html .= $klic.'.<br />';
			$html .= $this->nacti_text('data/'.$dir.'/'.$soubor);
			$html .= '</div>';
			$html .= '<br />';
		}
		return $html;
		
// 		$html = 'Slovo jsem nenasel';
// 		return $html;
	}
	
	/**********************************************************/
	/*	Funkce hledani slova a jeho poctu											*/
	/**********************************************************/
	public function hledej_s_bonusem_ngram($slovo, $typ)
	{
		$html = '';
		
		$dir = '';
		
		if($typ == 'b')
			$dir = 'business';
		else if($typ == 'e')
			$dir = 'entertainment';
		else if($typ == 'p')
			$dir = 'politics';
		else if($typ == 's')
			$dir = 'sport';
		else if($typ == 't')
			$dir = 'tech';	
		
		$slova = array();
		$vyskyty = array();
		//rozdelim slova podle mezer
		$slova = explode(' ', $slovo);
		
		$vysledne_slovo = '';
		$delka = 0;
		//ted rozdelene slovo spojim do formatu v jakym mam ulozene ngramy
		for($p=0; $p<count($slova); $p++)
		{
			if($slova[$p] != '')
			{
				if($vysledne_slovo != '')
					$vysledne_slovo .= '_';
				$vysledne_slovo .= $slova[$p];
				$delka++;
			}
		}
		
		$this->nacti_soubor_ngram($typ, $delka, $slovo);
		
		$html .= '<h3>'.$_SESSION['dir'].'</h3>';
		
		$html .= $this->hledej_radek_komplet($typ);
// 		$html .= $this->hledej_radek($typ);
// 		$html .= $this->hledej_radek_bonus($typ);
// 		$html .= $this->hledej_radek_bonus_ngram($typ);
		
		//projdu ulozenou matici
		for($i=0; $i<count($this->slova); $i++) 
		{
			//pokud najdu slovo
			if($vysledne_slovo == $this->slova[$i]['slovo'])
			{
				$koeficient = 1+(1/$this->slova[$i]['docFreq']);
// 				print_r($this->slova[$i]);
				//projdu jeho vyskyty a ulozim si je
				foreach($this->slova[$i] as $dokument => $kolik)
				{
					if($dokument != 'sum' && $dokument != 'docFreq' && $dokument != 'slovo')
					{
						if(array_key_exists($dokument, $vyskyty))
							$vyskyty[$dokument] = $vyskyty[$dokument]+$koeficient;
						else
							$vyskyty[$dokument] = $koeficient;
					}
				}
			}
		}
		//seradim od nejvetsiho dolu
		arsort($vyskyty);
		
		//vypis
		$html .= '<b>'.$slovo.'</b> is in documents:<br /><br />';
		foreach($vyskyty as $klic => $pocet)
		{
			$html .= $pocet.'x in document '.$klic.'<br />';
			$html .= '<div class="clanek">';
			if(strlen($klic) == 1)
				$soubor = '00'.$klic.'.txt';
			else if(strlen($klic) == 2)
				$soubor = '0'.$klic.'.txt';
			else
				$soubor = $klic.'.txt';
			$html .= $klic.'.<br />';
			$html .= $this->nacti_text('data/'.$dir.'/'.$soubor);
			$html .= '</div>';
			$html .= '<br />';
		}
		return $html;
		
// 		$html = 'Slovo jsem nenasel';
// 		return $html;
	}
	
	/**********************************************************/
	/*	Funkce hledani slova a jeho poctu											*/
	/**********************************************************/
	public function hledej_komplet($slovo, $typ)
	{
		$html = '';
		
		$dir = '';
		
		if($typ == 'b')
			$dir = 'business';
		else if($typ == 'e')
			$dir = 'entertainment';
		else if($typ == 'p')
			$dir = 'politics';
		else if($typ == 's')
			$dir = 'sport';
		else if($typ == 't')
			$dir = 'tech';	
		
		$html .= '<h3>'.$_SESSION['dir'].'</h3>';
		
		$html .= $this->hledej_radek_komplet($typ);
// 		$html .= $this->hledej_radek($typ);
// 		$html .= $this->hledej_radek_bonus($typ);
// 		$html .= $this->hledej_radek_bonus_ngram($typ);
		
		$slova = array();
		$vyskyty = array();
		//rozdelim slova podle mezer
		$slova = explode(' ', $slovo);
		
		//nactu zakladni matici
		$this->nacti_soubor($_GET['typ']);
		
		$delka = 0;
		//1. projdu jednotliva slova a udelam si pro ne sumaci
		for($p=0; $p<count($slova); $p++)
		{
			if($slova[$p] != '')
			{
				for($i=0; $i<count($this->slova); $i++) 
				{
					//pokud najdu slovo
					if($slova[$p] == $this->slova[$i]['slovo'])
					{
						$koeficient = 1+(1/$this->slova[$i]['docFreq']);
						//projdu jeho vyskyty a ulozim si je
						foreach($this->slova[$i] as $dokument => $kolik)
						{
							if($dokument != 'sum' && $dokument != 'docFreq' && $dokument != 'slovo')
							{
								if(array_key_exists($dokument, $vyskyty))
									$vyskyty[$dokument] = $vyskyty[$dokument]+$koeficient;
								else
									$vyskyty[$dokument] = $koeficient;
							}
						}
					}
				}
				$delka++;
			}
		}
		
		//2. udelam si ruzne kombinace slov
		if($delka > 1)
		{
// 			echo '2x :<br />';
			$this->nacti_soubor_ngram($typ, 2, $slovo);
			for($i=0; $i<count($slova); $i++)
			{
				if($slova[$i] != '')
				{
					//vezmu jedno slovo a hodim k nemu dalsi
					for($j=$i; $j<count($slova); $j++)
					{
						if($slova[$j] != '' && $j != $i)
						{
							$hledane_slovo = $slova[$i].'_'.$slova[$j];
// 							echo $hledane_slovo.'<br />';
							
							for($v=0; $v<count($this->slova); $v++) 
							{
								//pokud najdu slovo
								if($hledane_slovo == $this->slova[$v]['slovo'])
								{
									$koeficient = 1+(1/$this->slova[$v]['docFreq']);
									//projdu jeho vyskyty a ulozim si je
									foreach($this->slova[$v] as $dokument => $kolik)
									{
										if($dokument != 'sum' && $dokument != 'docFreq' && $dokument != 'slovo')
										{
											if(array_key_exists($dokument, $vyskyty))
												$vyskyty[$dokument] = $vyskyty[$dokument]+$koeficient;
											else
												$vyskyty[$dokument] = $koeficient;
										}
									}
								}
							}
						}
					}
				}
			}
		}
		
		if($delka > 2)
		{
// 			echo '3x :<br />';
			$this->nacti_soubor_ngram($typ, 3, $slovo);
			for($i=0; $i<count($slova); $i++)
			{
				if($slova[$i] != '')
				{
					//vezmu jedno slovo a hodim k nemu dalsi
					for($j=$i; $j<count($slova); $j++)
					{
						if($slova[$j] != '' && $j != $i)
						{
							for($k=$j; $k<count($slova); $k++)
							{
								if($slova[$k] != '' && $k != $i && $k != $j)
								{
									$hledane_slovo = $slova[$i].'_'.$slova[$j].'_'.$slova[$k];
// 									echo $hledane_slovo.'<br />';
									
									for($v=0; $v<count($this->slova); $v++) 
									{
										//pokud najdu slovo
										if($hledane_slovo == $this->slova[$v]['slovo'])
										{
											$koeficient = 1+(1/$this->slova[$v]['docFreq']);
											//projdu jeho vyskyty a ulozim si je
											foreach($this->slova[$v] as $dokument => $kolik)
											{
												if($dokument != 'sum' && $dokument != 'docFreq' && $dokument != 'slovo')
												{
													if(array_key_exists($dokument, $vyskyty))
														$vyskyty[$dokument] = $vyskyty[$dokument]+$koeficient;
													else
														$vyskyty[$dokument] = $koeficient;
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
		
		if($delka > 3)
		{
// 			echo '4x :<br />';
			$this->nacti_soubor_ngram($typ, 4, $slovo);
			for($i=0; $i<count($slova); $i++)
			{
				if($slova[$i] != '')
				{
					//vezmu jedno slovo a hodim k nemu dalsi
					for($j=$i; $j<count($slova); $j++)
					{
						if($slova[$j] != '' && $j != $i)
						{
							for($k=$j; $k<count($slova); $k++)
							{
								if($slova[$k] != '' && $k != $i && $k != $j)
								{
									for($l=$k; $l<count($slova); $l++)
									{
										if($slova[$l] != '' && $l != $i && $l != $j && $l != $k)
										{
											$hledane_slovo = $slova[$i].'_'.$slova[$j].'_'.$slova[$k].'_'.$slova[$l];
// 											echo $hledane_slovo.'<br />';
									
											for($v=0; $v<count($this->slova); $v++) 
											{
												//pokud najdu slovo
												if($hledane_slovo == $this->slova[$v]['slovo'])
												{
													$koeficient = 1+(1/$this->slova[$v]['docFreq']);
													//projdu jeho vyskyty a ulozim si je
													foreach($this->slova[$v] as $dokument => $kolik)
													{
														if($dokument != 'sum' && $dokument != 'docFreq' && $dokument != 'slovo')
														{
															if(array_key_exists($dokument, $vyskyty))
																$vyskyty[$dokument] = $vyskyty[$dokument]+$koeficient;
															else
																$vyskyty[$dokument] = $koeficient;
														}
													}
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
		
		if($delka > 4)
		{
// 			echo '5x :<br />';
			$this->nacti_soubor_ngram($typ, 5, $slovo);
			for($i=0; $i<count($slova); $i++)
			{
				if($slova[$i] != '')
				{
					//vezmu jedno slovo a hodim k nemu dalsi
					for($j=$i; $j<count($slova); $j++)
					{
						if($slova[$j] != '' && $j != $i)
						{
							for($k=$j; $k<count($slova); $k++)
							{
								if($slova[$k] != '' && $k != $i && $k != $j)
								{
									for($l=$k; $l<count($slova); $l++)
									{
										if($slova[$l] != '' && $l != $i && $l != $j && $l != $k)
										{
											for($m=$l; $m<count($slova); $m++)
											{
												if($slova[$m] != '' && $m != $i && $m != $j && $m != $k && $m != $l)
												{
													$hledane_slovo = $slova[$i].'_'.$slova[$j].'_'.$slova[$k].'_'.$slova[$l].'_'.$slova[$m];
// 													echo $hledane_slovo.'<br />';
									
													for($v=0; $v<count($this->slova); $v++) 
													{
														//pokud najdu slovo
														if($hledane_slovo == $this->slova[$v]['slovo'])
														{
															$koeficient = 1+(1/$this->slova[$v]['docFreq']);
															//projdu jeho vyskyty a ulozim si je
															foreach($this->slova[$v] as $dokument => $kolik)
															{
																if($dokument != 'sum' && $dokument != 'docFreq' && $dokument != 'slovo')
																{
																	if(array_key_exists($dokument, $vyskyty))
																		$vyskyty[$dokument] = $vyskyty[$dokument]+$koeficient;
																	else
																		$vyskyty[$dokument] = $koeficient;
																}
															}
														}
													}
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
		
		//seradim od nejvetsiho dolu
		arsort($vyskyty);
		
		//vypis
		$html .= '<b>'.$slovo.'</b> is in documents:<br /><br />';
		foreach($vyskyty as $klic => $pocet)
		{
			$html .= $pocet.' for document '.$klic.'<br />';
// 			$html .= '<div class="clanek">';
// 			if(strlen($klic) == 1)
// 				$soubor = '00'.$klic.'.txt';
// 			else if(strlen($klic) == 2)
// 				$soubor = '0'.$klic.'.txt';
// 			else
// 				$soubor = $klic.'.txt';
// 			$html .= $klic.'.<br />';
// 			$html .= $this->nacti_text('data/'.$dir.'/'.$soubor);
// 			$html .= '</div>';
// 			$html .= '<br />';
		}
		return $html;
	}
}

?>