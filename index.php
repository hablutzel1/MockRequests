<?php

// LIbraries

function backwardStrpos($haystack, $needle, $offset = 0) {
    $length = strlen($haystack);
    $offset = ($offset > 0) ? ($length - $offset) : abs($offset);
    $pos = strpos(strrev($haystack), strrev($needle), $offset);
    return ($pos === false) ? false : ($length - $pos - strlen($needle));
}

function simpleXMLToArray($xml,
                          $flattenValues = true,
                          $flattenAttributes = true,
                          $flattenChildren = true,
                          $valueKey = '@value',
                          $attributesKey = '@attributes',
                          $childrenKey = '@children') {

    $return = array();
    if (!($xml instanceof SimpleXMLElement)) {
        return $return;
    }
    $name = $xml->getName();
    $_value = trim((string) $xml);
    if (strlen($_value) == 0) {
        $_value = null;
    }
    ;

    if ($_value!==null) {
        if (!$flattenValues) {
            $return[$valueKey] = $_value;
        }
        else {
            $return = $_value;
        }
    }

    $children = array();
    $first = true;
    foreach ($xml->children() as $elementName => $child) {
        $value = simpleXMLToArray($child, $flattenValues, $flattenAttributes, $flattenChildren, $valueKey, $attributesKey, $childrenKey);
        if (isset($children[$elementName])) {
            if ($first) {
                $temp = $children[$elementName];
                unset($children[$elementName]);
                $children[$elementName][] = $temp;
                $first = false;
            }
            $children[$elementName][] = $value;
        }
        else {
            $children[$elementName] = $value;
        }
    }
    if (count($children) > 0) {
        if (!$flattenChildren) {
            $return[$childrenKey] = $children;
        }
        else {
            $return = array_merge($return, $children);
        }
    }

    $attributes = array();
    foreach ($xml->attributes() as $name => $value) {
        $attributes[$name] = trim($value);
    }
    if (count($attributes) > 0) {
        if (!$flattenAttributes) {
            $return[$attributesKey] = $attributes;
        }
        else {
            $return = array_merge($return, $attributes);
        }
    }

    return $return;
}


function obtenerTablaQueRodeaOcurrenciaDeCadena($subject, $string) {
    $offset = strpos($subject, $string);
    $firsttableOffset = backwardStrpos($subject, "<table", $offset);
    $lastTableOffset = strpos($subject, "</table>", $firsttableOffset);
    $entireString = substr($subject, $firsttableOffset, ($lastTableOffset - $firsttableOffset + 8));
    return $entireString;
}

function simplificarHtml($string) {


    // clean all attributes
    $patron = '/<([a-z]+) (.*?)>/i';
    $sustitucion = '<$1>';
    $response = preg_replace($patron, $sustitucion, $string);

    // clean img
    $patron = '/<img>/i';
    $sustitucion = '';
    $response = preg_replace($patron, $sustitucion, $response);


    // create xml from string
    $response = html_entity_decode($response);
    $response = utf8_encode($response);
    return $response;
}


function xmlStringToArray($string) {

    $xmlObj = simplexml_load_string($string);

    $xmlArray = simpleXMLToArray($xmlObj);
    return $xmlArray;
}

function valueToString($value) {
    $result = '';
    if (is_array($value)) {
        $result = implode(" ", $value);
    } else {
        $result = $value;
    }
    return $result;

}

function parseTextAndComposePerson($hc, $nombreCarrion, $curl_exec) {
    //  identificar y extraer seccion de interes
    $entireString = obtenerTablaQueRodeaOcurrenciaDeCadena($curl_exec, "DATOS PERSONALES");

    $response = simplificarHtml($entireString);

    $xmlArray = xmlStringToArray($response);

    $person = array();
    $person['hc'] = $hc;
    $person['nombre'] = valueToString($xmlArray['tr'][1]['td'][1]['B']);
    $person['fecha_nacimiento'] = valueToString($xmlArray['tr'][2]['td'][1]);
    $person['dni'] = valueToString($xmlArray['tr'][2]['td'][3]);
    $person['tipo_asegurado'] = valueToString($xmlArray['tr'][3]['td'][1]);
    $person['codigo_asegurado'] = valueToString($xmlArray['tr'][3]['td'][3]);
    $person['tipo_seguro'] = valueToString($xmlArray['tr'][4]['td'][3]);
    $person['centro_asistencial'] = valueToString($xmlArray['tr'][6]['td'][1]['b']);
    $person['afiliado_desde'] = valueToString($xmlArray['tr'][6]['td'][3]['b']);
    $person['direccion'] = valueToString($xmlArray['tr'][7]['td'][1]);
    $person['afiliado_hasta'] = valueToString($xmlArray['tr'][7]['td'][3]['b']);
    $person['centro_afiliacion'] = valueToString($xmlArray['tr'][8]['td'][1]);
    $person['nombre_hc_carrion'] = $nombreCarrion;
    return $person;
}


global $cabecera;
$cabecera = 0;


function generarCabecera($person) {
    global $cabecera;

    //  only work if it is touched the first time
    $row = '';
    if ($cabecera == 0) {
        $i = 0;
        foreach ($person as $key => $data) {
            $i++;
            $row .= $key;
            if ($i < count($person)) {
                $row .= ",";
            }

        }

           $row .= "\n";
        $cabecera = 1;
    }
    return $row;
}


global $resultadosFile;
$resultadosFile = "resultados.csv";

//  crear function para persistir una persona en la base de datos
function guardarRegistro($person) {
     global $resultadosFile;
    $myFile = $resultadosFile;
    $fh = fopen($myFile, 'a') or die("can't open file");
  $cabecera =  generarCabecera($person);


    $row = $cabecera;
    $i = 0;
    foreach ($person as $key => $data) {
        $i++;
        $row .= "\"";
        $row .= str_replace("\"", "'", $data);
        $row .= "\"";
        if ($i < count($person)) {
            $row .= ",";
        }

    }
    $row .= "\n";
    fwrite($fh, $row);
    fclose($fh);
}

function guardarRegistroNoExistente($hc) {
    $myFile = "resultados.csv";
    $fh = fopen($myFile, 'a') or die("can't open file");
    $row = "\"$hc\"";
    $row .= ",";
    $row .= "\"no asegurado\"";
    $row .= "\n";
    fwrite($fh, $row);
    fclose($fh);
}

/////////////////


//  create mock data

$searchArray = array();
$searchArray[] = array('apellidopat' => '');


$level = array_key_exists('level', $_GET) ? $_GET['level'] : 1;



//if ($level == null) {
//    $level = 1;
//}
// retrieve cookie
if ($level == null || $level == '1') {

    // make request to a web page with curl
    $ch = curl_init('http://ww4.essalud.gob.pe:7777/acredita/index.jsp');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    //curl_setopt($ch, CURLOPT_VERBOSE, true); // Display communication with server
    // captura cookies
    $curl_exec = curl_exec($ch);
    //    echo $curl_exec;
    preg_match('/^Set-Cookie: (.*?);/m', $curl_exec, $m);
    // list cookies
    $var = $m[1];
    $cookie = parse_url($var);
    $cookie = $cookie['path'];

    //  create form with image
    ?>

    <html>
    <head>

    </head>
    <body>
    <form action="index.php?level=3" method="POST">

        <img src="index.php?cookie=<?php echo urlencode($cookie); ?>&level=2" alt=""/>
        <br/>
        <label>captcha:</label> <input type="text" name="captcha_code"/> <br/>
        <input type="hidden" name="cookie"
               value="<?php echo urlencode($cookie); ?>"/>

        <label>tipo busqueda:</label>dni
        <input type="radio" name="tipo" value="dni" checked="checked"/> nombre <input
            type="radio" name="tipo" value="nombre"/> <br/>

        <!--
        <label>Nombre 1:</label> <input name="nom1" /> <br />
        <label>Nombre 2:</label> <input name="nom2" /> <br />

        <label>ap. paterno: </label> <input name="apepat" /> <br />
        <label>ap. materno: </label> <input name="apemat" /> <br />

        <label>dni:</label> <input type="text" name="document" /> <br />

        -->


        <!--        --> <!--          $tipo = $_POST['tipo'];--> <!--    $apePaterno = $_POST['apepat'];-->
        <!--    $apeMaterno = $_POST['apemat'];--> <!--    $nombre1 = $_POST['nom1'];-->
        <!--    $nombre2 = $_POST['nom2'];--> <input type="submit"/></form>
    </body>
    </html>

    <?php


    //  allow the user to make a request for retrieving data


    //  create the formof the expected request

} else if ($level == '2') {

    //  recibir cookie necesaria
    $cookie = $_GET['cookie'];
    header("Content-type: image/jpeg");


    //  get captcha image url with cookie
    $ch = curl_init('http://ww4.essalud.gob.pe:7777/acredita/captcha.jpg');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_COOKIE, urldecode($cookie));
    //    curl_setopt($ch, CU)

    //  display it
    $curl_exec = curl_exec($ch);
    //  y mostrar la imagen
    curl_close($ch);
    echo  $curl_exec;


} else if ($level == '3') {



    unlink($resultadosFile);

    /////////////////


    $captcha = $_POST['captcha_code'];
    $cookie = $_POST['cookie'];


//    $dni = $_POST['document'];
    $tipo = $_POST['tipo'];
//    $apePaterno = strtoupper($_POST['apepat']);
//    $apeMaterno = strtoupper($_POST['apemat']);
//    $nombre1 = strtoupper($_POST['nom1']);
//    $nombre2 = strtoupper($_POST['nom2']);


    //  iterar sobre los datos de origen

    if (($handle = fopen("fuente_con_dni_muestra.csv", "r")) !== FALSE) {
        while (($data = fgetcsv($handle, null, ",")) !== FALSE) {

            $hc = $data[0];
            $apePaterno = $data[1];
            $apeMaterno = $data[2];
            $nombre1 = $data[3];
            $nombre2 = $data[4];
            $nombreHcCarrion = $apePaterno . " " . $apeMaterno . ", " . $nombre1 . " " . $nombre2;

            $dni = $data[5];


            //  create request model
            $ch = curl_init("http://ww4.essalud.gob.pe:7777/acredita/servlet/Ctrlwacre");
            //  set to POST
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            // set the Referer:
            curl_setopt($ch, CURLOPT_REFERER, "http://ww4.essalud.gob.pe:7777/acredita/index.jsp?td=1");
            //  set the received cookie
            curl_setopt($ch, CURLOPT_COOKIE, urldecode($cookie));

            $requestContent = '';
            if ($tipo == 'dni' || $tipo == null) {

                //  set the request content
                $requestContent = "pg=1&ll=Libreta+Electoral%2FDNI&td=1&nd=" . $dni . "&submit=Consultar&captchafield_doc=" . $captcha;
                //pg=1&ll=Libreta+Electoral%2FDNI&td=1&nd=45377113&submit=Consultar&captchafield_doc=64329
            } else if ($tipo == 'nombre') {
                $requestContent = "pg=1&ap=" . urlencode($apePaterno) . "&am=" . urlencode($apeMaterno) . "&n1=" . urlencode($nombre1) . "&n2=" . urlencode($nombre2) . "&submit=Consultar&captchafield_nom=" . $captcha;
                //    $requestContent= urlencode($requestContent);
            }


            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestContent);


            //  execute request

            //  display it
            $curl_exec = curl_exec($ch);
            //  y mostrar la imagen
            curl_close($ch);


            // identificar de que tipo de respuesta se trata: vacio, u otros

            $notFound = strpos($curl_exec, "No se encontraron registros para las siguientes condiciones");

            if ($notFound != false) {
                //  asociar los datos de entrada a un registro no existente
                //                echo "no se encontraron registros";

                guardarRegistroNoExistente($hc);


            } else {

                //                echo "multiples registros";

                //  identificar unico o multiple
                $multiple = strpos($curl_exec, "Listado de asegurados");

                if ($multiple != false) {
                    //  multiples registros, navegar cada uno y asociar los datos de todos al mismo registro de origen


                    //  search TableListado and get the wrapper table

                    $entireString = obtenerTablaQueRodeaOcurrenciaDeCadena($curl_exec, "\"TableListado\"");
                    $entireString = simplificarHtml($entireString);
                    $xmlArray = xmlStringToArray($entireString);

                    $persons = array();
                    for ($i = 1; $i < count($xmlArray['tr']); $i++) {

                        //  create and make request

                        $ch = curl_init("http://ww4.essalud.gob.pe:7777/acredita/servlet/CtrlwAseg?ori=list");
                        //  set to POST
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        // set the Referer:
                        curl_setopt($ch, CURLOPT_REFERER, "Referer: http://ww4.essalud.gob.pe:7777/acredita/servlet/Ctrlwacre");
                        //  set the received cookie
                        curl_setopt($ch, CURLOPT_COOKIE, urldecode($cookie));


                        $numeroAsegurado = trim($xmlArray['tr'][$i]['td'][2]['a'], chr(0xC2) . chr(0xA0));

                        $requestContent = "pg=1&ap=" . urlencode($apePaterno) . "&am=" . urlencode($apeMaterno) . "&n1=" . urlencode($nombre1) . "&n2=" . urlencode($nombre2) . "&td=&nd=&pg=1&opt=1&tdVerAseg=7&ndVerAseg=" . $numeroAsegurado;


                        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestContent);

                        //  display it
                        $curl_exec = curl_exec($ch);
                        //  y mostrar la imagen
                        curl_close($ch);

                        // get and parse result
                        $person = parseTextAndComposePerson($hc, $nombreHcCarrion, $curl_exec);
                        $persons[] = $person;

                        guardarRegistro($person);

                    }


                } else {

                    //                    echo "registro unico";
                    $person = parseTextAndComposePerson($hc, $nombreHcCarrion, $curl_exec);
                    guardarRegistro($person);

                }
            }

        }
        fclose($handle);
    }

    ////////////////////////////////////


    echo "Completado, revisar resultados.csv";
}



?>