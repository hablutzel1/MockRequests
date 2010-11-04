<?php

  // make request to a web page with curl
  $ch = curl_init('http://www.google.com');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HEADER, 1);

  // captura cookies
  preg_match('/^Set-Cookie: (.*?);/m', curl_exec($ch), $m);


  // list cookies
  var_dump(parse_url($m[1]));

  // TODO get captcha image url


  // TODO display it

  // TODO create form


// TODO allow the user to make a request for retrieving data


// TODO create the formof the expected request


?>