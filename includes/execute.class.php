<?php
class ExecClass{
	private $con = null;
	
	function __construct($conexao){
		if($conexao){
			$this->con = $conexao;
		}
		else{
			require_once('../includes/OracleCieloJornada.class.php');
			$this->con = new OracleCielo();
		}
	}
		
	function __destruct(){
		$con = null;
	}

    public function getIdCondutor($nm, $emp){
        $sqlCond = "SELECT id_condutor
					FROM condutor
					WHERE nome LIKE '".$nm."'
					AND empresa = '".$emp."'
					LIMIT 1";
        echo '<pre> SELECT COND-->'.$sqlCond.'</pre>';
        $respostaCP = OCIParse($this->con, $sqlCond);

        $resCond = OCIExecute($respostaCP);

        oci_free_statement($respostaCP);
        oci_close($this->con);
        return $resCond[0]['id_condutor'];
    }

    public function insertAlerta($desc, $dtHr, $cond, $plc){
        $sqlIns = "INSERT INTO alertas
				   (condutor, placa, data_hora, descricao)
				   VALUES
				   (".$cond.", '".$plc."', '".$dtHr."', '".$desc."')";

        $respostaCP = OCIParse($this->con, $sqlIns);

        if(OCIExecute($respostaCP)){
            echo "<br>INSERT OK ALERTAS ############################################################## <pre>".$sqlIns."</pre>";
        }
        else{
            echo "<br>FALHOU INSERT ALERTAS ############################################################## <pre>".$sqlIns."</pre>";
            //exit;
        }
        oci_free_statement($respostaCP);
        oci_close($this->con);
    }

    /**
     * Função Função responsável por copiar os dados atuais da tabela JORNADA e gravar-los na tabela de JORNADA_LOG.
     * @param $dataAlteracao (String) Dentro da função erá formatada para DD/MM/YYYY
     * @param $idCondutor (Int) Código do condutor
     * @param $idUsuario (Int) Código do usuário logado no sistema
     * @param $justificativa (String) Justificativa de alteração que será gravada na tabela JORNADA_LOG
     * @param $horaAlteracao Hora do sistema no momento em que o usuário fez a alteração. Essa variavel sempre deve ser igual para os dois inserts de log que serão realizados.
     * Deve-se capturá-la com a function getHoraSistema() antes dos inserts e em seguida passar variavel de retorno para esta função.
     */
    public function gravaLogJornada($dataAlteracao, $idCondutor, $idUsuario, $justificativa, $horaAlteracao) {
        //CONSULTA JORNADA - ATUAL
        $sqlJornada = "
				SELECT 
						id_jornada,
						situacao,
						to_char(data_ini, 'DD/MM/YYYY hh24:mi:ss') as hora_ini, 
						to_char(data_fim, 'DD/MM/YYYY hh24:mi:ss') as hora_fim,
						to_char(data_db, 'DD/MM/YYYY') data_db,
						data_hora,
						condutor,
						placa,
						data_alt,
						situacao_ant,
						jornada_ant
				FROM jornada
				WHERE trunc(data_ini) >= to_date('".$dataAlteracao."', 'DD/MM/YYYY')
						AND trunc(data_fim) <= to_date('".$dataAlteracao."', 'DD/MM/YYYY')
						AND condutor = ".$idCondutor;

        $respostaJornada = oci_parse($this->con, $sqlJornada);

        if(!oci_execute($respostaJornada)){
            echo ' Erro no select da jornada atual.';
            echo $sqlJornada;
            exit();
        }

        //LOG
        while (($row = oci_fetch_assoc($respostaJornada)) != false) {
            $sqlLogAtual = "
							INSERT INTO jornada_log (
									id_jornada_log, 
									situacao, 
									data_ini, 
									data_fim, 
									data_db, 
									data_hora, 
									condutor, 
									placa, 
									data_alt, 
									usuario, 
									sit_anterior, 
									jornada_ant, 
									descricao,
									id_jornada)
							VALUES(
									SEQ_JORNADA_LOG.nextval,
									'".$row['SITUACAO']."',
									to_timestamp('".$row['HORA_INI']."', 'DD/MM/YYYY HH24:MI:SS'),
									to_timestamp('".$row['HORA_FIM']."', 'DD/MM/YYYY HH24:MI:SS'),
									to_timestamp('".$row['DATA_DB']."', 'DD/MM/YYYY'),
									'".$row['DATA_HORA']."',
									'".$row['CONDUTOR']."',
									'".$row['PLACA']."',
									to_timestamp('".$horaAlteracao."', 'DD/MM/YYYY HH24:MI:SS'),
									'".$idUsuario."',
									'".$row['SITUACAO_ANT']."',
									'".$row['JORNADA_ANT']."',
									'".$justificativa."',
									'".$row['ID_JORNADA']."'
							)
					";

            $respostaLog = oci_parse($this->con, $sqlLogAtual);
            if(!oci_execute($respostaLog)){
                echo ' Erro insert de log.';
                echo $sqlLogAtual;
                exit();
            }
        }
        oci_free_statement($respostaJornada);
//        oci_free_statement($respostaHora);
        oci_free_statement($respostaLog);
        oci_close($this->con);
    }

    /**
     * Função responsável por regerar a jornada de um condutor
     * @param $idCondutor (Int) Código do condutor
     * @param $idEmpCondutor (Int) Código da empresa do condutor
     * @param $data (String) Será convertida dentro da função para 'DDMMYYYY'
     */
    public function regeraJornada($idCondutor, $idEmpCondutor, $data){
        $sqlRegerarJornada  = "BEGIN pkg_jornada.beginJornada('".$idCondutor."', '".$idEmpCondutor."', to_date('".$data."', 'DDMMYYYY')); COMMIT; END;";
        $respostaRegera     = oci_parse($this->con, $sqlRegerarJornada);

        if(!oci_execute($respostaRegera)){
            echo ' Erro na função que regera a jornada.';
            echo $sqlRegerarJornada;
            exit();
        }
        oci_free_statement($respostaRegera);
        oci_close($this->con);
    }

    /**
     * Função que serve para atualizar a coluna EDITADO da tabela JORNADA. Esta coluna indica se houve alguma modificação em qualquer situação do dia
     * @param $condutor (Int) Código do condutor
     * @param $data (string) Data das situações que devem ser atualizadas - Será convertido no sql para 'DDMMYYYY'
     */
    public function atualizaEditado($condutor, $data){
        $sqlUpdateJornada   = "UPDATE jornada SET editado = 'T' WHERE condutor = '".$condutor."' AND trunc(data_ini) = to_date('".$data."', 'DDMMYYYY')";
        $respostaUpdate     = oci_parse($this->con, $sqlUpdateJornada);

        if(!oci_execute($respostaUpdate)){
            echo ' Erro no update na tabela JORNADA.';
            echo $sqlUpdateJornada;
            exit();
        }
        oci_free_statement($respostaUpdate);
        oci_close($this->con);
    }

    public function getHoraSistema(){
        $sqlHora = "  
					SELECT to_char(sysdate,'DD/MM/YYYY hh24:mi:ss') as data_sistema
					FROM dual";

        $respostaHora = oci_parse($this->con, $sqlHora);

        if(!oci_execute($respostaHora)){
            echo ' Erro ao buscar hora do sistema.';
            echo $sqlHora;
            exit();
        }else{
            $hora = oci_fetch_assoc($respostaHora);
            return $hora;
        }
    }

    public function regeraJornadaComLog($condutor, $data, $idUsuario, $empCond){
        //Consultando se existe algum registro de log
        //Sim: Grava novo log
        //Não: Apenas regera a jornada do condutor
        $sqlVerificaLog = "SELECT count(*) as numeroDeLog FROM jornada_log WHERE condutor = '".$condutor."' AND trunc(data_ini) = to_date('".$data."', 'DDMMYYYY')";
        $resLog = oci_parse($this->con, $sqlVerificaLog);

        if(!oci_execute($resLog)){
            echo ' Erro no select de consulta do do log.';
            echo $sqlVerificaLog;
            exit();
        }else{
            $log = oci_fetch_assoc($resLog);
            if($log['NUMERODELOG'] > 0){

                //Capturando a hora do sistema para incluir no log
                $horaDaAlteracao = getHoraSistema();

                //Grava a jornada atual
                $this->gravaLogJornada($data, $condutor, $idUsuario, "Dados regerados.", $horaDaAlteracao['DATA_SISTEMA']);

                //Regera a jornada
                $this->regeraJornada($condutor, $empCond, $data);

                //Grava a jornada regerada
                $this->gravaLogJornada($data, $condutor, $idUsuario, NULL, $horaDaAlteracao['DATA_SISTEMA']);

                //Atualiza a coluna de edição para 'T'
                $this->atualizaEditado($condutor, $data);
            }else{
                //Regera a jornada
                $this->regeraJornada($condutor, $empCond, $data);
            }
        }
        oci_free_statement($resLog);
    }

    public function atualizaAfastamento($condutor, $dataIni, $dataFim, $acao){
        if($acao == 'create'){
            $sqlIdAfast = "
            SELECT id_afastamento
            FROM afastamento
            WHERE condutor = '".$condutor."' 
                AND trunc(data_ini) = to_date('".$dataIni."', 'DD-MM-YYYY') 
                AND trunc(data_fim) = to_date('".$dataFim."', 'DD-MM-YYYY')";

            $respostaIdAfas = oci_parse($this->con, $sqlIdAfast);

            if(!oci_execute($respostaIdAfas)){
                echo ' Erro ao buscar id do afastamento.';
                echo $sqlIdAfast;
                exit();
            }else{
                $idAfastamento = oci_fetch_assoc($respostaIdAfas);
                $afastamento = $idAfastamento['ID_AFASTAMENTO'];
            }
            oci_free_statement($respostaIdAfas);
        }else{
            $afastamento = 'NULL';
        }

        $sqlUpdateJornada = "
            UPDATE jornada 
            SET afastamento = ".$afastamento." 
            WHERE condutor = '".$condutor."'
                    AND trunc(data_ini) >= to_date('".$dataIni."', 'DD/MM/YYYY')
                    AND trunc(data_fim) <= to_date('".$dataFim."', 'DD/MM/YYYY')";

        $respostaUpdate = oci_parse($this->con, $sqlUpdateJornada);

        if(!oci_execute($respostaUpdate)){
            echo ' Erro no update na tabela JORNADA.';
            echo $sqlUpdateJornada;
            exit();
        }
        oci_free_statement($respostaUpdate);
        oci_close($this->con);
    }

    /**
     * Função que retorna o tempo de horas noturnas para a situação
     * @param $dataIni Data de início da situação
     * @param $dataFim Data de fim da situação
     * @return int Total em minutos do tempo noturno referente a situação
     */
    function totalHorasNoturnas($dataIni, $dataFim){

        $quebraIni  = explode(':', $dataIni);
        $horaIni    = (int) $quebraIni[0];
        $minIni     = (int) $quebraIni[1];

        $quebraFim  = explode(':', $dataFim);
        $horaFim    = (int) $quebraFim[0];
        $minFim     = (int) $quebraFim[1];

        //Noite (22:00 até 23:59)
        if($horaFim >= 22) {
            if($horaIni < 22) {
                $totalFim = ($horaFim * 60) + $minFim;
                $total = $totalFim - 1320;
            }else{
                $totalIni = ($horaIni * 60) + $minIni;
                $totalFim = ($horaFim * 60) + $minFim;
                $total = $totalFim - $totalIni;
            }
        }

        //Manhã (00:00 até 05:00)
        if($horaIni < 5) {
            if($horaFim >= 5) {
                //Se a situação terminar DEPOIS das 05:00
                $totalIni = ($horaIni * 60) + $minIni;
                $total = 300 - $totalIni;
            }else{
                //Se a situação terminar ANTES das 05:00
                $totalIni = ($horaIni * 60) + $minIni;
                $totalFim = ($horaFim * 60) + $minFim;
                $total = $totalFim - $totalIni;
            }
        }
        return $total;
    }
		
//	public function insertSituacao($sit, $st, $cond, $plc, $dtIni, $dtFim, $idEve){
//		$dtIniAux = ($dtIni)?"to_timestamp('".$dtIni."', 'YYYY-MM-DD HH24:MI:SS')":'null';
//		$dtFimAux = ($dtFim)?"to_timestamp('".$dtFim."', 'YYYY-MM-DD HH24:MI:SS')":'null';
//		$stAux = ($st)?$st:'null';
//		$idEve = ($idEve)?$idEve:'null';
//		$plcAux = ($plc)?$plc:'null';
//		$sqlIns = "INSERT INTO diario_de_bordo
//				   (data_ini, data_fim, situacao, status, condutor, placa, evento)
//				   VALUES
//				   (".$dtIniAux.", ".$dtFimAux.", '".$sit."', ".$stAux.", '".$cond."', '".$plcAux."', ".$idEve.")";
//
//                $respostaCP = OCIParse($this->con, $sqlIns);
//
//		if(OCIExecute($respostaCP)){
//			echo "<br>INSERT OK startSituacao ############################################################## <pre>".$sqlIns."</pre>";
//			$this->updateEvento($idEve);
//		}
//		else{
//			echo "<br>FALHOU INSERT startSituacao ############################################################## <pre>".$sqlIns."</pre>";
//			//exit;
//		}
//	}
	
//	public function updateSituacao($cond, $dtHr, $idEve){
//		$sqlUp = "UPDATE diario_de_bordo SET
//				  data_fim = to_timestamp('".$dtHr."', 'YYYY-MM-DD HH24:MI:SS')
//				  WHERE condutor = ".$cond."
//				  AND data_fim IS NULL
//				  AND data_ini <= to_timestamp('".$dtHr."', 'YYYY-MM-DD HH24:MI:SS')";
//
//                $respostaCP = OCIParse($this->con, $sqlUp);
//
//		if(OCIExecute($respostaCP)){
//			echo "<br>UPDATE ############################################################## <pre>".$sqlUp."</pre>";
//			$this->updateEvento($idEve);
//		}
//		else{
//			echo "<br>FALHOU UPDATE ############################################################## <pre>".$sqlUp."</pre>";
//			//exit;
//		}
//	}

//	public function updateSitById($cond, $dtHr, $idSit){
//		$sqlUp = "UPDATE diario_de_bordo SET
//				  data_fim = to_timestamp('".$dtHr."', 'YYYY-MM-DD HH24:MI:SS')
//				  WHERE condutor = ".$cond."
//				  AND id_diario = ".$idSit."";
//
//                $respostaCP = OCIParse($this->con, $sqlUp);
//
//		if(OCIExecute($respostaCP)){
//			echo "<br>UPDATE BY ID ############################################################## <pre>".$sqlUp."</pre>";
//			$this->updateEvento($idEve);
//		}
//		else{
//			echo "<br>FALHOU UPDATE BY ID ############################################################## <pre>".$sqlUp."</pre>";
//			//exit;
//		}
//	}

//	public function updateSitIniById($cond, $dtHr, $idSit){
//		$sqlUp = "UPDATE diario_de_bordo SET
//				  data_ini = to_timestamp('".$dtHr."', 'YYYY-MM-DD HH24:MI:SS')
//				  WHERE condutor = ".$cond."
//				  AND id_diario = ".$idSit."";
//
//		$respostaCP = OCIParse($this->con, $sqlUp);
//
//		if(OCIExecute($respostaCP)){
//			echo "<br>UPDATE INI BY ID ############################################################## <pre>".$sqlUp."</pre>";
//			$this->updateEvento($idEve);
//		}
//		else{
//			echo "<br>FALHOU UPDATE INI BY ID ############################################################## <pre>".$sqlUp."</pre>";
//			//exit;
//		}
//	}
	
//	public function checkLastSituacao($cond, $dtHr, $idEve){
//		$delSitDif = "DELETE FROM diario_de_bordo
//					  WHERE condutor '".$cond."'
//					  AND to_char(data_ini, 'DD/MM/YYYY') = to_char(data_fim, 'DD/MM/YYYY')";
//
//		$respostaCP = OCIParse($this->con, $sqlUp);
//
//		if(OCIExecute($respostaCP)){
//			echo '<pre>OK DELETE DIARIO DIA INI DIFERENTE DIA FIM-->'.$delSitDif.'</pre>';
//                }else{
//			echo '<pre>N�O DELETE DIARIO DIA INI DIFERENTE DIA FIM-->'.$delSitDif.'</pre>';
//		}
//
//		$sqlSit = "SELECT id_diario
//				   FROM diario_de_bordo
//				   WHERE condutor = '".$cond."'
//				   AND to_timestamp('".$dtHr."', 'YYYY-MM-DD HH24:MI:SS') BETWEEN data_ini AND data_fim
//				   AND to_char(data_ini, 'DD/MM/YYYY') = to_char(data_fim, 'DD/MM/YYYY')
//				   ORDER BY data_ini
//				   LIMIT 1";
//		echo '<pre> SELECT SIT-->'.$sqlSit.'</pre>';
//
//                $respostaCP = OCIParse($this->con, $sqlSit);
//
//		$resSit = OCIExecute($respostaCP);
//		$idSit = $resSit[0]['id_diario'];
//
//		if($idSit){
//			$sqlDel = "DELETE FROM diario_de_bordo
//					   WHERE data_ini::date = '".date('Y-m-d', strtotime($dtHr))."'
//					   AND (data_ini > to_timestamp('".$dtHr."', 'YYYY-MM-DD HH24:MI:SS')
//						   or data_fim > to_timestamp('".$dtHr."', 'YYYY-MM-DD HH24:MI:SS'))
//					   AND id_diario <> ".$idSit."
//					   AND condutor = ".$cond."";
//                        $respostaCP = OCIParse($this->con, $sqlDel);
//
//			if(OCIExecute($respostaCP)){
//				echo '<pre>OK DELETE SIT-->'.$sqlDel.'</pre>';
//                        }else{
//				echo '<pre>N�O DELETE SIT-->'.$sqlDel.'</pre>';
//				//exit;
//			}
//			$sqlUp = "UPDATE diario_de_bordo SET
//					  data_fim = to_timestamp('".$dtHr."', 'YYYY-MM-DD HH24:MI:SS')
//					  WHERE id_diario = '".$idSit."'
//					  AND condutor = ".$cond."";
//			$respostaCP = OCIParse($this->con, $sqlUp);
//
//			if(OCIExecute($respostaCP)){
//				echo '<pre>OK UPDATD SIT-->'.$sqlUp.'</pre>';
//                        }else{
//				echo '<pre>N�O UPDATD SIT-->'.$sqlUp.'</pre>';
//				//exit;
//			}
//		}
//
//	}
	
//	public function updateEvento($idEve){
//		$sqlUp = "UPDATE evento SET
//				  processado = 'T'
//				  WHERE id_evento = ".$idEve."";
//		//echo '<pre> updateEventos-->'.$sqlUp.'</pre>';
//
//                $respostaCP = OCIParse($this->con, $sqlUp);
//
//		OCIExecute($respostaCP);
//	}
	
//	public function deleteDiario($idDiario, $dtHr, $sit){
//		if(!$idDiario){
//			$auxSql = "WHERE data_ini::date = '".date('Y-m-d', strtotime($dtHr))."'
//				       AND (data_ini > to_timestamp('".$dtHr."', 'YYYY-MM-DD HH24:MI:SS')
//					       or data_fim > to_timestamp('".$dtHr."', 'YYYY-MM-DD HH24:MI:SS'))
//				       AND situacao = ".$sit."";
//		}
//		else{
//			$auxSql = "WHERE id_diario = ".$idDiario."";
//		}
//
//		$sqlDel = "DELETE FROM diario_de_bordo
//				   ".$auxSql."";
//		$respostaCP = OCIParse($this->con, $sqlDel);
//
//                if(OCIExecute($respostaCP)){
//			echo '<pre>OK DELETE SIT-->'.$sqlDel.'</pre>';
//                }else{
//			echo '<pre>N�O DELETE SIT-->'.$sqlDel.'</pre>';
//			//exit;
//		}
//	}
	
//	public function getUltInsert($cond, $dt, $sit){
//		$sqlAux = ($sit)?" AND situacao IN (".$sit.")":'';
//		$sqlDt = ($dt)?" AND data_ini::date  = '".$dt."'":'';
//		$sqlCond = "SELECT id_diario, situacao, status, data_ini, data_fim, placa,
//						   (data_fim-data_ini)*60 AS tempo
//					FROM diario_de_bordo
//					WHERE condutor = ".$cond."
//					".$sqlDt."
//					".$sqlAux."
//					ORDER BY data_ini DESC, data_fim DESC
//					LIMIT 1";
//		echo '<pre> FUNCTION BUSCA ULTIMA SITUACAO --> '.$sqlCond.'</pre>';
//
//                $respostaCP = OCIParse($this->con, $sqlCond);
//
//                $resCond = OCIExecute($respostaCP);
//
//		$arrReturn['idDiario'] = $resCond[0]['id_diario'];
//		$arrReturn['situacao'] = $resCond[0]['situacao'];
//		$arrReturn['status']   = $resCond[0]['status'];
//		$arrReturn['dataFim']  = $resCond[0]['data_fim'];
//		$arrReturn['dataIni']  = $resCond[0]['data_ini'];
//		$arrReturn['placa']    = $resCond[0]['placa'];
//		$arrReturn['tempo']    = $resCond[0]['tempo'];
//
//		return $arrReturn;
//	}

//	public function getSituacaoDt($cond, $dt, $dtHr, $sit){
//		$sqlAux = '';
//		$sqlAux.= ($dtHr)?" AND to_timestamp('".$dtHr."', 'YYYY-MM-DD HH24:MI:SS') BETWEEN data_ini AND data_fim":'';
//		$sqlAux.= ($sit)?" AND situacao = ".$sit."":'';
//		$sqlDt = ($dt)?" AND data_ini::date  = '".$dt."'":'';
//
//		$sqlCond = "SELECT id_diario, situacao, status, data_ini, data_fim, placa,
//						   (data_fim-data_ini)*60 AS tempo
//					FROM diario_de_bordo
//					WHERE condutor = ".$cond."
//					".$sqlDt."
//					".$sqlAux."
//					ORDER BY data_ini DESC, data_fim DESC
//					LIMIT 1";
//		echo '<pre> FUNCTION BUSCA ULTIMA SITUACAO --> '.$sqlCond.'</pre>';
//
//                $respostaCP = OCIParse($this->con, $sqlCond);
//
//                $resCond = OCIExecute($respostaCP);
//
//		$arrReturn['idDiario'] = $resCond[0]['id_diario'];
//		$arrReturn['situacao'] = $resCond[0]['situacao'];
//		$arrReturn['status']   = $resCond[0]['status'];
//		$arrReturn['dataFim']  = $resCond[0]['data_fim'];
//		$arrReturn['dataIni']  = $resCond[0]['data_ini'];
//		$arrReturn['placa']    = $resCond[0]['placa'];
//		$arrReturn['tempo']    = $resCond[0]['tempo'];
//
//		return $arrReturn;
//	}
	
//	public function enviaEmailAlerta($tipoAlerta, $desc, $dtHr, $cond, $plc){
//		if($cond){
//			$sqlAux.=" WHERE condutor.id_condutor = ".$cond."";
//
//			$sqlDados ="select  id_alertasemail,
//						condutor,
//						condutor.nome,
//						CASE WHEN direcao = true
//						  	   THEN 'direcao'
//							   ELSE 'false'
//						END AS direcao,
//						CASE WHEN repouso = true
//						  	   THEN 'repouso'
//							   ELSE 'false'
//						END AS repouso,
//						CASE WHEN refeicao = true
//						  	   THEN 'refeicao'
//							   ELSE 'false'
//						END AS refeicao,
//						direcaoDia,
//						repousoDia,
//						refeicaoDia,
//						emailalertas
//						from alertasemail join condutor on(condutor = id_condutor)join empresa on (empresa = id_empresa)
//						".$sqlAux."
//						order by nome";
//			//echo "<pre>".$sqlDados."</pre>";
//
//                        $respostaCP = OCIParse($this->con, $sqlDados);
//
//			$resDados = OCIExecute($respostaCP);
//			//print_r($resDados);
//
//			$emaildestinatario = '';
//			if (($tipoAlerta == $resDados[0]['direcao'])||($tipoAlerta == $resDados[0]['repouso'])||($tipoAlerta == $resDados[0]['refeicao'])){
//			 	$quebra_linha = "\n";
//				$emailsender='contato@rastsat.com.br';
//
//
//				$sqlUpdate = '';
//				if (($tipoAlerta=='direcao')&&($resDados[0]['direcaodia']<$dtHr)){
//					$sqlUpdate = " UPDATE alertasemail
//								   SET direcaodia = '".$dtHr."'
//								   WHERE condutor = ".$cond;
//				}elseif (($tipoAlerta=='repouso')&&($resDados[0]['repousodia']<$dtHr)){
//					$sqlUpdate = " UPDATE alertasemail
//								   SET repousodia = '".$dtHr."'
//								   WHERE condutor = ".$cond;
//				}elseif (($tipoAlerta=='refeicao')&&($resDados[0]['refeicaodia']<$dtHr)){
//					$sqlUpdate = " UPDATE alertasemail
//								   SET refeicaodia = '".$dtHr."'
//								   WHERE condutor = ".$cond;
//				}
//
//				//s� envia email do alerta se o mesmo ainda n�o tiver sido enviado\\
//				if($sqlUpdate!=''){
//
//					$nomeremetente     = 'RastSat';
//					$emailremetente    = 'contato@rastsat.com.br';
//					$comcopia          = '';
//					$comcopiaoculta    = '';
//					$emaildestinatario = $resDados[0]['emailalertas'];
//
//					$assunto           = 'Alerta do Sistema';
//
//
//					$mensagemHTML = '<p><b><i> Prezado usu&#225;rio,</i></b></p>
//								    <p><b><i>Controle de Alertas do Sistema informa: </i></b></p>
//									Condutor: '.$resDados[0]['nome'].' Placa: '.$plc.' - '.$desc.
//									'<p><b>RastSat Gest&#227;o de Frotas</b><br>Fone: (54)3331-1309<p/>';
//
//
//					/* Montando o cabe�alho da mensagem */
//					$headers = "MIME-Version: 1.1".$quebra_linha;
//					$headers .= "Content-type: text/html; ".$quebra_linha;
//					// Perceba que a linha acima cont�m "text/html", sem essa linha, a mensagem n�o chegar� formatada.
//					$headers .= "From: ".$emailsender.$quebra_linha;
//					$headers .= "Return-Path: " . $emailsender . $quebra_linha;
//					// Esses dois "if's" abaixo s�o porque o Postfix obriga que se um cabe�alho for especificado, dever� haver um valor.
//					// Se n�o houver um valor, o item n�o dever� ser especificado.
//					if(strlen($comcopia) > 0) $headers .= "Cc: ".$comcopia.$quebra_linha;
//					if(strlen($comcopiaoculta) > 0) $headers .= "Bcc: ".$comcopiaoculta.$quebra_linha;
//					$headers .= "Reply-To: ".$emailremetente.$quebra_linha;
//					// Note que o e-mail do remetente ser� usado no campo Reply-To (Responder Para)
//
//					/* Enviando a mensagem */
//					if(($emaildestinatario != '') && mail($emaildestinatario, $assunto, $mensagemHTML, $headers, "-r". $emailsender)){
//						echo '!!!!!!!!!!!!!!!!!!!!!foi email!!!!!!!!!!!!!!!!!!!!!!!!!!';
//						//atualiza dia do alerta\\
//						$this->con->execSql($sqlUpdate);
//						echo "<br>UPDATE ALERTASEMAIL ############################################################## <pre>".$sqlUpdate."</pre>";
//					}else
//						echo '!!!!!!!!!!!!!!!!!!!!!!!FAIO EMAIL!!!!!!!!!!!!!!!!!!!!!!!';
//				}
//			}
//
//		}
//	}

}