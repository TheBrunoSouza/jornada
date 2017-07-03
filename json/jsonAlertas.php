<?php
	session_start();

	require_once('../includes/OracleCieloJornada.class.php');
	require_once('../includes/Controles.class.php');

	$OraCielo		= new OracleCielo();
	$conexao		= $OraCielo->getCon();
	$CtrlAcesso		= new Controles($_SERVER['REMOTE_ADDR'], $conexao);
	$idUsuario 		= $CtrlAcesso->getUserID($_SESSION);
	$nmUsuario 		= $CtrlAcesso->getUserLogin($_SESSION);
	$json_request 	= file_get_contents('php://input');
	$json 			= json_decode($json_request);
	$sqlAux 		= '';

	if($json->idAlerta){
		$sqlUp = "
			UPDATE alertas SET
		  		justificado = 'Atendido pelo usuário ".$nmUsuario."',
	  			usuario = ".$idUsuario.",
  				data_hora_atend = now()
	  		WHERE id_alerta = ".$json->idAlerta;
		//echo $sqlUp;
		$conexao->execSql($sqlUp);
	}

	if($_REQUEST['origem'] == 'grid'){
		$sqlAux.= "AND data_hora >= to_date('".date('dmY', strtotime('-3 days'))."', 'DDMMYYYY')";
	}elseif($_REQUEST['origem']=='relatorio'){
		if($_REQUEST['dtIni'])
			$sqlAux.= " AND data_hora >= to_date('".$_REQUEST['dtIni']."', 'YYYY-MM-DD')";
		if($_REQUEST['dtIni'])
			$sqlAux.= " AND data_hora-1 < to_date('".$_REQUEST['dtFim']."', 'YYYY-MM-DD')";
		if($_REQUEST['idCondutor'])
			$sqlAux.= " AND id_condutor = '".$_REQUEST['idCondutor']."'";
	}

	if(!$_REQUEST['idCondutor'] and $_SESSION['sessionEmpresa']){
		$sqlAux.=" AND condutor.empresa = ".$_SESSION['sessionEmpresa']."";
	}elseif(!$_REQUEST['idCondutor'] and $_SESSION['sessionCentral']){
		$sqlAux.=" AND condutor.empresa IN (SELECT id_empresa FROM empresa WHERE central = ".$_SESSION['sessionCentral']."";
	}

	if($_REQUEST['idEmpresa']){
		$sqlAler = "
			SELECT id_alerta, descricao, to_char(data_hora, 'DD/MM HH24:MI') AS dt_hr, condutor.nome, placa, justificado, to_char(data_hora_atend, 'DD/MM HH24:MI') AS dt_hr_atend
			FROM alertas, monitoramento.condutor
			WHERE id_condutor = condutor
				AND empresa = ".$_REQUEST['idEmpresa']."
				AND justificado IS NULL 
				".$sqlAux."
			ORDER BY data_hora DESC";
		#echo "<pre>".$sqlAler."</pre>";

		$resAler = OCIParse($conexao, $sqlAler);
		OCIExecute($resAler);
		$arrayAler = array();

		while(OCIFetchInto($resAler, $rowAler, OCI_ASSOC)){
            $descricao = explode(": ", $rowAler['DESCRICAO']);
            $tempo = (int)$descricao[1];
			$arrayAler[] = array(
				"idAlerta"			=>$rowAler['ID_ALERTA'],
				"descAlerta"		=>$descricao[0],
                "tempo"				=>$tempo,
				"dtHrAlerta"		=>$rowAler['DT_HR'],
				"nmCondutor"		=>$rowAler['NOME'],
				"plcAlerta"			=>$rowAler['PLACA'] ? $rowAler['PLACA'] : '-',
				"justAlerta"		=>$rowAler['JUSTIFICADO'],
				"dtHrAlertaAtend"	=>$rowAler['DT_HR_ATEND']
			);
		}
	}

	#print_r($arrayAler);
	$array['alertas'] 	= $arrayAler;
	$json 				= $array;
	$callback 			= $_REQUEST['jsonp'];

	//start output
	if ($callback) {
		header('Content-Type: text/javascript');
		echo $callback . '(' . json_encode($json) . ');';
	} else {
		header('Content-Type: application/x-json');
		echo json_encode($json);
	}