<?php
/**
 * Plugin Name: OligoPoly Homepage Research Collections Cards
 * Description: Styles the homepage "Research collections" cards (.op9-stack-card). CSS only, front page only. Delete this file to revert.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'wp_head', function () {
	if ( ! is_front_page() ) {
		return;
	}
	echo "<style id=\"opl-home-collections-css\">\n";
	echo <<<'CSS'
/* Homepage "Research collections" cards (.op9-stack-card), scoped to #op9-home. */
#op9-home .op9-grid-4:has(> .op9-stack-card){counter-reset:op9-coll;align-items:stretch}
#op9-home .op9-grid-4:has(> .op9-stack-card)>p{display:none}
#op9-home .op9-section-head>div+p{margin:0;flex:none}
#op9-home .op9-stack-card{counter-increment:op9-coll;position:relative;display:flex;min-width:0;flex-direction:column;border:1px solid var(--op9-line);border-radius:16px;background:linear-gradient(180deg,var(--op9-panel-2),var(--op9-panel));overflow:hidden;box-shadow:0 1px 0 rgba(255,255,255,.04) inset,0 18px 40px -28px rgba(0,0,0,.9);transition:transform .22s ease,border-color .22s ease,box-shadow .22s ease}
#op9-home .op9-stack-card>p{display:none}
#op9-home .op9-stack-card>img{display:block;width:100%;height:auto;aspect-ratio:8/5;object-fit:cover;background:#091426;border-bottom:1px solid var(--op9-line);transition:transform .5s ease,filter .3s ease}
#op9-home .op9-stack-card::before{content:"Collection " counter(op9-coll,decimal-leading-zero);position:absolute;z-index:2;top:12px;left:12px;padding:5px 10px;border:1px solid rgba(233,200,255,.28);border-radius:999px;background:rgba(5,10,20,.72);-webkit-backdrop-filter:blur(6px);backdrop-filter:blur(6px);color:#e9c8ff;font-size:11px;font-weight:800;letter-spacing:.1em;line-height:1.2;text-transform:uppercase}
#op9-home .op9-stack-body{display:flex;flex:1;flex-direction:column;padding:18px 20px 20px}
#op9-home .op9-stack-body h3{margin:0;font-size:19px;line-height:1.3;letter-spacing:-.01em}
#op9-home .op9-stack-body h3 a.op9-stack-link{color:#fff;-webkit-text-fill-color:#fff;text-decoration:none;background:none}
#op9-home .op9-stack-link::after{content:"";position:absolute;inset:0;z-index:3;border-radius:16px}
#op9-home .op9-stack-body p{margin:auto 0 0;padding-top:14px}
#op9-home .op9-stack-body small{display:inline-flex;align-items:center;gap:6px;color:#d9b9f2;font-size:14px;font-weight:800;letter-spacing:.01em}
#op9-home .op9-stack-card:hover{transform:translateY(-3px);border-color:var(--op9-accent);box-shadow:0 1px 0 rgba(255,255,255,.06) inset,0 24px 48px -26px rgba(130,53,189,.55)}
#op9-home .op9-stack-card:hover>img{transform:scale(1.04);filter:saturate(1.08)}
#op9-home .op9-stack-card:hover small{color:#fff}
#op9-home .op9-stack-card:focus-within{border-color:var(--op9-accent)}
#op9-home .op9-stack-link:focus-visible{outline:none}
#op9-home .op9-stack-link:focus-visible::after{outline:3px solid #e9c8ff;outline-offset:-3px}
@media(prefers-reduced-motion:reduce){#op9-home .op9-stack-card,#op9-home .op9-stack-card>img{transition:none}#op9-home .op9-stack-card:hover,#op9-home .op9-stack-card:hover>img{transform:none}}
@media(max-width:980px){#op9-home .op9-stack-body h3{font-size:18px}}
CSS;
	echo "\n</style>\n";
}, 100 );
