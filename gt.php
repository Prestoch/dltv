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
$html = str_get_html($gc);
if(!$html){ rdie(['error'=>'List HTML parse error']); }

// Find LIVE matches by span.match-live and extract match ID from its id attribute (live_time_ID)
$links = [];
$lives = $html->find('span[class=match-live]');
foreach($lives as $lv){
    $live_id = '';
    if($lv->hasAttribute('id')){
        $live_id = preg_replace('/[^0-9]/','',$lv->getAttribute('id'));
    }
    $recent_ok = true;
    $scd = $lv->find('span[class=scd]',0);
    if($scd && $scd->hasAttribute('data-time')){
        $dt = intval($scd->getAttribute('data-time'));
        $recent_ok = true; // loosened for testing
    }
    if(!$recent_ok || !$live_id){ continue; }
    $cur = $lv; $row = null; $hop = 0;
    while($cur && $hop<10){ if(isset($cur->tag) && strtolower($cur->tag)==='tr'){ $row=$cur; break; } $cur = $cur->parent(); $hop++; }
    $link = '';
    if($row){
        $as = $row->find('a');
        foreach($as as $a){ if($a->hasAttribute('href')){ $hurl = $a->getAttribute('href'); if(strpos($hurl,'-'.$live_id)!==false){ $link = (strpos($hurl,'http')===0?$hurl:'https://game-tournaments.com'.$hurl); break; } } }
    }
    if(!$link){
        $fa = $html->find('a[href*=-'.$live_id.']',0);
        if($fa && $fa->hasAttribute('href')){ $hurl=$fa->getAttribute('href'); $link = (strpos($hurl,'http')===0?$hurl:'https://game-tournaments.com'.$hurl); }
    }
    if($link && !in_array($link,$links)) $links[] = $link;
}
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
    if(!$c) continue;

    $nm = [];
    $nm['mid'] = $the_id;
    $team1 = ['heroes'=>[],'name'=>'Radiant'];
    $team2 = ['heroes'=>[],'name'=>'Dire'];

    // League (series) title
    $league = '';
    $ser = $c->find('a[itemprop=description][title]',0);
    if($ser && $ser->hasAttribute('title')){ $league = trim($ser->getAttribute('title')); }
    if(!$league){ $ti = $c->find('title',0); if($ti){ $league = trim($ti->plaintext); } }

    // Team names
    $tn_up = $c->find('div[class=team-name-up]',0); if($tn_up){ $team1['name']=trim($tn_up->plaintext); }
    $tn_down = $c->find('div[class=team-name-down]',0); if($tn_down){ $team2['name']=trim($tn_down->plaintext); }

    // Parse heroes
    $blocks = $c->find('div.heroes');
    if(sizeof($blocks)>=1){
        $cards = $blocks[0]->find('div.card');
        foreach($cards as $cd){
            $ht=$cd->find('div.hero-title',0); $img=$cd->find('img',0);
            if($ht){ $name=trim($ht->plaintext); $hid=array_search(cn_gt($name),$hero); $imgSrc=''; if($img){ $src=$img->getAttribute('src'); if($src){ $imgSrc = strpos($src,'http')===0?$src:('https://game-tournaments.com'.$src); } } if($hid!==false && $hid!==null && sizeof($team1['heroes'])<5){ $team1['heroes'][]=['id'=>$hid,'hname'=>$name,'image'=>$imgSrc,'wcc'=>'']; } }
        }
    }
    if(sizeof($blocks)>=2){
        $cards = $blocks[1]->find('div.card');
        foreach($cards as $cd){
            $ht=$cd->find('div.hero-title',0); $img=$cd->find('img',0);
            if($ht){ $name=trim($ht->plaintext); $hid=array_search(cn_gt($name),$hero); $imgSrc=''; if($img){ $src=$img->getAttribute('src'); if($src){ $imgSrc = strpos($src,'http')===0?$src:('https://game-tournaments.com'.$src); } } if($hid!==false && $hid!==null && sizeof($team2['heroes'])<5){ $team2['heroes'][]=['id'=>$hid,'hname'=>$name,'image'=>$imgSrc,'wcc'=>'']; } }
        }
    }
    if(sizeof($team1['heroes']) && sizeof($team2['heroes'])){
        $nm['league'] = $league;
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

        // Email body: League title on top, slightly larger icons, original per-hero WR + advantage, larger Total
        $leagueTitle = isset($m['league']) && $m['league'] ? $m['league'] : (($m['team1']['name']??'Radiant').' vs '.($m['team2']['name']??'Dire'));
        $gh = '<div style="width:680px;max-width:100%;border:1px solid #ccc;padding:16px;font-family:Arial,Helvetica,sans-serif;">';
        $gh.= '<h2 style="margin:0 0 12px 0;">'.htmlspecialchars($leagueTitle).'</h2>';
        $gh.='<div style="margin:4px 0 6px 0;font-weight:bold;">'.htmlspecialchars(($m['team1']['name']??'Radiant')).'</div>';
        $gh.='<div style="display:flex;gap:10px;">';
        for($i=0;$i<min(5,sizeof($m['team1']['heroes']));$i++){
            $hi=$m['team1']['heroes'][$i];
            $gh.='<div style="width:110px;text-align:center;font-size:12px;">';
            if(!empty($hi['image'])){ $gh.='<img src="'.htmlspecialchars($hi['image']).'" style="width:72px;height:auto;border-radius:4px;">'; }
            $gh.='<div style="margin-top:2px">'.htmlspecialchars($hi['name']).'</div>';
            $gh.='<div style="font-size:14px">'.htmlspecialchars($hi['wr']).' + <span style="'.(($hi['wr_2_success']??true)?'color:green;':'color:red;').'">'.htmlspecialchars($hi['wr_2']).'</span></div>';
            $gh.='</div>';
        }
        $gh.='</div>';
        $gh.='<hr style="border:none;border-top:1px solid #eee;margin:10px 0;">';
        $gh.='<div style="margin:4px 0 6px 0;font-weight:bold;">'.htmlspecialchars(($m['team2']['name']??'Dire')).'</div>';
        $gh.='<div style="display:flex;gap:10px;">';
        for($i=0;$i<min(5,sizeof($m['team2']['heroes']));$i++){
            $hi=$m['team2']['heroes'][$i];
            $gh.='<div style="width:110px;text-align:center;font-size:12px;">';
            if(!empty($hi['image'])){ $gh.='<img src="'.htmlspecialchars($hi['image']).'" style="width:72px;height:auto;border-radius:4px;">'; }
            $gh.='<div style="margin-top:2px">'.htmlspecialchars($hi['name']).'</div>';
            $gh.='<div style="font-size:14px">'.htmlspecialchars($hi['wr']).' + <span style="'.(($hi['wr_2_success']??true)?'color:green;':'color:red;').'">'.htmlspecialchars($hi['wr_2']).'</span></div>';
            $gh.='</div>';
        }
        $gh.='</div>';
        $gh.='<div style="margin-top:12px;font-size:32px;'.($m['total_success']?'color:green;':'color:red;').'">Total: '.htmlspecialchars($m['total']).'</div>';
        $gh.='</div>';

        $mets=[]; $total_f = floatval($m['total']);
        if(($total_f<0&&$total_f<$email_if_less)||$total_f>$email_if_greater){ $cond_one=true; $mets[]='Condition 1 is met'; }
        if((!isset($team_have_plus)||!is_array($team_have_plus)||!sizeof($team_have_plus)) ||
           in_array($m['team1']['cc_pos'].'+'.$m['team2']['cc_neg'].'-',$team_have_plus) ||
           in_array($m['team2']['cc_pos']+'+'+$m['team1']['cc_neg'].'-',$team_have_plus) ||
           in_array($m['team1']['cc_pos'].'+'.$m['team2']['cc_pos'].'+',$team_have_plus) ||
           in_array($m['team2']['cc_pos'].'+'.$m['team1']['cc_pos'].'+',$team_have_plus) ||
           in_array($m['team1']['cc_neg'].'-'.$m['team2']['cc_neg'].'-',$team_have_plus) ||
           in_array($m['team2']['cc_neg'].'-'.$m['team1']['cc_neg'].'-',$team_have_plus)){
            $cond_2=true; $mets[]='Condition 2 is met';
        }
        if((!isset($hero_have)||!sizeof($hero_have))||$hero_have_hh){ $cond_3=true; $mets[]='Condition 3 is met'; }
        if((!isset($anh_have)||!sizeof($anh_have))||$hero_have_anh){ $cond_4=true; $mets[]='Condition 4 is met'; }
        $cond_5=true;

        if($debug){ echo '<pre>'.json_encode($mets,JSON_PRETTY_PRINT).'</pre>'; }
        if($debug||($cond_one&&$cond_2&&$cond_3&&$cond_4&&$cond_5)){
            $mail = new PHPMailer(true);
            try{
                $mail->SMTPDebug = 0; $mail->isSMTP(); $mail->Host=$smtp_host; $mail->SMTPAuth=true; $mail->Username=$smtp_user; $mail->Password=$smtp_pass; $mail->SMTPSecure=$smtp_pro; $mail->Port=$smtp_port;
                $mail->setFrom($smtp_from, $smtp_from_name);
                if($debug){ $mail->addAddress($email_destination ?: $gt_email ?: $smtp_from); }
                else if(isset($gt_email) && $gt_email){ $mail->addAddress($gt_email); }
                $mail->SMTPOptions = ['ssl'=>['verify_peer'=>false,'verify_peer_name'=>false,'allow_self_signed'=>true]];
                $mail->CharSet='UTF-8'; $mail->isHTML(true);
                $league = isset($m['league'])?$m['league']:'';
                $mail->Subject = ($m['team1']['name']??'Radiant').' vs '.($m['team2']['name']??'Dire').($league?(' - '.$league):'');
                $mail->Body = $gh; $mail->AltBody = 'GT match alert';
                $mail->send(); if($debug) echo 'email sent';
            }catch(Exception $e){ if($debug) echo 'email error'; }
            if($debug) echo '<br/>';
        }
        file_put_contents($file, json_encode($m));
    }
}