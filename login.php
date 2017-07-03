<?php
/* Limpa a cache */
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, cachehack=".time());
header("Cache-Control: no-store, must-revalidate");
header("Cache-Control: post-check=-1, pre-check=-1", false);
/* Fim Limpa a cache */

session_start("CIELO_JORNADA");
session_unset();
require('includes/BancoPost.class.php');
$conexao = new BancoPost();

$_SESSION['sessionUserLogin'] = "";  
$_SESSION['sessionUserId']    = "";  

$sqlUsu = "SELECT *
		   FROM usuarios
		   WHERE UPPER(login) = UPPER('".$_REQUEST["loginUsuario"]."')
		   AND UPPER(senha) = UPPER('".$_REQUEST["pswUsuario"]."')";
//echo $sqlUsu;
$resUsu = $conexao->getResult($sqlUsu);

$_SESSION['sessionUserLogin']    = $resUsu[0]['login'];  
$_SESSION['sessionUserId']       = $resUsu[0]['id_usuario'];  
$_SESSION['sessionUserGroup']    = $resUsu[0]['grupo'];  
$_SESSION['sessionEmpresa']      = $resUsu[0]['empresa'];  
$_SESSION['sessionCentral']      = $resUsu[0]['central'];  

echo "Carregando...";

if($_SESSION['sessionUserLogin']){
    echo "{success: true}";
	header("location:jornada.php");
}
 else {
	echo '<script type="text/javascript">
			alert("Login ou senha incorreto!");
			window.location.href = "http://jornada.cielo.ind.br";
          </script>';
}
?>