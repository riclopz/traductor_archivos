<?php
set_time_limit(1200);

include('AccessTokenAuthentication.class.php');
include('HTTPTranslator.class.php');

//para reemplazar caracteres especiales
function special_char($str){
  $str = str_replace('&lt;', '', $str);
	$str = str_replace('&euro;', ' euro', $str);
	$str = str_replace('&nbsp;', ' ', $str);
	$str = str_replace('&gt;', '>', $str);
	$str = str_replace('&iquest;', '', $str);
	return $str;
	}	


//traducir todo por partes de 5 lineas
function traducir($lineas, $origen, $destino){
		$traduccion = array();
		
		//Client ID of the application.
		$clientID       = "TU ID DE CLIENTE";
		//Client Secret key of the application.
		$clientSecret = "TU ID DE APLICACION o SECRETO DE CLIENTE";
		//OAuth Url.
		$authUrl      = "https://datamarket.accesscontrol.windows.net/v2/OAuth2-13/";
		//Application Scope Url
		$scopeUrl     = "http://api.microsofttranslator.com";
		//Application grant type
		$grantType    = "client_credentials";
		
		//Create the AccessTokenAuthentication object.
		$authObj      = new AccessTokenAuthentication();
		//Get the Access token.
		$accessToken  = $authObj->getTokens($grantType, $scopeUrl, $clientID, $clientSecret, $authUrl);
		//Create the authorization Header string.
		$authHeader = "Authorization: Bearer ". $accessToken;
		
		//Set the Params.
		$fromLanguage   = $origen;
		$toLanguage        = $destino;
		$user            = 'Testuser';
		$category       = "general";
		$uri             = null;
		$contentType    = "text/html";
		$maxTranslation = 5;
	//Input text Array.
		$inputStrArr = array((utf8_encode(html_entity_decode(special_char($lineas[0])))),(utf8_encode(html_entity_decode(special_char($lineas[1])))),(utf8_encode(html_entity_decode(special_char($lineas[2])))),(utf8_encode(html_entity_decode(special_char($lineas[3])))),(utf8_encode(html_entity_decode(special_char($lineas[4])))));

		/*print_r($inputStrArr);
		die;*/
		//HTTP GetTranslationsArray Method Url.
		$getTranslationUrl = "http://api.microsofttranslator.com/V2/Http.svc/GetTranslationsArray";
	
		//Create the Translator Object.
		$translatorObj = new HTTPTranslator();
	
		//Get the Request XML Format.
		$requestXml = $translatorObj->createReqXML($fromLanguage,$toLanguage,$category,$contentType,$user,$inputStrArr,$maxTranslation);
	
		//Call HTTP Curl Request.
		$curlResponse = $translatorObj->curlRequest($getTranslationUrl, $authHeader, $requestXml);
	
		// Interprets a string of XML into an object.
		$xmlObj = simplexml_load_string($curlResponse);
		$translationResponse = $xmlObj->GetTranslationsResponse;
		$y=0;
		foreach($translationResponse as $translationArr) {
			$translationMatchArr = $translationArr->Translations->TranslationMatch;
			echo $inputStrArr[$y]." = ";
			foreach($translationMatchArr as $translationMatch) {
				echo $translationMatch->TranslatedText.'<br>';
				$traduccion[] = $translationMatch->TranslatedText;
			}
			$y++;
		}
		return $traduccion;
	}


//funcion para obtener la lista de archivos dentro de carpetas y subcarpetas
function obtenerArchivos($dir){
	$lista = array();
	// Abrir un directorio conocido, y proceder a leer sus contenidos
	if (is_dir($dir)) {
		if ($gd = opendir($dir)) {
			$i = 0;
			while ($archivo = readdir($gd)) {
				if($archivo != '.' && $archivo != '..' && $archivo != 'traductor.php'){
					if(is_dir($dir.$archivo)){
						$otraLista = obtenerArchivos($dir.$archivo.'/');
						for($x = 0; $x < count($otraLista); $x++){
							$lista[$i] = $otraLista[$x];
							$i++;
							}
						}else{
							if(filtrar($dir.$archivo)){
								$lista[$i] = $dir.$archivo;
								$i++;
								}
							}
					}
			
				}
			closedir($gd);
			}
		}
	return $lista;
	}

//function para obtener el contenido de un archivo y devolver un array con sus datos
function contenido($ruta_archivo){
	$linea = array();
	$archivo = file_get_contents($ruta_archivo);
	preg_match_all("/define.'(?:.*)',(?: )?'(.*)'(?: )?.;/i", $archivo, $texto);
	$a = 0;
	for($i = 0; $i < count($texto[1]); $i++){
		if($texto[1][$i] != ''){
			$linea[$a]['texto'] = $texto[1][$i];
			$linea[$a]['linea'] = $texto[0][$i];
			$a++;
			}
		}
		return $linea;
	}
	
//para filtrar, si no es php no me interesa
function filtrar($ruta_archivo){
	$nombre = explode('.', basename($ruta_archivo));
	if($nombre[1] == 'php'){
		return true;
		}else{
			return false;
			}
	}

//guardar el nuevo archivo
function nuevoArchivo($lineas, $ruta_archivo, $destino){
	$nuevo_archivo = file_get_contents($ruta_archivo);
	
	switch($destino){
		case 'en':
		$carpeta = 'english';
		break;
		case 'fr':
		$carpeta = 'french';
		break;
		case 'it':
		$carpeta = 'italian';
		break;
		case 'pt':
		$carpeta = 'portuguese';
		break;
		}
	
	if(!is_dir('./'.$carpeta)){
		mkdir($carpeta);
		}
	$ruta_archivo = str_replace('espanol', $carpeta, $ruta_archivo);
	crearCarpetas($ruta_archivo);
	for($z = 0; $z < count($lineas); $z++){
		$nueva_linea = str_replace($lineas[$z]['texto'], $lineas[$z]['traduccion'], $lineas[$z]['linea']);
		$nuevo_archivo = str_replace($lineas[$z]['linea'], $nueva_linea, $nuevo_archivo);
		}
	
	$nuevo_nombre = dirname($ruta_archivo).'/'.basename($ruta_archivo);
	$fp = fopen($nuevo_nombre,"w+");
	fwrite($fp, $nuevo_archivo);
	fclose($fp);
	}
//./italiano/modules/order_total
//funcion para crear carpetas
function crearCarpetas($ruta){
	//primero verifico si existe el derectorio
	$ruta = dirname($ruta);
	if(!is_dir($ruta)){
		//si no existe lo creare, pero primero tengo que verificar que exista su directorio padre
		$dirPadre = (dirname($ruta));
		if(!is_dir($dirPadre)){
			//como no existe su directorio padre entonces tengo que crear este primero pero tengo que hacerlo recursivamente
			crearCarpetas($ruta);
			//una vez creado el directorio padre pasare a crear la nueva carpeta
			mkdir(($ruta));
			}else{
				//como existe su directorio padre entonces si puedo crear la carpeta
				mkdir($ruta);
				}
		}
	}

//para el procesado de la traduccion segun el idioma seleccionado

if(isset($_GET['did'])){
	
	$origen = 'es';//$_GET['oid'];
	$destino = $_GET['did'];
	
	//primero obtengo los archivos
	$archivos = obtenerArchivos('./');
	//ahora voy archivo por archivo para obtener su contenido, traduciendolo y generando el nuevo archivo
	for($w = 0; $w < count($archivos); $w++){
		//ahora obtengo el ceontenido de cada archivo
		$contenido = contenido($archivos[$w]);
		//si existe contenido que lo traduzca de lo contrario que pase al siguiente
		if(count($contenido) > 0){
			//ahora recorro todo el contenido y voy pasando cada 5 lineas para traducirlas
			for($t = 0; $t < count($contenido); $t = $t + 5){
				//verifico si existe el indice
				if(!isset($contenido[$t]['texto'])){
					$contenido[$t]['texto'] = '';
					$contenido[$t]['linea'] = '';
					}
				if(!isset($contenido[$t+1]['texto'])){
					$contenido[$t+1]['texto'] = '';
					$contenido[$t+1]['linea'] = '';
					}
				if(!isset($contenido[$t+2]['texto'])){
					$contenido[$t+2]['texto'] = '';
					$contenido[$t+2]['linea'] = '';
					}
				if(!isset($contenido[$t+3]['texto'])){
					$contenido[$t+3]['texto'] = '';
					$contenido[$t+3]['linea'] = '';
					}
				if(!isset($contenido[$t+4]['texto'])){
					$contenido[$t+4]['texto'] = '';
					$contenido[$t+4]['linea'] = '';
					}
				//ahora que verifique paso a traducirlo formando un arrar
				$atraducir = array($contenido[$t]['texto'], $contenido[$t+1]['texto'], $contenido[$t+2]['texto'], $contenido[$t+3]['texto'], $contenido[$t+4]['texto']);
				//llamo a la funcion para traducir y esta me tiene que devolver un array con 5 valores los cuales colocare en el array contenido
				$traduccion = traducir($atraducir, $origen, $destino);	
				$contenido[$t]['traduccion'] = $traduccion[0];
				$contenido[$t+1]['traduccion'] = $traduccion[1];
				$contenido[$t+2]['traduccion'] = $traduccion[2];
				$contenido[$t+3]['traduccion'] = $traduccion[3];
				$contenido[$t+4]['traduccion'] = $traduccion[4];
				}
			//ya tendo la traduccion del contenido genero el nuevo archivo
			nuevoArchivo($contenido, $archivos[$w], $destino);
//			break;
			}
		}
}else{
?>
<html>
<head>
<title>Traductor</title>
</head>
<body>
<form action="traductor.php">
<!--<label>Seleccione el idioma de origen: </label>
<select name="oid">
<option value="es">Espa&ntilde;ol</option>
<option value="en">Ingles</option>
</select>
<br />-->
<label>Seleccione el idioma de destino: </label>
<select name="did">
<option value="en">Ingles</option>
<option value="it">Italiano</option>
<option value="fr">Franc&eacute;s</option>
<option value="pt">Portugues</option>
</select>
<br />
<input type="submit" value="Traducir" />
</form>
</body>
</html>
<?php
}
