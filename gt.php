<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
define('MAX_FILE_SIZE', 120000000);
error_reporting(E_ALL);
require 'vendor/autoload.php';
require_once('config.php');
require_once('sd.php');

$debug = isset($_GET['debug']);
if($debug){ echo 'Debug<br/>'; }

function rdie($a){ echo json_encode($a); die(); }
function pre($a){ echo '<pre>'.json_encode($a,JSON_PRETTY_PRINT).'</pre>'; }

$mf = dirname(__FILE__).'/matches_gt';
if (!file_exists($mf)) { mkdir($mf, 0777, true); }

// Load cs.json data
if(!file_exists(dirname(__FILE__).'/cs.json')){ die('cs.json not found'); }
$csjson = file_get_contents(dirname(__FILE__).'/cs.json');
$f1 = explode(', heroes_bg = ',$csjson);
$f2 = explode('var heroes = ',$f1[0]);
if(!isset($f2[1])){ die('cs.json heroes problem'); }
$h = json_decode($f2[1],true); if(!is_array($h)){ die('cs.json heroes problem'); }
$f3 = explode(', win_rates =',$csjson);
$f4 = explode(', heroes_wr = ',$f3[0]);
if(!isset($f4[1])){ die('cs.json heroes_wr problem'); }
$h_wr = json_decode($f4[1],true); if(!is_array($h_wr)){ die('cs.json heroes_wr problem'); }
$f5 = explode('win_rates = ',$csjson); if(!isset($f5[1])){ die('cs.json win_rates problem'); }
$f6 = explode(', update_time',$f5[1]);
$h_wrs = json_decode($f6[0],true); if(!is_array($h_wrs)){ die('cs.json win_rates problem'); }

$hero = [];
function cn_gt($s){ return preg_replace('/[0-9]+/', '', strtolower(preg_replace("/[^A-Za-z0-9 ]/", '', $s))); }
foreach($h as $hh){ $hero[] = cn_gt($hh); }

// List page (Scrape.do proxy via get_html)
$listUrl = 'https://game-tournaments.com/en/dota-2/matches';
$gc = get_html($listUrl);
if(!$gc){ rdie(['error'=>'No list HTML']); }
if($debug){ @file_put_contents('/tmp/gt_list.html',$gc); }

// Discover match links via regex (limit to avoid heavy scans)
$links = [];
if(preg_match_all('~href="(/en/dota-2/[^"\s]*?-([0-9]+))"~i', $gc, $m)){
    foreach($m[1] as $i => $rel){
        $id = $m[2][$i];
        $abs = 'https://game-tournaments.com'.$rel;
        $links[$id] = $abs;
        if(count($links) >= 20) break; // cap
    }
}
$links = array_values(array_unique($links));
if($debug){ echo 'Found links: '.sizeof($links).'<br/>'; }
if(!sizeof($links)){
    rdie(['error'=>'No live games']);
}

$res_matches = [];
foreach($links as $a){
    $the_id = preg_replace('~.*-([0-9]+)$~','$1',$a);
    $the_file = $mf.'/gt.'.$the_id.'.json';
    if(!$debug && file_exists($the_file)) continue;

    $b = get_html($a);
    if(!$b) continue;
    if($debug){ echo 'scan '.$the_id.' len='.strlen($b).'<br/>'; }
    $c = str_get_html($b);

    // Optional time filter (loose for now)
    $recent_ok = true;
    $scd = $c ? $c->find('span.scd[data-time]',0) : null;
    if($scd){ $dt = intval($scd->getAttribute('data-time')); $recent_ok = true; }
    if(!$recent_ok) continue;

    $nm = [];
    $nm['mid'] = $the_id;
    $team1 = ['heroes'=>[],'name'=>'Radiant'];
    $team2 = ['heroes'=>[],'name'=>'Dire'];

    // Picks from draft sections
    if($c){
        $draftContainers = $c->find('div[class*=draft], section[class*=draft]');
        foreach($draftContainers as $draft){
            $nodes = $draft->find('[class*=pick], .pick, [data-hero-name], [data-hero-id]');
            foreach($nodes as $p){
                $cls = isset($p->class) ? strtolower($p->class) : '';
                if(strpos($cls,'ban')!==false) continue;
                // Determine side
                $side = null; $cur = $p; $iter=0;
                while($cur && $iter<6){
                    $ccls = isset($cur->class)? strtolower($cur->class):'';
                    if(strpos($ccls,'radiant')!==false){ $side='radiant'; break; }
                    if(strpos($ccls,'dire')!==false){ $side='dire'; break; }
                    $cur = $cur->parent(); $iter++;
                }
                // Extract hero name
                $hero_name = '';
                if($p->hasAttribute('data-hero-name')) $hero_name = $p->getAttribute('data-hero-name');
                if(!$hero_name && $p->hasAttribute('title')) $hero_name = trim($p->getAttribute('title'));
                if(!$hero_name){ $im=$p->find('img',0); if($im){ $hero_name=$im->getAttribute('alt')?:$im->getAttribute('title'); } }
                $hero_name = $hero_name ? trim($hero_name) : '';
                if(!$hero_name || !$side) continue;
                $hid = array_search(cn_gt($hero_name),$hero);
                if($hid===false || $hid===null) continue;
                $hh = ['id'=>$hid,'hname'=>$hero_name,'image'=>'','wcc'=>''];
                if($side==='radiant'){ if(sizeof($team1['heroes'])<5) $team1['heroes'][]=$hh; }
                if($side==='dire'){ if(sizeof($team2['heroes'])<5) $team2['heroes'][]=$hh; }
            }
        }
    }

    if(sizeof($team1['heroes']) && sizeof($team2['heroes'])){
        $nm['team1']=$team1; $nm['team2']=$team2; $res_matches[]=$nm;
    }
}

echo 'Games : '.sizeof($res_matches)."<br/>";
foreach($res_matches as $m){
    echo $m['mid']."<br/>";
    $file = $mf.'/gt.'.$m['mid'].'.json';
    if($debug || !file_exists($file)){
        $cond_one=false;$cond_2=false;$cond_3=false;$cond_4=false;$cond_5=false;
        $hero_have_hh=false;$hero_have_anh=false; $nb1=0;$nb2=0;
        $m['team1']['cc_neg']=0;$m['team1']['cc_pos']=0; $m['team2']['cc_neg']=0;$m['team2']['cc_pos']=0;

        for($i=0;$i<min(5,sizeof($m['team1']['heroes']));$i++){
            $m['team1']['heroes'][$i]['wr'] = $h_wr[$m['team1']['heroes'][$i]['id']];
            $nb1 += floatval($h_wr[$m['team1']['heroes'][$i]['id']]);
            $m['team1']['heroes'][$i]['name'] = $h[$m['team1']['heroes'][$i]['id']];
            $nb1a=0; for($a=0;$a<min(5,sizeof($m['team2']['heroes']));$a++){ $nb1a+=floatval($h_wrs[$m['team2']['heroes'][$a]['id']][$m['team1']['heroes'][$i]['id']][0])*-1; }
            $m['team1']['heroes'][$i]['wr_2_success'] = $nb1a > 0 ? false : true;
            $m['team1'][($nb1a > 0 ? 'cc_neg':'cc_pos')]++;
            $m['team1']['heroes'][$i]['wr_2'] = number_format($nb1a, 2, '.', '')*-1;
            $an_t1 = $m['team1']['heroes'][$i]['wr_2'];
            if(sizeof($anh_have)){
                foreach($anh_have as $an){ $anh_f=floatval(str_replace(['-','+'],'',$an)); $cv1=floatval(str_replace(['-','+'],'',$an_t1)); if((strpos($an,'-')===false && strpos($an_t1,'-')===false && $cv1>$anh_f) || (strpos($an,'-')!==false && strpos($an_t1,'-')!==false && $cv1>$anh_f)){ $hero_have_anh=true; break; } }
            }
        }
        for($i=0;$i<min(5,sizeof($m['team2']['heroes']));$i++){
            $m['team2']['heroes'][$i]['wr'] = $h_wr[$m['team2']['heroes'][$i]['id']];
            $nb2 += floatval($h_wr[$m['team2']['heroes'][$i]['id']]);
            $m['team2']['heroes'][$i]['name'] = $h[$m['team2']['heroes'][$i]['id']];
            $nb2a=0; for($a=0;$a<min(5,sizeof($m['team1']['heroes']));$a++){ $nb2a+=floatval($h_wrs[$m['team1']['heroes'][$a]['id']][$m['team2']['heroes'][$i]['id']][0])*-1; }
            $m['team2']['heroes'][$i]['wr_2_success'] = $nb2a > 0 ? false : true;
            $m['team2'][($nb2a > 0 ? 'cc_neg':'cc_pos')]++;
            $m['team2']['heroes'][$i]['wr_2'] = number_format($nb2a, 2, '.', '')*-1;
            $an_t2 = $m['team2']['heroes'][$i]['wr_2'];
            if(sizeof($anh_have)){
                foreach($anh_have as $an){ $anh_f=floatval(str_replace(['-','+'],'',$an)); $cv2=floatval(str_replace(['-','+'],'',$an_t2)); if((strpos($an,'-')===false && strpos($an_t2,'-')===false && $cv2>$anh_f) || (strpos($an,'-')!==false && strpos($an_t2,'-')!==false && $cv2>$anh_f)){ $hero_have_anh=true; break; } }
            }
        }

        $m['team1']['score'] = number_format($nb1, 2, '.', '');
        $m['team2']['score'] = '- '.number_format($nb2, 2, '.', '');
        $m['total'] = number_format(($nb1-$nb2), 2, '.', '');
        $m['total_success'] = ($nb1>$nb2);

        $mets=[]; $total_f = floatval($m['total']);
        if(($total_f<0&&$total_f<$email_if_less)||$total_f>$email_if_greater){ $cond_one=true; $mets[]='Condition 1 is met'; }
        if((!isset($team_have_plus)||!is_array($team_have_plus)||!sizeof($team_have_plus)) ||
           in_array($m['team1']['cc_pos'].'+'.$m['team2']['cc_neg'].'-',$team_have_plus) ||
           in_array($m['team2']['cc_pos'].'+'.$m['team1']['cc_neg'].'-',$team_have_plus) ||
           in_array($m['team1']['cc_pos'].'+'.$m['team2']['cc_pos'].'+',$team_have_plus) ||
           in_array($m['team2']['cc_pos'].'+'.$m['team1']['cc_pos'].'+',$team_have_plus) ||
           in_array($m['team1']['cc_neg'].'-'.$m['team2']['cc_neg'].'-',$team_have_plus) ||
           in_array($m['team2']['cc_neg'].'-'.$m['team1']['cc_neg'].'-',$team_have_plus)){
            $cond_2=true; $mets[]='Condition 2 is met';
        }
        if((!isset($hero_have)||!sizeof($hero_have))||$hero_have_hh){ $cond_3=true; $mets[]='Condition 3 is met'; }
        if((!isset($anh_have)||!sizeof($anh_have))||$hero_have_anh){ $cond_4=true; $mets[]='Condition 4 is met'; }
        $cond_5=true; // TD not applicable

        echo '<pre>'.json_encode($mets,JSON_PRETTY_PRINT).'</pre>';
        if($debug||($cond_one&&$cond_2&&$cond_3&&$cond_4&&$cond_5)){
            $mail = new PHPMailer(true);
            try{
                $mail->SMTPDebug = 0; $mail->isSMTP(); $mail->Host=$smtp_host; $mail->SMTPAuth=true; $mail->Username=$smtp_user; $mail->Password=$smtp_pass; $mail->SMTPSecure=$smtp_pro; $mail->Port=$smtp_port;
                $mail->setFrom($smtp_from, $smtp_from_name);
                if($debug){ $mail->addAddress($email_destination ?: $gt_email ?: $smtp_from); }
                else if(isset($gt_email) && $gt_email){ $mail->addAddress($gt_email); }
                $mail->SMTPOptions = ['ssl'=>['verify_peer'=>false,'verify_peer_name'=>false,'allow_self_signed'=>true]];
                $mail->CharSet='UTF-8'; $mail->isHTML(true);
                $mail->Subject = $m['team1']['name'].' vs '.$m['team2']['name'].' - Game-Tournaments';
                $mail->Body = 'Total: '.$m['total'];
                $mail->AltBody = 'GT match alert';
                $mail->send(); echo 'email sent';
            }catch(Exception $e){ echo 'email error'; }
            echo '<br/>';
        }
        file_put_contents($file, json_encode($m));
    }
}