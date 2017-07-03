<?php
error_reporting(0);
session_start();

require_once('../includes/OracleCieloJornada.class.php');
require_once('../includes/Controles.class.php');

$OraCielo   = new OracleCielo();
$conexaoOra = $OraCielo->getCon();

//$data_request = json_decode(file_get_contents('php://input'));
$json_request   = file_get_contents('php://input');
$json           = json_decode($json_request);
$sqlAux         = '';
$arrayUsu       = array();

//if($json->idUsuario){
//    $sqlDel = "DELETE FROM usuarios WHERE id_usuario = '".$json->idUsuario."'";
//    $conexao->execSql($sqlDel);
//}

if($_REQUEST['acao'] == 'verificarSenha'){
    $sqlVerificaSenha = "
        SELECT count(senha) as saida
        FROM usuario
        WHERE id_usuario = ".$_REQUEST['idUsuario']." 
              AND senha = '".$_REQUEST['senhaUsuario']."'";
    
    $respostaSenha = oci_parse($conexaoOra, $sqlVerificaSenha);

    if(!oci_execute($respostaSenha)){
//        echo ' Status SELECT: ERRO ->'.$sqlVerificaSenha;exit();
        $status = 'ERRO';
        $msg    = 'Favor informar o departamento de TI.';
        
    }else{
//        echo ' Status SELECT: OK ->'.$sqlVerificaSenha;exit();
        $status = 'OK_SENHA';
        $msg    = 'Senha incorreta.';
        
        while (($row = oci_fetch_assoc($respostaSenha)) != false) {
            $contem = $row['SAIDA'];
        }
        
        if($contem == 1){
            $msg    = "Senha confirmada.";
        }
    }
    $array['status']  = $status;
    $array['msg']     = $msg;
}else{
    if($_REQUEST['idEmpresa']){
        $sqlAux.= " AND empresa = ".$_REQUEST['idEmpresa']."";
    }else{
        $sqlAux.= "";
    }

    if(!$_SESSION['sessionCentral'] and $_REQUEST['grupoUsu']){
        $sqlAux.= "";
    }else{
        $sqlAux.= " AND central = ".$_SESSION['sessionCentral']."";
    }

    if($_REQUEST['nmUsuario'])
	$sqlAux .= " AND UPPER(usuario.nome) LIKE UPPER('".$_REQUEST['nmUsuario']."%') ";    

    $sqlUsuario = "
        SELECT  usuario.id_usuario, usuario.nome, usuario.login, usuario.email, grupo.id_grupo AS tp_usu, grupo.descricao AS descricao_tp_usu, ep.id_empresa, ep.nome as nome_empresa
        FROM    usuario, grupo, monitoramento.empresa ep
        WHERE   usuario.grupo = grupo.id_grupo
                AND usuario.empresa = ep.id_empresa
                ".$sqlAux."
        ORDER BY usuario.nome";

    $respostaUsuario = oci_parse($conexaoOra, $sqlUsuario);

    if(!oci_execute($respostaUsuario)){
    //    echo ' Status SELECT: ERRO ->'.$sqlUsuario;exit();
    }else{
    //    echo ' Status SELECT: OK ->'.$sqlUsuario;exit();
    }

    while (($row = oci_fetch_assoc($respostaUsuario)) != false) {
        $arrayUsu[] = array(
            "idUsuario"             => $row['ID_USUARIO'],
            "nomeUsuario"           => htmlentities($row['NOME']),
            "loginUsuario"          => $row['LOGIN'],
            "emailUsuario"          => $row['EMAIL'],
            "descricaoTipoUsuario"  => $row['DESCRICAO_TP_USU'],
            "idTipoUsuario"         => $row['TP_USU'],
            "idEmpresa"             => $row['ID_EMPRESA'],
            "nomeEmpresa"           => $row['NOME_EMPRESA']
        );
    }
    $array['usuarios']  = $arrayUsu;
}

$callback   = $_REQUEST['jsonp'];
$json       = $array;

//start output
if ($callback) {
    header('Content-Type: text/javascript');
    echo $callback . '(' . json_encode($json) . ');';
} else {
    header('Content-Type: application/x-json');
    echo json_encode($json);
}