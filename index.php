<?php
/* ===== CONFIG ===== */
$DB=[
    'host'=>getenv('DB_HOST')?:'localhost',
    'port'=>getenv('DB_PORT')?:'4000', // TiDB default port
    'name'=>getenv('DB_NAME')?:'birthday_galaxy',
    'user'=>getenv('DB_USER')?:'root',
    'pass'=>getenv('DB_PASS')?:''
];
$MUSIC='assets/music/the-only-exception.mp3'; $MUSIC2='assets/music/when-i-met-you.mp3'; $SONG='The Only Exception';
$PASSWORD='4311';

function h($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function src($f){
  if(strpos($f,'dummy:')===0){
    $n=(int)substr($f,6);$c=['#150a33','#0a1a3a','#24103f','#0c2147','#1d0d3a','#0b1c3d'];$bg=$c[($n-1)%6];
    $svg='<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 750"><rect width="600" height="750" fill="'.$bg.'"/><path d="M0 750L190 520l120 110 120-80 170 200z" fill="#fff" opacity=".07"/><circle cx="460" cy="160" r="52" fill="#fff" opacity=".12"/><text x="300" y="380" font-size="64" fill="#b9a6ff" text-anchor="middle" font-family="Arial" font-weight="bold">PHOTO '.$n.'</text><text x="300" y="440" font-size="26" fill="#8a7fc0" text-anchor="middle" font-family="Arial">upload your picture</text></svg>';
    return 'data:image/svg+xml;utf8,'.rawurlencode($svg);
  } return h($f);
}
$S=['name'=>'Allan Mari Castigador','birthday'=>'2005-11-20','headline'=>'Turning 21 and so loved','message'=>'Import database.sql in phpMyAdmin to load your content.','greeting'=>"Dear Allan,\n\nImport database.sql to load the full letter.",'secret_letter'=>'Happy Birthday!'];
$P=[];$wishes=[];$pdo=null;

try{
  /* ===== TIDB CONNECTION WITH SSL ===== */
  $options = [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/certs/ca-certificates.crt',
      PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
  ];
  $pdo=new PDO("mysql:host={$DB['host']};port={$DB['port']};dbname={$DB['name']};charset=utf8mb4",$DB['user'],$DB['pass'],$options);

  /* ===== UPLOAD HANDLER ===== */
  if($_SERVER['REQUEST_METHOD']==='POST' && isset($_FILES['image']) && isset($_POST['frame_index'])){
    $idx=(int)$_POST['frame_index'];$file=$_FILES['image'];
    $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
    header('Content-Type: application/json');
    if(in_array($ext,['jpg','jpeg','png','gif','webp'])){
      $dir=__DIR__.'/assets/img/uploads/';if(!is_dir($dir))mkdir($dir,0777,true);
      $fname='img_'.$idx.'_'.time().'.'.$ext;
      if(move_uploaded_file($file['tmp_name'],$dir.$fname)){
        $path='assets/img/uploads/'.$fname;
        $chk=$pdo->prepare("SELECT COUNT(*) FROM photos WHERE sort_order=?");$chk->execute([$idx]);
        if($chk->fetchColumn()>0)$pdo->prepare("UPDATE photos SET file=? WHERE sort_order=?")->execute([$path,$idx]);
        else $pdo->prepare("INSERT INTO photos (title, caption, file, sort_order) VALUES (?,?,?,?)")->execute(['Memory '.$idx,'Uploaded photo',$path,$idx]);
        echo json_encode(['ok'=>1,'url'=>$path]);exit;
      }
    }
    echo json_encode(['ok'=>0]);exit;
  }
  if($_SERVER['REQUEST_METHOD']==='POST'){
    $n=mb_substr(trim($_POST['name']??''),0,60);$m=mb_substr(trim($_POST['message']??''),0,500);header('Content-Type: application/json');
    if($n!==''&&$m!==''){$pdo->prepare("INSERT INTO wishes(name,message) VALUES(?,?)")->execute([$n,$m]);echo json_encode(['ok'=>1,'name'=>$n,'message'=>$m]);}else echo json_encode(['ok'=>0]);
    exit;
  }
  if($r=$pdo->query("SELECT * FROM settings WHERE id=1")->fetch())$S=$r;
  $P=$pdo->query("SELECT * FROM photos ORDER BY sort_order,id")->fetchAll();
  $wishes=$pdo->query("SELECT * FROM wishes ORDER BY id DESC")->fetchAll();
}catch(Throwable $e){$dbError=true;}
for($i=count($P);$i<22;$i++)$P[]=['title'=>'Photo '.($i+1),'caption'=>'Add your caption','file'=>'dummy:'.($i+1)];
$FIRST=strtok($S['name'],' ');
$fn=strtolower(preg_replace('/[^a-z]/i','',$FIRST))?:'player';

/* ===== helpers ===== */
function pic($P,$i,$cls='',$st=''){$p=$P[$i];$s=src($p['file']);
  return '<figure class="ph '.$cls.'" style="'.$st.'" data-full="'.$s.'" data-cap="'.h($p['title'].' — '.$p['caption']).'"><img src="'.$s.'" alt="'.h($p['title']).'" loading="lazy"><div class="up-ov"><input type="file" accept="image/*" onchange="uploadImage(this,'.$i.')"><span>UPLOAD PHOTO</span></div></figure>';}
function fr($P,$i,$t,$k=0,$st=''){$p=$P[$i];$s=src($p['file']);
  return '<figure class="ph fr '.$t.'" style="--i:'.$k.';'.$st.'" data-full="'.$s.'" data-cap="'.h($p['title'].' — '.$p['caption']).'"><div class="fi"><div class="wb"><i></i><i></i><i></i><span>▸ '.h($p['title']).'</span></div><div class="im"><img src="'.$s.'" alt="'.h($p['title']).'" loading="lazy"><div class="up-ov"><input type="file" accept="image/*" onchange="uploadImage(this,'.$i.')"><span>UPLOAD PHOTO</span></div></div></div></figure>';}
function ic($s,$c='currentColor',$z=20){return '<svg class="ic" viewBox="0 0 64 64" style="color:'.$c.';width:'.$z.'px;height:'.$z.'px"><use href="#'.$s.'"/></svg>';}
function bloom($c,$hh,$x=''){return '<svg class="ic bl" viewBox="0 0 64 96" style="color:'.$c.';height:'.$hh.';'.$x.'"><use href="#iBloom"/></svg>';}
function film($P,$idx,$t,$cls=''){$o='<div class="film '.$cls.'" style="--n:'.count($idx).'"><div class="fgrid">';foreach($idx as $k=>$i)$o.=fr($P,$i,$t,$k);return $o.'</div></div>';}
function ph($id,$cmd,$title,$sub,$tag,$ico){return '<div class="head"><div><div class="cmd" data-t="'.$cmd.'"></div><h2 class="ttl">'.$title.'</h2><p class="sub">'.$sub.'</p></div><span class="tagp">'.ic($ico,'currentColor',16).' '.$tag.'</span></div>';}

/* ===== birthday maths + calendar ===== */
$bd=new DateTime($S['birthday']);$today=new DateTime('today');
$next=new DateTime($today->format('Y').'-'.$bd->format('m-d'));if($next<$today)$next->modify('+1 year');
$age=(int)$next->format('Y')-(int)$bd->format('Y');
$cy=(int)$next->format('Y');$cm=(int)$next->format('n');$cd=(int)$next->format('j');
$first=new DateTime("$cy-$cm-01");$dow=(int)$first->format('w');$dim=(int)$first->format('t');
$mname=$first->format('F');
$cells='';for($i=0;$i<$dow;$i++)$cells.='<span></span>';
for($d=1;$d<=$dim;$d++)$cells.='<span class="'.($d==$cd?'mark':'').'">'.$d.($d==$cd?'<em>♛</em>':'').'</span>';
$tabs=[['home','Home','Birthday dashboard','iPad'],['letter','Secret Letter','A special note for you','iMail'],['m1','Memories I','Where it all began','iHeart'],['m2','Memories II','Our little adventures','iCam'],['m3','Memories III','Level up together','iGem'],['m4','Memories IV','The gallery','iCam'],['wishes','Wishes','Messages for you','iChat'],['surprise','Surprise','Blow the candles','iStar']];
?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Happy Birthday <?=h($S['name'])?> ♪</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700;900&family=Rajdhani:wght@500;600;700&family=Share+Tech+Mono&family=Michroma&display=swap" rel="stylesheet">
<style>
:root{--v:#8b5cff;--b:#3d8bff;--p:#ff5fd2;--c:#5fd4ff;--ink:#ece8ff;--dim:#8f86c0;--g:#a78bfa;--cyan:#5fd4ff;--ac:#8b5cff;
--head:'Orbitron','Segoe UI',sans-serif;--body:'Rajdhani','Segoe UI',sans-serif;--mono:'Share Tech Mono','Courier New',monospace}
*{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;overflow:hidden}
body{height:100dvh;color:var(--ink);font:500 1.05rem/1.4 var(--body);background:radial-gradient(circle at 10% 0,rgba(139,92,255,.28),transparent 45%),radial-gradient(circle at 100% 20%,rgba(61,139,255,.22),transparent 42%),radial-gradient(circle at 50% 125%,rgba(255,95,210,.18),transparent 55%),#04020c}
#stars,#fx{position:fixed;inset:0;width:100%;height:100%;pointer-events:none}#stars{z-index:0}#fx{z-index:120}
.ic{fill:none;flex:none;filter:drop-shadow(0 0 5px currentColor)}
.bl{width:auto;aspect-ratio:64/96;filter:drop-shadow(0 0 8px currentColor) drop-shadow(0 0 18px currentColor);animation:sway 5s ease-in-out infinite alternate;transform-origin:50% 100%}
@keyframes sway{to{rotate:3deg}}
@keyframes blink{50%{opacity:0}}
/* ---------- shiny chamfered panel (mirror-black glass) ---------- */
.pn,.file,.form,.rlog,.pc,.mp,.cmdl{--cut:14px;--cut2:calc(var(--cut) - .2px);--fc:var(--v);position:relative;isolation:isolate;background:linear-gradient(135deg,var(--b),var(--fc) 50%,var(--p));
 clip-path:polygon(0 0,calc(100% - var(--cut)) 0,100% var(--cut),100% 100%,var(--cut) 100%,0 calc(100% - var(--cut)))}
.pn:after,.file:after,.form:after,.rlog:after,.pc:after,.mp:after,.cmdl:after{content:"";position:absolute;inset:1.5px;z-index:-1;pointer-events:none;clip-path:polygon(0 0,calc(100% - var(--cut2)) 0,100% var(--cut2),100% 100%,var(--cut2) 100%,0 calc(100% - var(--cut2)));
 background:linear-gradient(135deg,rgba(255,255,255,.13),transparent 32%),linear-gradient(200deg,rgba(139,92,255,.16),transparent 55%),linear-gradient(180deg,#0b0722,#04020d);box-shadow:inset 0 0 26px rgba(139,92,255,.3)}
.pn:before{content:"";position:absolute;inset:0;z-index:2;pointer-events:none;background:linear-gradient(115deg,transparent 42%,rgba(255,255,255,.13) 50%,transparent 58%) 0 0/260% 100%;animation:sheen 8s ease-in-out infinite}
@keyframes sheen{0%{background-position:130% 0}55%,100%{background-position:-130% 0}}
.pn>h3{font:700 .8rem var(--head);letter-spacing:.14em;color:#fff;padding:10px 16px 0;text-shadow:0 0 10px var(--v)}
#app{position:fixed;inset:0;z-index:2;display:grid;grid-template-columns:262px minmax(0,1fr);opacity:0;visibility:hidden;transition:opacity 1s ease,visibility 0s linear 1s}
body.live #app{opacity:1;visibility:visible;transition:opacity 1s ease}
.side{display:flex;flex-direction:column;gap:8px;min-height:0;padding:12px;border-right:1px solid rgba(139,92,255,.4);background:linear-gradient(180deg,rgba(14,8,40,.92),rgba(4,2,12,.95));box-shadow:inset -1px 0 24px rgba(139,92,255,.18)}
.logo{text-align:center;padding:6px 0 4px}.logo .ic{width:88px;height:58px;color:var(--v)}
.logo b{display:block;font:900 1.05rem var(--head);letter-spacing:.1em;color:#fff;text-shadow:0 0 12px var(--v)}.logo small{font:400 .6rem var(--mono);letter-spacing:.2em;color:var(--dim)}
.nav{flex:1;min-height:0;overflow-y:auto;display:flex;flex-direction:column;gap:5px;scrollbar-width:none}.nav::-webkit-scrollbar{display:none}
.nav a{display:flex;align-items:center;gap:11px;padding:7px 10px;text-decoration:none;color:var(--dim);border:1px solid transparent;border-radius:10px;transition:.3s}
.nav a .ic{width:26px;height:26px}.nav a b{display:block;font:600 .95rem var(--body);color:#d8d0ff;line-height:1.1}.nav a small{font:400 .62rem var(--mono);color:var(--dim);letter-spacing:.04em}
.nav a:hover{background:rgba(139,92,255,.12);color:#fff}
.nav a.on{color:#fff;border-color:var(--b);background:linear-gradient(135deg,rgba(61,139,255,.25),rgba(139,92,255,.3));box-shadow:0 0 18px rgba(139,92,255,.55),inset 0 0 14px rgba(255,255,255,.1)}
.nav a.on b{color:#fff;text-shadow:0 0 10px #fff}
.mp{--cut:10px;display:flex;align-items:center;gap:9px;padding:9px 10px}
#play{width:30px;height:30px;flex:none;border:1px solid var(--c);background:rgba(95,212,255,.1);color:var(--c);cursor:pointer;transition:.3s}#play:hover{background:var(--c);color:#04020c}
.mi{flex:1;min-width:0}.mi small{display:block;font:400 .52rem var(--mono);letter-spacing:.2em;color:var(--dim)}
.mi b{display:block;font:700 .72rem var(--head);color:#fff;text-transform:uppercase;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.pb{height:3px;margin-top:4px;background:rgba(255,255,255,.14);cursor:pointer}#prog{display:block;height:100%;width:0;background:linear-gradient(90deg,var(--b),var(--p));box-shadow:0 0 8px var(--v)}
#tm{font:400 .68rem var(--mono);color:var(--c)}
.cmdl{--cut:8px;display:flex;align-items:center;gap:6px;padding:0 9px;height:34px}.cmdl b{font:400 .68rem var(--mono);color:var(--g);white-space:nowrap}
.cmdl input{flex:1;min-width:0;padding:0;border:0;background:transparent;font:400 .78rem var(--mono);color:var(--ink);outline:none;box-shadow:none}
.mainc{display:grid;grid-template-rows:auto minmax(0,1fr);min-width:0;min-height:0}
.topb{display:flex;align-items:center;gap:12px;height:38px;padding:0 16px;border-bottom:1px solid rgba(139,92,255,.3);background:rgba(4,2,12,.7)}
.dots{display:flex;gap:6px}.dots i{width:10px;height:10px;border-radius:50%;background:#ff5f56}.dots i:nth-child(2){background:#ffbd2e}.dots i:nth-child(3){background:#27c93f}
.clk{margin-left:auto;font:400 .74rem var(--mono);color:var(--c);letter-spacing:.14em}
.led{display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--c);box-shadow:0 0 10px var(--c);margin-right:8px;animation:pulse 1.2s infinite}@keyframes pulse{50%{opacity:.35}}
.upt{position:relative;display:flex;align-items:center;gap:7px;cursor:pointer;user-select:none;font:400 .66rem var(--mono);letter-spacing:.14em;color:var(--dim)}
.upt input{position:absolute;opacity:0;width:0;height:0}
.upt i{position:relative;width:32px;height:16px;border:1px solid var(--dim);border-radius:9px;background:rgba(0,0,0,.55);transition:.3s}
.upt i:after{content:"";position:absolute;left:2px;top:2px;width:10px;height:10px;border-radius:50%;background:var(--dim);transition:.3s}
.upt em{font-style:normal;min-width:2.2em}
.upt input:checked~i{border-color:var(--v);box-shadow:0 0 10px rgba(139,92,255,.6)}.upt input:checked~i:after{translate:16px 0;background:var(--v);box-shadow:0 0 8px var(--v)}.upt input:checked~em{color:var(--v)}
.view{position:relative;min-height:0;overflow:hidden}
.pg{position:absolute;inset:0;isolation:isolate;display:flex;flex-direction:column;padding:clamp(10px,2vh,22px) clamp(12px,2.4vw,40px) 10px;opacity:0;visibility:hidden;pointer-events:none;transform:translateY(12px);transition:opacity .5s ease,transform .6s cubic-bezier(.2,.8,.2,1),visibility 0s linear .55s}
.pg.on{opacity:1;visibility:visible;pointer-events:auto;transform:none;transition:opacity .5s ease .05s,transform .6s cubic-bezier(.2,.8,.2,1) .05s,visibility 0s}
.pg:before{content:"";position:absolute;inset:0;z-index:-1;background:radial-gradient(circle at 85% 10%,color-mix(in srgb,var(--ac) 18%,transparent),transparent 46%),radial-gradient(circle at 5% 100%,color-mix(in srgb,var(--ac) 10%,transparent),transparent 40%)}
.head{flex:none;display:flex;justify-content:space-between;align-items:flex-end;gap:16px;margin-bottom:clamp(8px,1.6vh,16px)}
.cmd{font:400 .8rem var(--mono);color:var(--g);min-height:1.2em;text-shadow:0 0 8px rgba(139,92,255,.6)}.cmd:after{content:"▌";margin-left:2px;animation:blink 1s steps(1) infinite}
.ttl{font:400 clamp(1.1rem,3.8vh,2rem)/1.1 'Michroma',var(--head);letter-spacing:.06em;text-transform:uppercase;margin-top:4px;background:linear-gradient(100deg,#fff 0,var(--ac) 45%,#fff 60%,var(--ac) 100%) 0 0/220% 100%;-webkit-background-clip:text;background-clip:text;color:transparent;filter:drop-shadow(0 0 12px color-mix(in srgb,var(--ac) 70%,transparent));animation:glint 8s linear infinite}
@keyframes glint{to{background-position:-220% 0}}
.sub{margin-top:4px;font:400 .76rem var(--mono);color:color-mix(in srgb,var(--ac) 70%,#fff)}
.tagp{display:flex;align-items:center;gap:8px;font:400 .68rem var(--mono);letter-spacing:.2em;color:var(--ac);padding:5px 12px;border:1px solid color-mix(in srgb,var(--ac) 60%,transparent);background:color-mix(in srgb,var(--ac) 10%,transparent);text-transform:uppercase;white-space:nowrap}
.pf{flex:none;display:flex;justify-content:flex-end;align-items:center;gap:12px;padding-top:6px;margin-top:6px;border-top:1px solid rgba(139,92,255,.25)}
.pr{display:flex;align-items:center;gap:12px}
.nx,.sb{text-decoration:none;font:700 .66rem var(--head);letter-spacing:.14em;color:var(--c);border:1px solid var(--c);background:rgba(95,212,255,.07);padding:7px 14px;cursor:pointer;transition:.3s}
.nx:hover,.sb:hover{background:var(--c);color:#04020c;box-shadow:0 0 16px var(--c)}.sb{padding:6px 11px}.wcnt{font:400 .75rem var(--mono);color:var(--c)}
.btn{display:inline-block;cursor:pointer;text-decoration:none;font:700 .72rem var(--head);letter-spacing:.12em;text-transform:uppercase;padding:10px 18px;border:1px solid var(--ac);color:var(--ac);background:color-mix(in srgb,var(--ac) 10%,transparent);transition:.3s}
.btn:hover{background:var(--ac);color:#04020c;box-shadow:0 0 18px var(--ac)}.btn:disabled{opacity:.5;pointer-events:none}
.wel{flex:none;display:flex;align-items:center;gap:14px;margin-bottom:10px}.wel small{display:block;font:500 .8rem var(--body);letter-spacing:.14em;color:var(--dim);text-transform:uppercase}
.wel b{font:700 clamp(1.3rem,4vh,2rem) var(--body);color:#fff;text-shadow:0 0 14px var(--v)}
.badge{padding:5px 14px;border-radius:20px;background:rgba(139,92,255,.22);border:1px solid rgba(139,92,255,.6);font:600 .85rem var(--body);box-shadow:0 0 14px rgba(139,92,255,.4)}
.dash{flex:1;min-height:0;display:grid;grid-template-columns:minmax(0,.85fr) minmax(0,1.6fr) minmax(0,.9fr);grid-template-rows:minmax(0,1.25fr) minmax(0,1fr);gap:clamp(8px,1.4vw,16px)}
.d-ph{padding:8px}.d-ph .ph{position:absolute;inset:10px;overflow:hidden;clip-path:polygon(0 0,calc(100% - 12px) 0,100% 12px,100% 100%,12px 100%,0 calc(100% - 12px))}
.d-ph .lab{position:absolute;left:14px;bottom:12px;z-index:5;font:700 .8rem var(--head);color:#fff;letter-spacing:.08em;text-shadow:0 0 10px #000,0 0 10px var(--v);pointer-events:none}
.hero{display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;min-width:0;min-height:0}
.crown{width:clamp(40px,8vh,70px);color:var(--v)}
.big{font:900 clamp(1.8rem,min(9vh,5.2vw),4.4rem)/.95 var(--head);font-style:italic;letter-spacing:.02em;text-transform:uppercase;background:linear-gradient(180deg,#fff 10%,#c9b6ff 50%,#8b5cff 100%);-webkit-background-clip:text;background-clip:text;color:transparent;filter:drop-shadow(0 0 14px rgba(139,92,255,.9)) drop-shadow(0 0 34px rgba(61,139,255,.6));-webkit-box-reflect:below -4px linear-gradient(transparent 60%,rgba(255,255,255,.22))}
.lvl{margin:clamp(4px,1.6vh,12px) 0;font:700 clamp(.6rem,1.8vh,.85rem) var(--head);letter-spacing:.4em;color:#fff;text-transform:uppercase;text-shadow:0 0 10px var(--v)}
.msg{width:100%;max-width:520px;padding:12px 18px;text-align:left;font:italic 500 clamp(.9rem,2.3vh,1.1rem)/1.3 var(--body);color:#e6dcff}
.msg small{display:block;margin-top:4px;font:italic 500 .85rem var(--body);color:#c9b6ff}
.cal .gr{display:grid;grid-template-columns:repeat(7,1fr);padding:6px 14px 12px;text-align:center}
.cal .mo{padding:8px 16px;text-align:center;font:600 clamp(.9rem,2.4vh,1.15rem) var(--body);color:#fff}
.cal .gr b{font:600 .8rem var(--body);color:var(--c);padding-bottom:4px}
.cal .gr span{position:relative;font:500 clamp(.72rem,2vh,1rem)/clamp(20px,4.2vh,34px) var(--body)}
.cal .mark{color:#fff;font-weight:700;border:2px solid var(--v);border-radius:9px;background:rgba(139,92,255,.3);box-shadow:0 0 14px var(--v)}
.cal .mark em{position:absolute;left:50%;top:-9px;translate:-50% 0;font-style:normal;font-size:.7rem;color:#ffd36b;line-height:1;filter:drop-shadow(0 0 5px #ffb300)}
.prof{padding:8px 4px}
.prof dl{display:grid;grid-template-columns:auto 1fr;gap:3px 14px;padding:8px 16px;font:500 clamp(.8rem,2.1vh,1rem) var(--body)}.prof dt{color:var(--dim)}.prof dd{color:#fff}
.xp{display:flex;align-items:center;gap:10px;padding:0 16px;font:600 .85rem var(--body)}.xp i{flex:1;height:8px;border-radius:5px;background:rgba(255,255,255,.12);overflow:hidden}.xp i:after{content:"";display:block;height:100%;width:100%;background:linear-gradient(90deg,var(--v),var(--b));box-shadow:0 0 12px var(--v);animation:fill 2s .6s backwards}@keyframes fill{from{width:0}}
.cdn{display:flex;flex-direction:column;justify-content:center}
.dg{display:flex;align-items:center;justify-content:center;gap:clamp(4px,1vw,12px);padding:6px 12px 0;font:700 clamp(1.3rem,5.4vh,2.6rem) var(--head);color:#d9c9ff;text-shadow:0 0 16px var(--v)}.dg i{font-style:normal;color:var(--b)}
.dl{display:flex;justify-content:space-around;font:500 .85rem var(--body);color:#e0d8ff;padding:0 12px 6px}
.bday{margin:4px 14px 12px;padding:6px;text-align:center;border:1px solid var(--b);box-shadow:0 0 16px rgba(61,139,255,.5),inset 0 0 14px rgba(139,92,255,.3);font:italic 700 clamp(.9rem,2.6vh,1.25rem) var(--body);color:#fff;text-shadow:0 0 12px var(--v)}
.spm{padding:0 16px 8px;font:italic 500 clamp(.85rem,2.2vh,1.05rem)/1.3 var(--body);color:#e6dcff;overflow:hidden}.spm b{display:block;margin-top:4px;font:700 1rem var(--body);color:#fff;font-style:normal}
.fr{--a:var(--p);--ab:color-mix(in srgb,var(--a) 55%,#fff);--cut:14px;--cut2:calc(var(--cut) - .2px);position:relative;display:flex;flex-direction:column;min-width:0;min-height:0;cursor:zoom-in;rotate:var(--r,0deg);padding:1px;background:var(--a);
 clip-path:polygon(var(--cut) 0,100% 0,100% calc(100% - var(--cut)),calc(100% - var(--cut)) 100%,0 100%,0 var(--cut));transition:opacity .8s ease calc(var(--i)*.09s + .25s),filter .8s,translate .3s,scale .3s,background .3s}
.fi{position:relative;flex:1;min-height:0;min-width:0;display:flex;flex-direction:column;background:rgba(4,2,12,.97);clip-path:polygon(var(--cut2) 0,100% 0,100% calc(100% - var(--cut2)),calc(100% - var(--cut2)) 100%,0 100%,0 var(--cut2))}
.fi:before{content:"";position:absolute;inset:0;z-index:4;pointer-events:none;background:linear-gradient(var(--ab),var(--ab)) 18px 0/30% 3px no-repeat,repeating-linear-gradient(115deg,var(--ab) 0 2px,transparent 2px 6px) calc(100% - 28px) 0/38px 4px no-repeat,linear-gradient(var(--ab),var(--ab)) calc(100% - 20px) 100%/24% 2px no-repeat}
.pg:not(.on) .fr{opacity:0;filter:blur(6px);transition:opacity .3s,filter .3s}
.fr:hover{translate:0 -4px;scale:1.015;z-index:9;background:#fff}
.lv{--a:var(--p)}.vi{--a:var(--v)}.ml{--a:var(--c)}
.wb{flex:none;display:flex;align-items:center;gap:5px;height:21px;padding:0 8px 0 16px;border-bottom:1px solid color-mix(in srgb,var(--a) 45%,transparent);background:color-mix(in srgb,var(--a) 12%,#04020c);font:400 .64rem var(--mono);color:var(--a);white-space:nowrap;overflow:hidden}
.wb i{width:6px;height:6px;border-radius:50%;background:var(--a);opacity:.7;flex:none}.wb span{margin-left:5px;overflow:hidden;text-overflow:ellipsis}
.fr .im{position:relative;flex:1;min-height:0;overflow:hidden;background:#080618}
.ph img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block}
.ph{position:relative;cursor:zoom-in;overflow:hidden;background:#080618}
.fr .im:after{content:"";position:absolute;inset:0;pointer-events:none;background:linear-gradient(135deg,rgba(255,255,255,.1),transparent 40%),repeating-linear-gradient(0deg,rgba(255,255,255,.03) 0 1px,transparent 1px 3px)}
.up-ov{display:none;position:absolute;inset:0;z-index:10;align-items:center;justify-content:center;background:rgba(0,0,0,.7)}
body.up-on .up-ov{display:flex}
.up-ov input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.up-ov span{pointer-events:none;font:700 .62rem var(--mono);color:var(--c);border:1px solid var(--c);background:rgba(0,0,0,.85);padding:6px 10px;box-shadow:0 0 15px var(--c);letter-spacing:.1em;text-align:center}
.lay{flex:1;min-height:0}.lay>.film{height:100%}
.film{--hole:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='24'%3E%3Crect x='10' y='4' width='20' height='16' rx='4'/%3E%3C/svg%3E");position:relative;display:flex;flex-direction:column;min-height:0;overflow:hidden;border-radius:12px;background:linear-gradient(180deg,#0a0626,#05030f);border-top:1px solid var(--ac);border-bottom:1px solid var(--ac);box-shadow:0 0 26px color-mix(in srgb,var(--ac) 30%,transparent),inset 0 0 40px rgba(120,60,255,.12)}
.film:before,.film:after{content:"";flex:none;height:24px;margin:5px 0;background:var(--ac);-webkit-mask:var(--hole) 0 50%/40px 24px repeat-x;mask:var(--hole) 0 50%/40px 24px repeat-x;animation:filmrun 2.2s linear infinite}.film:after{order:3}
@keyframes filmrun{to{-webkit-mask-position:-40px 50%;mask-position:-40px 50%}}
.fgrid{order:2;flex:1;min-height:0;display:grid;grid-template-columns:repeat(var(--n),minmax(0,1fr));grid-template-rows:minmax(0,1fr);gap:clamp(10px,1.6vw,22px);padding:6px clamp(10px,1.6vw,22px)}.fgrid .fr{height:100%}
.lgrid{flex:1;min-height:0;display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.45fr) minmax(0,1fr);gap:clamp(12px,2.4vw,34px)}.lgrid>.fr{height:100%}
.lmid{min-width:0;min-height:0;display:flex;flex-direction:column;justify-content:center;gap:clamp(10px,2.2vh,20px)}
.file{--cut:16px;width:100%;padding:clamp(18px,3.4vh,32px) clamp(18px,2vw,28px);cursor:pointer;text-align:left;transition:.4s}.file:hover{--fc:#fff;translate:0 -4px}
.file>.ic:first-child{width:clamp(44px,8vh,64px);height:clamp(44px,8vh,64px);color:var(--v)}
.file b{display:block;margin-top:10px;font:400 clamp(1.05rem,2.8vh,1.4rem) var(--mono);color:#fff}.file small{display:block;font:400 .72rem var(--mono);color:var(--dim);letter-spacing:.14em;margin-top:2px}
.file .fp{height:3px;margin-top:14px;background:rgba(255,255,255,.1);overflow:hidden}.file .fp i{display:block;height:100%;width:0;background:var(--v);box-shadow:0 0 10px var(--v)}.file.run .fp i{width:100%;transition:width 1.3s linear}
.file .fs{margin-top:8px;font:400 .74rem var(--mono);color:var(--c)}.file .go{margin-top:12px;font:700 .72rem var(--head);letter-spacing:.12em;color:var(--v)}
.rlog{font:400 clamp(.76rem,2vh,.95rem)/1.75 var(--mono);color:#e7d9ff;padding:12px 16px;overflow:hidden}.rlog .c{color:#6f6490}.rlog .k{color:var(--v)}.rlog .s{color:var(--p)}
.paper{position:fixed;left:50%;top:50%;translate:-50% -46%;width:min(760px,94vw);max-height:80dvh;overflow:auto;z-index:80;padding:46px clamp(20px,5vw,52px) 28px;background:rgba(6,4,20,.98);border:1px solid var(--v);box-shadow:0 0 0 100vmax rgba(2,1,8,.75),0 0 34px rgba(139,92,255,.5);opacity:0;visibility:hidden;transition:opacity .6s ease,translate .6s ease,visibility 0s linear .6s}
.paper.open{opacity:1;visibility:visible;translate:-50% -50%;transition:opacity .6s ease,translate .6s ease,visibility 0s}
.paper:before{content:"// DECRYPTED · love_letter.enc";position:absolute;left:16px;top:13px;font:400 .68rem var(--mono);letter-spacing:.16em;color:var(--c)}
.paper .x{position:absolute;right:10px;top:8px;width:28px;height:28px;border:1px solid var(--c);background:rgba(95,212,255,.1);color:var(--c);cursor:pointer}.paper .x:hover{background:var(--c);color:#04020c}
#ltxt{font:600 clamp(1.05rem,2.8vh,1.4rem)/1.55 var(--body);color:#ece8ff}#ltxt span{display:inline-block;margin-right:.28em}
.paper.open #ltxt span{animation:ink .6s backwards;animation-delay:calc(var(--k)*.03s + .5s)}@keyframes ink{from{opacity:0;filter:blur(4px);translate:0 8px}}
.artw{flex:1;min-height:0;display:flex;align-items:center;justify-content:center;container-type:size}
.art{position:relative;width:min(100cqw,177.78cqh);aspect-ratio:1672/941;background:center/100% 100% no-repeat;border-radius:10px;box-shadow:0 0 40px rgba(139,92,255,.45)}
.art .ph{position:absolute;border-radius:6px}
.art .ph:hover{z-index:9;filter:brightness(1.12)}
.art .ph:after{content:"";position:absolute;inset:0;pointer-events:none;background:linear-gradient(135deg,rgba(255,255,255,.16),transparent 38%)}
.art .cn{position:absolute;font:400 .68rem var(--mono);color:var(--c);pointer-events:none}
.gal{position:relative;flex:1;min-height:0;display:flex;align-items:center;justify-content:center;gap:clamp(8px,2vw,30px);perspective:1300px}
.gal:after{content:"";position:absolute;left:50%;bottom:4%;translate:-50% 0;width:min(70%,640px);height:14%;border-radius:50%;border:2px solid rgba(61,139,255,.8);box-shadow:0 0 30px rgba(61,139,255,.7),inset 0 0 30px rgba(139,92,255,.6);background:radial-gradient(ellipse,rgba(139,92,255,.35),transparent 70%)}
.gc{position:relative;z-index:1;flex:none;height:min(50vh,420px);aspect-ratio:.78;border-radius:18px;border:2px solid var(--b);box-shadow:0 0 22px rgba(61,139,255,.7),inset 0 0 18px rgba(139,92,255,.4);transition:.5s;transform:rotateY(var(--ry,0deg)) scale(var(--s,.86))}
.gc.c{--s:1.12;border-color:var(--v);box-shadow:0 0 34px rgba(139,92,255,.95),inset 0 0 18px rgba(255,255,255,.15)}
.gc:hover{--s:1.2;z-index:5}
.gc .tp{position:absolute;left:50%;top:-18px;translate:-50% 0;z-index:12;width:34px;height:34px;display:grid;place-items:center;border-radius:50%;background:#0a0626;border:1px solid var(--v);box-shadow:0 0 14px var(--v);color:#fff}
.gc .ph{position:absolute;inset:0;border-radius:16px}
.wl{flex:1;min-height:0;display:grid;grid-template-columns:minmax(240px,350px) minmax(0,1fr);gap:clamp(14px,2.6vw,40px)}
.fcol{display:flex;flex-direction:column;justify-content:center;align-items:center;gap:2vh}
.form{--cut:14px;width:100%;padding:16px;display:grid;gap:10px}.form .c{font:400 .75rem var(--mono);color:var(--c)}
input,textarea{width:100%;padding:10px 12px;border:1px solid rgba(139,92,255,.5);background:rgba(0,0,0,.5);font:600 1.05rem var(--body);color:var(--ink);outline:none;border-radius:0;resize:none}input:focus,textarea:focus{border-color:var(--v);box-shadow:0 0 12px var(--v)}
::placeholder{color:#6a6090}
.pcs{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));grid-template-rows:repeat(2,minmax(0,1fr));gap:14px;min-height:0}
.pc{--fc:var(--c2,var(--v));min-height:0;overflow:hidden;padding:30px 16px 12px;font:600 clamp(.95rem,2.4vh,1.15rem)/1.3 var(--body);animation:fade .7s backwards}
.pc>*{position:relative}
@keyframes fade{from{opacity:0;translate:0 8px}}
.pc:before{content:"// wish";position:absolute;top:9px;left:14px;font:400 .62rem var(--mono);letter-spacing:.18em;color:var(--c)}
.pc .mt{display:-webkit-box;-webkit-line-clamp:5;-webkit-box-orient:vertical;overflow:hidden}
.pc b{display:block;margin-top:6px;font:400 .78rem var(--mono);color:var(--c)}
.stg{flex:1;min-height:0;display:flex;align-items:center;justify-content:center;gap:clamp(14px,4vw,70px)}
.cakeW{display:flex;flex-direction:column;align-items:center;gap:1.6vh}
.cakeS{height:min(44vh,380px);width:auto;overflow:visible;filter:drop-shadow(0 0 14px rgba(139,92,255,.5))}
.cn2 path:first-child{stroke:#fff;stroke-width:3;stroke-linecap:round;fill:none}.cn2{cursor:pointer}
.fl{fill:#9fd8ff;stroke:none;transform-box:fill-box;transform-origin:50% 100%;animation:fk .25s infinite alternate;transition:opacity .5s,scale .5s}
.out .fl{opacity:0;scale:0}@keyframes fk{from{rotate:-4deg}to{rotate:4deg;scale:1.1 .94}}
.cake-c{font:400 .8rem var(--mono);color:var(--c)}
.sl{position:fixed;left:50%;top:50%;translate:-50% -44%;max-width:min(560px,92vw);max-height:76dvh;overflow:auto;padding:28px;font:600 clamp(1.1rem,3vh,1.5rem)/1.45 var(--body);background:rgba(6,4,20,.98);border:1px solid var(--p);box-shadow:0 0 0 100vmax rgba(2,1,8,.65),0 0 30px rgba(255,95,210,.5);opacity:0;visibility:hidden;transition:opacity .7s ease,translate .7s ease,visibility 0s linear .7s;z-index:80;cursor:pointer}
.sl.show{opacity:1;visibility:visible;translate:-50% -50%;transition:opacity .7s ease,translate .7s ease,visibility 0s}
.sl small{display:block;margin-top:12px;font:400 .68rem var(--mono);letter-spacing:.2em;color:var(--c)}
#lb{position:fixed;inset:0;z-index:90;background:rgba(2,1,8,.94);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;padding:18px;cursor:zoom-out;opacity:0;visibility:hidden;transition:opacity .45s,visibility 0s linear .45s}
#lb.on{opacity:1;visibility:visible;transition:opacity .45s,visibility 0s}
#lb img{max-width:92vw;max-height:78vh;border:1px solid var(--v);box-shadow:0 0 40px rgba(139,92,255,.7)}#lb p{color:var(--c);font:400 1rem var(--mono)}
#out{position:fixed;left:278px;bottom:18px;z-index:62;max-width:min(560px,80vw);font:400 .82rem/1.55 var(--mono);color:var(--c);white-space:pre-wrap;background:rgba(6,4,20,.95);border:1px solid var(--v);padding:10px 14px;box-shadow:0 0 18px rgba(139,92,255,.4);opacity:0;translate:0 8px;transition:.45s;pointer-events:none}#out.show{opacity:1;translate:0}
#ach{position:fixed;right:16px;top:50px;z-index:63;display:flex;gap:10px;padding:9px 14px;font:400 .72rem/1.4 var(--mono);color:#ffd36b;border:1px solid #ffd36b;background:rgba(10,8,2,.94);translate:130% 0;transition:translate .6s cubic-bezier(.2,.8,.2,1);pointer-events:none}#ach.show{translate:0}#ach b{color:#fff;font-weight:400}
#gate{position:fixed;inset:0;z-index:100;display:flex;align-items:center;justify-content:center;padding:14px;background:rgba(3,1,10,.55);-webkit-backdrop-filter:blur(7px);backdrop-filter:blur(7px);transition:opacity 1s ease,visibility 0s linear 1s}
#gate.go{opacity:0;visibility:hidden;pointer-events:none}
.gbox{position:relative;overflow:hidden;width:min(740px,94vw);border:1px solid var(--v);background:rgba(5,3,16,.97);animation:glow 3s ease-in-out infinite alternate;transition:scale 1s}
#gate.go .gbox{scale:1.06}
.gbox:after{content:"";position:absolute;left:-30%;top:0;height:2px;width:30%;background:linear-gradient(90deg,transparent,#fff,transparent);animation:sweep 3s linear infinite}
@keyframes sweep{to{left:100%}}
@keyframes glow{from{box-shadow:0 0 18px rgba(139,92,255,.4),0 0 50px rgba(61,139,255,.15)}to{box-shadow:0 0 30px rgba(139,92,255,.8),0 0 100px rgba(61,139,255,.35)}}
@keyframes shake{25%{translate:-9px 0}75%{translate:9px 0}}
.gbox.bad{border-color:#ff5f56;animation:shake .4s,glow 3s ease-in-out infinite alternate}
.gt{display:flex;align-items:center;gap:10px;height:32px;padding:0 0 0 10px;border-bottom:1px solid rgba(139,92,255,.5);background:rgba(139,92,255,.14);font:400 .76rem var(--mono);color:#c9b6ff;white-space:nowrap}
.gt .ci{padding:0 3px;font-size:.58rem;line-height:14px;background:#000;border:1px solid var(--v)}
.gt .wc{margin-left:auto;display:flex;height:100%}.gt .wc i{width:42px;display:grid;place-items:center;font-style:normal;color:var(--dim)}
.term{height:min(58vh,430px);overflow-y:auto;padding:12px 14px;font:400 clamp(.78rem,2vh,.92rem)/1.6 var(--mono);color:#e0d8ff;cursor:text;scrollbar-width:thin;scrollbar-color:var(--v) transparent}
.tl{white-space:pre-wrap;word-break:break-word;min-height:1.6em}.tl .p{color:var(--c);text-shadow:0 0 8px rgba(95,212,255,.5)}.tl b{font-weight:400;color:#fff}.tl .ok{color:var(--v);text-shadow:0 0 8px var(--v)}
.tl.note{margin:4px 0;padding-left:10px;border-left:3px solid var(--p);color:#ffb8e8}.tl.er{color:#ff8aa8}.tl.grant{color:var(--c);text-shadow:0 0 10px var(--c)}
.tl.in{display:flex;align-items:center;gap:8px}.tl.in .p{flex:none}
.tl.in input{flex:1;min-width:0;padding:0;border:0;background:transparent;box-shadow:none;font:inherit;letter-spacing:.2em;color:#fff;caret-color:var(--v)}
.tl.in button{flex:none;height:26px;padding:0 12px;border:1px solid var(--v);background:rgba(139,92,255,.12);color:var(--v);font:inherit;cursor:pointer}.tl.in button:hover{background:var(--v);color:#04020c}
#musicNote{padding:0 14px 8px;font:400 .72rem var(--mono);color:#ffb8e8}
#intro{position:fixed;inset:0;z-index:90;background:#05030f;display:flex;align-items:center;justify-content:center;container-type:size;opacity:0;visibility:hidden;transition:opacity 1s ease,visibility 0s linear 1s}
#intro.on{opacity:1;visibility:visible;transition:opacity 1s ease,visibility 0s}
#intro .art{background-image:url('assets/img/ui-intro.jpg');border-radius:0;box-shadow:none}
#start{position:absolute;left:36%;top:64%;width:29%;height:11.5%;background:transparent;border:0;cursor:pointer;border-radius:14px}
#start:hover{box-shadow:0 0 40px 6px rgba(160,110,255,.8),inset 0 0 26px rgba(255,255,255,.25);background:rgba(160,110,255,.12)}
#start:after{content:"";position:absolute;inset:-6px;border:2px solid rgba(190,150,255,.8);border-radius:18px;animation:ping 2s infinite}@keyframes ping{to{scale:1.18;opacity:0}}
@keyframes spin{to{rotate:360deg}}
@media(max-width:900px){
 #app{grid-template-columns:1fr;grid-template-rows:auto minmax(0,1fr)}
 .side{flex-direction:row;align-items:center;padding:6px;border-right:0;border-bottom:1px solid rgba(139,92,255,.4)}.logo,.mp,.cmdl{display:none}
 .nav{flex-direction:row;overflow-x:auto}.nav a{flex:none;padding:6px 9px}.nav a div{display:none}
 #out{left:12px}
 .dash{grid-template-columns:1fr 1fr;grid-template-rows:none;overflow:auto;grid-auto-rows:minmax(180px,auto)}.hero{grid-column:1/3;order:-1}
 .lgrid{grid-template-columns:1fr}.lgrid>.fr{display:none}
 .wl{grid-template-columns:1fr}.pcs{grid-template-columns:repeat(2,minmax(0,1fr))}.tagp{display:none}
 .gc:not(.c){display:none}.fgrid{grid-template-columns:repeat(2,minmax(0,1fr));grid-template-rows:repeat(2,minmax(0,1fr))}
}
@media(prefers-reduced-motion:reduce){*{animation-duration:.01s!important;animation-iteration-count:1!important;transition-duration:.01s!important}}
</style></head><body>

<svg width="0" height="0" style="position:absolute" aria-hidden="true"><defs>
<symbol id="iHeart" viewBox="0 0 64 64"><g fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M32 55C12 40 6 28 11 18C16 9 27 10 32 19C37 10 48 9 53 18C58 28 52 40 32 55Z" fill="currentColor" fill-opacity=".22"/></g></symbol>
<symbol id="iMail" viewBox="0 0 64 64"><g fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="14" width="48" height="36" rx="4" fill="currentColor" fill-opacity=".12"/><path d="M8 18L32 36L56 18"/></g></symbol>
<symbol id="iCam" viewBox="0 0 64 64"><g fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="18" width="52" height="34" rx="5" fill="currentColor" fill-opacity=".12"/><circle cx="32" cy="35" r="10"/><path d="M22 18L26 10H38L42 18"/></g></symbol>
<symbol id="iGem" viewBox="0 0 64 64"><g fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M18 10H46L58 26L32 56L6 26Z" fill="currentColor" fill-opacity=".15"/><path d="M6 26H58M24 10L20 26L32 56L44 26L40 10"/></g></symbol>
<symbol id="iPad" viewBox="0 0 64 64"><g fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M16 22H48C56 22 60 40 56 48C53 54 47 52 44 46L42 42H22L20 46C17 52 11 54 8 48C4 40 8 22 16 22Z" fill="currentColor" fill-opacity=".12"/><path d="M20 28V36M16 32H24"/><circle cx="42" cy="29" r="2"/><circle cx="48" cy="35" r="2"/></g></symbol>
<symbol id="iChat" viewBox="0 0 64 64"><g fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M8 12H56V42H30L18 54V42H8Z" fill="currentColor" fill-opacity=".12"/><path d="M18 24H46M18 32H36"/></g></symbol>
<symbol id="iStar" viewBox="0 0 64 64"><g fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M32 6L39 25H58L43 37L49 57L32 45L15 57L21 37L6 25H25Z" fill="currentColor" fill-opacity=".18"/></g></symbol>
<symbol id="iLock" viewBox="0 0 64 64"><g fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><rect x="12" y="28" width="40" height="28" rx="4" fill="currentColor" fill-opacity=".12"/><path d="M20 28V20C20 12 44 12 44 20V28"/><circle cx="32" cy="42" r="3.5" fill="currentColor"/></g></symbol>
<symbol id="iCrown" viewBox="0 0 64 40"><g fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 34L2 10L18 20L32 4L46 20L62 10L58 34Z" fill="currentColor" fill-opacity=".18"/><path d="M6 34H58"/></g></symbol>
<symbol id="iBloom" viewBox="0 0 64 96"><g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M32 94V50"/><path d="M32 80C22 78 16 70 14 62C24 62 30 68 32 80Z" stroke="#a78bfa" fill="#a78bfa" fill-opacity=".15"/><path d="M32 72C42 70 48 62 50 54C40 54 34 60 32 72Z" stroke="#a78bfa" fill="#a78bfa" fill-opacity=".15"/><path d="M32 50C14 48 8 28 16 10C22 18 27 20 32 10C37 20 42 18 48 10C56 28 50 48 32 50Z" fill="currentColor" fill-opacity=".2"/><path d="M32 50C24 40 24 26 32 14C40 26 40 40 32 50Z" stroke-opacity=".55"/><path d="M22 22C20 29 22 36 26 42" stroke="#fff" stroke-opacity=".85"/></g></symbol>
</defs></svg>
<canvas id="stars"></canvas><canvas id="fx"></canvas>

<div id="gate"><div class="gbox" id="gbox">
  <div class="gt"><span class="ci">C:\</span><span>Command Prompt — birthday.exe</span><span class="wc"><i>—</i><i>▢</i><i>✕</i></span></div>
  <div class="term" id="term"></div><div id="musicNote"></div>
</div></div>

<div id="intro"><div class="art"><button id="start" aria-label="Tap to start"></button></div></div>

<div id="app">
 <aside class="side">
  <div class="logo"><svg class="ic" viewBox="0 0 64 64" style="width:88px;height:58px"><use href="#iPad"/></svg><b>ALLAN'S GALAXY</b><small>PLAY • CODE • CELEBRATE</small></div>
  <nav class="nav" id="nav"><?php foreach($tabs as $t): ?><a href="#<?=$t[0]?>"><?=ic($t[3],'currentColor',26)?><div><b><?=h($t[1])?></b><small><?=h($t[2])?></small></div></a><?php endforeach; ?></nav>
  <div class="mp" id="rec"><button id="play" title="Play / pause">▶</button><div class="mi"><small>NOW PLAYING</small><b><?=h($SONG)?></b><div class="pb"><i id="prog"></i></div></div><span id="tm">0:00</span></div>
  <label class="cmdl"><b>&gt;_</b><input id="cmd" autocomplete="off" spellcheck="false" placeholder='command… try "help"'></label>
 </aside>
 <div class="mainc">
  <header class="topb"><div class="dots"><i></i><i></i><i></i></div>
   <label class="upt" title="Turn photo upload on/off"><b>UPLOAD</b><input type="checkbox" id="upToggle"><i></i><em id="upSt">OFF</em></label>
   <span class="clk"><i class="led"></i><span id="clk">00:00:00</span></span></header>
  <main class="view">

<section class="pg on" id="home" style="--ac:#8b5cff">
 <div class="wel"><div><small>Welcome back,</small><b><?=h($FIRST)?></b></div><span class="badge">♛ Legendary Programmer</span></div>
 <?php if(!empty($dbError)): ?><p style="font:400 .8rem var(--mono);color:#ff8aa8;margin-bottom:6px">Database not connected. Import database.sql in phpMyAdmin.</p><?php endif; ?>
 <div class="dash">
  <div class="pn d-ph"><?=pic($P,20)?><span class="lab">PLAYER 01 ///</span></div>
  <div class="hero">
   <svg class="crown ic" viewBox="0 0 64 40"><use href="#iCrown"/></svg>
   <h1 class="big">Happy<br>Birthday!</h1>
   <p class="lvl">Level up another year</p>
   <div class="pn msg"><span style="position:relative"><?=h($S['message'])?><small>— Enjoy your special day, <?=h($FIRST)?>! <span style="color:var(--p)">♥</span></small></span></div>
  </div>
  <div class="pn cal"><h3>BIRTHDAY CALENDAR</h3><div class="mo"><?=$mname?> <?=$cy?></div><div class="gr"><b>Sun</b><b>Mon</b><b>Tue</b><b>Wed</b><b>Thu</b><b>Fri</b><b>Sat</b><?=$cells?></div></div>
  <div class="pn prof"><h3>PLAYER PROFILE</h3><dl><dt>Username</dt><dd>: <?=h($FIRST)?></dd><dt>Class</dt><dd>: Gamer / Programmer</dd><dt>Level</dt><dd>: <?=$age?></dd><dt>Status</dt><dd>: ♛ Legendary</dd></dl><div class="xp">XP <i></i> 100%</div></div>
  <div class="pn cdn count" data-birthday="<?=h($S['birthday'])?>"><h3>BIRTHDAY COUNTDOWN</h3>
   <div class="dg"><span data-u="d">00</span><i>:</i><span data-u="h">00</span><i>:</i><span data-u="m">00</span><i>:</i><span data-u="s">00</span></div>
   <div class="dl"><span>Days</span><span>Hours</span><span>Minutes</span><span>Seconds</span></div>
   <div class="bday" id="cdLabel">🎁 It's Your Birthday!</div></div>
  <div class="pn"><h3>SPECIAL MESSAGE</h3><div class="spm">Another year, another level of greatness! 🎮<b>Happy Birthday, <?=h($FIRST)?>! <span style="color:var(--p)">♥</span></b></div></div>
 </div>
</section>

<section class="pg" id="letter" style="--ac:#4d9dff">
<?=ph('letter','$ ls ./letters &amp;&amp; decrypt love_letter.enc','A letter for you','// words i kept just for you ♥','encrypted','iLock')?>
<div class="lgrid">
  <?=fr($P,2,'lv',0,'--r:-1.5deg')?>
  <div class="lmid">
    <div class="file" id="env"><?=ic('iLock','#8b5cff',44)?><b>love_letter.enc</b><small>1 FILE · ENCRYPTED WITH ♥</small><div class="fp"><i></i></div><div class="fs" id="fs">status: locked</div><div class="go">$ decrypt --key=heart</div><?=bloom('#ff5fd2','62%','position:absolute;right:18px;bottom:14px')?></div>
<pre class="rlog"><span class="c">// reasons.log</span>
<span class="k">reason_01</span> = <span class="s">"your smile is my favorite view"</span>
<span class="k">reason_02</span> = <span class="s">"home feels like wherever you are"</span>
<span class="k">reason_03</span> = <span class="s">"you make ordinary days beautiful"</span>
<span class="k">reason_04</span> = <span class="s">"i would choose you every time"</span>
<span class="k">reason_05</span> = <span class="s">"i love you more each day ♥"</span></pre>
  </div>
  <?=fr($P,0,'vi',1,'--r:1.5deg')?>
</div>
</section>

<section class="pg" id="m1" style="--ac:#ff5fd2">
<?=ph('m1','$ cat memories/first_hello.md','Where it all began','// the day my heart said yes to you ♥','first hello','iHeart')?>
<div class="lay"><?=film($P,[3,4,5,6],'lv')?></div>
</section>

<section class="pg" id="m2" style="--ac:#8b5cff">
<?=ph('m2','$ cat memories/us_together.md','Our little adventures','// every moment with you is my favorite ♥','just us','iCam')?>
<div class="artw"><div class="art" style="background-image:url('assets/img/ui-mem2.jpg')">
 <?=pic($P,7,'','left:41.8%;top:13.6%;width:27.4%;height:56%')?>
 <?=pic($P,8,'','left:74.6%;top:19%;width:12.8%;height:18.5%;rotate:7deg')?>
 <?=pic($P,9,'','left:72.4%;top:42.2%;width:11.6%;height:20.5%;rotate:-3deg')?>
</div></div>
</section>

<section class="pg" id="m3" style="--ac:#3d8bff">
<?=ph('m3','$ cat memories/level_up.md','Level up together','// good vibes · big dreams · more levels ♥','game on','iGem')?>
<div class="artw"><div class="art" style="background-image:url('assets/img/ui-mem3.jpg')">
 <?=pic($P,10,'','left:34.2%;top:18.8%;width:34%;height:53%')?>
 <?=pic($P,11,'','left:4%;top:34%;width:20.6%;height:22%;rotate:-7deg')?>
 <?=pic($P,12,'','left:10.6%;top:58.5%;width:16.8%;height:19.5%;rotate:-5deg')?>
 <?=pic($P,13,'','left:73.6%;top:29%;width:19.5%;height:23%;rotate:7deg')?>
 <?=pic($P,14,'','left:74.8%;top:57%;width:19.5%;height:20%;rotate:6deg')?>
</div></div>
</section>

<section class="pg" id="m4" style="--ac:#5fd4ff">
<?=ph('m4','$ ls memories/gallery','The gallery','// five worlds, one favorite person ♥','gallery','iCam')?>
<div class="gal">
 <?php foreach([[15,-24,'iHeart'],[16,-14,'iStar'],[17,0,'iGem'],[18,14,'iCam'],[19,24,'iMail']] as $k=>$g): ?>
 <div class="gc<?=$k==2?' c':''?>" style="--ry:<?=$g[1]?>deg"><span class="tp"><?=ic($g[2],'currentColor',16)?></span><?=pic($P,$g[0])?></div>
 <?php endforeach; ?>
</div>
</section>

<section class="pg" id="wishes" style="--ac:#8b5cff">
<?=ph('wishes','$ git log --wishes','Birthday wishes','','incoming','iChat')?>
<div class="wl">
  <div class="fcol">
    <form class="form" id="wishForm"><div class="c">$ git commit -m "wish"</div><input name="name" placeholder="Your name" maxlength="60" required><textarea name="message" rows="3" placeholder="Write your birthday wish…" maxlength="500" required></textarea><button class="btn">$ git push wish</button></form>
    <?=bloom('#ff5fd2','clamp(70px,20vh,180px)')?>
  </div>
  <div class="pcs" id="wall">
<?php $cl=['#5fd4ff','#8b5cff','#ff5fd2','#3d8bff','#b48cff'];foreach($wishes as $i=>$w): ?>
    <div class="pc" style="--c2:<?=$cl[$i%5]?>"><span class="mt"><?=h($w['message'])?></span><b>— <?=h($w['name'])?></b></div>
<?php endforeach; ?>
  </div>
</div>
<div class="pf"><div class="pr"><button class="sb" id="wprev">◀</button><span class="wcnt" id="wcnt">1 / 1</span><button class="sb" id="wnext">▶</button></div></div>
</section>

<section class="pg" id="surprise" style="--ac:#ff5fd2">
<?=ph('surprise','$ ./make_a_wish.sh --blow','Blow the candles','','bonus level','iStar')?>
<div class="stg">
  <?=bloom('#5fd4ff','clamp(110px,32vh,280px)')?>
  <div class="cakeW">
    <svg class="cakeS" id="cakeS" viewBox="0 0 300 270">
      <g fill="none" stroke-linejoin="round" stroke-linecap="round" stroke-width="2">
        <path d="M14 250H286" stroke="#a78bfa"/>
        <rect x="38" y="192" width="224" height="58" rx="8" stroke="#ff5fd2" fill="rgba(255,95,210,.08)"/>
        <rect x="68" y="136" width="164" height="56" rx="8" stroke="#8b5cff" fill="rgba(139,92,255,.1)"/>
        <rect x="98" y="88" width="104" height="48" rx="8" stroke="#3d8bff" fill="rgba(61,139,255,.1)"/>
        <path d="M38 208c14 10 24-6 38 0s24 10 38 0 24-6 38 0 24 10 38 0 24-6 38 0" stroke="#ff5fd2" stroke-opacity=".55"/>
        <path d="M68 152c12 8 20-5 32 0s20 8 32 0 20-5 32 0 20 8 32 0" stroke="#8b5cff" stroke-opacity=".55"/>
      </g>
      <?php foreach([118,134,150,166,182] as $x): ?><g class="cn2" transform="translate(<?=$x?> 0)"><path d="M0 88V58"/><path class="fl" d="M0 54C-6 46 -3 38 0 28C3 38 6 46 0 54Z"/></g><?php endforeach; ?>
    </svg>
    <div class="cake-c">// make a wish ♥</div>
    <button class="btn" id="blow">$ ./blow_candles.sh</button>
  </div>
  <?=bloom('#b48cff','clamp(110px,32vh,280px)')?>
</div>
</section>
  </main>
 </div>
</div>

<div class="paper" id="paper"><button class="x" id="px">✕</button><div id="ltxt" data-text="<?=h($S['greeting'])?>"></div></div>
<div class="sl" id="sl">💌 <?=nl2br(h($S['secret_letter']))?><small>// TAP TO CLOSE</small></div>
<div id="lb"><img alt=""><p></p></div>
<div id="out"></div><div id="ach"></div>
<audio id="bgm" loop preload="auto"><source src="<?=h($MUSIC)?>"><source src="<?=h($MUSIC2)?>"></audio>

<script>
const $=(s,r=document)=>r.querySelector(s),$$=(s,r=document)=>[...r.querySelectorAll(s)];
const PW='<?=h($PASSWORD)?>';
let started=false,unlocked=false,live=false;
const audio=$('#bgm'),play=$('#play'),cols=['#8b5cff','#3d8bff','#ff5fd2','#5fd4ff','#b48cff'];

/* ---- music ---- */
audio.lastElementChild.addEventListener('error',()=>{$('#musicNote').textContent='// music file not found: put your mp3 in assets/music/ (the-only-exception.mp3)'});
function startMusic(){started=true;audio.play().catch(()=>{})}
play.onclick=()=>{if(!started)return startMusic();audio.paused?audio.play():audio.pause()};
audio.onplay=()=>play.textContent='❚❚';audio.onpause=()=>play.textContent='▶';
const fmt=t=>Math.floor(t/60)+':'+String(Math.floor(t%60)).padStart(2,'0');
audio.ontimeupdate=()=>{$('#tm').textContent=fmt(audio.currentTime);if(audio.duration)$('#prog').style.width=audio.currentTime/audio.duration*100+'%'};
$('.pb').onclick=e=>{if(audio.duration){const r=e.currentTarget.getBoundingClientRect();audio.currentTime=(e.clientX-r.left)/r.width*audio.duration}};
function ach(t){const a=$('#ach');a.innerHTML='🏆 <span>achievement unlocked<br><b>'+t+'</b></span>';a.classList.add('show');clearTimeout(ach.t);ach.t=setTimeout(()=>a.classList.remove('show'),3400)}

/* ---- login ---- */
const gate=$('#gate'),gbox=$('#gbox'),term=$('#term');let busyLogin=false,pw=null;
const FN='<?=h($fn)?>',FULL='<?=h($S['name'])?>',FIRST='<?=h($FIRST)?>',SONGN='<?=h($SONG)?>';
const PS='C:\\Users\\'+FN+'\\birthday&gt;';
const wait=ms=>new Promise(r=>setTimeout(r,ms));
function line(html,cls=''){const d=document.createElement('div');d.className='tl '+cls;d.innerHTML=html;term.append(d);term.scrollTop=term.scrollHeight;return d}
async function typeLine(text,speed=30){const d=line('<span class="p">'+PS+'</span> <span class="ty"></span>'),s=d.querySelector('.ty');for(const ch of text){s.textContent+=ch;await wait(speed)}}
function prompt(){
  const d=line('<span class="p">'+PS+'</span><input id="pw" type="password" inputmode="numeric" autocomplete="off" spellcheck="false" aria-label="password"><button type="button" id="go">↵ run</button>','in');
  pw=$('#pw',d);pw.focus();pw.onkeydown=e=>{if(e.key==='Enter')submit(d)};$('#go',d).onclick=()=>submit(d)}
async function submit(d){
  if(busyLogin)return;const v=pw.value.trim();if(!v)return;
  d.className='tl';d.innerHTML='<span class="p">'+PS+'</span> '+'*'.repeat(v.length);
  if(v.split(/\s+/).pop()!==PW){gbox.classList.add('bad');setTimeout(()=>gbox.classList.remove('bad'),450);line('Access denied. Wrong password.','er');line('// hint: it is both our password ♥','note');prompt();return}
  busyLogin=true;startMusic();
  line('&nbsp;');line('<span class="p">'+PS+'</span> unlock --key=****');await wait(450);
  for(const [t,r] of [['Verifying password ....................','match'],['Decrypting memories .....................','OK'],['Decrypting love_letter.enc ................','OK'],['Lighting <?=$age?> candles ........................','OK'],['Syncing heartbeat ♥ ..........................','OK'],['Starting track "'+SONGN+'" ......','OK']]){line(t+' <b class="ok">'+r+'</b>');await wait(420)}
  const bar=line('Loading galaxy [<span id="bb"></span>] <span id="bp">0%</span>');
  for(let i=0;i<=20;i++){$('#bb',bar).textContent='█'.repeat(i)+'░'.repeat(20-i);$('#bp',bar).textContent=i*5+'%';await wait(75)}
  line('&nbsp;');line('Access granted. Happy Birthday, <b>'+FIRST+'</b>! ♥','grant');line('Launching website...');
  await wait(1100);gate.classList.add('go');unlocked=true;$('#intro').classList.add('on')}
gbox.onclick=()=>{if(pw&&document.body.contains(pw)&&!busyLogin)pw.focus()};
(async()=>{await wait(350);
  line('Birthday Galaxy [Version <?=$age?>.0.<?=$bd->format('Y')?>]');line('(c) '+FULL+'. All rights reserved. ♥');line('&nbsp;');
  await typeLine('birthday.exe --open --level <?=$age?>');await wait(350);
  line('Initializing birthday_galaxy ............ <b class="ok">OK</b>');await wait(300);
  line('Player one detected: <b>'+FULL+'</b> ♥');await wait(300);line('&nbsp;');
  line('// note: you know this one — it\'s both our password now ♥','note');await wait(300);
  line('Enter the password to open the birthday:');prompt()})();

/* ---- intro ---- */
$('#start').onclick=e=>{live=true;document.body.classList.add('live');$('#intro').classList.remove('on');location.hash='#home';route();setTimeout(()=>{rain();ach('access_granted')},500)};

/* ---- stars + confetti ---- */
const st=$('#stars'),sc=st.getContext('2d'),fx=$('#fx'),fc=fx.getContext('2d');let parts=[],stars=[];
function fxs(){fx.width=st.width=innerWidth;fx.height=st.height=innerHeight;stars=Array.from({length:Math.round(innerWidth*innerHeight/9500)},()=>({x:Math.random()*innerWidth,y:Math.random()*innerHeight,r:Math.random()*1.3+.2,p:Math.random()*6,s:.5+Math.random()*1.5}))}addEventListener('resize',()=>fxs());fxs();
function burst(x,y,n=70,pal=cols){for(let i=0;i<n;i++){const a=Math.random()*6.283,v=3+Math.random()*8;parts.push({x,y,vx:Math.cos(a)*v,vy:Math.sin(a)*v-4,c:pal[i%pal.length],w:6+Math.random()*9,h:3+Math.random()*5,l:1,r:Math.random()*6,vr:(Math.random()-.5)*.4})}}
function rain(pal){for(let i=0;i<5;i++)burst(Math.random()*innerWidth,innerHeight*.15+Math.random()*innerHeight*.3,45,pal)}
(function loop(t){sc.clearRect(0,0,st.width,st.height);
  for(const s of stars){sc.globalAlpha=.3+.6*Math.abs(Math.sin(t/900*s.s+s.p));sc.fillStyle='#d8ccff';sc.beginPath();sc.arc(s.x,s.y,s.r,0,7);sc.fill()}
  fc.clearRect(0,0,fx.width,fx.height);parts=parts.filter(p=>(p.l-=.01)>0);
  for(const p of parts){p.vy+=.18;p.vx*=.99;p.x+=p.vx;p.y+=p.vy;p.r+=p.vr;fc.save();fc.globalAlpha=Math.min(1,p.l*2);fc.fillStyle=p.c;fc.shadowColor=p.c;fc.shadowBlur=8;fc.translate(p.x,p.y);fc.rotate(p.r);fc.fillRect(-p.w/2,-p.h/2,p.w,p.h);fc.restore()}
  requestAnimationFrame(loop)})(0);

/* ---- clock + countdown ---- */
setInterval(()=>{$('#clk').textContent=new Date().toTimeString().slice(0,8)},1000);$('#clk').textContent=new Date().toTimeString().slice(0,8);
let daysLeft=0;
{const [,m,d]=$('.count').dataset.birthday.split('-').map(Number);
 const tick=()=>{const now=new Date(),td=new Date(now.getFullYear(),now.getMonth(),now.getDate());let nx=new Date(now.getFullYear(),m-1,d);if(nx<td)nx.setFullYear(now.getFullYear()+1);
  const z=n=>String(n).padStart(2,'0');
  if(nx.getTime()===td.getTime()){daysLeft=0;$('#cdLabel').textContent='🎉 IT IS TODAY! 🎉';$$('[data-u]').forEach(b=>b.textContent='00');return}
  const df=nx-now,v={d:Math.floor(df/864e5),h:Math.floor(df/36e5)%24,m:Math.floor(df/6e4)%60,s:Math.floor(df/1e3)%60};daysLeft=Math.ceil(df/864e5);
  for(const k in v)$(`[data-u=${k}]`).textContent=z(v[k])};tick();setInterval(tick,1000)}

/* ---- typewriter ---- */
function typeIt(el){const t=el.dataset.t.replace(/&amp;/g,'&');el.textContent='';el._k=(el._k||0)+1;const k=el._k;let i=0;setTimeout(function n(){if(el._k!==k)return;el.textContent=t.slice(0,++i);if(i<t.length)setTimeout(n,26)},250)}

/* ---- encrypted letter ---- */
{const el=$('#ltxt'),esc=w=>w.replace(/[&<>]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]));let k=0;
 el.innerHTML=el.dataset.text.split('\n').map(l=>l.trim()?l.trim().split(' ').map(w=>`<span style="--k:${k++}">${esc(w)}</span>`).join(' ')+'<br>':'<br>').join('')}
const file=$('#env');let busy=false;
file.onclick=()=>{if(busy)return;busy=true;file.classList.add('run');$('#fs').textContent='status: decrypting…';setTimeout(()=>{$('#fs').textContent='status: decrypted ✓';file.classList.remove('run');$('#paper').classList.add('open');busy=false},1400)};
$('#px').onclick=()=>closeModals();
function closeModals(){$('#paper').classList.remove('open');$('#sl').classList.remove('show');file.classList.remove('run');busy=false;$('#fs').textContent='status: locked'}

/* ---- wishes ---- */
let wp=0;const WS=6;
function wpage(d=0,set){const w=$$('#wall .pc'),n=Math.max(1,Math.ceil(w.length/WS));wp=set!==undefined?set:((wp+d)%n+n)%n;w.forEach((x,i)=>x.style.display=(i>=wp*WS&&i<(wp+1)*WS)?'':'none');$('#wcnt').textContent=(wp+1)+' / '+n}
$('#wprev').onclick=()=>wpage(-1);$('#wnext').onclick=()=>wpage(1);wpage(0);

/* ---- routing ---- */
const ids=$$('.pg').map(p=>p.id),seen=new Set();
function route(){const id=(location.hash||'#home').slice(1),pg=$('#'+id)||$('#home');closeModals();
  $$('.pg').forEach(p=>p.classList.toggle('on',p===pg));$$('#nav a').forEach(a=>a.classList.toggle('on',a.hash==='#'+pg.id));
  $$('.cmd',pg).forEach(typeIt);
  if(!seen.has(pg.id)){seen.add(pg.id);if(live&&pg.id!=='home')ach('page_found: '+pg.id)}
  if(pg.id==='surprise'){$('#cakeS').classList.remove('out');$('#blow').disabled=false;$('#blow').textContent='$ ./blow_candles.sh'}}
addEventListener('hashchange',route);route();
const goTo=id=>{location.hash='#'+id};
const step=d=>{const cur=ids.indexOf($('.pg.on').id);goTo(ids[(cur+d+ids.length)%ids.length])};

/* ---- command pad ---- */
const out=$('#out');
function say(t){out.textContent=t;out.classList.add('show');clearTimeout(say.t);say.t=setTimeout(()=>out.classList.remove('show'),5200)}
const alias={home:'home',cover:'home',letter:'letter',m1:'m1',mem1:'m1',m2:'m2',mem2:'m2',m3:'m3',mem3:'m3',m4:'m4',mem4:'m4',gallery:'m4',wishes:'wishes',wish:'wishes',surprise:'surprise',cake:'surprise'};
const C={help:()=>'commands: ls · cd <page> · next · prev · play · pause\n          celebrate · love · date · whoami',
 ls:()=>'home  letter  m1  m2  m3  m4  wishes  surprise',next:()=>{step(1);return 'next →'},prev:()=>{step(-1);return '← previous'},
 play:()=>{startMusic();return '♪ playing: '+SONGN},pause:()=>{audio.pause();return 'paused'},
 celebrate:()=>{rain();return '🎉 deploying confetti…'},love:()=>{rain(['#ff5fd2','#8b5cff','#fff']);return 'status: loved ♥'},
 date:()=>daysLeft?daysLeft+' days until the big day':'it is TODAY! 🎉',whoami:()=>FIRST+' — player one, level <?=$age?>'};
$('#cmd').onkeydown=e=>{if(e.key==='Escape'){e.target.blur();return}if(e.key!=='Enter')return;
  const v=e.target.value.trim().toLowerCase();e.target.value='';if(!v)return;const [c,...a]=v.split(/\s+/);let r;
  if(c==='cd'||c==='open'){const id=alias[(a[0]||'').replace(/[_-]/g,'')];r=id?(goTo(id),'cd '+id):'no such page'}
  else if(C[c])r=C[c]();else if(alias[c]){goTo(alias[c]);r='cd '+alias[c]}else r='command not found: '+c+' (try "help")';
  say('$ '+v+'\n'+r)};
addEventListener('keydown',e=>{if(!live||e.target.matches('input,textarea'))return;
  if(e.key==='/'){e.preventDefault();$('#cmd').focus();return}if(e.key==='ArrowRight')step(1);if(e.key==='ArrowLeft')step(-1)});

/* ---- lightbox ---- */
const lb=$('#lb');
$$('.ph').forEach(p=>p.addEventListener('click',()=>{if(document.body.classList.contains('up-on'))return;$('img',lb).src=p.dataset.full;$('p',lb).textContent=p.dataset.cap;lb.classList.add('on')}));
lb.onclick=()=>lb.classList.remove('on');
const blow=$('#blow');blow.onclick=()=>{$('#cakeS').classList.add('out');blow.disabled=true;blow.textContent='★ your wish is on its way';setTimeout(()=>{rain();setTimeout(rain,600);setTimeout(rain,1200);$('#sl').classList.add('show');ach('wish_sent ♥')},500)};
$('#sl').onclick=()=>$('#sl').classList.remove('show');
$$('.cn2').forEach(c=>c.onclick=()=>{const f=$('.fl',c);f.style.opacity=f.style.opacity==='0'?1:0});

/* ---- wishes ---- */
$('#wishForm').onsubmit=async e=>{e.preventDefault();const f=e.target;
  try{const r=await(await fetch('index.php',{method:'POST',body:new FormData(f)})).json();
  if(r.ok){const d=document.createElement('div');d.className='pc';d.style.setProperty('--c2',cols[Math.floor(Math.random()*5)]);const m=document.createElement('span');m.className='mt';m.textContent=r.message;const b=document.createElement('b');b.textContent='— '+r.name;d.append(m,b);$('#wall').prepend(d);f.reset();wpage(0,0);rain()}}catch(x){}};

/* ---- upload toggle ---- */
{const t=$('#upToggle');
 const set=v=>{document.body.classList.toggle('up-on',v);$('#upSt').textContent=v?'ON':'OFF';try{localStorage.setItem('upOn',v?'1':'0')}catch(e){}};
 let s=false;try{s=localStorage.getItem('upOn')==='1'}catch(e){}
 t.checked=s;set(s);t.onchange=()=>{set(t.checked);t.blur()}}

/* ---- upload function ---- */
async function uploadImage(input,index){
  if(!input.files||!input.files[0])return;
  const fig=input.closest('.ph'),img=fig.querySelector('img'),span=fig.querySelector('.up-ov span'),old=span.textContent;
  const fd=new FormData();fd.append('image',input.files[0]);fd.append('frame_index',index);
  img.style.opacity='.5';span.textContent='UPLOADING...';
  try{const d=await(await fetch('index.php',{method:'POST',body:fd})).json();
    if(d.ok){img.src=d.url;fig.dataset.full=d.url;span.textContent='SUCCESS ✓'}else span.textContent='FAILED ✕'}
  catch(e){span.textContent='ERROR ✕'}
  finally{img.style.opacity='1';setTimeout(()=>span.textContent=old,2000)}}
</script>
</body></html>