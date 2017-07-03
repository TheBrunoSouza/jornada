<?
    session_start();

    require_once('../includes/OracleCieloJornada.class.php');
    require_once('../includes/Controles.class.php');

    $oraCielo       = new OracleCielo();
    $conexaoOra     = $oraCielo->getCon();
    $CtrlAcesso     = new Controles($_SERVER['REMOTE_ADDR'], $conexaoOra);
    $idUsuario      = $CtrlAcesso->getUserID($_SESSION);
    $acao           = $_REQUEST['acao'];
    $json           = array();
    $arrayFeriados  = array();

    switch ($acao){
        case 'filtrar':
            $sqlFeriadoEmpresa = "
                    SELECT 
                        id_feriado,
                        descricao,
                        data,
                        empresa
                    FROM feriado
                    WHERE empresa = ".$_REQUEST['idEmpresa'];
            #print_r($sqlFeriadoEmpresa);exit();

            $respostaFeriados = oci_parse($conexaoOra, $sqlFeriadoEmpresa);

            if(!oci_execute($respostaFeriados)){
                echo ' Erro no select de BANCO_HORAS.';
                echo $sqlFeriadoEmpresa;
                exit();
            }else{
                #echo $sqlBancoHoras;
                while (($row = oci_fetch_assoc($respostaFeriados)) != false) {
                    $arrayFeriados[] = array(
                        "idFeriado" => $row['ID_FERIADO'],
                        "descricao" => $row['DESCRICAO'],
                        "data"      => $row['DATA'],
                        "idEmpresa" => $row['EMPRESA']
                    );
                }
                $array['status']    = 'OK';
            }

            $array['feriados']  = $arrayFeriados;
            break;
        case 'cadastrar':
            $sqlCadastrarFeriado = "
                    INSERT INTO feriado(
                        id_feriado,
                        descricao,
                        empresa,
                        user_cad,
                        data)
                    VALUES (
                        SEQ_FERIADO.nextval, 
                        '".$_REQUEST['descFeriado']."',
                        '".$_REQUEST['idEmpresa']."',
                        '".$idUsuario."',
                        '".$_REQUEST['dataFeriado']."')";
            #print_r($sqlFeriadoEmpresa);exit();

            $respostaCadastrarFeriado = oci_parse($conexaoOra, $sqlCadastrarFeriado);

            if(!oci_execute($respostaCadastrarFeriado)){
                echo ' Erro ao inserir feriado.';
                echo $sqlCadastrarFeriado;
                exit();
            }else{
                $array['status']  = 'OK';
                $array['msg']  = 'Feriado cadastrado! <br><br> Você deve regerar os dados do período afetado.';
                #echo $respostaExcluirFeriado;
            }

            break;

        case 'excluir':

            $sqlExcluiFeriado = "
                    DELETE FROM feriado WHERE id_feriado = ".$_REQUEST['idFeriado'];
            #print_r($sqlExcluiFeriado);exit();

            $respostaExcluirFeriado = oci_parse($conexaoOra, $sqlExcluiFeriado);

            if(!oci_execute($respostaExcluirFeriado)){
                echo ' Erro ao excluir feriado.';
                echo $sqlExcluiFeriado;
                exit();
            }else{
                $array['status']    = 'OK';
                #echo $respostaExcluirFeriado;
            }

//            unset($sqlFeriadoEmpresa, $array, $status);
            break;

//        case 'alterar':
//            $sqlFeriadoEmpresa = "
//                    UPDATE
//                            feriado
//                    SET
//                            descricao = '".$_REQUEST['descricaoFeriado']."',
//                            data = '".$_REQUEST['dataFeriado']."'
//                    WHERE   empresa = ".$_REQUEST['idEmpresa']."
//                            AND id_feriado = ".$_REQUEST['idFeriado'].";";
//    //        print_r($sqlFeriadoEmpresa);exit();
//
//            $status             = $conexao->getResult($sqlFeriadoEmpresa);
//            $array['status']    = $status;
//            $json               = $array;
//
//            unset($sqlFeriadoEmpresa, $array, $status);
//            break;
    }

    $json = $array;
    header('Content-Type: application/x-json');
    echo json_encode($json);
