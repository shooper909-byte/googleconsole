<?php
/**
 * One-time production changes for SEO Phases 1-3. Run with WP-CLI after the MU
 * plugin is installed:  wp eval-file apply-seo-phases.php [--apply] [--report=path]
 * Without --apply it only reports what it would change. Every previous value is
 * stored in the option `opseo3_backup_20260929` before it is overwritten.
 */
$apply = in_array('--apply', $args ?? [], true);
$report_path = null;
foreach ($args ?? [] as $a) { if (strpos($a, '--report=') === 0) { $report_path = substr($a, 9); } }
global $wpdb;
$dir = __DIR__;
$r = ['mode' => $apply ? 'apply' : 'dry-run', 'changes' => [], 'errors' => []];
$backup = get_option('opseo3_backup_20260929', []);
$remember = function (string $key, $value) use (&$backup) { if (!array_key_exists($key, $backup)) { $backup[$key] = $value; } };

/* 1) Rank Math titles and descriptions ----------------------------------- */
$meta = [
    12  => ['Retatrutide 10 mg Research Peptide (OL-3RT) | OligoPoly Laboratories', 'Retatrutide (OL-3RT) 10 mg research peptide for laboratory GIP, GLP-1 and glucagon receptor studies. CAS 2381089-83-2. For research use only.'],
    39  => ['Tirzepatide 10 mg Research Peptide (OL-2TZ) | OligoPoly Laboratories', 'Tirzepatide (OL-2TZ) 10 mg research peptide for dual GIP/GLP-1 receptor studies. CAS 2023788-19-2, published COA batch 20260609. Research use only.'],
    436 => ['Cagrilintide 5 mg Research Peptide (OL-CAG) | OligoPoly Laboratories', 'Cagrilintide (OL-CAG) 5 mg lipidated amylin-analogue research peptide for laboratory receptor studies. CAS 1415456-99-3. For research use only.'],
    49  => ['BPC-157 10 mg Research Peptide | CAS 137525-51-0 | OligoPoly', 'BPC-157 10 mg research peptide (GEPPPGKPADDAGLV, CAS 137525-51-0) with compound identity, references and COA resources. For research use only.'],
    55  => ['BPC-157 + TB-500 Research Blend (10 mg + 10 mg) | OligoPoly', 'BPC-157 10 mg + TB-500 10 mg research blend with component identities, CAS numbers and documentation resources. For laboratory research use only.'],
    441 => ['GHK-Cu 50 mg Research Peptide | Published COA | OligoPoly', 'GHK-Cu 50 mg copper-peptide research material (CAS 89030-95-5) with published COA batch SGX-OL-2026-0811-K99. For laboratory research use only.'],
    443 => ['MOTS-c 10 mg Research Peptide | CAS 1627580-64-6 | OligoPoly', 'MOTS-c 10 mg research peptide (MRWQEMGYIFYPRKLR, CAS 1627580-64-6) for mitochondrial-signaling laboratory studies. For research use only.'],
    63  => ['NAD+ 500 mg Research Material | Published COA | OligoPoly', 'NAD+ 500 mg research material (CAS 53-84-9) with published COA batch AYK20260418-NAD500 for laboratory coenzyme studies. Research use only.'],
    447 => ['Selank 5 mg Research Peptide | Published COA | OligoPoly', 'Selank 5 mg research peptide (TKPRPGP, CAS 129954-34-3) with published COA batch OP-20260720-B001. For laboratory research use only.'],
    2505 => [null, 'Verify an OligoPoly research-material batch: search by batch ID to open the matching certificate of analysis and laboratory report. Research use only.'],
];
foreach ($meta as $id => [$title, $desc]) {
    if (get_post_status($id) !== 'publish') { $r['errors'][] = "post $id is not published; skipped"; continue; }
    foreach (['rank_math_title' => $title, 'rank_math_description' => $desc] as $key => $val) {
        if ($val === null) { continue; }
        $old = get_post_meta($id, $key, true);
        if ($old === $val) { continue; }
        $remember("meta:$id:$key", $old);
        $r['changes'][] = ['post' => $id, 'field' => $key, 'from' => $old, 'to' => $val];
        if ($apply) { update_post_meta($id, $key, $val); }
    }
}

/* 2) Canonicals for duplicate articles ----------------------------------- */
$canon = [
    1260 => 'https://www.oligopolypeptides.com/what-is-retatrutide/',        // /what-is-retatrutide-triple-agonist/
    764  => 'https://www.oligopolypeptides.com/retatrutide-vs-tirzepatide/', // /retatrutide-vs-tirzepatide-research/
    763  => 'https://www.oligopolypeptides.com/semaglutide-vs-tirzepatide/', // /tirzepatide-vs-semaglutide-research/
    1268 => 'https://www.oligopolypeptides.com/semaglutide-vs-tirzepatide/', // /semaglutide-vs-tirzepatide-research-comparison/
];
foreach ($canon as $id => $url) {
    if (get_post_status($id) !== 'publish') { $r['errors'][] = "canonical: post $id not published"; continue; }
    $old = get_post_meta($id, 'rank_math_canonical_url', true);
    if ($old === $url) { continue; }
    $remember("meta:$id:rank_math_canonical_url", $old);
    $r['changes'][] = ['post' => $id, 'field' => 'rank_math_canonical_url', 'from' => $old, 'to' => $url];
    if ($apply) { update_post_meta($id, 'rank_math_canonical_url', $url); }
}

/* 3) Signup popup heading: H1 -> H2 (one H1 per page) --------------------- */
$snip = $wpdb->get_row("SELECT id, code FROM {$wpdb->prefix}snippets WHERE id = 49", ARRAY_A);
if ($snip) {
    $code = $snip['code'];
    $open = '<h1 id="opl-entry-title">';
    $pos = strpos($code, $open);
    $close = $pos === false ? false : strpos($code, '</h1>', $pos);
    if ($pos === false || $close === false || substr_count($code, $open) !== 1) {
        if (strpos($code, '<h2 id="opl-entry-title">') === false) { $r['errors'][] = 'snippet 49: heading not found as expected'; }
    } else {
        $new = substr($code, 0, $close) . '</h2>' . substr($code, $close + 5);
        $new = str_replace($open, '<h2 id="opl-entry-title">', $new);
        $css_count = substr_count($new, '.opl-entry-card h1');
        $new = str_replace('.opl-entry-card h1', '.opl-entry-card #opl-entry-title', $new);
        $remember('snippet:49:code', $code);
        $r['changes'][] = ['snippet' => 49, 'field' => 'code', 'note' => "popup title h1->h2, $css_count CSS selector(s) retargeted"];
        if ($apply) {
            $wpdb->update("{$wpdb->prefix}snippets", ['code' => $new], ['id' => 49]);
        }
    }
}

/* 4) Product attributes read by the existing specification block ---------- */
$attrs = [
    12  => ['CAS number' => '2381089-83-2'],
    39  => ['CAS number' => '2023788-19-2', 'Molecular weight' => '4813 g/mol (computed; PubChem CID 166567236)'],
    436 => ['CAS number' => '1415456-99-3', 'Molecular weight' => '4409 g/mol (computed; PubChem CID 171397054)'],
    49  => ['CAS number' => '137525-51-0', 'Molecular weight' => '1419.5 g/mol (computed; PubChem CID 9941957)'],
    441 => ['CAS number' => '89030-95-5', 'Molecular weight' => '402.92 g/mol (computed; PubChem CID 71587328)'],
    443 => ['CAS number' => '1627580-64-6', 'Molecular weight' => '2174.6 g/mol (computed; PubChem CID 146675088)'],
    63  => ['CAS number' => '53-84-9', 'Molecular weight' => '663.4 g/mol (computed; PubChem CID 5892)'],
    447 => ['CAS number' => '129954-34-3', 'Molecular weight' => '751.9 g/mol (computed; PubChem CID 11765600)'],
];
foreach ($attrs as $id => $pairs) {
    $product = wc_get_product($id);
    if (!$product) { $r['errors'][] = "product $id not found"; continue; }
    $existing = $product->get_attributes();
    $remember("attrs:$id", get_post_meta($id, '_product_attributes', true));
    $changed = false;
    foreach ($pairs as $name => $value) {
        $slug = sanitize_title($name);
        foreach ($existing as $ex) {
            if (is_object($ex) && stripos($ex->get_name(), strtok($name, ' ')) !== false && sanitize_title($ex->get_name()) !== $slug) {
                $r['errors'][] = "product $id already has attribute '{$ex->get_name()}' that could match '$name'; skipped";
                continue 2;
            }
        }
        if (isset($existing[$slug]) && $existing[$slug]->get_options() === [$value]) { continue; }
        $attr = new WC_Product_Attribute();
        $attr->set_name($name);
        $attr->set_options([$value]);
        $attr->set_visible(true);
        $attr->set_variation(false);
        $attr->set_position(count($existing));
        $existing[$slug] = $attr;
        $changed = true;
        $r['changes'][] = ['product' => $id, 'attribute' => $name, 'to' => $value];
    }
    if ($changed && $apply) { $product->set_attributes($existing); $product->save(); }
}

/* 5) New cluster research guides ------------------------------------------ */
$cat = get_term_by('slug', 'research-guides', 'category');
$author = (int) $wpdb->get_var("SELECT post_author FROM {$wpdb->posts} WHERE ID = 1411");
$guides = [
    'what-is-tirzepatide-research-guide' => ['What Is Tirzepatide? Research Guide', 'What Is Tirzepatide? Dual GIP/GLP-1 Agonist Research Guide | OligoPoly', 'Tirzepatide research guide: compound identity (CAS 2023788-19-2), dual GIP/GLP-1 receptor pharmacology, analytical testing and documentation. Research use only.', 'tirzepatide research guide'],
    'what-is-ghk-cu-research-guide'      => ['What Is GHK-Cu? Research Guide', 'What Is GHK-Cu? Copper Peptide Research Guide | OligoPoly', 'GHK-Cu research guide: copper(II)-GHK identity, CAS numbers, ECM and gene-expression research, analytical testing and published COA. Research use only.', 'ghk-cu research guide'],
    'what-is-mots-c-research-guide'      => ['What Is MOTS-c? Research Guide', 'What Is MOTS-c? Mitochondrial-Derived Peptide Research Guide | OligoPoly', 'MOTS-c research guide: 16-residue mitochondrial-derived peptide identity, AMPK and mitonuclear signaling research, testing and documentation. Research use only.', 'mots-c research guide'],
];
foreach ($guides as $slug => [$title, $seo_title, $seo_desc, $kw]) {
    $html = @file_get_contents("$dir/content/$slug.html");
    if (!$html) { $r['errors'][] = "content missing for $slug"; continue; }
    $found = get_page_by_path($slug, OBJECT, 'post');
    if ($found) { $r['changes'][] = ['post' => $found->ID, 'note' => "$slug already exists; left unchanged"]; continue; }
    $r['changes'][] = ['new_post' => $slug, 'title' => $title];
    if ($apply) {
        $id = wp_insert_post(['post_type' => 'post', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $title,
            'post_content' => $html, 'post_author' => $author ?: 1, 'post_category' => $cat ? [(int) $cat->term_id] : [], 'comment_status' => 'closed'], true);
        if (is_wp_error($id)) { $r['errors'][] = "$slug: " . $id->get_error_message(); continue; }
        // wp_insert_post runs kses without an unfiltered_html user: store the reviewed HTML verbatim.
        $wpdb->update($wpdb->posts, ['post_content' => $html], ['ID' => $id]);
        clean_post_cache($id);
        update_post_meta($id, 'rank_math_title', $seo_title);
        update_post_meta($id, 'rank_math_description', $seo_desc);
        update_post_meta($id, 'rank_math_focus_keyword', $kw);
        update_post_meta($id, 'rank_math_robots', ['index']);
        $backup["created:$slug"] = $id;
    }
}

/* 6) Refresh caches --------------------------------------------------------- */
if ($apply) {
    update_option('opseo3_backup_20260929', $backup, false);
    if (class_exists('\\RankMath\\Sitemap\\Cache')) { \RankMath\Sitemap\Cache::invalidate_storage(); }
    delete_transient('code_snippets_cache');
    wp_cache_flush();
}
$r['backup_keys'] = array_keys($backup);
$json = wp_json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if ($report_path) { file_put_contents($report_path, $json); }
echo "mode={$r['mode']} changes=" . count($r['changes']) . " errors=" . count($r['errors']) . "\n";
