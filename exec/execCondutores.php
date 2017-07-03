<?php
    session_start();

    require_once('../includes/OracleCieloJornada.class.php');

    $OraCielo   = new OracleCielo();
    $conexaoOra = $OraCielo->getCon();
    $retorno    = Array();
    #$CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexaoOra);

    #$idEmpresa = ($_POST['idEmpresa'])?$_POST['idEmpresa']:$CtrlAcesso->getUserEmpresa($_SESSION);

    if($_REQUEST['acao'] == 'insert'){
        $sqlInsCondutor = "
            INSERT INTO monitoramento.condutor(
                id_condutor,
                nome,
                cpf,
                identidade, 
                celular,
                matricula,
                data_nascimento,
                ativo--,
                --empresa
            )VALUES(
                seq_usuario.nextVal,
                '".utf8_decode($_POST['nomeCondutor'])."', 
                '".$_POST['cpfCondutor']."', 
                '".$_POST['rgCondutor']."', 
                '".$_POST['celularCondutor']."',
                '".$_POST['matriculaCondutor']."',
                to_date('".$_POST['dtNascCondutor']."', 'DD/MM/YYYY'), 
                '".$_POST['situacaoCondutor']."'
            )";
    
    $respostaInsCond = oci_parse($conexaoOra, $sqlInsCondutor);
    
    if(!oci_execute($respostaInsCond)){
        $status = 'ERRO';
        $msg    = 'Favor informar o departamento de TI.';
        #echo ' Status INSERT: ERRO ->'.$sqlInsCondutor;exit();
    }else{
        $status = 'OK';
        $msg    = 'Condutor inserido.';
        #echo ' Status INSERT: OK ->'.$sqlInsCondutor;exit();
    }

    oci_free_statement($respostaInsCond);
}
elseif($_REQUEST['acao'] == 'update'){
	#$sqlAux = ($_POST['situacaoCondutor'] == 11)?' , data_desativado = now()':'';

	$sqlUpCondutor = "
        UPDATE monitoramento.condutor 
           SET nome = '".utf8_decode($_REQUEST['nmCondutor'])."',
               cpf = '".$_POST['cpfCondutor']."', 
               identidade = '".$_POST['rgCondutor']."',
               celular = '".$_POST['celularCondutor']."', 
               matricula = '".$_POST['matriculaCondutor']."', 
               data_nascimento = to_date('".$_POST['dtNascCondutor']."', 'DD/MM/YYYY'), 
               --ativo = 'T', 
               profissao = '".$_POST['profissaoCondutor']."'
         WHERE id_condutor = ".$_REQUEST['idCondutor'];

        #echo '<pre>'.$sqlUpCondutor.'</pre>'; exit();

        $respostaUpCond = oci_parse($conexaoOra, $sqlUpCondutor);
    
        if(!oci_execute($respostaUpCond)){
            $status = 'ERRO';
            $msg = 'Favor informar o departamento de TI';
            #echo ' Status UPDATE: ERRO ->'.$sqlUpCondutor; exit();
        } else {
            $status = 'OK';
            $msg = 'Cadastro do condutor atualizado!';
            #echo ' Status UPDATE: OK ->'.$sqlUpCondutor; exit();
        }
        oci_free_statement($respostaUpCond);
    }elseif($_REQUEST['acao'] == 'desativar') {
        $sqlDesatCondutor = "
            UPDATE monitoramento.condutor cond
            SET cond.ativo = 'F'
            WHERE cond.id_condutor = ".$_REQUEST['idCondutor'];

        #echo '<pre>'.$sqlDesatCondutor.'</pre>'; exit();
        $respostaDesatCond = oci_parse($conexaoOra, $sqlDesatCondutor);

        if(!oci_execute($respostaDesatCond)) {
            $status = 'ERRO';
            $msg = 'Favor informar o departamento de TI';
            #echo ' Status UPDATE: ERRO ->'.$sqlDesatCondutor; exit();
        } else {
            $status = 'OK';
            $msg = 'Condutor desativado!';
            #echo ' Status UPDATE: OK ->'.$sqlDesatCondutor; exit();
        }    
        oci_free_statement($respostaDesatCond);
    }

    $retorno['status']  = $status;
    $retorno['msg']     = $msg;
    $json               = $retorno;

    oci_close($conexaoOra);

    header('Content-Type: application/x-json');
    echo json_encode($json);