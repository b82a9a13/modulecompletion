<?php
require_once(__DIR__.'/../../../../config.php');
use local_modulecompletion\lib;
require_login();
$lib = new lib;
$returnText = new stdClass();

if(isset($_POST['id'])){
    $id = $_POST['id'];
    if(!preg_match("/^[0-9]*$/", $id) || empty($id)){
        $returnText->error = 'Course id provided is not a number.';
    } else {
        //Retreive relevant data
        $array = $lib->get_enrolled_learners($id);
        if($array != 'invalid'){
            if($array[0] != 'invalid'){
                $html = '
                    <table style="width:100%;">
                        <thead>
                            <tr>
                                <th class="tp-title"><h2><b>Full Name</b></h2></th>
                                <th><h2><b>Tracking</b></h2></th>
                            </tr>
                        </thead>
                        <tbody>
                ';
                $type = 'a';
                if($_SESSION['hl_menu_type']){
                    if($_SESSION['hl_menu_type'] == 'all'){
                        $type = 'a';
                    } elseif($_SESSION['hl_menu_type'] == 'one'){
                        $type = 'c';
                    }
                }
                foreach($array as $arr){
                    $html .= "
                        <tr>
                            <td>
                                <h4>$arr[0]</h4>
                            </td>
                            <td>";
                    if($arr[3]){
                        if($arr[4]){
                            $html .="
                                        <div class='otj-outer d-flex' onclick='window.location.href=`./teacher_modulecompletion.php?cid=$arr[1]&uid=$arr[2]&e=$type`'>
                                            <img src='./classes/img/Completion.png' class='tp-img'>
                                            <canvas width='120px' height='120px' prval='".$arr[5][0]."' peval='".$arr[5][1]."' class='otjh-canvas'></canvas>
                                            <div>
                                                <h4>Progress: ".$arr[5][0]."%</h4>
                                                <h4>Expected: ".$arr[5][1]."%</h4>
                                            </div>
                                        </div>
                                    </td>
                                </tr>        
                            "; 
                        } else {
                            $html .="
                                        <div class='otj-outer text-center' onclick='window.location.href=`./../trainingplan/sign.php?cid=$arr[1]&uid=$arr[2]&e=$type`'>
                                            <img src='./classes/img/Signature.png' class='tp-img'>
                                        </div>
                                    </td>
                                </tr>        
                            "; 
                        }
                    } else {
                        $html .="
                                    <div class='otj-outer text-center' onclick='window.location.href=`./../trainingplan/setup.php?cid=$arr[1]&uid=$arr[2]&e=$type`'>
                                        <img src='./classes/img/Setup.png' class='tp-img'>
                                    </div>
                                </td>
                            </tr>        
                        ";
                    }
                }
                $html .= '</tbody></table>';
                $returnText->return = str_replace("  ","",$html);
            } else {
                $returnText->error = 'No learners available';
            }
        } else {
            $returnText->error = 'Invalid course id provided';
        }        
    }
} else {
    $returnText->error = 'No course id provided.';
}
echo(json_encode($returnText));