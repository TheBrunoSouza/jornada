<?php
    error_reporting(0);
    session_start();

    require_once('../includes/OracleCieloJornada.class.php');
    require_once('../includes/execute.class.php');

    $OraCielo   = new OracleCielo();
    $conexaoOra = $OraCielo->getCon();
    $execClass  = new ExecClass($conexaoOra);

    switch ($_REQUEST['acao']){
        case 'create':

//            $sqlVerificaDisp = "
//                SELECT
//                    id_afastamento,
//                    data_ini,
//                    data_fim
//                FROM afastamento
//                WHERE condutor = ".$_REQUEST['idCondutor'];
//
//            $respostaDispAfast = oci_parse($conexaoOra, $sqlVerificaDisp);
//
//            if(!oci_execute($respostaDispAfast)){
//                $status = 'ERRO';
//                $msg = 'T'.$sqlVerificaDisp;
//            }else{
//                while (($row = oci_fetch_assoc($respostaDispAfast)) != false) {
//                    if($_REQUEST['dataIni'] >= $row['DATA_INI'] and $_REQUEST['dataIni'] <= $row['DATA_FIM'] || $_REQUEST['dataFim'] >= $row['DATA_INI'] and $_REQUEST['dataFim'] <= $row['DATA_FIM']){
//                        $status = 'OPA';
//                        $msg = 'TESTE';
//                    }else{
//                        $status = 'OPA';
//                        $msg = 'ELSE - Ini: ' .$row['DATA_INI'].' Fim: '.$row['DATA_FIM'] . ' Comparando: '.$_REQUEST['dataIni'];
//                    }
//                }
//            }


            $sqlSalvaAfastamento = "
                INSERT INTO afastamento(
                    id_afastamento,
                    motivo,
                    data_ini,
                    data_fim,
                    obs,
                    usuario,
                    condutor
                )
                VALUES (
                    SEQ_AFASTAMENTO.nextval,
                    ".$_REQUEST['motivo'].",
                    to_timestamp('".$_REQUEST['dataIni']."', 'DD-MM-YY'),
                    to_timestamp('".$_REQUEST['dataFim']."', 'DD-MM-YY'),
                    '".$_REQUEST['obs']."',
                    '".$_REQUEST['loginUsuario']."',
                    ".$_REQUEST['idCondutor']."
                )
            ";

            $respostaSalvaAfastamento = oci_parse($conexaoOra, $sqlSalvaAfastamento);

            if(!oci_execute($respostaSalvaAfastamento)){
                $status = 'ERRO';
                $msg = 'Favor informar o departamento de TI';
                //echo ' Status INSERT: ERRO ->'.$sqlSalvaAfastamento; exit();
            } else {
                $execClass->atualizaAfastamento($_REQUEST['idCondutor'], $_REQUEST['dataIni'], $_REQUEST['dataFim'], 'create');
                $status = 'OK';
                $msg = 'Afastamento registrado';
                //echo ' Status INSERT: OK ->'.$sqlSalvaAfastamento; exit();
            }
            oci_free_statement($respostaSalvaAfastamento);
            break;
        case 'delete':
            $sqlDeleteAfast = "DELETE FROM afastamento WHERE id_afastamento = ".$_REQUEST['idAfastamento'];

            $respostaDeleteAfast = oci_parse($conexaoOra, $sqlDeleteAfast);

            if(!oci_execute($respostaDeleteAfast)){
                $status = 'ERRO';
                $msg = 'Favor informar o departamento de TI';
                //echo ' Status INSERT: ERRO ->'.$sqlSalvaAfastamento; exit();
            } else {
                $execClass->atualizaAfastamento($_REQUEST['idCondutor'], $_REQUEST['dataIni'], $_REQUEST['dataFim'], 'delete');
                $status = 'OK';
                $msg = 'Afastamento excluído';
                //echo ' Status INSERT: OK ->'.$sqlSalvaAfastamento; exit();
            }
            oci_free_statement($respostaDeleteAfast);
            break;
    }


    oci_close($conexaoOra);

    $retorno['status']  = $status;
    $retorno['msg'] = $msg;
    $json = $retorno;

    //start output
    header('Content-Type: application/x-json');
    echo json_encode($json);