<?php
function h(string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function jd(string $s): array { $r = json_decode($s ?? '[]', true); return is_array($r) ? array_filter($r) : []; }

$nome       = $data['nome_fantasia'] ?: $data['nome_empresarial'];
$razao      = $data['nome_empresarial'];
$fantasia   = $data['nome_fantasia'];
$cnpj       = $data['cnpj'];
$tel        = $data['telefone'] ?? '';
$email      = $data['email_empresa'] ?? '';
$logradouro = $data['logradouro'] ?? '';
$numero     = $data['numero'] ?? '';
$complemento= $data['complemento'] ?? '';
$bairro     = $data['bairro'] ?? '';
$cidade     = $data['municipio'] ?? '';
$uf         = $data['uf'] ?? '';
$cep        = $data['cep'] ?? '';
$abertura   = $data['data_abertura'] ?? '';
$situacao   = strtoupper($data['situacao'] ?? 'ATIVA');
$porte      = $data['porte'] ?? '';
$natJur     = $data['natureza_juridica'] ?? '';
$atPrinc    = $data['atividade_principal'] ?? '';

$sobre        = $data['sobre_empresa'] ?? '';
$missao       = $data['missao'] ?? '';
$visao        = $data['visao'] ?? '';
$valores      = jd($data['valores'] ?? '');
$diferenciais = jd($data['diferenciais'] ?? '');
$servicos     = jd($data['servicos'] ?? '');
$beneficios   = jd($data['beneficios'] ?? '');
$atividadesArr= jd($data['atividades_secundarias'] ?? '');

$endLine1 = trim("$logradouro" . ($numero ? ", $numero" : '') . ($complemento ? " $complemento" : ''));
$endLine2 = trim("$bairro" . ($bairro && $cidade ? ' — ' : '') . "$cidade" . ($cidade && $uf ? "/$uf" : $uf));
$mapQuery  = urlencode("$logradouro $numero, $bairro, $cidade $uf $cep Brasil");

$telW = preg_replace('/\D/', '', $tel);
if (strlen($telW) === 10 || strlen($telW) === 11) $telW = '55' . $telW; else $telW = '';

$iniciais = mb_strtoupper(mb_substr($nome, 0, 2, 'UTF-8'), 'UTF-8');

$hue  = abs(crc32($razao)) % 340;
$cor1 = "hsl($hue,55%,32%)";
$cor2 = "hsl($hue,55%,22%)";
$cor3 = "hsl($hue,55%,16%)";
$acc  = "hsl($hue,60%,42%)";
$accL = "hsl($hue,60%,94%)";
$accM = "hsl($hue,60%,88%)";

// Anos de mercado
$anos = 0;
if ($abertura) {
    $p = explode('/', $abertura);
    if (count($p) === 3 && is_numeric($p[2])) {
        $anos = max(0, (int)date('Y') - (int)$p[2]);
    }
}

$icones = ['🎯','⭐','🔒','🤝','💡','🚀','✅','🌟','💎','🏆','⚡','🔥'];
$srvIcn = ['🛠','📦','🔧','💼','📊','🌐','🏗','🤝','📋','🔑','🎨','⚙'];

// Schema.org JSON-LD para SEO
$schemaOrg = json_encode(array_filter([
    '@context'    => 'https://schema.org',
    '@type'       => 'LocalBusiness',
    'name'        => $nome,
    'legalName'   => $razao ?: null,
    'description' => $sobre ?: ($atPrinc ?: null),
    'telephone'   => $tel ?: null,
    'email'       => $email ?: null,
    'foundingDate'=> $abertura ? implode('-', array_reverse(explode('/', $abertura))) : null,
    'url'         => 'https://' . ($_SERVER['HTTP_HOST'] ?? ''),
    'address'     => array_filter([
        '@type'          => 'PostalAddress',
        'streetAddress'  => $endLine1 ?: null,
        'addressLocality'=> $cidade ?: null,
        'addressRegion'  => $uf ?: null,
        'postalCode'     => $cep ?: null,
        'addressCountry' => 'BR',
    ]),
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// Número de seções para determinar layout do nav
$temSobre      = $sobre || $missao || $visao;
$temServicos   = !empty($servicos);
$temDiferenciais = !empty($diferenciais);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="<?=h($sobre ?: ($atPrinc ?: "Site oficial de $nome — $cidade/$uf"))?>">
<meta name="robots" content="index,follow">
<meta property="og:title" content="<?=h($nome)?> — Site Oficial">
<meta property="og:description" content="<?=h($sobre ?: $atPrinc)?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="pt_BR">
<title><?=h($nome)?> — Site Oficial</title>
<script type="application/ld+json"><?=$schemaOrg?></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
:root{--c1:<?=$cor1?>;--c2:<?=$cor2?>;--c3:<?=$cor3?>;--acc:<?=$acc?>;--accL:<?=$accL?>;--accM:<?=$accM?>}
body{font-family:'Inter',sans-serif;background:#f1f5f9;color:#1e293b;line-height:1.7}

/* ── HEADER ── */
.hdr{background:var(--c3);color:#fff;padding:0 24px;height:62px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:999;box-shadow:0 2px 20px rgba(0,0,0,.4);gap:12px}
.hdr-l{display:flex;align-items:center;gap:12px;min-width:0}
.av{width:36px;height:36px;border-radius:10px;background:rgba(255,255,255,.15);border:2px solid rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;font-weight:900;font-size:13px;flex-shrink:0}
.hdr-name{font-size:14px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:280px}
.hdr-badge{background:<?=$situacao==='ATIVA'?'rgba(34,197,94,.2)':'rgba(239,68,68,.2)'?>;color:<?=$situacao==='ATIVA'?'#86efac':'#fca5a5'?>;border:1px solid <?=$situacao==='ATIVA'?'rgba(34,197,94,.3)':'rgba(239,68,68,.3)'?>;padding:3px 11px;border-radius:20px;font-size:10px;font-weight:700;white-space:nowrap;flex-shrink:0}
.hdr-nav{display:flex;gap:4px;align-items:center}
.hdr-nav a{color:rgba(255,255,255,.7);text-decoration:none;font-size:12px;font-weight:600;padding:5px 10px;border-radius:7px;transition:.15s;white-space:nowrap}
.hdr-nav a:hover{background:rgba(255,255,255,.12);color:#fff}
@media(max-width:640px){.hdr-nav{display:none}.hdr-name{max-width:160px}}

/* ── HERO ── */
.hero{background:linear-gradient(145deg,var(--c1) 0%,var(--c2) 60%,var(--c3) 100%);color:#fff;padding:60px 24px 50px;text-align:center;position:relative;overflow:hidden}
.hero::before{content:'';position:absolute;width:450px;height:450px;border-radius:50%;background:rgba(255,255,255,.04);top:-120px;right:-100px}
.hero::after{content:'';position:absolute;width:250px;height:250px;border-radius:50%;background:rgba(255,255,255,.04);bottom:-60px;left:-60px}
.hero-av{width:90px;height:90px;border-radius:24px;background:rgba(255,255,255,.15);border:3px solid rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:900;margin:0 auto 20px;position:relative;z-index:1;box-shadow:0 8px 32px rgba(0,0,0,.2)}
.hero h1{font-size:clamp(22px,4.5vw,40px);font-weight:900;margin-bottom:6px;position:relative;z-index:1;letter-spacing:-.5px}
.hero-razao{font-size:13px;opacity:.65;margin-bottom:14px;position:relative;z-index:1;font-style:italic}
.hero-desc{font-size:15px;opacity:.88;max-width:600px;margin:0 auto 22px;line-height:1.8;position:relative;z-index:1}
.pills{display:flex;gap:8px;justify-content:center;flex-wrap:wrap;position:relative;z-index:1;margin-bottom:24px}
.pill{background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.22);backdrop-filter:blur(4px);padding:5px 14px;border-radius:20px;font-size:12px;font-weight:600}
.hero-btns{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;position:relative;z-index:1}

/* ── STATS BAR ── */
.stats-bar{background:#fff;border-bottom:1px solid #e2e8f0;display:flex;justify-content:center;flex-wrap:wrap;gap:0}
.stat{padding:16px 28px;text-align:center;border-right:1px solid #e2e8f0;flex:1;min-width:100px}
.stat:last-child{border-right:none}
.stat-n{font-size:20px;font-weight:900;color:var(--acc);line-height:1;margin-bottom:3px}
.stat-l{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#94a3b8}
@media(max-width:480px){.stat{padding:12px 16px}.stat-n{font-size:17px}}

/* ── CONTAINER ── */
.wrap{max-width:960px;margin:0 auto;padding:0 16px}

/* ── SECTION ── */
.sec{padding:48px 0}
.sec-hd{text-align:center;margin-bottom:32px}
.sec-tag{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:2px;color:var(--acc);margin-bottom:8px}
.sec-title{font-size:clamp(20px,3vw,28px);font-weight:900;color:#0f172a;margin-bottom:10px;letter-spacing:-.3px}
.sec-sub{font-size:14px;color:#64748b;max-width:520px;margin:0 auto;line-height:1.6}

/* ── DIVIDER ── */
.divider{height:1px;background:linear-gradient(90deg,transparent,#e2e8f0 30%,#e2e8f0 70%,transparent);margin:4px 0}

/* ── SOBRE / MISSÃO / VISÃO ── */
.smv-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px}
.smv-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:28px;transition:all .2s}
.smv-card:hover{box-shadow:0 8px 30px rgba(0,0,0,.08);transform:translateY(-2px)}
.smv-card.destaque{border-top:3px solid var(--acc)}
.smv-icon{font-size:32px;margin-bottom:14px}
.smv-title{font-size:16px;font-weight:800;color:#0f172a;margin-bottom:10px}
.smv-text{font-size:14px;color:#475569;line-height:1.8}

/* ── VALORES ── */
.valores-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px}
.val-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;display:flex;gap:14px;align-items:flex-start;transition:all .2s}
.val-card:hover{box-shadow:0 6px 20px rgba(0,0,0,.07);transform:translateY(-2px)}
.val-ic{width:46px;height:46px;border-radius:13px;background:var(--accL);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0}
.val-label{font-weight:800;color:#0f172a;margin-bottom:3px;font-size:13px}
.val-txt{font-size:13px;color:#475569;line-height:1.6}

/* ── DIFERENCIAIS ── */
.dif-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:14px}
.dif-card{background:linear-gradient(135deg,var(--c1),var(--c2));color:#fff;border-radius:16px;padding:24px;display:flex;gap:16px;align-items:flex-start;transition:all .2s;position:relative;overflow:hidden}
.dif-card::before{content:'';position:absolute;width:100px;height:100px;border-radius:50%;background:rgba(255,255,255,.06);right:-20px;top:-20px}
.dif-card:hover{transform:translateY(-2px);box-shadow:0 10px 30px rgba(0,0,0,.2)}
.dif-num{width:38px;height:38px;border-radius:11px;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-weight:900;font-size:16px;flex-shrink:0}
.dif-txt{font-size:14px;line-height:1.65;opacity:.95;font-weight:500}

/* ── SERVIÇOS ── */
.srv-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px}
.srv-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:22px;display:flex;gap:14px;align-items:flex-start;transition:all .2s}
.srv-card:hover{box-shadow:0 6px 24px rgba(0,0,0,.08);border-color:var(--acc);transform:translateY(-2px)}
.srv-ic{width:46px;height:46px;border-radius:13px;background:var(--accL);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0}
.srv-txt{font-size:14px;font-weight:600;color:#1e293b;line-height:1.5}
.srv-txt span{display:block;font-size:12px;color:#94a3b8;font-weight:400;margin-top:3px}

/* ── ATIVIDADES ── */
.act-list{display:flex;flex-direction:column;gap:10px}
.act-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:15px 20px;display:flex;gap:12px;align-items:flex-start;transition:all .2s}
.act-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.06)}
.act-card.principal{border-left:4px solid var(--acc)}
.act-dot{width:10px;height:10px;border-radius:50%;background:var(--acc);flex-shrink:0;margin-top:7px}
.act-dot.sec{background:#cbd5e1}
.act-tag{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--acc);margin-bottom:3px}
.act-txt{font-size:13px;color:#334155;line-height:1.6}

/* ── BENEFÍCIOS ── */
.ben-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px}
.ben-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;display:flex;gap:14px;align-items:flex-start;transition:all .2s}
.ben-card:hover{box-shadow:0 6px 20px rgba(0,0,0,.07);transform:translateY(-2px)}
.ben-ic{width:44px;height:44px;border-radius:12px;background:#dcfce7;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
.ben-txt{font-size:14px;color:#334155;line-height:1.6;font-weight:500}

/* ── INFO CARDS ── */
.q-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:12px;margin-bottom:16px}
.q-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:16px;display:flex;align-items:center;gap:12px;transition:all .2s}
.q-card:hover{box-shadow:0 6px 20px rgba(0,0,0,.07);transform:translateY(-2px)}
.q-ic{width:42px;height:42px;border-radius:12px;background:var(--accL);display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
.q-lbl{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;margin-bottom:3px}
.q-val{font-size:13px;font-weight:700;color:#1e293b;word-break:break-word}

/* ── TABELA DE DADOS ── */
.dtbl{background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden}
.dr{display:flex;padding:13px 20px;border-bottom:1px solid #f1f5f9;font-size:13px;gap:16px}
.dr:last-child{border-bottom:none}
.dr:hover{background:#fafbfc}
.dk{color:#64748b;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.3px;min-width:180px;flex-shrink:0;padding-top:1px}
.dv{color:#1e293b;flex:1;word-break:break-word}

/* ── CONTATO ── */
.ct-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px}
.ct-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;display:flex;gap:14px;align-items:flex-start;transition:all .2s}
.ct-card:hover{box-shadow:0 6px 20px rgba(0,0,0,.07)}
.ct-ic{width:46px;height:46px;border-radius:13px;background:var(--accL);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0}
.ct-lbl{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;margin-bottom:4px}
.ct-val{font-size:14px;font-weight:700;color:#1e293b;word-break:break-word}
.ct-sub{font-size:12px;color:#64748b;margin-top:2px}
.map-frame{width:100%;height:300px;border:none;border-radius:14px;margin-top:16px;display:block}

/* ── BOTÕES ── */
.btn-row{display:flex;gap:10px;flex-wrap:wrap;margin-top:20px}
.btn{display:inline-flex;align-items:center;gap:8px;padding:12px 22px;border-radius:11px;font-size:13px;font-weight:700;text-decoration:none;transition:all .15s;border:none;cursor:pointer}
.btn-w{background:#25d366;color:#fff;box-shadow:0 4px 14px rgba(37,211,102,.35)}
.btn-w:hover{background:#1ebe5d;color:#fff;transform:translateY(-1px)}
.btn-m{background:var(--acc);color:#fff;box-shadow:0 4px 14px rgba(0,0,0,.15)}
.btn-m:hover{opacity:.9;color:#fff;transform:translateY(-1px)}
.btn-e{background:#f1f5f9;color:#334155;border:1px solid #e2e8f0}
.btn-e:hover{background:#e2e8f0;transform:translateY(-1px)}

/* ── FLOATING WHATSAPP ── */
.float-wa{position:fixed;bottom:24px;right:24px;width:56px;height:56px;background:#25d366;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 20px rgba(37,211,102,.5);z-index:1000;transition:all .2s;text-decoration:none}
.float-wa:hover{transform:scale(1.1);box-shadow:0 6px 28px rgba(37,211,102,.6)}
.float-wa svg{width:28px;height:28px;fill:#fff}

/* ── CTA BANNER ── */
.cta{background:linear-gradient(135deg,var(--c1),var(--c2));color:#fff;padding:56px 24px;text-align:center;margin-top:40px;position:relative;overflow:hidden}
.cta::before{content:'';position:absolute;width:300px;height:300px;border-radius:50%;background:rgba(255,255,255,.05);right:-80px;top:-80px}
.cta h2{font-size:clamp(20px,3.5vw,30px);font-weight:900;margin-bottom:10px;letter-spacing:-.3px;position:relative;z-index:1}
.cta p{font-size:15px;opacity:.85;margin-bottom:26px;max-width:500px;margin-left:auto;margin-right:auto;position:relative;z-index:1}

/* ── FOOTER ── */
.ftr{background:var(--c3);color:rgba(255,255,255,.6);padding:36px 24px;font-size:13px}
.ftr-inner{max-width:960px;margin:0 auto;display:grid;grid-template-columns:1fr auto;gap:20px;align-items:start}
.ftr-brand{font-size:18px;font-weight:900;color:#fff;margin-bottom:8px;letter-spacing:-.3px}
.ftr-info{font-size:12px;line-height:2;opacity:.7}
.ftr-badges{display:flex;flex-direction:column;gap:8px;align-items:flex-end}
.ftr-badge{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);padding:6px 14px;border-radius:20px;font-size:11px;font-weight:700;color:rgba(255,255,255,.8);white-space:nowrap}
.ftr-copy{max-width:960px;margin:20px auto 0;padding-top:16px;border-top:1px solid rgba(255,255,255,.1);text-align:center;font-size:11px;opacity:.5}
@media(max-width:580px){.ftr-inner{grid-template-columns:1fr}.ftr-badges{align-items:flex-start;flex-direction:row;flex-wrap:wrap}}

/* ── STATUS BADGE ── */
.sit-ok{display:inline-flex;align-items:center;gap:5px;background:#dcfce7;color:#166534;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700}
.sit-no{display:inline-flex;align-items:center;gap:5px;background:#fee2e2;color:#991b1b;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700}

/* ── FUNDO ALTERNADO ── */
.sec-alt{background:linear-gradient(180deg,#f8fafc,#f1f5f9)}

@media(max-width:640px){
  .dif-grid,.smv-grid{grid-template-columns:1fr}
  .dk{min-width:120px}
  .stat{padding:12px 14px}
  .hero{padding:44px 16px 40px}
  .cta{padding:40px 16px}
}
</style>
</head>
<body>

<?php if($telW):?>
<!-- FLOATING WHATSAPP -->
<a href="https://wa.me/<?=h($telW)?>?text=<?=urlencode("Olá! Encontrei o site da $nome e gostaria de mais informações.")?>" class="float-wa" target="_blank" title="Falar no WhatsApp">
  <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
</a>
<?php endif;?>

<!-- HEADER -->
<header class="hdr">
  <div class="hdr-l">
    <div class="av"><?=h($iniciais)?></div>
    <span class="hdr-name"><?=h($nome)?></span>
  </div>
  <nav class="hdr-nav">
    <?php if($temSobre):?><a href="#sobre">Sobre</a><?php endif;?>
    <?php if($temServicos):?><a href="#servicos">Serviços</a><?php endif;?>
    <?php if($temDiferenciais):?><a href="#diferenciais">Diferenciais</a><?php endif;?>
    <a href="#contato">Contato</a>
  </nav>
  <span class="hdr-badge"><?=$situacao==='ATIVA'?'● ATIVA':'● '.h($situacao)?></span>
</header>

<!-- HERO -->
<section class="hero">
  <div class="hero-av"><?=h($iniciais)?></div>
  <h1><?=h($nome)?></h1>
  <?php if($fantasia && $fantasia !== $razao):?>
    <p class="hero-razao"><?=h($razao)?></p>
  <?php endif;?>
  <?php if($sobre):?>
    <p class="hero-desc"><?=h($sobre)?></p>
  <?php elseif($atPrinc):?>
    <p class="hero-desc"><?=h($atPrinc)?></p>
  <?php endif;?>
  <div class="pills">
    <?php if($cidade):?><span class="pill">📍 <?=h($cidade)?>/<?=h($uf)?></span><?php endif;?>
    <?php if($abertura):?><span class="pill">📅 Desde <?=h($abertura)?></span><?php endif;?>
    <?php if($porte):?><span class="pill">🏷 <?=h($porte)?></span><?php endif;?>
  </div>
  <div class="hero-btns">
    <?php if($telW):?>
    <a href="https://wa.me/<?=h($telW)?>?text=<?=urlencode("Olá! Gostaria de mais informações sobre $nome.")?>" class="btn btn-w" target="_blank">
      <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
      Falar no WhatsApp
    </a>
    <?php endif;?>
    <?php if($mapQuery):?>
    <a href="https://www.google.com/maps/search/?api=1&query=<?=$mapQuery?>" target="_blank" class="btn btn-m">🗺 Ver no Mapa</a>
    <?php endif;?>
    <?php if($email):?>
    <a href="mailto:<?=h($email)?>" class="btn" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);">✉ E-mail</a>
    <?php endif;?>
  </div>
</section>

<!-- STATS BAR -->
<?php $statsCount = ($anos>0?1:0) + ($cidade?1:0) + ($situacao?1:0) + ($cnpj?1:0); if($statsCount>0):?>
<div class="stats-bar">
  <?php if($anos > 0):?>
  <div class="stat">
    <div class="stat-n"><?=$anos?>+</div>
    <div class="stat-l">Anos no Mercado</div>
  </div>
  <?php endif;?>
  <?php if($cidade):?>
  <div class="stat">
    <div class="stat-n" style="font-size:15px;"><?=h($cidade)?>/<?=h($uf)?></div>
    <div class="stat-l">Localização</div>
  </div>
  <?php endif;?>
  <?php if($situacao):?>
  <div class="stat">
    <div class="stat-n"><span class="<?=$situacao==='ATIVA'?'sit-ok':'sit-no'?>"><?=$situacao==='ATIVA'?'✓ ATIVA':'⚠ '.h($situacao)?></span></div>
    <div class="stat-l">Situação CNPJ</div>
  </div>
  <?php endif;?>
  <?php if($cnpj):?>
  <div class="stat">
    <div class="stat-n" style="font-size:15px;">🇧🇷 RF</div>
    <div class="stat-l">Receita Federal</div>
  </div>
  <?php endif;?>
</div>
<?php endif;?>

<div class="wrap">

  <!-- SOBRE / MISSÃO / VISÃO -->
  <?php if($sobre || $missao || $visao): ?>
  <div class="sec" id="sobre">
    <div class="sec-hd">
      <div class="sec-tag">🏢 Quem Somos</div>
      <h2 class="sec-title">Sobre a <?=h($nome)?></h2>
      <?php if($atPrinc):?><p class="sec-sub"><?=h($atPrinc)?></p><?php endif;?>
    </div>
    <div class="smv-grid">
      <?php if($sobre):?>
      <div class="smv-card destaque">
        <div class="smv-icon">🏢</div>
        <div class="smv-title">Nossa Empresa</div>
        <p class="smv-text"><?=h($sobre)?></p>
      </div>
      <?php endif;?>
      <?php if($missao):?>
      <div class="smv-card destaque">
        <div class="smv-icon">🎯</div>
        <div class="smv-title">Nossa Missão</div>
        <p class="smv-text"><?=h($missao)?></p>
      </div>
      <?php endif;?>
      <?php if($visao):?>
      <div class="smv-card destaque">
        <div class="smv-icon">👁️</div>
        <div class="smv-title">Nossa Visão</div>
        <p class="smv-text"><?=h($visao)?></p>
      </div>
      <?php endif;?>
    </div>
  </div>
  <div class="divider"></div>
  <?php endif;?>

  <!-- NOSSOS VALORES -->
  <?php if(!empty($valores)):?>
  <div class="sec">
    <div class="sec-hd">
      <div class="sec-tag">💎 Nossa Cultura</div>
      <h2 class="sec-title">Nossos Valores</h2>
      <p class="sec-sub">Os princípios que guiam cada decisão e ação da nossa equipe</p>
    </div>
    <div class="valores-grid">
      <?php foreach($valores as $i=>$v):
        $parts = explode(':', $v, 2);
        $icon = $icones[$i % count($icones)];
      ?>
      <div class="val-card">
        <div class="val-ic"><?=$icon?></div>
        <div>
          <?php if(count($parts) > 1):?>
            <div class="val-label"><?=h(trim($parts[0]))?></div>
            <div class="val-txt"><?=h(trim($parts[1]))?></div>
          <?php else:?>
            <div class="val-txt"><?=h($v)?></div>
          <?php endif;?>
        </div>
      </div>
      <?php endforeach;?>
    </div>
  </div>
  <div class="divider"></div>
  <?php endif;?>

  <!-- NOSSOS DIFERENCIAIS -->
  <?php if(!empty($diferenciais)):?>
  <div class="sec" id="diferenciais">
    <div class="sec-hd">
      <div class="sec-tag">🏆 Por Que Nos Escolher</div>
      <h2 class="sec-title">Nossos Diferenciais</h2>
      <p class="sec-sub">O que nos torna a melhor escolha para você e sua empresa</p>
    </div>
    <div class="dif-grid">
      <?php foreach($diferenciais as $i=>$d):?>
      <div class="dif-card">
        <div class="dif-num"><?=($i+1)?></div>
        <div class="dif-txt"><?=h($d)?></div>
      </div>
      <?php endforeach;?>
    </div>
  </div>
  <div class="divider"></div>
  <?php endif;?>

  <!-- NOSSOS SERVIÇOS -->
  <?php if(!empty($servicos)):?>
  <div class="sec" id="servicos">
    <div class="sec-hd">
      <div class="sec-tag">🛠️ O Que Oferecemos</div>
      <h2 class="sec-title">Nossos Serviços</h2>
      <p class="sec-sub">Soluções completas para atender todas as suas necessidades com excelência</p>
    </div>
    <div class="srv-grid">
      <?php foreach($servicos as $i=>$s):?>
      <div class="srv-card">
        <div class="srv-ic"><?=$srvIcn[$i % count($srvIcn)]?></div>
        <div class="srv-txt"><?=h($s)?></div>
      </div>
      <?php endforeach;?>
    </div>
  </div>
  <div class="divider"></div>
  <?php endif;?>

  <!-- ATIVIDADES ECONÔMICAS -->
  <?php if($atPrinc || !empty($atividadesArr)):?>
  <div class="sec">
    <div class="sec-hd">
      <div class="sec-tag">📊 Registro Oficial</div>
      <h2 class="sec-title">Atividades Econômicas</h2>
      <p class="sec-sub">Atividades registradas oficialmente na Receita Federal do Brasil</p>
    </div>
    <div class="act-list">
      <?php if($atPrinc):?>
      <div class="act-card principal">
        <div class="act-dot"></div>
        <div>
          <div class="act-tag">Atividade Principal</div>
          <div class="act-txt"><?=h($atPrinc)?></div>
        </div>
      </div>
      <?php endif;?>
      <?php foreach(array_slice($atividadesArr, 0, 8) as $at):?>
      <div class="act-card">
        <div class="act-dot sec"></div>
        <div class="act-txt"><?=h($at)?></div>
      </div>
      <?php endforeach;?>
    </div>
  </div>
  <div class="divider"></div>
  <?php endif;?>

  <!-- BENEFÍCIOS PARA O CLIENTE -->
  <?php if(!empty($beneficios)):?>
  <div class="sec">
    <div class="sec-hd">
      <div class="sec-tag">📈 Vantagens</div>
      <h2 class="sec-title">Benefícios Para o Cliente</h2>
      <p class="sec-sub">Tudo que você ganha ao escolher a <?=h($nome)?> como sua parceira</p>
    </div>
    <div class="ben-grid">
      <?php foreach($beneficios as $b):?>
      <div class="ben-card">
        <div class="ben-ic">✅</div>
        <div class="ben-txt"><?=h($b)?></div>
      </div>
      <?php endforeach;?>
    </div>
  </div>
  <div class="divider"></div>
  <?php endif;?>

  <!-- DADOS CADASTRAIS -->
  <div class="sec">
    <div class="sec-hd">
      <div class="sec-tag">🪪 Dados Oficiais</div>
      <h2 class="sec-title">Informações Cadastrais</h2>
      <p class="sec-sub">Dados registrados oficialmente na Receita Federal</p>
    </div>
    <div class="q-grid">
      <?php if($cnpj):?><div class="q-card"><div class="q-ic">🪪</div><div><div class="q-lbl">CNPJ</div><div class="q-val"><?=h($cnpj)?></div></div></div><?php endif;?>
      <?php if($tel):?><div class="q-card"><div class="q-ic">📞</div><div><div class="q-lbl">Telefone</div><div class="q-val"><?=h($tel)?></div></div></div><?php endif;?>
      <?php if($email):?><div class="q-card"><div class="q-ic">✉️</div><div><div class="q-lbl">E-mail</div><div class="q-val"><?=h($email)?></div></div></div><?php endif;?>
      <?php if($cidade):?><div class="q-card"><div class="q-ic">📍</div><div><div class="q-lbl">Cidade</div><div class="q-val"><?=h($cidade)?>/<?=h($uf)?></div></div></div><?php endif;?>
      <?php if($abertura):?><div class="q-card"><div class="q-ic">📅</div><div><div class="q-lbl">Fundação</div><div class="q-val"><?=h($abertura)?></div></div></div><?php endif;?>
      <?php if($situacao):?><div class="q-card"><div class="q-ic"><?=$situacao==='ATIVA'?'✅':'⚠️'?></div><div><div class="q-lbl">Situação</div><div class="q-val"><span class="<?=$situacao==='ATIVA'?'sit-ok':'sit-no'?>"><?=$situacao==='ATIVA'?'✓ ATIVA':'⚠ '.h($situacao)?></span></div></div></div><?php endif;?>
    </div>
    <div class="dtbl">
      <?php if($cnpj):?><div class="dr"><span class="dk">CNPJ</span><span class="dv"><?=h($cnpj)?></span></div><?php endif;?>
      <?php if($razao):?><div class="dr"><span class="dk">Razão Social</span><span class="dv"><?=h($razao)?></span></div><?php endif;?>
      <?php if($fantasia && $fantasia !== $razao):?><div class="dr"><span class="dk">Nome Fantasia</span><span class="dv"><?=h($fantasia)?></span></div><?php endif;?>
      <?php if($abertura):?><div class="dr"><span class="dk">Data de Abertura</span><span class="dv"><?=h($abertura)?></span></div><?php endif;?>
      <?php if($natJur):?><div class="dr"><span class="dk">Natureza Jurídica</span><span class="dv"><?=h($natJur)?></span></div><?php endif;?>
      <?php if($porte):?><div class="dr"><span class="dk">Porte</span><span class="dv"><?=h($porte)?></span></div><?php endif;?>
      <?php if($situacao):?><div class="dr"><span class="dk">Situação</span><span class="dv"><span class="<?=$situacao==='ATIVA'?'sit-ok':'sit-no'?>"><?=$situacao==='ATIVA'?'✓ ATIVA':'⚠ '.h($situacao)?></span></span></div><?php endif;?>
      <?php if($cep):?><div class="dr"><span class="dk">CEP</span><span class="dv"><?=h($cep)?></span></div><?php endif;?>
      <?php if($endLine1):?><div class="dr"><span class="dk">Endereço</span><span class="dv"><?=h($endLine1)?><?=$endLine2?", ".h($endLine2):''?></span></div><?php endif;?>
    </div>
  </div>

  <!-- CONTATO & LOCALIZAÇÃO -->
  <?php if($tel || $email || $endLine1): ?>
  <div class="sec" id="contato">
    <div class="sec-hd">
      <div class="sec-tag">📞 Fale Conosco</div>
      <h2 class="sec-title">Contato &amp; Localização</h2>
      <p class="sec-sub">Estamos prontos para atender você</p>
    </div>
    <div class="ct-grid">
      <?php if($endLine1):?>
      <div class="ct-card">
        <div class="ct-ic">📍</div>
        <div>
          <div class="ct-lbl">Endereço</div>
          <div class="ct-val"><?=h($endLine1)?></div>
          <?php if($bairro):?><div class="ct-sub"><?=h($bairro)?></div><?php endif;?>
          <?php if($cidade):?><div class="ct-sub"><?=h($cidade)?>/<?=h($uf)?></div><?php endif;?>
          <?php if($cep):?><div class="ct-sub">CEP <?=h($cep)?></div><?php endif;?>
        </div>
      </div>
      <?php endif;?>
      <?php if($tel):?>
      <div class="ct-card">
        <div class="ct-ic">📞</div>
        <div>
          <div class="ct-lbl">Telefone</div>
          <div class="ct-val"><?=h($tel)?></div>
          <?php if($telW):?><div class="ct-sub">WhatsApp disponível ✓</div><?php endif;?>
        </div>
      </div>
      <?php endif;?>
      <?php if($email):?>
      <div class="ct-card">
        <div class="ct-ic">✉️</div>
        <div>
          <div class="ct-lbl">E-mail</div>
          <a href="mailto:<?=h($email)?>" style="color:inherit;text-decoration:none;"><div class="ct-val"><?=h($email)?></div></a>
        </div>
      </div>
      <?php endif;?>
      <?php if($cnpj):?>
      <div class="ct-card">
        <div class="ct-ic">🪪</div>
        <div>
          <div class="ct-lbl">CNPJ</div>
          <div class="ct-val"><?=h($cnpj)?></div>
          <div class="ct-sub">Empresa <?=strtolower($situacao)?> na RF</div>
        </div>
      </div>
      <?php endif;?>
    </div>
    <div class="btn-row">
      <?php if($telW):?><a href="https://wa.me/<?=h($telW)?>?text=<?=urlencode("Olá! Encontrei o site da $nome e gostaria de saber mais.")?>" target="_blank" class="btn btn-w"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg> WhatsApp</a><?php endif;?>
      <?php if($mapQuery):?><a href="https://www.google.com/maps/search/?api=1&query=<?=$mapQuery?>" target="_blank" class="btn btn-m">🗺 Ver no Mapa</a><?php endif;?>
      <?php if($email):?><a href="mailto:<?=h($email)?>" class="btn btn-e">✉ Enviar E-mail</a><?php endif;?>
    </div>
    <?php if($mapQuery):?>
    <iframe class="map-frame" loading="lazy"
      src="https://maps.google.com/maps?q=<?=$mapQuery?>&output=embed&hl=pt-BR"
      allowfullscreen></iframe>
    <?php endif;?>
  </div>
  <?php endif;?>

</div>

<!-- CTA -->
<div class="cta">
  <h2>Entre em Contato com a <?=h($nome)?></h2>
  <p>Estamos prontos para atender você com qualidade, agilidade e profissionalismo</p>
  <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;position:relative;z-index:1;">
    <?php if($telW):?><a href="https://wa.me/<?=h($telW)?>?text=<?=urlencode("Olá! Quero saber mais sobre os serviços de $nome.")?>" class="btn btn-w" target="_blank">💬 Falar no WhatsApp</a><?php endif;?>
    <?php if($email):?><a href="mailto:<?=h($email)?>" class="btn" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);">✉ Enviar E-mail</a><?php endif;?>
    <?php if($mapQuery):?><a href="https://www.google.com/maps/search/?api=1&query=<?=$mapQuery?>" target="_blank" class="btn" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);">🗺 Como Chegar</a><?php endif;?>
  </div>
</div>

<!-- FOOTER -->
<footer class="ftr">
  <div class="ftr-inner">
    <div>
      <div class="ftr-brand"><?=h($nome)?></div>
      <div class="ftr-info">
        <?php if($cnpj):?>CNPJ: <?=h($cnpj)?><br><?php endif;?>
        <?php if($endLine1):?><?=h($endLine1)?><?=$cidade?" — ".h($cidade)."/".h($uf):''?><br><?php endif;?>
        <?php if($tel):?>Tel: <?=h($tel)?><?=$email?" &nbsp;|&nbsp; ".h($email):''?><?php elseif($email):?><?=h($email)?><?php endif;?>
      </div>
    </div>
    <div class="ftr-badges">
      <span class="ftr-badge">✅ CNPJ <?=$situacao==='ATIVA'?'ATIVA':'REGULAR'?></span>
      <span class="ftr-badge">🇧🇷 Receita Federal</span>
      <?php if($anos>0):?><span class="ftr-badge">⭐ <?=$anos?>+ Anos</span><?php endif;?>
    </div>
  </div>
  <div class="ftr-copy">Empresa registrada e regular perante a Receita Federal do Brasil</div>
</footer>

</body>
</html>

