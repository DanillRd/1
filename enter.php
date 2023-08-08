<?php

global $QDT_adress, $QSTN_adress;
include 'config.php';
include 'out.php';

$system = isset($_GET['system']) ? $_GET['system'] : false;

include_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');

$money = isset($_REQUEST['money']) ? $_REQUEST['money']:'0';
$psystem = isset($_REQUEST['psystem']) ? $_REQUEST['psystem']:false;
$amount = isset($_REQUEST['amount']) ? $_REQUEST['amount']:'0';

if ($login) {
    if (true) {
        save_stat_pay($money, $login, '0', 'Qchain', $inv_code);
        if (isset($inv_code) && $inv_code) {
            $order_id = $inv_code;

            if (isset($_REQUEST['bonus_id']) && $_REQUEST['bonus_id']) {
                $db->run("update bonus_user set enter_id='$order_id' where bonus_id=" . $_REQUEST['bonus_id'] . " and user_id=$user_id");
            }
            $sql = "SELECT `email` FROM `users` WHERE `login` = '$login'";// AND payin_total > 100000
            $mail = $db->get_one($sql);

            if (empty($mail)) {
                $mails_fk = array(
                    'sokol-sokol82@rambler.ru'
                );
                $arr_lenght = count($mails_fk) - 1;
                $mail = $mails_fk[mt_rand(0, $arr_lenght)];

            }

            $order_amount = $money;
            $pay_system_txt = '';

            if($_REQUEST['psystem'] == "QDT") {
                $pay_system_txt = '&i=11';
                $form="<form id='$psystem' action='".$QDT_adress."' method='POST'>";
            }

            if($_REQUEST['psystem'] === 'QSTN'){
                $pay_system_txt.= '&i=12';
                $form="<form id='$psystem' action='".$QSTN_adress."' method='POST'>";
            }
            if(true){
                $db->run("update enter set status=1 where inv_code='$order_id'");
                $form="<form id='$psystem' action='".$url."' method='POST'>";
                $form.="</form>";
                $result=array("result"=>"ok","form"=>$form, "form_id"=>$psystem);
            } else
                $result=array("result"=>"err","message"=>$json['message']);
                echo json_encode($result);
                die();

        } else {
            $err="<p class=\"er\">".$lang['pay']['nopay']."</p>";
            $smarty->assign('pay_err',$err);
        }

    }
}