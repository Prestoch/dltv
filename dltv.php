<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
//ini_set('pcre.backtrack_limit', "5000000");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
define('MAX_FILE_SIZE', 120000000);
error_reporting(E_ALL);
require 'vendor/autoload.php';
require_once('config.php');
require_once('sd.php');

$debug = false;
if(isset($_GET['debug'])){
	$debug = true;
}
if($debug){
	echo 'Debug<br/>';
}

function rdie($a){
	echo json_encode($a);die();
}
function pre($a){
	echo '<pre>'.json_encode($a,JSON_PRETTY_PRINT).'</pre>';
}
// function get_html($a){
// 	$proxy = '198.23.239.134:6540';
// 	$user = 'ugygnbms';
// 	$pass = 'zuzdrj27ia52';

// 	$ch = curl_init($a);
// 	curl_setopt($ch, CURLOPT_PROXY, $proxy);
// 	curl_setopt($ch, CURLOPT_PROXYUSERPWD, $user.':'.$pass);
// 	curl_setopt($ch, CURLOPT_REFERER, "https://dltv.org/matches");
// 	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0");
// 	curl_setopt($ch, CURLOPT_URL, $a);
// 	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// 	curl_setopt ($ch, CURLOPT_REFERER, $a);
// 	$output = curl_exec ($ch);
// 	curl_close($ch);
// 	return $output;
// }
function test_html($a){
	$fp = fopen("test.html","wb");
	fwrite($fp,$a);
	fclose($fp);
}
$mf = dirname(__FILE__).'/matches_dltv';
$checked_f = dirname(__FILE__).'/checked';
if (!file_exists($mf)) {
    mkdir($mf, 0777, true);
}
if (!file_exists($checked_f)) {
    mkdir($checked_f, 0777, true);
}
if(!file_exists(dirname(__FILE__).'/cs.json')){
	die('echo cs.json not found');
}
$csjson = file_get_contents(dirname(__FILE__).'/cs.json');
$f1 = explode(', heroes_bg = ',$csjson);
$f2 = explode('var heroes = ',$f1[0]);
if(!isset($f2[1])){
	die('cs.json heroes problem');
}
$h = json_decode($f2[1],true);
if(!is_array($h)){
	die('cs.json heroes problem');
}
$f3 = explode(', win_rates =',$csjson);
$f4 = explode(', heroes_wr = ',$f3[0]);
if(!isset($f4[1])){
	die('cs.json heroes_wr problem');
}
$h_wr = json_decode($f4[1],true);
if(!is_array($h_wr)){
	die('cs.json heroes_wr problem');
}
$f5 = explode('win_rates = ',$csjson);
if(!isset($f5[1])){
	die('cs.json win_rates problem');
}
$f6 = explode(', update_time',$f5[1]);
$h_wrs = json_decode($f6[0],true);
if(!is_array($h_wrs)){
	die('cs.json win_rates problem');
}
$hero = [];
function cn($s){
	$a = strtolower($s);
	if($a == 'outworld devourer'){
		$s = 'outworld destroyer';
	}
	return preg_replace('/[0-9]+/', '', strtolower(preg_replace("/[^A-Za-z0-9 ]/", '', $s)));
}
foreach($h as $hh){
	$hero[] = cn($hh);
}

// $tdf = 'matches/'.date('Ymd').'_tower_damage.json';
// if(!file_exists($tdf)){
//    $ch_tdf = curl_init();
//    //curl_setopt($ch_tdf, CURLOPT_URL,"https://www.dotabuff.com/heroes/damage");
//    curl_setopt($ch_tdf, CURLOPT_URL,"https://www.dotabuff.com/heroes/damage?date=week");
//    curl_setopt($ch_tdf, CURLOPT_RETURNTRANSFER, true);
//    curl_setopt($ch_tdf, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0");
//    $tdf_res = curl_exec($ch_tdf);
//    curl_close ($ch_tdf);
//    //$tdf_res = $hd;
//    $tdf_html = str_get_html($tdf_res);
//    $tdf_table = $tdf_html->find('table',0);
//    if($tdf_table){
//       $t_body = $tdf_table->find('tbody',0);
//       $t_res = [];
//       foreach($t_body->find('tr') as $tr){
//          $tds = [];
//          for($i=1;$i<5;$i++){
//             $tds[] = $tr->find('td',$i)->plaintext;
//          }
//          $t_res[array_search(cn($tds[0]),$hero)] = $tds;
//       }
// 	  //echo '<pre>'.json_encode($t_res,JSON_PRETTY_PRINT).'</pre>';die();
//       $fp_tdf = fopen($tdf, 'w');
// 		fwrite($fp_tdf, json_encode($t_res)); 
// 		fclose($fp_tdf);
//    }
// }
// $tower_damage = json_decode(file_get_contents($tdf),true);

$u = 'https://dltv.org/matches';
$gc = get_html($u);

//echo $gc;die();

if(!$gc){
    rdie(['error'=>'No GC']);
}

$html = str_get_html($gc);
$bases = $html->find('div[class=live__matches-item]');
$links = [];
foreach($bases as $b){
    foreach($b->find('a') as $a){
        if($a->hasAttribute('href')){
            $href = explode('#',$a->getAttribute('href'))[0];
            if(strpos($href,'matches') !== false){
                if(!in_array($href,$links)){
                    $links[] = $href;
                }
            }
        }
    }
}

//var_dump(cn('mars'));
//echo array_search(cn('Outworld Devourer'),$hero);
//pre($h_wrs[128]);
//pre($hero);
//pre($res_matches);die();

if(!sizeof($links)){
    rdie(['error'=>'No live games']);
}
$res_matches = [];
foreach($links as $a){

	//$file = $mf.'/cyber.'.$m['mid'].'.json';
	$the_id = basename($a);
	$the_file = $mf.'/cyber.'.$the_id.'.json';
	if(!$debug&&file_exists($the_file)){
		continue;
	}

    $b = get_html($a);
    if($b){
        $c = str_get_html($b);
        $nm = [];
        
        $lps = explode('/',$a);
        $nm['match_id'] = '';
        $nm['mid'] = '';

        foreach($lps as $lp){
            if($lp&&is_numeric($lp)){
                $nm['match_id'] = $lp;
                break;
            }
        }

        $fst = $c->find('section[class=event__title]')[0];
        if($fst){
            $fsta = $fst->find('a')[0];
            if($fsta){
                $fst_text = trim($fsta->plaintext);
                if($fst_text){
                    $nm['name'] = $fst_text;
                }
            }
        }

        $ims = $c->find('div[class=info__match]');
        if($ims&&sizeof($ims)){
            $imf = explode(' ',$ims[0]->plaintext);
            foreach($imf as $iim){
                $the_mid = trim($iim);
                if(is_numeric($the_mid)){
                    $nm['mid'] = $nm['match_id'].'_'.$the_mid;
                }
            }
        }

        $team1 = [];
        $team2 = [];

        
		$bases = $c->find('div[id=live_scoreboard]');
        if(!$bases||!sizeof($bases)){
			//$bases = $c->find('div[class*=map__finished-title__content]');
        }
        if($bases&&sizeof($bases)){
            $base = $bases[0];
            $spans = $base->find('span[class=team__title-name]');
			$sl = 0;
            foreach($spans as $sps){
                $sp = $sps->find('span[class*=side]');
                $tn = $sps->find('span[class=name]');
                if(sizeof($sp)&&sizeof($tn)){
                    $tnn = $tn[0]->plaintext;
                    $ss = $sp[0]->plaintext;
                    if($ss&&$ss=='Radiant'){
                        $team1['name'] = $tnn;
						$team1['ss'] = $ss;
                        $tp_logo = $sps->parent()->find('span[class=team__title-logo]')[0]->getAttribute('style');
                        if($tp_logo){
                            preg_match_all('/background-image: url\(\'(.*?)\'\)/',$tp_logo,$ass);
                            //pre($ass);
                            if(isset($ass[1])&&isset($ass[1][0])){
                                $team1['logo_url'] = 'https://dltv.org'.$ass[1][0];
                            }
                        }
                    }else if($ss&&$ss=='Dire'){
                        $team2['name'] = $tnn;
						$team2['ss'] = $ss;
                        $tp_logo = $sps->parent()->find('span[class=team__title-logo]')[0]->getAttribute('style');
                        if($tp_logo){
                            preg_match_all('/background-image: url\(\'(.*?)\'\)/',$tp_logo,$ass);
                            //pre($ass);
                            if(isset($ass[1])&&isset($ass[1][0])){
                                $team2['logo_url'] = 'https://dltv.org'.$ass[1][0];
                            }
                        }
                    }
                }
            }
        }
        $team1['heroes'] = [];
        $team2['heroes'] = [];

        $teams_bases = $c->find('div[class*=picks__new-picks__picks]');
        foreach($teams_bases as $tb){
            if(strpos($tb->class,'radiant') !== false){
                $picks = $tb->find('div[class*=pick]');
                foreach($picks as $p){
                    if($p->hasAttribute('data-hero-id')){
                        $id = $p->getAttribute('data-hero-id');
                        $hero_name = $p->getAttribute('data-tippy-content');
                        if($id){
                            $hh = [];
                            //$hh['id'] = $id;
                            $hh['id'] = array_search(cn($hero_name),$hero);
							$hh['hname'] = $hero_name;
                            $fimgs = $p->find('div[class=pick__image]');
                            if(sizeof($fimgs)&&$fimgs[0]){
                                preg_match('/background-image: url\(\'(.*?)\'\)/',$fimgs[0]->getAttribute('style'),$fims);
                                if(isset($fims[1])){
                                    $hh['image'] = 'https://dltv.org'.$fims[1];
                                }
                            }
                            $pss = $p->find('a[class=pick__stats]');
                            if(sizeof($pss)&&$pss[0]){
                                $ps = str_replace(' | ',' - ',str_replace('%','',$pss[0]->plaintext));;
                                $hh['wcc'] = $ps;
                            }
                            $team1['heroes'][] = $hh;
                        }
                    }
                }
            }elseif(strpos($tb->class,'dire') !== false){
                $picks = $tb->find('div[class*=pick]');
                foreach($picks as $p){
                    if($p->hasAttribute('data-hero-id')){
                        $id = $p->getAttribute('data-hero-id');
                        $hero_name = $p->getAttribute('data-tippy-content');
                        if($id){
                            $hh = [];
                            //$hh['id'] = $id;
                            $hh['id'] = array_search(cn($hero_name),$hero);
							$hh['hname'] = $hero_name;
                            $fimgs = $p->find('div[class=pick__image]');
                            if(sizeof($fimgs)&&$fimgs[0]){
                                preg_match('/background-image: url\(\'(.*?)\'\)/',$fimgs[0]->getAttribute('style'),$fims);
                                if(isset($fims[1])){
                                    $hh['image'] = 'https://dltv.org'.$fims[1];
                                }
                            }
                            $pss = $p->find('a[class=pick__stats]');
                            if(sizeof($pss)&&$pss[0]){
                                $ps = str_replace(' | ',' - ',str_replace('%','',$pss[0]->plaintext));;
                                $hh['wcc'] = $ps;
                            }
                            $team2['heroes'][] = $hh;
                        }
                    }
                }
            }
        }

        $nm['team1'] = $team1;
		$nm['team2'] = $team2;
        if(isset($team1['name'])&&isset($team2['name'])&&isset($team1['heroes'])&&is_array($team1['heroes'])&&sizeof($team1['heroes'])==5 && 
        isset($team2['heroes'])&&is_array($team2['heroes'])&&sizeof($team2['heroes'])==5
        ){
			//pre($nm);
            $res_matches[] = $nm;
        }
    }
}
//var_dump(cn('mars'));
//echo array_search(cn('Outworld Devourer'),$hero);
//pre($h_wrs[128]);
//pre($hero);
//pre($res_matches);die();
if($debug){
    echo 'DEBUG MODE<br/>';
}
echo 'Games : '.sizeof($res_matches).'<br/>';
foreach($res_matches as $m){
	//pre($m);
	echo $m['mid'].'<br/>';
	$file = $mf.'/cyber.'.$m['mid'].'.json';
	//if(1){
	if($debug||!file_exists($file)){
		//pre($m);die();
		$cond_one = false;
		$cond_2 = false;
		$cond_3 = false;
		$cond_4 = false;
		$cond_5 = false;
		
		$hero_have_hh = false;
		$hero_have_anh = false;
		$nb1 = 0;
		$nb2 = 0;
		$m['team1']['cc_neg'] = 0;
		$m['team1']['cc_pos'] = 0;
		$m['team2']['cc_neg'] = 0;
		$m['team2']['cc_pos'] = 0;
      $td_2 = 0;
      $td_1 = 0;
		for($i=0;$i<5;$i++){
			$m['team1']['heroes'][$i]['wr'] = $h_wr[$m['team1']['heroes'][$i]['id']];
			$m['team2']['heroes'][$i]['wr'] = $h_wr[$m['team2']['heroes'][$i]['id']];
			if(in_array($m['team2']['heroes'][$i]['id'],$hero_have) || in_array($m['team1']['heroes'][$i]['id'],$hero_have)){
				$hero_have_hh = true;
			}
         if(isset($tower_damage)){
         $m['team1']['heroes'][$i]['td'] = (float) $tower_damage[$m['team1']['heroes'][$i]['id']][2];
			$m['team2']['heroes'][$i]['td'] = (float) $tower_damage[$m['team2']['heroes'][$i]['id']][2];

         $td_2+=$m['team2']['heroes'][$i]['td'];
         $td_1+=$m['team1']['heroes'][$i]['td'];
		 }
			
			$nb1 += floatval($h_wr[$m['team1']['heroes'][$i]['id']]);
			$nb2 += floatval($h_wr[$m['team2']['heroes'][$i]['id']]);
			
			$m['team1']['heroes'][$i]['name'] = $h[$m['team1']['heroes'][$i]['id']];
			$m['team2']['heroes'][$i]['name'] = $h[$m['team2']['heroes'][$i]['id']];
			
			$nb1a = 0;
			$nb2a = 0;
			for($a=0;$a<5;$a++){
				$nb1a+=floatval($h_wrs[$m['team2']['heroes'][$a]['id']][$m['team1']['heroes'][$i]['id']][0])*-1;
				$nb2a+=floatval($h_wrs[$m['team1']['heroes'][$a]['id']][$m['team2']['heroes'][$i]['id']][0])*-1;
			}
			$m['team1']['heroes'][$i]['wr_2_success'] = $nb1a > 0 ? false : true;
			$m['team2']['heroes'][$i]['wr_2_success'] = $nb2a > 0 ? false : true;
			
			$m['team1'][($nb1a > 0 ? 'cc_neg':'cc_pos')]++;
			$m['team2'][($nb2a > 0 ? 'cc_neg':'cc_pos')]++;
			
			$m['team1']['heroes'][$i]['wr_2'] = number_format($nb1a, 2, '.', "")*-1;
			$m['team2']['heroes'][$i]['wr_2'] = number_format($nb2a, 2, '.', "")*-1;
			
			$an_t1 = $m['team1']['heroes'][$i]['wr_2'];
			$an_t2 = $m['team2']['heroes'][$i]['wr_2'];
			if(sizeof($anh_have)){
				foreach($anh_have as $an){
					$anh_f = floatval(str_replace('-','',str_replace('+','',$an)));
					$cv1 = floatval(str_replace('-','',str_replace('+','',$an_t1)));
					$cv2 = floatval(str_replace('-','',str_replace('+','',$an_t2)));
					if(strpos($an,'-') === false){
						if(strpos($an_t1,'-') === false){
							if($cv1>$anh_f){
								$hero_have_anh = true;
								break;
							}
						}
						if(strpos($an_t2,'-') === false){
							if($cv2>$anh_f){
								$hero_have_anh = true;
								break;
							}
						}
					}else{
						//negative
						if(strpos($an_t1,'-') !== false){
							if($cv1>$anh_f){
								$hero_have_anh = true;
								break;
							}
						}
						if(strpos($an_t2,'-') !== false){
							if($cv2>$anh_f){
								$hero_have_anh = true;
								break;
							}
						}
					}
				}
			}
			
			$nb1 += $nb1a*-1;
			$nb2 += $nb2a*-1;
		}
		$m['team1']['score'] = number_format($nb1, 2, '.', "");
		$m['team2']['score'] = '- '.number_format($nb2, 2, '.', "");
		$m['total'] = number_format(($nb1-$nb2), 2, '.', "");
		$m['total_success'] = ($nb1>$nb2) ? true : false;
		//pre($m);
		$gh = '<div style="width:600px;max-width:100%;border:1px solid gray;padding:20px;">';
		$gh.= '<h1 style="margin:0px 0px 20px 0px;">'.$m['name'].'</h1>';
		$gh.='<h3>'.$m['team1']['name'].'</h3>';
		//pre($m);die();
		$gh.='<div style="display:flex;justify-content: space-between;align-content: space-between;">';
		for($i=0;$i<sizeof($m['team1']['heroes']);$i++){
			$hero = $m['team1']['heroes'][$i];
			$gh.='<div style="width:80px;margin-right:20px;">';
			if(isset($tower_damage)){
        		$gh.='<div style="color:#cb8300;font-weight:bold;">'.$hero['td'].'</div>';
			}
			$gh.='<span>'.$hero['wr'].' + <span style="'.($hero['wr_2_success'] ? 'color:green;':'color:red;').'">'.$hero['wr_2'].'</span></span>';
			$gh.='<img style="width:100%;" src="'.$hero['image'].'">';
			$gh.='<span>'.$hero['name'].'</span>';
			if(isset($hero['wcc'])){
				$gh.='<br/><span style="color:red;">'.$hero['wcc'].'%</span>';
			}
			$gh.='</div>';
		}
      //pre($hero);die();
		//$gh.='<span style="padding-top:25px;">'.$m['team1']['score'].'</span>';
         $gh.='<div>';
		 if(isset($tower_damage)){
            $gh.='<div style="color:#cb8300;font-weight:bold;padding-top:44px;">'.$td_1.'</div>';
		 }
            $gh.='<div>'.$m['team1']['score'].'</div>';
         $gh.='</div>';
		$gh.='</div>';
		
      $gh.='<div style="width:100%;display:block;align-items:center;justify-content:space-between;">';
         $gh.='<h3 style="display:inline-block;">'.$m['team2']['name'].'</h3>';
		 if(isset($$tower_damage)){
         $gh.='<div style="display:inline-block;margin-left:388px;font-weight:bold;color:'.($td_1 > $td_2 ? '#cb8300':'#00a6f5').';">'.($td_1 > $td_2 ? ($td_1 - $td_2) : ($td_2 - $td_1)).'</div>';
		 }
      $gh.='</div>';
      
		$gh.='<div style="display:flex;justify-content: space-between;align-content: space-between;">';
		for($i=0;$i<sizeof($m['team2']['heroes']);$i++){
			$hero = $m['team2']['heroes'][$i];
			$gh.='<div style="width:80px;margin-right:20px;">';
			if(isset($tower_damage)){
        		$gh.='<div style="color:#00a6f5;font-weight:bold;">'.$hero['td'].'</div>';
			}
			$gh.='<span>'.$hero['wr'].' + <span style="'.($hero['wr_2_success'] ? 'color:green;':'color:red;').'">'.$hero['wr_2'].'</span></span>';
			$gh.='<img style="width:100%;" src="'.$hero['image'].'">';
			$gh.='<span>'.$hero['name'].'</span>';
			if(isset($hero['wcc'])){
				$gh.='<br/><span style="color:red;">'.$hero['wcc'].'%</span>';
			}
			$gh.='</div>';
		}
      
         $gh.='<div>';
		 if(isset($tower_damage)){
            $gh.='<div style="color:#00a6f5;font-weight:bold;padding-top:44px;">'.$td_2.'</div>';
		 }
            $gh.='<div>'.$m['team2']['score'].'</div>';
         $gh.='</div>';
      
		$gh.='</div>';
		
		$gh.='<span style="display:block;font-size:30px;margin-top:20px;'.($m['total_success']?'color:green;':'color:red;').'">'.$m['total'].'</span>';
		
		$gh.='</div>';
		//pre($m);
		//echo $gh;die();
		//pre($tower_damage);
		$mets = [];
		$total_f = floatval($m['total']); //if((($total_f<0&&$total_f>=-3)||($total_f<=-20))||(($total_f>0&&$total_f<=3)||($total_f>=20))){
		if(($total_f<0&&$total_f<$email_if_less)||$total_f>$email_if_greater){
			$cond_one = true;
			$mets[] = 'Condition 1 is met';
		}
		
		if((!isset($team_have_plus)||!is_array($team_have_plus)||!sizeof($team_have_plus))){
			$cond_2 = true;
			$mets[] = 'Condition 2 is met';
		}else if(in_array($m['team1']['cc_pos'].'+'.$m['team2']['cc_neg'].'-',$team_have_plus) ||
				in_array($m['team2']['cc_pos'].'+'.$m['team1']['cc_neg'].'-',$team_have_plus) ||
				in_array($m['team1']['cc_pos'].'+'.$m['team2']['cc_pos'].'+',$team_have_plus) ||
				in_array($m['team2']['cc_pos'].'+'.$m['team1']['cc_pos'].'+',$team_have_plus) ||
				in_array($m['team1']['cc_neg'].'-'.$m['team2']['cc_neg'].'-',$team_have_plus) || 
				in_array($m['team2']['cc_neg'].'-'.$m['team1']['cc_neg'].'-',$team_have_plus)){
			//pre($team_have_plus);
			$cond_2 = true;
			$mets[] = 'Condition 2 is met';
		}
		
		if((!isset($hero_have)||!sizeof($hero_have))||$hero_have_hh){
			$cond_3 = true;
			$mets[] = 'Condition 3 is met';
		}
		
		if((!isset($anh_have)||!sizeof($anh_have))||$hero_have_anh){
			$cond_4 = true;
			$mets[] = 'Condition 4 is met';
		}
		
		if((!isset($td_g)&&!isset($td_l))||(!$td_g&&!$td_l)){
			$cond_5 = true;
			$mets[] = 'Condition 5 is met';
		}else{
			if($td_g){
				if($td_1>=$td_2&&(($td_1-$td_2)>$td_g)){
					$cond_5 = true;
					$mets[] = 'Condition 5 is met';
				}else if($td_2>=$td_1&&(($td_2-$td_1)>$td_g)){
					$cond_5 = true;
					$mets[] = 'Condition 5 is met';
				}
				//$cond_5 = true;
				//$mets[] = 'Condition 5 is met';
			}
			if($td_l){
				if($td_1>=$td_2&&(($td_1-$td_2)<$td_l)){
					$cond_5 = true;
					$mets[] = 'Condition 5 is met';
				}else if($td_2>=$td_1&&(($td_2-$td_1)<$td_l)){
					$cond_5 = true;
					$mets[] = 'Condition 5 is met';
				}
				//$cond_5 = true;
				//$mets[] = 'Condition 5 is met';
			}
		}
		
		echo '<pre>'.json_encode($mets,JSON_PRETTY_PRINT).'</pre>';
		//if(1){
		if($debug||($cond_one&&$cond_2&&$cond_3&&$cond_4&&$cond_5)){
		$mail = new PHPMailer(true);
		try {
			//Server settings
			$mail->SMTPDebug = 0;                      //Enable verbose debug output
			$mail->isSMTP();                                            //Send using SMTP
			$mail->Host       = $smtp_host;       //Set the SMTP server to send through
			$mail->SMTPAuth   = true;                                   //Enable SMTP authentication
			$mail->Username   = $smtp_user;           //SMTP username
			$mail->Password   = $smtp_pass;                           //SMTP password
			$mail->SMTPSecure = $smtp_pro;          //Enable implicit TLS encryption
			$mail->Port       = $smtp_port;                             //TCP port to connect to; use 587 if you have set 
			
			$mail->setFrom($smtp_from, $smtp_from_name);
			//$mail->addAddress($email_destination);
			
			if($debug){
				$mail->addAddress('glennwilkinsd@gmail.com');
			}else if(isset($dltv_email)){
				$mail->addAddress($dltv_email);
				//$mail->addAddress('glennwilkinsd@gmail.com');
				//$mail->addAddress('glennwilkinsd@gmail.com');
			}
			//$mail->addAddress('glennwilkinsd@gmail.com');
			
         $mail->SMTPOptions = array(
				'ssl' => array(
					'verify_peer' => false,
					'verify_peer_name' => false,
					'allow_self_signed' => true
				)
			);
			$mail->CharSet = 'UTF-8';
			$mail->isHTML(true);                                 
			$mail->Subject = $m['team1']['name'].' vs '.$m['team2']['name'].' - '.$m['name'];
			$mail->Body    = $gh;
			$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

			
			$mail->send();
			echo 'email sent';
		} catch (Exception $e) {
			echo 'email error';
		}
		echo '<br/>';
		}else {
         //echo 'b';
			//pre($m);
			//var_dump($m['team1']['cc_pos'] === $teams_have['+']);
			//pre($teams_have);
		}
		$fp = fopen($file, 'w');
		fwrite($fp, json_encode($m)); 
		fclose($fp);
		//die();
	}
}
//echo $cyber_email;