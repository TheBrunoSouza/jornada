<?php
    session_start();
    require_once('../includes/OracleCieloJornada.class.php');
    require_once('../includes/Controles.class.php');

    $OraCielo   = new OracleCielo();
    $conexaoOra = $OraCielo->getCon();
    $retorno    = Array();
    $CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexao);
    (!$CtrlAcesso->checkUsuario($_SESSION)) ? exit : null;
    $loginUsu   = $CtrlAcesso->getUserLogin($_SESSION);

    if($_REQUEST['acao'] == 'insert') {
        $teclado = ($_POST['teclado'] == 2) ? 'T' : 'F';
		$sqlIns = "
			INSERT INTO empresa_jornada
				(empresa, usuario_cad, teclado_alfa)
			VALUES
				('".$_POST['idEmpresa']."', '".$loginUsu."', '".$teclado."')";
        #echo "<pre>".$sqlIns."</pre>";

        $respostaInsEmp = oci_parse($conexaoOra, $sqlIns);

        if(!oci_execute($respostaInsEmp)) {
            $status = 'ERRO';
            $msg = 'Favor informar ao departamento de TI';
            #echo ' Status INSERT: ERRO ->'.$sqlIns;exit();
        } else {
			$status = 'OK';
            $msg = 'Empresa inserida';
			#echo ' Status INSERT: OK ->'.$sqlIns;exit();
		}
        oci_free_statement($respostaInsEmp);
    }elseif($_REQUEST['acao'] == 'desativar') {
        $sqlDesatEmp = "
            UPDATE empresa_jornada
            SET ativa = 'F'
            WHERE empresa = ".$_REQUEST['idEmpresa'];

        #echo '<pre>'.$sqlDesatEmp.'</pre>'; exit();
        
        $respostaDesatEmp = oci_parse($conexaoOra, $sqlDesatEmp);

        if(!oci_execute($respostaDesatEmp)) {
            $status = 'ERRO';
            $msg = 'Favor informar ao departamento de TI';
            #echo ' Status UPDATE: ERRO ->'.$sqlDesatCondutor; exit();
        } else {
            $status = 'OK';
            $msg = 'Empresa desativada';
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