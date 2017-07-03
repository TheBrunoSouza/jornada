<?php
    error_reporting(0);
    session_start();

    require_once('../includes/OracleCieloJornada.class.php');

    $OraCielo = new OracleCielo();
    $conexaoOra = $OraCielo->getCon();
    $contemUsuario = false;
    $retorno = Array();

    if($_POST['idEmpresaUsuario'] == null) {
        $sqlIdEmpresa = "NULL";
    } else {
        $sqlIdEmpresa = $_POST['idEmpresaUsuario'];
    }

    if($_REQUEST['acao'] == 'insert') {
        $sqlVerificaUsuario = " 
            SELECT id_usuario, nome, login 
              FROM usuario
             WHERE UPPER(login) = UPPER('".$_POST['loginUsuario']."')";

        $respostaVerUsu = oci_parse($conexaoOra, $sqlVerificaUsuario);

        if(!oci_execute($respostaVerUsu)){
    //        echo ' Status SELECT: ERRO ->'.$sqlVerificaUsuario;exit();
        } else {
    //        echo ' Status SELECT: OK ->'.$sqlVerificaUsuario;exit();
        }

        while (($row = oci_fetch_assoc($respostaVerUsu)) != false) {
            $contemUsuario = true;
        }

        if($contemUsuario == false){
            $sqlInsertUsuario = "
                INSERT INTO usuario
                    (id_usuario,
                     nome, 
                     login, 
                     senha, 
                     grupo, 
                     empresa, 
                     email)
                VALUES
                    (seq_usuario.nextVal,
                    '".utf8_decode($_POST['nomeUsuario'])."', 
                    '".$_POST['loginUsuario']."', 
                    '".$_POST['senhaUsuario']."', 
                    ".$_POST['grupoUsuario'].", 
                    ".$sqlIdEmpresa.", 
                    '".$_POST['emailUsuario']."')";

            $respostaInsUsu = oci_parse($conexaoOra, $sqlInsertUsuario);

            if(!oci_execute($respostaInsUsu)){
                $status = 'ERRO';
                $msg    = 'Favor informar o departamento de TI';
                // echo ' Status INSERT: ERRO ->'.$sqlInsertUsuario;exit();
            } else {
                $status = 'OK';
                $msg    = 'Usuário inserido';
                // echo ' Status INSERT: OK ->'.$sqlInsertUsuario;exit();
            }
            oci_free_statement($respostaInsUsu);
        } else {
            $status = 'OK_EXCECAO_USUARIO';
            $msg = 'Este usuário já existe no sistema';
        }
    } elseif($_REQUEST['acao'] == 'update') {

        if($_REQUEST['senhaUsuario'] != '' || $_REQUEST['senhaUsuario'] != null) {
            $sqlSenha = "senha = '".$_REQUEST['senhaUsuario']."',";
        } else {
            $sqlSenha = "";
        }

        $sqlUpdateUsuario = "
            UPDATE usuario SET
                   nome = '".utf8_decode($_REQUEST['nomeUsuario'])."',
                   login = '".$_REQUEST['loginUsuario']."',
                   ".$sqlSenha."
                   grupo = ".$_POST['grupoUsuario'].",
                   email = '".$_POST['emailUsuario']."',
                   empresa = ".$_POST['idEmpresaUsuario']."
             WHERE id_usuario = ".$_REQUEST['idUsuario']."";

        $respostaUpUsu = oci_parse($conexaoOra, $sqlUpdateUsuario);

        if(!oci_execute($respostaUpUsu)){
            $status = 'ERRO';
            $msg = 'Favor informar o Departamento de TI';
            // echo ' Status UPDATE: ERRO ->'.$sqlUpdateUsuario;exit();
        }else{
            $status = 'OK';
            $msg = 'Cadastro de usuário atualizado';
            // echo ' Status UPDATE: OK ->'.$sqlUpdateUsuario;exit();
        }
        oci_free_statement($respostaUpUsu);
    } elseif($_REQUEST['acao'] == 'delete') {
        $sqlDeleteUsuario = "DELETE FROM usuario WHERE id_usuario = ".$_REQUEST['idUsuario'];

         $respostaDelUsu = oci_parse($conexaoOra, $sqlDeleteUsuario);

        if(!oci_execute($respostaDelUsu)){
            $status = 'ERRO';
            $msg = 'Favor informar o departamento de TI';
            // echo ' Status DELETE: ERRO ->'.$sqlDeleteUsuario;exit();
        } else {
            $status = 'OK';
            $msg = 'Usuário excluído';
            // echo ' Status DELETE: OK ->'.$sqlDeleteUsuario;exit();
        }
        oci_free_statement($respostaDelUsu);
    }

$retorno['status']  = $status;
$retorno['msg'] = $msg;
$json = $retorno;

oci_close($conexaoOra);

//start output

header('Content-Type: application/x-json');
echo json_encode($json);