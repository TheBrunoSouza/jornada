<?
require('../../../jornada/includes/BancoPost.class.php');
$conexao = new BancoPost();

if($_REQUEST['acao']=='insert'){
	$sqlIns = "INSERT INTO grupo_usuarios
			   (descricao)
			   VALUES
			   ('".utf8_decode($_POST['descGrupo'])."')";

	//echo $sqlIns;
	$result = $conexao->execSql($sqlIns);

	echo "{\"success\":true,\"msg\":\"Grupo inserido com sucesso!\"}";
}
elseif($_REQUEST['acao']=='update'){
	$sqlUp = "UPDATE grupo_usuarios SET
			  descricao = '".utf8_decode($_REQUEST['descGrupo'])."'
			  WHERE id_grupo_usuario = ".$_REQUEST['idGrupo']."";
	$result = $conexao->execSql($sqlUp);
	//echo $sqlUp;
	if($result)
		echo "{\"success\":true,\"msg\":\"Grupo alterado com sucesso!\"}";
	else
		echo "{\"failure\":true,\"msg\":\"Não foi possível alterar o grupo!\"}";	
}
?>
