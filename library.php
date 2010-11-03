<?
/**
 * @accept array textfields, string boundary, array images
 * La forma del array debe ser array('image1.jpg', 'image2.jpg');
 *
 * @return string postrequest

 */
function arraytomultipart($textfields,$boundary,$images){
  //en el array de entrada $textfields los campos tienen la forma name=value,necesitamos obtener
  //$boundary\r\n
  //Content-Disposition: form-data; name="$name"\r\n\r\n
  //$value\r\n
  $string = "";
  foreach($textfields as $name=>$value){
    $string .= "--".$boundary."\r\n";
    $string .= 'Content-Disposition: form-data; name="'.$name."\"\r\n\r\n";
    $string .= $value."\r\n";
  }
  $string .= "--".$boundary."\r\n";
  //agregando las imagenes, como maximo 4.
  $i = 0;
  for($i;$i<4;$i++){
    if(!isset($images[$i])){
           break;
    }
    $fp = fopen($images[$i], 'rb');
    while(!feof($fp)){
      $image .= fread($fp,128);
    }
      
      $string .= 'Content-Disposition: form-data; name="image'.($i+1)."\"; filename=\"".$images[$i]."\"\r\n";
      $string .= "Content-Type: image/jpeg\r\n\r\n";
      $string .= $image."\r\n";
      }  

  //despues de que acabe el loop imprimimos un bounday y dos guiones al final
  $string .= "--".$boundary."--";

  return $string;
}

//array to post request x-form-url-encoded
function arraytopost($array){
foreach($array as $key=>$value){
  $postvar[] = urlencode($key) . "=" .urlencode($value);
}
$postvar = implode("&", $postvar);
return $postvar;
}

//peticion post, abrir, escribir y cerrar, devuelve cadena
function postrequestfunc($host, $request, $port=80){
$fp = fsockopen($host, $port);
fwrite($fp, $request);
while(!feof($fp)){
  $string .= fread($fp, 128);
}
fclose($fp);
return $string;
}

//extrae y devuelve el formulario, requiere string, la respuesta variara dependiendo de que la funcion encuentre las cadenas de anuncio duplicado y otros errores
function returnform($htmlinput){
  if(strpos($htmlinput, "Duplicate Ad Found") > 2){
    $onlyform = '<p>Se encontro un anuncio duplicado</p>';
    return $onlyform;
  } else {
$inicio = strpos($htmlinput , '<form name="f"');
$final = strpos($htmlinput, 'value=" Place Ad Now " class="button" id="submit_button"></div>');
$len = $final - $inicio + strlen('value=" Place Ad Now " class="button" id="submit_button"></div>');
$onlyform = substr($htmlinput, $inicio, $len);
$onlyform = str_replace('<form name="f"', '<form target="_blank" name="f"', $onlyform);
$onlyform = str_replace('onsubmit=\'document.getElementById("submit_button").value = "Placing Ad..."; document.getElementById("submit_button").disabled = true; return true;\'','',$onlyform);
$onlyform = str_replace('Place Ad Now " class="button" id="submit_button"></div>', 'Enviar " class="button" id="submit_button"></div></form>', $onlyform);
return $onlyform;
}

}

//funcion que reemplace etiquetas por city, state, and completestate para cada post
function postfiller($cadena, $citystring, $state, $completestate = NULL, $opt1 = NULL){
  //que vamos a reemplazar 
  $replacetokens = array('<<city>>', '<<state>>', '<<completestate>>', '<<opt1>>');

  $replaceto = array($citystring, $state, $completestate, $opt1);
  //
  $readypost =  str_ireplace($replacetokens, $replaceto, $cadena);
  $readypost = strtr($readypost, '’', "'");

return $readypost;
}

//funcion principal
function postanewad($section, $category, $state, $completestate, $email, $city, $region, $cityred, $mapaddress, $zip, $title, $descripcion,$superregion = NULL, $citystring,$opt1){



  /**BACKUP (1)
//preprocesamiento de el titulo y descripcion, si hay superregion, entonces se usa esta en vez del city
$title = postfiller($title, $citystring, $state, $completestate);
$descripcion = postfiller($descripcion, $citystring, $state, $completestate);
//estructura de peticion post
$postrequest = array('u'=>$cityred, 'serverName'=> $city.".backpage.com", 'section'=> $section,
		     'category'=> $category, 'nextPage'=>'previewAd', 'superRegion'=>$superregion, 'title'=>$title,
		     'ad'=> $descripcion, 'email'=> $email, 'regionOther' => $region, 'mapAddress'=>$mapaddress,'mapZip'=>$zip, 'emailConfirm'=> $email, 'allowreplies'=>'Anonymous', 'allowSolicitations'=>'No',
		     'showAdLinks'=>'No', 'autoRepost'=> 4 , 'sponsorWeeks'=> 1, 'printWeeks'=> 1);
$postrequest = arraytopost($postrequest);


  **/

  //POST request for multipart/form-data (1)
  $title = postfiller($title, $citystring, $state, $completestate, $opt1);
  $descripcion = postfiller($descripcion, $citystring, $state, $completestate,$opt1);

  //validando el titulo y la descripcion
    if(strlen($title)<5){
    die('Faltan campos indispensables, regresa a la pagina anterior');
  }
    if(strlen($descripcion)<5){
    die('Faltan campos indispensables, regresa a la pagina anterior');
  }


$postrequest = array('u'=>$cityred, 'serverName'=> $city.".backpage.com", 'section'=> $section,
		     'category'=> $category, 'nextPage'=>'previewAd', 'superRegion'=>$superregion, 'title'=>$title,
		     'ad'=> $descripcion, 'email'=> $email, 'regionOther' => $region, 'mapAddress'=>$mapaddress,'mapZip'=>$zip, 
		     'emailConfirm'=> $email, 'allowreplies'=>'Anonymous', 'allowSolicitations'=>'No',
		     'showAdLinks'=>'No', 'autoRepost'=> 4 , 'sponsorWeeks'=> 1, 'printWeeks'=> 1);

//esta funciona convertira el array en una cadena compatible con multipart/form-data
$boundary = "---------------------------244441554121806";
$imagesarray = array('logo.jpg');
$postrequest = arraytomultipart($postrequest,$boundary,$imagesarray);

//x-www-form-data
//$postrequest = arraytopost($postrequest);



//cadena de peticion
$httppost = "POST /online/classifieds/PostAd.html/".$cityred."/".$city.".backpage.com/ HTTP/1.0\r\n";
//Tambien se puede enviar esta peticion con HTTP/1.1, pero esto le dice al servidor que puede enviar su contenido chunked, osea por partes y esto no nos conviene, porque no podemos procesar facilmente esto.
//$httppost = "POST /online/classifieds/PostAd.html/".$cityred."/".$city.".backpage.com/ HTTP/1.1\r\n";
$httppost .= "Host: posting.".$city.".backpage.com\r\n";
$httppost .= "User-Agent: ".$_SERVER[HTTP_USER_AGENT]."\r\n";
$httppost .= "Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5\r\n";
$httppost .= "Accept-Language: en-us,en;q=0.5\r\n";
$httppost .= "Accept-Encoding: gzip,deflate\r\n";
$httppost .= "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n";
$httppost .= "Keep-Alive: 300\r\n";
$httppost .= "Connection: keep-alive\r\n";


//BACKUP
//$httppost .= "Content-Length: ".strlen($postrequest)."\r\n\r\n"; //solo un retorno de carro
//$httppost .= $postrequest;


//peticion multipart
$httppost .= "Content-Type: multipart/form-data; boundary=".$boundary."\r\n";
$httppost .= "Content-Length: ".strlen($postrequest)."\r\n\r\n"; //solo un retorno de carro
$httppost .= $postrequest;

//direccion defitiva
$i = "posting.".$city.".backpage.com";
$respuesta =  postrequestfunc($i, $httppost);

//peticion, debug purposes
//echo $httppost;
//echo '<hr />';
//echo $respuesta;

echo returnform($respuesta);

}

//funcion para extraer entradas de la base de datos, por defecto postea diez entradas
function fetch_row_mysql($section, $category, $state, $completestate, $email, $city, $region, $cityred, $mapaddress, $zip, $start = 1, $end= 10, $superregion, $citystring,$opt1){
  //se conecta
$host = 'mysql400.ixwebhosting.com';
$user = 'champio_jaime';
$pass = 'champion1';
@$mysql = mysql_connect($host, $user, $pass);

if(!$mysql){
  echo "mysql_error: ". mysql_error();
  echo '<br />';
  echo "mysql_errno: ". mysql_errno();
  echo '<br />';
  exit;
}

//calcular $from and $quant para la consulta con limit
//si $start es 0 rectifica
if($start == 0){
  $start = 1;
}
//si $end es menor que $start tambien rectifica
if($end < $start){
  $end = $start + 10;
}
//listo
$from = $start - 1;
$quant = $end - $start;

mysql_select_db('champio_bppost', $mysql);
$qstring = "select title, description from posts limit " .$from. ", ".$quant;
$query = mysql_query($qstring);


while(false !== ($row = mysql_fetch_assoc($query))){
  $title = stripslashes($row['title']);
  $description = stripslashes($row['description']);
  postanewad($section, $category, $state, $completestate, $email, $city, $region, $cityred, $mapaddress, $zip, $title, $description, $superregion, $citystring,$opt1);
}
mysql_close();
}

function contarposts(){
$host = 'mysql400.ixwebhosting.com';
$user = 'champio_jaime';
$pass = 'champion1';
@$mysql = mysql_connect($host, $user, $pass);

if(!$mysql){
  echo "mysql_error: ". mysql_error();
  echo '<br />';
  echo "mysql_errno: ". mysql_errno();
  echo '<br />';
  exit;
}

mysql_select_db('champio_bppost', $mysql);
$qstring = "select count(title)from posts";
$query = mysql_query($qstring);
$row = mysql_fetch_row($query);
return $row[0];
}

?>