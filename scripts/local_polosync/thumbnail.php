<?php
/**
 * Bunny Stream thumbnail proxy.
 * Fetches thumbnails server-side (with correct Referer) so browsers
 * never make a cross-origin request to the Bunny CDN directly.
 *
 * Usage: /local/polosync/thumbnail.php?guid={bunny-video-guid}
 */

// Validate GUID — only hex digits and hyphens, exactly 36 chars
$guid = preg_replace('/[^a-f0-9\-]/i', '', $_GET['guid'] ?? '');
if (strlen($guid) !== 36) {
    http_response_code(400);
    exit;
}

$url = "https://vz-5315ec84-18f.b-cdn.net/{$guid}/thumbnail.jpg";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_REFERER        => 'https://cursos.dankarh.com.br/',
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (Moodle thumbnail proxy)',
    CURLOPT_SSL_VERIFYPEER => true,
]);

$data   = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$ctype  = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($status !== 200 || empty($data)) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . ($ctype ?: 'image/jpeg'));
header('Cache-Control: public, max-age=86400');
header('X-Content-Type-Options: nosniff');
echo $data;
