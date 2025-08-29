<?php
$dbhost = 'localhost';
$dbname = 'u1552031_dotabuff';
$dbuser = 'u1552031_dotabuf';
$dbpass = 'tinidor221@@';
try{
	$con = new PDO('mysql:host='.$dbhost.';dbname='.$dbname.';charset=utf8',$dbuser,$dbpass);
	$con->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
}catch(PDOException $e){
	echo $e->getMessage();
	die();
}
$smtp_host = 'mail.gametrench.ru';
$smtp_user = 'gametrench@gametrench.ru';
$smtp_pass = 'tinidor221@@';
$smtp_port = 587;
$smtp_pro = 'tls';

$smtp_from = 'gametrench@gametrench.ru';
$smtp_from_name = 'GameTrench';

// notification reciever
$email_destination = '';
$cyber_email = '';
$dltv_email = '';
$egw_email = '';
$email_if_less = -5;
$email_if_greater = 5;

// create new $team_have_plus[] = '5-5+';  if you want to add or delete the line if you want to remove
$team_have_plus = [];
$hero_have = [];
$anh_have = [];
$td_g = '';
$td_l = '';
$prox = [];
$scr_token = false;
if(file_exists('settings/set.json')){
	$set = json_decode(file_get_contents('settings/set.json'),true);
	if(is_array($set)){
		$team_have_plus = $set['pns'];
		$email_destination = $set['email']['add'];
		$cyber_email = $set['cyber_email'];
		$dltv_email = $set['dltv_email'];
		$egw_email = $set['egw_email'];
		$hero_have = $set['hh'];
		$email_if_less = (int) $set['less'];
		$email_if_greater = (int) $set['greater'];
		$anh_have = isset($set['anh']) ? $set['anh'] : [];
		if(isset($set['td_g'])){
			$td_g = floatval($set['td_g']);
		}
		if(isset($set['td_l'])){
			$td_l = floatval($set['td_l']);
		}
		if(isset($set['prox'])){
			$prox = $set['prox'];
		}
		if(isset($set['scr_token'])){
			$scr_token = $set['scr_token'];
		}
	}
}

function get_proxy(){
	global $prox;
	$vs = [];
	foreach($prox as $k => $v){
		// if($v['status']){
		// 	$vs[] = $v;
		// }
        $vs[] = $v;
	}
    if(!sizeof($vs)){
        return [];
    }
	return $vs[array_rand($vs)];
	return [];
}

$the_prox = get_proxy();


function get_html($a){
	global $scr_token;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    $token = "9245411e295947a2b8d5da35198bfd39c7cc5d2d5ce";
	if($scr_token){
		$token = $scr_token;
	}
    $proxy = sprintf("http://%s:@proxy.scrape.do:8080", $token);
    curl_setopt($curl, CURLOPT_URL, $a);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($curl, CURLOPT_PROXY, $proxy);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        "Accept: */*",
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}


function get_html_V2($a){
	global $the_prox,$prox,$set;
	// $proxy = '198.23.239.134:6540';
	// $user = 'ugygnbms';
	// $pass = 'zuzdrj27ia52';

	$the_prox = get_proxy();

    if(!sizeof($the_prox)){

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        $url = "https://httpbin.co/anything";
        $token = "70e1a6a0c8b746369c03d91bb6a2e622becd4286d44";
        $proxy = sprintf("http://%s:@proxy.scrape.do:8080", $token);
        curl_setopt($curl, CURLOPT_URL, $a);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_PROXY, $proxy);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Accept: */*",
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
        return '';
    }

	$ch = curl_init($a);

	if(isset($the_prox['ip'])){
		echo 'using proxy : '.$the_prox['ip'].':'.$the_prox['port'].'<br/>';
		curl_setopt($ch, CURLOPT_PROXY, $the_prox['ip'].':'.$the_prox['port']);
		curl_setopt($ch, CURLOPT_PROXYUSERPWD,$the_prox['user'].':'.$the_prox['pass']);
	}

	//curl_setopt($ch, CURLOPT_REFERER,$a);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0");
	curl_setopt($ch, CURLOPT_URL, $a);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt ($ch, CURLOPT_REFERER, $a);
	$output = curl_exec ($ch);
	curl_close($ch);
	//$output = '';
	if(!$output){
		if(isset($the_prox['ip'])&&isset($prox[$the_prox['ip']])){
			$prox[$the_prox['ip']]['status'] = false;
			$set['prox'] = $prox;
			file_put_contents('settings/set.json',json_encode($set));
			//pre($set);die();
		}
	}
	return $output;
}