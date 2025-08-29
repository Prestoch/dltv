<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'shd.php';
$set_folder = 'sets';
$sfl = dirname(__FILE__).'/'.$set_folder;
if(!file_exists($sfl)){
    mkdir($sfl,0777);
}
function gc($u){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$u);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0');
    $o = curl_exec($ch);
    curl_close($ch);
    return $o;
}
if(!function_exists('pre')){
    function pre($a){
        echo '<pre>'.json_encode($a,JSON_PRETTY_PRINT).'</pre>';
    }
}
function generate_new_set($sl){
 
    if(file_exists($sl)){
        return @json_decode(file_get_contents($sl),true);
    }

    $html = str_get_html(gc('https://www.dotabuff.com/heroes?show=heroes&view=meta&mode=all-pick&date=7.39'));
    $trs = $html->find('tr[class*=tw-border-b tw-transition-colors hover:tw-bg-muted/50 data-[state=selected]:tw-bg-muted]');
    $d = [];
    foreach($trs as $tr){
        $img = $tr->find('img[src*=/assets/heroes]');
        if(isset($img[0])){
            $img_src = $img[0]->src;
            $hero_name_f = $tr->find('div[class*=tw-flex tw-flex-col tw-gap-0]');
            $href = $tr->find('a[href*=/heroes/]')[0]->href;
            preg_match('/\/heroes\/(.*?).jpg$/',$img_src,$alt);
            if(isset($hero_name_f[0])){
                $hero_name = preg_replace("/[^A-Za-z0-9 ]/","",htmlspecialchars_decode($hero_name_f[0]->plaintext,ENT_QUOTES));
                $d[$hero_name] = [
                    'bg'=>$img_src,
                    'wr'=>trim(str_replace('%','',$tr->find('td')[2]->find('span')[0]->plaintext)),
                    'href'=>$href,
                    'alt'=>$alt[1]
                ];
            }
        }
    }
    ksort($d);
    file_put_contents($sl,json_encode($d));
    return $d;
}
$set_loc = $sfl.'/'.date('Ymd').'set.json';
if(isset($_GET['force'])&&$_GET['force']){
    @unlink($set_loc);
}
$new_set = generate_new_set($set_loc);
//pre($new_set);die();
$len = sizeof(array_keys($new_set));
$ss = 0;
foreach($new_set as $k => $v){
    if(!isset($v['wrs'])){
        $u = 'https://www.dotabuff.com/heroes/'.$v['alt'].'/counters?date=patch_7.39';
        $gc = gc($u);
        $html = str_get_html($gc);
        $table = false;
        foreach($html->find('table') as $t){
            $pt = preg_match('/matches played/',strtolower($t->plaintext),$pts);
            if(sizeof($pts)){
                $table = $t;
                break;
            }
        }
        $aa = [];
        $aa[$k] = null;
        foreach($table->find('tr') as $tr){
            $hns = $tr->find('a[class*=link-type-hero]');
            if($hns&&isset($hns[0])){
                $hero_name = $hns[0]->plaintext;
                $dd = [];
                foreach($tr->find('td') as $td){
                    if(isset($td->attr['data-value'])){
                        preg_match("/[a-z]/i", $td->attr['data-value'],$tda);
                        if(!sizeof($tda)){
                            $dd[] = $td->attr['data-value'];
                        }
                    }
                }
                $aa[$hero_name] = $dd;
            }
        }
        ksort($aa);
        $new_wrs = [];
        foreach($aa as $k2 => $v2){
            $new_wrs[] = $v2;
        }
        $new_set[$k]['wrs'] = $new_wrs;
        file_put_contents($set_loc,json_encode($new_set));
        break;
    }
    $ss++;
}
$ee = [];
$ee['status'] = 'generating';
$ee['current'] = $ss;
$ee['len'] = $len;

if($ss>=$len){
    $ee['status'] = 'converting to js';
    $heroes = [];
    $heroes_bg = [];
    $heroes_wr = [];
    $win_rates = [];
    foreach($new_set as $k =>$v){
        $heroes[] = $k;
        $heroes_bg[] = $v['bg'];
        $heroes_wr[] = $v['wr'];
        $win_rates[] = $v['wrs'];
    }
    $aa = 'var heroes = '.json_encode($heroes).', heroes_bg = '.json_encode($heroes_bg).', heroes_wr = '.json_encode($heroes_wr).', win_rates = '.json_encode($win_rates).', update_time = "'.date('Y-m-d').'", new_generator = true;';
    $cs_loc = $sfl.'/cs.json';
    $cs_copy = dirname(dirname($sfl)).'/cs.json';
    file_put_contents($cs_loc,$aa);
    copy($cs_loc,$cs_copy);
    $ee['cs_loc'] = $cs_loc;
    $ee['cs_copy'] = $cs_copy;
    $ee['status'] = 'done';
}
echo json_encode($ee);
