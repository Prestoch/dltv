<?php
require_once 'a.php';
?>
<!DOCTYPE html>
<html>
<head>
<title>D2 Settings</title>
<meta content="width=device-width, initial-scale=1" name="viewport" />
<!-- CSS only -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-0evHe/X+R7YkIZDRvuzKMRqM+OrBnVFBL6DOitfPri4tjfHxaWutUpFmBp4vmVor" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<link rel="stylesheet" href="a.css?a=<?php echo uniqid();?>">
<link rel="stylesheet" href="//code.jquery.com/ui/1.13.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.1/jquery-ui.js"></script>
</head>
<body>

<div class="container-fluid pt-3">
	<div class="row mb-5">
		<div class="col-md-12">
			<div class="card">
				<div class="card-header">
					DotaBuff
				</div>
				<div class="card-body">
					<button type="button" class="btn btn-success" id="gcs">Generate new cs.json</button>
				</div>
			</div>
		</div>
	</div>
	<div class="row mb-5">
		<div class="col-md-12">
			<div class="card">
				<div class="card-header">
					Email Notification
				</div>
				<div class="card-body">
					<div class="row">
						<!--
						<div class="col-md-3">
							<div class="form-group">
								<label>From Name</label>
								<input type="text" class="form-control" id="email_from" value="<?php _e(isset($set['email'])&&isset($set['email']['from'])?$set['email']['from']:'Razorgame Fun');?>">
							</div>
						</div>
						-->
						<div class="col-md-3">
							<div class="form-group">
								<label>Address</label>
								<input type="email" class="form-control" id="email_add" value="<?php _e(isset($set['email'])&&isset($set['email']['add'])?$set['email']['add']:'razorgamefun@gmail.com');?>">
							</div>
						</div>
						<div class="col-md-3">
							<div class="form-group">
								<label>Cyber Address</label>
								<input type="email" class="form-control" id="cyber_email" value="<?php _e(isset($set['cyber_email'])?$set['cyber_email']:'razorgamefun@gmail.com');?>">
							</div>
						</div>
						<div class="col-md-3">
							<div class="form-group">
								<label>DLTV Address</label>
								<input type="email" class="form-control" id="dltv_email" value="<?php _e(isset($set['dltv_email'])?$set['dltv_email']:'razorgamefun@gmail.com');?>">
							</div>
						</div>
						<div class="col-md-3">
							<div class="form-group">
								<label>EGW Address</label>
								<input type="email" class="form-control" id="egw_email" value="<?php _e(isset($set['egw_email'])?$set['egw_email']:'razorgamefun@gmail.com');?>">
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<div class="row mb-5">
		<div class="col-md-12">
			<div class="card">
				<div class="card-header">
					Greater / Less
				</div>
				<div class="card-body">
					<div class="row">
						
						<div class="col-md-1">
							<div class="form-group">
								<label>If Greater</label>
								<input type="text" class="form-control" id="greater" value="<?php _e(isset($set['greater'])?$set['greater']:'');?>" placeholder="5">
							</div>
						</div>
						
						<div class="col-md-1">
							<div class="form-group">
								<label>If Less</label>
								<input type="text" class="form-control" id="less" value="<?php _e(isset($set['less'])?$set['less']:'');?>" placeholder="-5">
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	
	<div class="row mb-5">
		<div class="col-md-12">
		
			<div class="card">
				<div class="card-header">
					Positive / Negative
				</div>
				<div class="card-body">
					<div id="pnbase" class="pnbase mb-3">
					<!--
					<select>
					<?php for($i=1;$i<6;$i++){
						for($ii=1;$ii<6;$ii++){
							echo '<option>'.$i.'+'.$ii.'-</option>';
							echo '<option>'.$i.'-'.$ii.'+</option>';
						}
					}?>
					</select>
					-->
					<?php if(isset($set)&&isset($set['pns'])){
						foreach($set['pns'] as $pn){?>
						<div class="box">
							<div class="input-group mb-3">
							<!--<input class="form-control pns" type="text" value="<?php _e($pn);?>">-->
							<select class="form-control pns">
							<?php for($i=1;$i<6;$i++){
								for($ii=1;$ii<6;$ii++){
									$v1 = $i.'+'.$ii.'-';
									$v2 = $i.'-'.$ii.'+';
									echo '<option value="'.$v1.'"'.($pn === $v1 ? ' selected':'').'>'.$v1.'</option>';
									echo '<option value="'.$v2.'"'.($pn === $v2 ? ' selected':'').'>'.$v2.'</option>';
								}
							}?>
							</select>
							<button class="btn btn-danger" type="button" onclick="del_pn(this)">x</button>
							</div>
						</div>
					<?php }}?>
					</div>
					<button type="button" class="btn btn-primary" onclick="add_pn(this)">Add New</button>
				</div>
			</div>
		</div>
	</div>
	
	<div class="row mb-5">
		<div class="col-md-12">
		
			<div class="card">
				<div class="card-header">
					Have Hero
				</div>
				<div class="card-body">
					<div id="hbase">
					<?php if(isset($set['hh'])&&is_array($set['hh'])){
						foreach($set['hh'] as $id){?>
						<div class="hb_box hb_<?php _e($hero[$id]);?>" data-id="<?php _e($id);?>"><img src="<?php _e(bg($id));?>"><label><?php _e(isset($hero[$id])?ucfirst($hero[$id]):'');?></label><button class="btn btn-danger" type="button" onclick="del_hb(this)">X</button></div>
					<?php }}?>
					</div>
					<div class="row">
						<div class="col-md-2">
							<div class="form-group">
								<input type="text" id="sh" class="form-control" placeholder="Search Hero">
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row mb-5">
		<div class="col-md-12">
		
			<div class="card">
				<div class="card-header">
					Any Hero Have % Less or Greater ( if positive dont put ( + ) sign )
				</div>
				<div class="card-body">
					<div id="anh_base" class="pnbase">
					<?php if(isset($set)&&isset($set['anh'])){
						foreach($set['anh'] as $pn){?>
						<div class="box">
							<div class="input-group mb-3">
							<input class="form-control anh_item" type="text" value="<?php _e($pn);?>">
							<button class="btn btn-danger" type="button" onclick="del_anh(this)">x</button>
							</div>
						</div>
					<?php }}?>
					</div>
					<button type="button" class="btn btn-primary" onclick="add_anh(this)">Add New</button>
				</div>
			</div>
		</div>
	</div>
	
	<div class="row mb-5">
		<div class="col-md-12">
		
			<div class="card">
				<div class="card-header">
					Tower Damage ( if team has )
				</div>
				<div class="card-body">
					<div class="row">
						<div class="col-md-1">
							<div class="form-group">
								<label>Greater than</label>
								<input type="number" class="form-control" id="td_g" style="width:100px;" value="<?php echo isset($set['td_g']) ? $set['td_g'] : '';?>">
							</div>
						</div>
						<div class="col-md-1">
							<div class="form-group">
								<label>Less than</label>
								<input type="number" class="form-control" id="td_l" style="width:100px;" value="<?php echo isset($set['td_l']) ? $set['td_l'] : '';?>">
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="row mb-5">
		<div class="col-md-12">
			
			<div class="card">
				<div class="card-header">
					Scrape.do Token
				</div>
				<div class="card-body">
					<div class="row">
						<div class="col-md-4">
							<div class="form-group">
								<label>Token</label>
								<input type="email" class="form-control" id="scr_token" placeholder="token" <?php echo isset($set['scr_token']) ? ' value="'.$set['scr_token'].'"':'';?>>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
						
	</div>

	<div class="row mb-5 d-none">
		<div class="col-md-12">
		
			<div class="card">
				<div class="card-header">
							Proxies
				</div>
				<div class="card-body">
					<!-- <div class="row"> -->
						<input type="file" accept=".txt" name="prox" id="prox">

						<br/>
						<br/>
						<button type="button" id="rem_x_prox" class="btn btn-danger">Remove all not working</button>
						<br/><br/>
						<table class="table ms-2" style="width:auto;font-size:14px;">
							<tr>
								<th>IP</th>
								<th>PORT</th>
								<th>USER</th>
								<th>PASS</th>
								<th>Status</th>
							</tr>
						<?php
						if(isset($set['prox'])){
							foreach($set['prox'] as $k => $v){
								echo '<tr class="text-white '.(!$v['status']?'bg-danger':'bg-success').'">';
								?>
									<td ><?php echo $v['ip'];?></td>
									<td><?php echo $v['port'];?></td>
									<td><?php echo $v['user'];?></td>
									<td><?php echo $v['pass'];?></td>
									<td><?php echo $v['status']?'Working':'Not Working';?></td>
								</tr>
								<?php
							}
						}
						?>
						</table>
					<!-- </div> -->
				</div>
			</div>
		</div>
	</div>
	
</div>

<div class="sb_base">
	<button type="button" class="btn btn-success btn-lg" onclick="save(this)">Save</button>
</div>

<script>
const hero = ["<?php _e(implode('","',$hero));?>"];
const _id = e => { return document.getElementById(e) }
const _cll = e => { return document.getElementsByClassName(e) }
const _cre = e => { return document.createElement(e) }

_id('rem_x_prox').addEventListener('click',()=>{
	_id('rem_x_prox').disabled = true;
	let n = new XMLHttpRequest();
	let f = new FormData();
	f.append('rem_x_prox',true);
	n.onreadystatechange=function(){
		if(n.readyState===4){
			location.reload();
		}
	}
	n.open('POST','',true);
	n.send(f);
});

const add_anh = b => {
	let nb = _cre('div');
	nb.className = 'box';
	
	let nb_box = _cre('div');
		nb_box.className = 'input-group mb-3';
		
		let nbi = _cre('input');
			nbi.className = 'form-control anh_item';
			nbi.type = 'text';
		nb_box.appendChild(nbi);
		
		let nbb = _cre('button');
			nbb.className = 'btn btn-danger';
			nbb.type = 'button';
			nbb.innerHTML = 'x';
			nbb.setAttribute('onclick','del_anh(this)');
		nb_box.appendChild(nbb);
	nb.appendChild(nb_box);
		
	_id('anh_base').appendChild(nb);
};

const add_pn = b => {
	let nb = _cre('div');
	nb.className = 'box';
	
	let nb_box = _cre('div');
		nb_box.className = 'input-group mb-3';
		/*
		let nbi = _cre('input');
			nbi.className = 'form-control pns';
			nbi.type = 'text';
		nb_box.appendChild(nbi);
		*/
		
		let nbi = _cre('select');
			nbi.className = 'form-control pns';
			for(i=1;i<6;i++){
				for(ii=1;ii<6;ii++){
					let v1s = _cre('option');
					v1s.innerHTML = i+'+'+ii+'-';
					nbi.appendChild(v1s);
					
					let v2s = _cre('option');
					v2s.innerHTML = i+'-'+ii+'+';
					nbi.appendChild(v2s);
				}
			}
			//nbi.type = 'text';
		nb_box.appendChild(nbi);
		
		let nbb = _cre('button');
			nbb.className = 'btn btn-danger';
			nbb.type = 'button';
			nbb.innerHTML = 'x';
			nbb.setAttribute('onclick','del_pn(this)');
		nb_box.appendChild(nbb);
	nb.appendChild(nb_box);
		
	_id('pnbase').appendChild(nb);
};
const del_anh = b => {
	if(!confirm('Are you sure you want to remove this?')){
		return false;
	}
	let tb = b.parentNode.parentNode;
	tb.parentNode.removeChild(tb);
};
const del_pn = b => {
	if(!confirm('Are you sure you want to remove this?')){
		return false;
	}
	let tb = b.parentNode.parentNode;
	tb.parentNode.removeChild(tb);
};
const save = b => {
	
	b.disabled = true;
	b.innerHTML = 'Saving...';
	
	let net = new XMLHttpRequest();
	let form = new FormData();
		form.append('s',true);
	
	let pns = [];
	Array.from(_cll('pns')).forEach(i=>{
		if(i.value){
			pns.push(i.value.replace(/ /g,''));
		}
	});
	
	let anh_items = [];
	Array.from(_cll('anh_item')).forEach(i=>{
		if(i.value){
			anh_items.push(i.value.replace(/ /g,''));
		}
	});
	form.append('anh_items',JSON.stringify(anh_items));
	
	form.append('pns',JSON.stringify(pns));
	if(_id('prox')&&_id('prox').files.length){
		form.append('prox',_id('prox').files[0]);
	}
	/*
	if(_id('email_from')&&_id('email_from').value){
		form.append('email_from',_id('email_from').value);
	}
	*/
	if(_id('email_add')&&_id('email_add').value){
		form.append('email_add',_id('email_add').value);
	}

	if(_id('scr_token')&&_id('scr_token').value){
		form.append('scr_token',_id('scr_token').value);
	}
	
	form.append('td_g',_id('td_g').value ? _id('td_g').value : '');
	form.append('td_l',_id('td_l').value ? _id('td_l').value : '');
	
	let hrs = [];
	Array.from(_cll('hb_box')).forEach(h=>{
		if(h.getAttribute('data-id')){
			hrs.push(h.getAttribute('data-id'));
		}
	});
	form.append('hrs',JSON.stringify(hrs));
	form.append('greater',_id('greater').value);
	form.append('less',_id('less').value);
	form.append('cyber_email',_id('cyber_email').value);
	form.append('dltv_email',_id('dltv_email').value);
	form.append('egw_email',_id('egw_email').value);
	net.onreadystatechange = function(){
		if(net.readyState===4){
			b.disabled = false;
			b.innerHTML = 'Save';
			let res = net.responseText;
			console.log(res);
			alert('Saved');
		}
	}
	
	net.open('POST','',true);
	net.send(form);
};
const del_hb = b => {
	if(!confirm('Are you sure you want to remove this?')){
		return false;
	}
	let a = b.parentNode;
	a.parentNode.removeChild(a);
};
$(function(){
    $("#sh").autocomplete({
		source: function(request, response) {
			var results = $.ui.autocomplete.filter(hero, request.term);
			response(results.slice(0, 5));
		},
		select: function (event, ui) {
			_id('sh').disabled = true;
			let v = ui.item.value;
			let net = new XMLHttpRequest();
			let form = new FormData();
			form.append('ghd',true);
			form.append('h',v);
			net.onreadystatechange = function(){
				if(net.readyState===4){
					_id('sh').disabled = false;
					_id('sh').value = '';
					let r = JSON.parse(net.responseText);
					if(_cll('hb_'+v).length){
						alert('Hero exists');
					}
					if(r.hero&&!_cll('hb_'+v).length){
						let da = _cre('div');
							da.className = 'hb_box hb_'+v;
							da.setAttribute('data-id',r.id);
							let db = _cre('img');
								db.src = r.bg;
							da.appendChild(db);
							
							let dc = _cre('label');
							dc.innerHTML = r.hero;
							da.appendChild(dc);
							
							let dd = _cre('button');
								dd.className = 'btn btn-danger';
								dd.type = 'button';
								dd.setAttribute('onclick','del_hb(this)');
								dd.innerHTML = 'X';
							da.appendChild(dd);
							
						_id('hbase').appendChild(da);
					}
				}
			}
			net.open('POST','',true);
			net.send(form);
		}
    });
});
let let_run = true;
let is_first = true;
const generate_cs = () => {
	let n = new XMLHttpRequest();
	n.onreadystatechange=function(){
		if(n.readyState===4){
			if(is_first){
				is_first = false;
			}
			try{
				let r = JSON.parse(n.responseText);
				console.log(r);
				if(r.status=='generating'){
					_id('gcs').innerHTML = _id('gcs').getAttribute('data-old')+' '+Math.round((100 * parseInt(r.current)) / parseInt(r.len))+'%';
				}else if(r.status=='done'){
					_id('gcs').innerHTML = _id('gcs').getAttribute('data-old');
					_id('gcs').disabled = false;
					let_run = false;
					is_first = true;
					alert('Generating done.');
				}
			}catch(e){

			}
			if(let_run){
				generate_cs();
			}else{
				_id('gcs').disabled = false;
			}
		}
	}
	n.open('GET','../wrg/g.php'+(is_first?'?force=true':''),true);
	n.send();
}
_id('gcs').addEventListener('click',()=>{
	let_run = true;
	generate_cs();
	_id('gcs').disabled = true;
	_id('gcs').setAttribute('data-old',_id('gcs').innerText);
});
</script>

</body>
</html>