<?php
error_reporting(0);
session_start();
include('../includes/json.php');

require_once('../includes/OracleCieloJornada.class.php');
$OraCielo=new OracleCielo();
$conexao=$OraCielo->getCon();

$_SESSION['sessionUserLogin'] = "";  
$_SESSION['sessionUserId']    = "";  

$sqlUsu = "SELECT *
		   FROM usuario
		   LEFT JOIN monitoramento.empresa ON empresa.id_empresa = usuario.empresa
		   WHERE UPPER(usuario.login) = UPPER('".$_POST["loginUsuario"]."')
		   AND UPPER(usuario.senha) = UPPER('".$_POST["pswUsuario"]."')";

//echo $sqlUsu;
//exit;

$resUsu = OCIParse($conexao, $sqlUsu);
OCIExecute($resUsu);
OCIFetchInto($resUsu, $ddUsu, OCI_ASSOC);

$_SESSION['sessionUserLogin']  = $ddUsu['LOGIN'];
$_SESSION['sessionUserId']     = $ddUsu['ID_USUARIO'];
$_SESSION['sessionUserGroup']  = $ddUsu['GRUPO'];
$_SESSION['sessionEmpresa']    = $ddUsu['EMPRESA'];

//echo $ddUsu['LOGIN'];

//$json = new json();

//$object = new stdClass();
//$object->FirstName = 'John';
//$object->LastName = 'Doe';
//$array = array(1,'2', 'Pieter', true);
//$jsonOnly = '{"Hello" : "darling"}';

//$json->add('status', '200');
//$json->add("worked");
//$json->add("things", false);
//$json->add('friend', $object);
//$json->add("arrays", $array);
////$json->add("json", $jsonOnly, false);
//if($$ddUsu['ATIVA']<>'t' and $ddUsu['ATIVA']=='f') {
//    $json->add('status', '200');
//    $json->add('failure', true);
//    $json->add('msg', 'Acesso Indisponível!');
//}elseif($_SESSION['sessionUserLogin']){
//    $json->add('status', '200');
//    $json->add('success', true);
//}else{
//    $json->add('status', '200');
//    $json->add('failure', true);
//    $json->add('msg', 'Usuário ou senha inválido!');
//}
// This will output the legacy JSON
//$json->send();

if($$ddUsu['ATIVA']<>'T' and $ddUsu['ATIVA']=='F') {
	echo "{\"failure\":true,\"msg\":\"Acesso indisponível!\"}";
} elseif($_SESSION['sessionUserLogin']){
    echo "{success: true}";
} else {
	echo "{\"failure\":true,\"msg\":\"Usuário ou senha inválido!\"}";
}
?>