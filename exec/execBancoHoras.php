<?php
    session_start();
    
    require_once('../includes/Controles.class.php');
    require_once('../includes/OracleCieloJornada.class.php');
    require_once('../includes/execute.class.php');
        
    $OraCielo           = new OracleCielo();
    $conexaoOra         = $OraCielo->getCon();
    $CtrlAcesso         = new Controles($_SERVER['REMOTE_ADDR'], $conexao);
    $usuarioName        = trim($CtrlAcesso->getUserLogin($_SESSION));
    $usuarioId          = trim($CtrlAcesso->getUserID($_SESSION));
    $usuarioIdEmp       = trim($CtrlAcesso->getUserEmpresa($_SESSION));
    $execClass          = new ExecClass($conexaoOra);
    $dataCad            = $execClass->getHoraSistema();

    $acao               = filter_input(INPUT_POST, 'acao');
    $idBancoHoras       = filter_input(INPUT_POST, 'idBancoHoras');
    $idPeriodoBH        = filter_input(INPUT_POST, 'idPeriodoBH');
    $dataIniBH          = filter_input(INPUT_POST, 'dataIniBH');
    $horasDiasUteisBH   = filter_input(INPUT_POST, 'horasDiasUteisBH');
    $horasSabadosBH     = filter_input(INPUT_POST, 'horasSabadosBH');
    $idCondutorBH       = filter_input(INPUT_POST, 'idCondutorBH');
    $idEmpresaBH        = filter_input(INPUT_POST, 'idEmpresaBH');
    $idTipoFechamento   = filter_input(INPUT_POST, 'idTipoFechamento');
    $idBancoFechamento  = filter_input(INPUT_POST, 'idBancoFechamento');
    $obsFechamento      = filter_input(INPUT_POST, 'obsFechamento');
    $checkTodos         = filter_input(INPUT_POST, 'checkTodos');
    $arrayCondutorHora  = json_decode(filter_input(INPUT_POST, 'arrayCondutorHora'));

    $vencimentoBH       = $_REQUEST['vencimentoBH'];

    $tempoUteisBH       = explode(':', $horasDiasUteisBH);
    $tempoSabBH         = explode(':', $horasSabadosBH);
    $horasUteisBH       = $tempoUteisBH[0];
    $minUteisBH         = $tempoUteisBH[1];
    $horasSabBH         = $tempoSabBH[0];
    $minSabBH           = $tempoSabBH[1];
    $totalMinUteisBH    = ($horasUteisBH * 60) + $minUteisBH;
    $totalMinSabBH      = ($horasSabBH * 60) + $minSabBH;

    $idBancoHorasAux    = 0;
    $totalInicial       = 0;

    switch ($acao){
        case 'delete':

            #===========================================================================================================
            #Desativando um banco de horas (Desativa o banco de horas e os dados da tabela banco_condutor)
            #===========================================================================================================

            $sqlInativarBC = "UPDATE banco_condutor SET ativo = 'F' WHERE id_banco_horas = '".$idBancoHoras."'";
            $respostaInativarBC = oci_parse($conexaoOra, $sqlInativarBC);
            if(!oci_execute($respostaInativarBC)){
                #echo ' Erro ao desativar BANCO_CONDUTOR: '.$sqlInativarBC;
            }else{
                $sqlInativarBancoHora   = "UPDATE banco_horas SET ativo = 'F' WHERE id_banco_horas = '".$idBancoHoras."'";
                $respostaInativarBH     = oci_parse($conexaoOra, $sqlInativarBancoHora);
                if(!oci_execute($respostaInativarBH)){
                    $status = 'ERRO';
                    $msg = 'Favor informar o departamento de TI';
                    $retorno['erro'] = oci_error($respostaDeleteBH);
                    #echo ' Delete BANCO_HORAS: ERRO ->'.$sqlDeletarBancoHora; exit();
                }else{
                    $status = 'OK';
                    $msg = 'Banco de Horas desativado! <br><br> Você ainda tem a possibilidade de consultar mais tarde os dados gerados neste banco clicando em FECHAMENTO';
                }
                oci_free_statement($respostaInativarBH);
            }
            oci_free_statement($respostaInativarBC);
            break;
        case 'create';

            #===========================================================================================================
            #Criação de um novo banco de horas (Insert na table banco_horas + insert table banco_condutor e chamada da
            #procedure de criação dos dias do banco de horas
            #===========================================================================================================

            $sqlSalvarBancoHora = "
                INSERT INTO banco_horas(
                    id_banco_horas,
                    periodo,
                    vencimento,
                    min_semana,
                    min_sab,
                    user_id_cad,
                    user_name_cad,
                    data_cad,
                    data_ini,
                    empresa
                )
                VALUES (
                    SEQ_BANCO_HORAS.nextval,
                    ".$idPeriodoBH.",
                    to_number('".$vencimentoBH."'),
                    ".$totalMinUteisBH.",
                    ".$totalMinSabBH.",
                    ".$usuarioId.",
                    '".$usuarioName."',
                    to_timestamp('".$dataCad['DATA_SISTEMA']."', 'DD/MM/YY HH24:MI:SS'),
                    to_timestamp('".$dataIniBH."', 'DD/MM/YY'),
                    '".$idEmpresaBH."'
                )
            ";

            $respostaCreateBH = oci_parse($conexaoOra, $sqlSalvarBancoHora);

            if(!oci_execute($respostaCreateBH)){
                $status = 'ERRO';
                $msg = 'Favor informar o departamento de TI';
                $retorno['erro'] = oci_error($respostaCreateBH);
                #echo ' Insert BANCO_HORAS: ERRO ->'.$sqlSalvarBancoHora; exit();
            } else {

                #Buscando o ultimo registro da sequence para futura inclusao na table banco_condutor
                $sqlIdBH = "SELECT last_number FROM user_sequences  WHERE sequence_name like '%SEQ_BANCO_HORAS%'";
                $respostaIdBH = oci_parse($conexaoOra, $sqlIdBH);

                if(!oci_execute($respostaIdBH)){
                    #echo ' Erro na consulta da sequence BANCO_HORA'.$sqlIdBH;
                    $msg = 'Favor informar o departamento de TI';
                    $retorno['erro'] = oci_error($respostaIdBH);
                }else{
                    $row = oci_fetch_assoc($respostaIdBH);
                    $idBancoHorasAux = $row['LAST_NUMBER'] - 1;
                }
                oci_free_statement($respostaIdBH);
            }
            oci_free_statement($respostaCreateBH);

            #===========================================================================================================
            #Insert BANCO_CONDUTOR

            $sqlBuscaTotalSemanas = " SELECT total_semanas FROM BANCO_HORAS_PERIODO WHERE ID_PERIODO = ".$idPeriodoBH;
            $respostaSemanas = oci_parse($conexaoOra, $sqlBuscaTotalSemanas);

            if(!oci_execute($respostaSemanas)){
                #echo ' Erro ao buscar total de semanas do periodo.'.$sqlBuscaTotalSemanas;
                #exit();
            }else{
                $totalSemanas = oci_fetch_assoc($respostaSemanas);
            }

            oci_free_statement($respostaSemanas);

            #Percorrendo os condutores da empresa
            $sqlConsultaCondutores = "
                SELECT id_condutor, nome
                FROM monitoramento.condutor
                WHERE empresa = '".$usuarioIdEmp."' AND ativo = 'T' 
                ORDER BY nome
            ";

            $respostaCondutores = oci_parse($conexaoOra, $sqlConsultaCondutores);

            if(!oci_execute($respostaCondutores)){
                $status = 'ERRO';
                $msg = 'Favor informar o departamento de TI';
                $retorno['erro'] = oci_error($respostaCondutores);
                #echo ' select CONDUTORES: ERRO ->'.$sqlConsultaCondutores; exit();
            } else {
                #Laço percorrendo o retorno da consulta dos condutores da empresa
                while (($rowTodosCondutores = oci_fetch_assoc($respostaCondutores)) != false) {
                    #laço percorrendo o array de exceções
                    if($arrayCondutorHora) {
                        foreach ($arrayCondutorHora as $rowCondutorExc) {
                            if ($rowCondutorExc[0] == $rowTodosCondutores['ID_CONDUTOR']) {

                                $horasInicial = $rowCondutorExc[1];
                                $saldoInicial = explode(':', $horasInicial);
                                $horasInicial = $saldoInicial[0];
                                $minInicial = $saldoInicial[1];
                                $totalInicial = ($horasInicial * 60) + $minInicial;

                                #'C'  = Creditar // 'D'  = Debitar
                                if ($rowCondutorExc[2] == 'D') {
                                    $totalInicial = $totalInicial - ($totalInicial * 2);
                                }
                                break;
                            } else {
                                $totalInicial = 0;
                            }
                        }
                    }

                    $sqlInsertBancoCondutor = "
                        INSERT INTO banco_condutor(
                            id_banco_condutor,
                            condutor,
                            saldo_ini,
                            acumulado,
                            saldo_atual,
                            data_ini,
                            data_fim,
                            id_banco_horas
                        )
                        VALUES (
                            SEQ_BANCO_CONDUTOR.nextval,
                            ".$rowTodosCondutores['ID_CONDUTOR'].",
                            ".$totalInicial.",
                            ".$totalInicial.",
                            ".$totalInicial.",
                            to_timestamp('".$dataIniBH."', 'DD/MM/YY'),
                            --add_months(to_timestamp('".$dataIniBH."', 'DD/MM/YY'), ".$periodoBH.")-1,
                            to_timestamp('".$dataIniBH."', 'DD/MM/YY') + (".$totalSemanas['TOTAL_SEMANAS']." * 7) -1,
                            ".$idBancoHorasAux."
                        )
                    ";
                    #echo $sqlInsertBancoCondutor; exit();

                    $respostaInsertBC = oci_parse($conexaoOra, $sqlInsertBancoCondutor);

                    if(!oci_execute($respostaInsertBC)){
                        $status = 'ERRO';
                        $msg = 'Favor informar o departamento de TI';
                        $retorno['erro'] = oci_error($respostaInsertBC);
                        #echo ' insert BANCO_CONDUTOR: ERRO ->'.$sqlInsertBancoCondutor; exit();
                    }else{
                        $status = 'OK';
                        $msg = 'Banco de horas criado!';
                    }
                    oci_free_statement($respostaInsertBC);
                }

                #Calculando o tempo previsto de jornada para os condutores baseado no tempo de trab da semana e dos sabados
                $sqlTotalBancoHora ="
                    BEGIN
                        pkg_banco_horas.setTotalBancoHoras('".$idBancoHorasAux."', to_date('".$dataIniBH."', 'DD/MM/YY'), to_timestamp('".$dataIniBH."', 'DD/MM/YY') + (".$totalSemanas['TOTAL_SEMANAS']." * 7) -1);
                        COMMIT;
                    END;";

                    /*BEGIN
                      pkg_banco_horas.setTotalBancoHoras('281', to_date('01/03/2017', 'DD/MM/YY'), to_date('31/03/2017', 'DD/MM/YY'));
                      COMMIT;
                    END;
                    */

                $respostaTotalBancoHora = oci_parse($conexaoOra, $sqlTotalBancoHora);


                if(!oci_execute($respostaTotalBancoHora)){
                    $status = 'ERRO';
                    $msg = 'Favor informar o departamento de TI';
                    $retorno['erro'] = oci_error($respostaTotalBancoHora);
                    #echo ' PROCEDURE: ERRO ->'.$retorno['erro'].$sqlTotalBancoHora; exit();
                }else{
                    #Gerando os dados dos dias dos condutores desde a data de início do banco de horas até o dia atual
                    $sqlGeraBancoCondutor ="
                    DECLARE
                      start_date  NUMBER;
                      end_date    NUMBER;
                    BEGIN
                      start_date  := to_number(to_char(to_date('".$dataIniBH."', 'DD/MM/YYYY'), 'j'));
                      end_date    := to_number(to_char(SYSDATE, 'j'));
                      FOR cur_r IN start_date..end_date LOOP
                        BEGIN
                          pkg_banco_horas.geraBancoCondutorDia(to_date(cur_r, 'j'), '".$idEmpresaBH."'); 
                          COMMIT;
                        END;
                      END LOOP; 
                    END;";

                    $respostaGeraBanco = oci_parse($conexaoOra, $sqlGeraBancoCondutor);

                    if(!oci_execute($respostaGeraBanco)){
                        $status = 'ERRO';
                        $msg = 'Favor informar o departamento de TI';
                        $retorno['erro'] = oci_error($respostaGeraBanco);
                        echo ' PROCEDURE: ERRO ->'.$retorno['erro'].$sqlGeraBancoCondutor; exit();
                    }else{
                        #echo ' PROCEDURE: OK ->'.$sqlTotalBancoHora;
                    }
                    oci_free_statement($respostaGeraBanco);
                }
                oci_free_statement($respostaTotalBancoHora);

            }
            oci_free_statement($respostaCondutores);
            break;
        case 'updateFechamento':

            #===========================================================================================================
            #Atualizando os fechamentos
            #===========================================================================================================

            $sqlUpdateFechamento = '';

            if(isset($idTipoFechamento) AND isset($obsFechamento)){
                //Teste para verificação se o usuario quer fechar todos os bancos de horas em aberto
                $sqlAuxFechamento = "";
                if($checkTodos == 'true'){
                    $sqlAuxFechamento = "WHERE tipo_fechamento IS NULL";
                }else{
                    $sqlAuxFechamento = "WHERE id_banco_fechamento = '".$idBancoFechamento."'";
                }

                $sqlUpdateFechamento = "
                    UPDATE banco_fechamento
                    SET tipo_fechamento = '".$idTipoFechamento."',
                         obs_fechamento = '".$obsFechamento."',
                         user_fechamento = '".$usuarioId."',
                         dt_fechamento = SYSDATE
                    ".$sqlAuxFechamento."
                ";
                $msg = 'Banco de Horas fechado!';
            }else{
                $sqlUpdateFechamento = "
                    UPDATE banco_fechamento
                    SET tipo_fechamento = NULL,
                         obs_fechamento = NULL,
                         user_fechamento = NULL,
                         dt_fechamento = NULL
                    WHERE id_banco_fechamento = '".$idBancoFechamento."'
                ";
                $msg = 'Fechamento excluído!';
            }

            $respostaUpdateFechamento = oci_parse($conexaoOra, $sqlUpdateFechamento);

            if(!oci_execute($respostaUpdateFechamento)){
                $status = 'ERRO';
                $msg = 'Favor informar o departamento de TI';
                $retorno['erro'] = oci_error($respostaUpdateFechamento);
                #echo ' Erro no update da tabela BANCO_FECHAMENTO ->'.$sqlUpdateFechamento; exit();
            } else {
                #echo $sqlUpdateFechamento; exit();
                $status = 'OK';
            }
            oci_free_statement($respostaUpdateFechamento);
            break;
        case 'regerarFechado':

            #===========================================================================================================
            #Regera o período para os condutores em que o banco de horas já foi fechado
            #===========================================================================================================

            $minSemBH = $_REQUEST['minSemBH'];
            $minSabBH = $_REQUEST['minSabBH'];
            $minTotalBC = $_REQUEST['minTotalBC'];
            $dataIni = $_REQUEST['dataIni'];
            $dataFim = $_REQUEST['dataFim'];
            $idBancoHora = $_REQUEST['idBancoHora'];
            $idBancoCondutor = $_REQUEST['idBancoCondutor'];

            $sqlRegerarFechado ="
                BEGIN
                    pkg_banco_horas.regerarFechado(".$minSemBH.", ".$minSabBH.", ".$minTotalBC.", to_date('".$dataIni."', 'DD/MM/YYYY'), to_date('".$dataFim."', 'DD/MM/YYYY'), ".$idBancoHora.");
                    COMMIT;
                END;";

            $respostaRegerarFechado = oci_parse($conexaoOra, $sqlRegerarFechado);


            if(!oci_execute($respostaRegerarFechado)){
                $status = 'ERRO';
                $msg = 'Favor informar o departamento de TI';
                $retorno['erro'] = oci_error($respostaRegerarFechado);
            }else{
                $status = 'OK';
                $msg = 'Dados regerados!';
            }
            oci_free_statement($respostaRegerarFechado);

            break;
        case 'regerar':

            #===========================================================================================================
            #Regerar os dados do banco de horas que está em execução no momento
            #===========================================================================================================

            $minSemBH = $_REQUEST['minSemBH'];
            $minSabBH = $_REQUEST['minSabBH'];
            $minTotalBC = $_REQUEST['minTotalBC'];
            $dataIni = $_REQUEST['dataIni'];
            $dataFim = $_REQUEST['dataFim'];
            $idBancoHora = $_REQUEST['idBancoHora'];
            $idBancoCondutor = $_REQUEST['idBancoCondutor'];

            $sqlRegerar ="
                BEGIN
                    pkg_banco_horas.regerar(".$minSemBH.", ".$minSabBH.", ".$minTotalBC.", to_date('".$dataIni."', 'DD/MM/YYYY'), to_date('".$dataFim."', 'DD/MM/YYYY'), ".$idBancoHora.");
                    COMMIT;
                END;";

            $respostaRegerar = oci_parse($conexaoOra, $sqlRegerar);


            if(!oci_execute($respostaRegerar)){
                $status = 'ERRO';
                $msg = 'Favor informar o departamento de TI';
                $retorno['erro'] = oci_error($respostaRegerar);
            }else{
                #echo $sqlRegerar; exit();
                $status = 'OK';
                $msg = 'Dados regerados!';
            }
            oci_free_statement($respostaRegerar);

            break;
        default:
            $status = 'OK';
            $msg = 'Ops, alguma coisa aconteceu... Tente novamente.';
            break;
    }

    oci_close($conexaoOra);

    $retorno['status']  = $status;
    $retorno['msg'] = $msg;
    $json = $retorno;

    header('Content-Type: application/x-json');
    echo json_encode($json);