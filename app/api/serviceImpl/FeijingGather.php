<?php


namespace app\api\serviceImpl;


use QL\QueryList;
use support\Db;

class FeijingGather implements \app\api\service\GatherService
{

    public function gatherFootGame()
    {

        // TODO: Implement gatherFootGame() method.
        $html = file_get_contents('http://101.200.220.18/feijing.php?type=1');
        $mids = file_get_contents('http://101.200.220.18/feijing.php?type=2');
        $mids = json_decode($mids,1);
        $mids = $mids["list"][0]["jczq"];
        $data = json_decode($html,1);
        $data= $data["list"];
        foreach ($data as $k=>$v){
            $p_goal = $v["rqspf"]["goal"];
            foreach ($mids as $mk=>$vk){
                if($vk["id"] == $v["id"] ){
                    $mid = $vk["matchId"];
                    break;
                }
            }
            $d_time = str_replace("\\","-",$v["matchTime"]);
            $weekarray = ["周一"=>1,"周二"=>2,"周三"=>3,"周四"=>4,"周五"=>5,"周六"=>6,"周日"=>7];

            $num_zhou = mb_substr($v["id"],0,2);
            $num_num = mb_substr($v["id"],2);
            $num = $weekarray[$num_zhou].$num_num;

            $l_cn = $v["league"];
            $h_cn = $v["home"];
            $a_cn = $v["away"];
            $spf_status = $v["spf"]? $v["spf"]["stop"]?0:1:0;
            $rqspf_status = $v["rqspf"]?$v["rqspf"]["stop"]?0:1:0;
            $bf_status = $v["bf"]?$v["bf"]["stop"]?0:1:0;
            $jq_status = $v["jq"]?$v["jq"]["stop"]?0:1:0;
            $bqc_status = $v["bqc"]?$v["bqc"]["stop"]?0:1:0;
            $p_status = $spf_status.",".$rqspf_status.",".$bf_status.",".$jq_status.",".$bqc_status;
            $spf_single = $v["singleSpf"]?1:0;
            $rqspf_single  = $v["singleRqspf"]?1:0;
            $bf_single  = $v["singleBf"]?1:0;
            $jq_single  = $v["singleJq"]?1:0;
            $bqc_single  = $v["singleBqc"]?1:0;
            $p_single = $spf_single.",".$rqspf_single.",".$bf_single.",".$jq_single.",".$bqc_single;


            $v["had_odds"] =$v["spf"]?$v["spf"]["spf3"].",".$v["spf"]["spf1"].",".$v["spf"]["spf0"]:'';


            $v["hhad_odds"] =$v["rqspf"]?$v["rqspf"]["rq3"].",".$v["rqspf"]["rq1"].",".$v["rqspf"]["rq0"]:'';


            $bqc =  $v["bqc"];
            unset($bqc["stop"]);
            $v["hafu_odds"] = implode(",",$bqc);

            $v["crs_win"] =$v["bf"]? $v["bf"]["sw10"].",".$v["bf"]["sw20"].",".$v["bf"]["sw21"].",".$v["bf"]["sw30"].",".$v["bf"]["sw31"].",".$v["bf"]["sw32"].",".$v["bf"]["sw40"].",".$v["bf"]["sw41"].",".$v["bf"]["sw42"].",".$v["bf"]["sw50"].",".$v["bf"]["sw51"].",".$v["bf"]["sw52"].",".$v["bf"]["sw5"]:'';

            $v["crs_draw"] =$v["bf"]?$v["bf"]["sd00"].",".$v["bf"]["sd11"].",".$v["bf"]["sd22"].",".$v["bf"]["sd33"].",".$v["bf"]["sd4"]:'';

            $v["crs_lose"] =$v["bf"]?$v["bf"]["sl01"].",".$v["bf"]["sl02"].",".$v["bf"]["sl12"].",".$v["bf"]["sl03"].",".$v["bf"]["sl13"].",".$v["bf"]["sl23"].",".$v["bf"]["sl04"].",".$v["bf"]["sl14"].",".$v["bf"]["sl24"].",".$v["bf"]["sl05"].",".$v["bf"]["sl15"].",".$v["bf"]["sl25"].",".$v["bf"]["sl5"]:'';

            $jq = $v["jq"];
            unset($jq["stop"]);
            $v["ttg_odds"] = implode(",",$jq);



            $res = Db::table("eb_football_mix_odds")->where(["type" => 1, "mid" => $mid])->first();

            $game_data = ["mid"=>$mid,"num"=>$num,"dtime"=>$d_time,"l_cn"=>$l_cn,"h_cn"=>$h_cn,"a_cn"=>$a_cn,"status"=>1,"m_status"=>"Fixture","p_status"=>$p_status,"p_goal"=>"0".",".$p_goal.","."0".","."0".","."0",
                "had_odds"=>$v["had_odds"],"hhad_odds"=>$v["hhad_odds"],"crs_win"=>$v["crs_win"],
                "crs_draw"=>$v["crs_draw"],
                "crs_lose"=>$v["crs_lose"],
                "ttg_odds"=>isset($v["ttg_odds"])?$v["ttg_odds"]:"",
                "hafu_odds"=>$v["hafu_odds"],
                "l_cn_a"=>$l_cn,
                "h_cn_a"=>$h_cn,
                "a_cn_a"=>$a_cn,
                'p_single'=>$p_single,
                "type"=>1];
            if ($res) {

                unset($game_data["h_cn_a"]);
                unset($game_data["a_cn_a"]);
                unset($game_data["h_cn"]);
                unset($game_data["a_cn"]);

                //更新
                Db::table("eb_football_mix_odds")->where(["id" => $res->id])->update($game_data);
            } else {

                Db::table("eb_football_mix_odds")->insert($game_data);
            }

        }
    }

    public function updateFootGame()
    {
        // TODO: Implement updateFootGame() method.
        $data = file_get_contents("http://101.200.220.18/feijing.php?type=4");
        $data = json_decode($data,1);
        $data = $data["matchList"];
        foreach ($data as $k=>$v){
            $up_data = [];
            if ($v["state"] == -1){
                $up_data["half"] = $v["homeHalfScore"].":".$v["awayHalfScore"];
                $up_data["final"] = $v["homeScore"].":".$v["awayScore"];
                $up_data['fs_hin'] = $v["homeScore"];
                $up_data['fs_ain'] = $v["awayScore"];
                $up_data['hts_hin'] = $v["homeHalfScore"];
                $up_data['hts_ain'] = $v["awayHalfScore"];
                $mid = $v["matchId"];
                $res = Db::table("eb_football_mix_odds")->where(["mid" => $mid])->update(["m_status"=>"Played","result"=>json_encode($up_data)]);
            }else if(in_array($v["state"],[1,2,3,4,5])){
                $mid = $v["matchId"];
                Db::table("eb_football_mix_odds")->where(["mid" => $mid])->update(["m_status"=>"Playing"]);
            }else if($v["state"] == -10){
                $mid = $v["matchId"];
                Db::table("eb_football_mix_odds")->where(["mid" => $mid])->update(["m_status"=>"Cancelled"]);
            }else if($v["state"] == -14){

                $mid = $v["matchId"];
                Db::table("eb_football_mix_odds")->where(["mid" => $mid])->update(["m_status"=>"Postponed"]);

            }
        }
    }

    public function gatherBasketGame()
    {
        // TODO: Implement gatherBasketGame() method.
        $html = file_get_contents('http://101.200.220.18/feijing.php?type=3');
        $mids = file_get_contents('http://101.200.220.18/feijing.php?type=2');
        $mids = json_decode($mids,1);
        $mids = $mids["list"][0]["jclq"];
        $data = json_decode($html,1);
        $data= $data["list"];

        foreach ($data as $k=>$v){

            $dxf = $v["dxf"]["goal"];
            $p_goal = $v["rfsf"]["goal"];
            foreach ($mids as $mk=>$vk){
                if($vk["id"] == $v["id"] ){
                    $mid = $vk["matchId"];
                    break;
                }
            }


            $d_time = str_replace("\\","-",$v["matchTime"]);
            $weekarray = ["周一"=>1,"周二"=>2,"周三"=>3,"周四"=>4,"周五"=>5,"周六"=>6,"周日"=>7];

            $num_zhou = mb_substr($v["id"],0,2);
            $num_num = mb_substr($v["id"],2);
            $num = $weekarray[$num_zhou].$num_num;
            $l_cn = $v["league"];
            $h_cn = $v["homeTeam"];
            $a_cn = $v["awayTeam"];

            $sf_status = $v["sf"]?$v["sf"]["stop"]?0:1:0;
            $rfsf_status = $v["rfsf"]? $v["rfsf"]["stop"]?0:1:0;
            $sfc_status = $v["sfc"]?$v["sfc"]["stop"]?0:1:0;
            $dxf_status = $v["dxf"]?$v["dxf"]["stop"]?0:1:0;
            $p_status = $sf_status.",".$rfsf_status.",".$sfc_status.",".$dxf_status;

            $sf_single = $v["singleSf"]?1:0;
            $rfsf_single = $v["singleRfsf"]?1:0;
            $sfc_single = $v["singleSfc"]?1:0;
            $dxf_single = $v["singleDxf"]?1:0;
            $p_single = $sf_single.",".$rfsf_single.",".$sfc_single.",".$dxf_single;

            $v["mnl_odds"] = $v["sf"]? $v["sf"]["lose"].",".$v["sf"]["win"]:"";
            $v["hdc_odds"] = $v["rfsf"]? $v["rfsf"]["lose"].",".$v["rfsf"]["win"]:'';
            $v["wnm_lose"] = $v["sfc"]? $v["sfc"]["w1_5"].",".$v["sfc"]["w6_10"].",".$v["sfc"]["w11_15"].",".$v["sfc"]["w16_20"].",".$v["sfc"]["w21_25"].",".$v["sfc"]["w26"]:'';
            $v["wnm_win"] =$v["sfc"]? $v["sfc"]["L1_5"].",".$v["sfc"]["L6_10"].",".$v["sfc"]["L11_15"].",".$v["sfc"]["L16_20"].",".$v["sfc"]["L21_25"].",".$v["sfc"]["L26"]:'';

            $v["hilo_odds"] =$v["dxf"]?$v["dxf"]["over"].",".$v["dxf"]["under"]:'';

            $res = Db::table("eb_basketball_mix_odds")->where(["type" => 1, "mid" => $mid])->first();

            $game_data = ["mid"=>$mid,"num"=>$num,"dtime"=>$d_time,"l_cn"=>$l_cn,"h_cn"=>$h_cn,"a_cn"=>$a_cn,"status"=>1,"m_status"=>"Fixture","p_status"=>$p_status,"p_goal"=>"0".",".$p_goal.","."0".","."+".$dxf,
                "mnl_odds"=>$v["mnl_odds"],"hilo_odds"=>$v["hilo_odds"],"hdc_odds"=>$v["hdc_odds"],
                "wnm_win"=>$v["wnm_lose"],
                "wnm_lose"=>$v["wnm_win"],

                "l_cn_abbr"=>$l_cn,
                "h_cn_abbr"=>$h_cn,
                "a_cn_abbr"=>$a_cn,
                'p_single'=>$p_single,
                "type"=>1];
            if ($res) {

                unset($game_data["h_cn_a"]);
                unset($game_data["a_cn_a"]);
                unset($game_data["h_cn"]);
                unset($game_data["a_cn"]);

                //更新
                Db::table("eb_basketball_mix_odds")->where(["id" => $res->id])->update($game_data);
            } else {

                Db::table("eb_basketball_mix_odds")->insert($game_data);
            }

        }
    }

    public function updateBaketGame()
    {
        // TODO: Implement updateBaketGame() method.
        $data = file_get_contents("http://101.200.220.18/feijing.php?type=5");
        $data = json_decode($data,1);
        $data = $data["list"];

        foreach ($data as $k=>$v){
            $up_data = [];

            $mid = $v["matchId"];
            if ($v["matchState"] == -1){

                $up_data["qcbf"] = $v["awayScore"].":".$v["homeScore"];
                $res = Db::table("eb_basketball_mix_odds")->where(["mid" => $mid])->update(["m_status"=>"Played","result"=>json_encode($up_data)]);
            }else if(in_array($v["matchState"],[1,2,3,4,5,6,7,50])){


                Db::table("eb_basketball_mix_odds")->where(["mid" => $mid])->update(["m_status"=>"Playing"]);

            }else if($v["matchState"] == -4){


                Db::table("eb_basketball_mix_odds")->where(["mid" => $mid])->update(["m_status"=>"Cancelled"]);

            }else if($v["matchState"] == -5){


                Db::table("eb_basketball_mix_odds")->where(["mid" => $mid])->update(["m_status"=>"Postponed"]);

            }
        }
    }
    public function getqh(){
        $qh = file_get_contents("https://cp.zgzcw.com/lottery/bdplayvsforJsp.action?lotteryId=200&v=1224");
        $qh = QueryList::html($qh)->find("#selectissue")->val();
        return $qh;
    }
    public function gatherBdGame()
    {
        // TODO: Implement gatherBdGame() method.
        $qh = $this->getqh();
        $data =  file_get_contents('http://101.200.220.18/feijing.php?type=6');
        $data = json_decode($data,1);
        $data= $data["list"];
        foreach ($data as $k=>$v){
            $d_time = str_replace("\\","-",$v["matchTime"]);
            $mid = $v["issueNum"].$v["id"];
            $goal = $v["spf"]? $v["spf"]["goal"]:0;
            $crs_win = [];
            $crs_lose = [];
            $crs_draw = [];
            if($v["bf"]){
                foreach ($v["bf"] as $k1=>$v1){
                    $str = substr($k1,0,2);
                    if($str == "sw"){
                        $crs_win[] = $v1;
                    }
                    if($str == "sl"){
                        $crs_lose[] = $v1;
                    }
                    if($str == "sd"){
                        $crs_draw[] = $v1;
                    }
                }
            }
            $crs_win =$crs_win?implode(",",$crs_win):"0,0,0,0,0,0,0,0,0,0";
            $crs_lose =$crs_lose?implode(",",$crs_lose):"0,0,0,0,0,0,0,0,0,0";

            $crs_draw = $crs_draw?implode(",",$crs_draw):"0,0,0,0,0";
            $spf = $v["spf"]?$v["spf"]["sf3"].",".$v["spf"]["sf1"].",".$v["spf"]["sf0"]:"0,0,0";
            $jq =$v["jq"]?implode(",",$v["jq"]):"0,0,0,0,0,0,0,0";
            $bqc =$v["bqc"]?implode(",",$v["bqc"]):"0,0,0,0,0,0,0,0,0";
            $sxds =$v["sxds"]?implode(",",$v["sxds"]):"0,0,0,0";

            if(Db::table("eb_football_bd_odds")->where(["mid"=>$mid])->first()){
                Db::table("eb_football_bd_odds")->where(["mid"=>$mid])->update([
                    "had_odds"=>$spf,
                    "hafu_odds"=>$bqc,
                    "on_up"=>$sxds,
                    "ttg_odds"=>$jq,
                    "crs_win"=>$crs_win,
                    "crs_draw"=>$crs_draw,
                    "crs_lose"=>$crs_lose,
                    "dtime"=>$d_time,
                    "p_goal"=>$goal,
                ]);
            }else{
                Db::table("eb_football_bd_odds")->insert([
                    "game_no"=>$v["id"],
                    "qh"=>$qh,
                    "m_status"=>"销售中",
                    "had_odds"=>$spf,
                    "hafu_odds"=>$bqc,
                    "on_up"=>$sxds,
                    "ttg_odds"=>$jq,
                    "crs_win"=>$crs_win,
                    "crs_draw"=>$crs_draw,
                    "crs_lose"=>$crs_lose,
                    "l_cn"=>$v["league"],
                    "h_cn"=>$v["home"],
                    "a_cn"=>$v["away"],
                    "mid"=>$mid,
                    "type"=>5,
                    "dtime"=>$d_time,
                    "p_goal"=>$goal,
                ]);
            }

        }
    }

    public function updateBdGame()
    {
        // TODO: Implement updateBdGame() method.
        $html = file_get_contents("http://www.310win.com/beijingdanchang/rangqiushengpingfu/kaijiang_dc_all.html");

        $sql = QueryList::html($html);
        $qh = $sql->find("select")->val();

        $data = $sql->rules([
            "mid"=>['td:eq(0)', 'text'],
            "dtime"=>['td:eq(2)', 'text'],
            "p_goal"=>['td:eq(3) font', 'text'],
            "bifen_result"=>['td:eq(4)', 'text'],
            "spf_pl"=>['td:eq(6) a', 'text'],
            "ttg_pl"=>['td:eq(7) a', 'text'],
            "bf_pl"=>['td:eq(9) a', 'text'],
            "onup_pl"=>['td:eq(8) a', 'text'],
            "bqc_pl"=>['td:eq(10) a', 'text'],
        ])->range("#lottery_container tr:not(:first-child)")->query()->getData()->toArray();

        foreach ($data as $k=>$v){

            $t_data = Db::table("eb_football_bd_odds")->where(["mid"=>$qh.$v["mid"]])->first();
            $game = $t_data?$t_data->toArray():[];
            if($game){

                if(strlen(trim($v["bifen_result"])) < 7 ){

                    // Db::table("eb_order_detail")->where(["game_id"=>$game["id"]])->update(["state"=>1]);
                }else{

                    $bifen = trim($v["bifen_result"]);
                    $bifen = str_replace("-",":",$bifen);
                    $bifen = explode(" ",$bifen);
                    if(isset($bifen[1])){
                        $half = $bifen[1];
                        $final = $bifen[0];

                        $half_jq = explode(":",$half);

                        $final_jq = explode(":",$final);
                        $v["p_goal"] = $v["p_goal"] !=''?$v["p_goal"]:0;

                        $res = [
                            "half" => $half,"hhad_goal"=>(int)$v["p_goal"], "final" => $final, "fs_hin" => $final_jq[0], "fs_ain" => $final_jq[1], "hts_hin" => $half_jq[0], "hts_ain" => $half_jq[1],
                            "spf_pl"=>$v["spf_pl"],
                            "ttg_pl"=>$v["ttg_pl"],
                            "bf_pl"=>$v["bf_pl"],
                            "onup_pl"=>$v["onup_pl"],
                            "bqc_pl"=>$v["bqc_pl"]
                        ];
                        $res = json_encode($res);
                        Db::table("eb_football_bd_odds")->where(["id"=>$game["id"]])->update(["m_status"=>"Played","result"=>$res]);
                    }

                }

            }
        }
    }

    public function gatherPlGame()
    {
        // TODO: Implement gatherPlGame() method.
        $data = file_get_contents("https://webapi.sporttery.cn/gateway/lottery/getHistoryPageListV1.qry?gameNo=35&provinceId=0&pageSize=30&isVerify=1&pageNo=1&termLimits=30");
        $data = json_decode($data,1);
        $data = $data["value"]["list"];

        foreach ($data as $k=>$v){

            $result = $v["lotteryDrawResult"];
            $result = explode(" ",$result);


            $res =Db::table("eb_qh")->where(["qh"=>$v["lotteryDrawNum"]])->first();
            if(!$res){
                Db::table("eb_qh")->insert(["value"=>$result[0].$result[1].$result[2],"qh"=>$v["lotteryDrawNum"],"dtime"=>$v['lotterySaleEndtime']]);
            }
        }
    }
}