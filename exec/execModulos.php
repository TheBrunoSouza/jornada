<?
require('../../../jornada/includes/BancoPost.class.php');
$conexao = new BancoPost();

if($_REQUEST['acao']=='insert'){
	$sqlIns = "INSERT INTO modulos
			   (descricao, icon, url)
			   VALUES
			   ('".utf8_decode($_POST['descModulo'])."', '".$_POST['iconModulo']."', '".$_POST['urlModulo']."')";

	//echo $sqlIns;
	$result = $conexao->execSql($sqlIns);

	echo "{\"success\":true,\"msg\":\"Módulo inserido com sucesso!\"}";
}
elseif($_REQUEST['acao']=='update'){
	$sqlUp = "UPDATE modulos SET
			  descricao = '".utf8_decode($_POST['descModulo'])."',
			  icon      = '".$_REQUEST['iconModulo']."',
			  url       = '".$_REQUEST['urlModulo']."'
			  WHERE id_modulo = ".$_REQUEST['idModulo']."";
	$result = $conexao->execSql($sqlUp);
	//echo $sqlUp;
	if($result)
		echo "{\"success\":true,\"msg\":\"Módulo alterado com sucesso!\"}";
	else
		echo "{\"failure\":true,\"msg\":\"Não foi possível alterar o módulo!\"}";	
}
?>
