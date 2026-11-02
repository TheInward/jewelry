<?php
/** SiteGround's official Speed Optimizer socket protocol, scoped to this site.
 * Run with wp eval-file; does not install or change optimizer settings.
 */
defined('ABSPATH') || exit;
if (!defined('WP_CLI') || !WP_CLI) exit;
$socket = '/chroot/tmp/site-tools.sock';
if (!file_exists($socket)) WP_CLI::error('SiteGround cache socket unavailable.');
$connection = stream_socket_client('unix://'.$socket, $errno, $error, 5);
if (!$connection) WP_CLI::error('Cannot connect to SiteGround cache service.');
stream_set_timeout($connection, 15);
$request = ['api'=>'domain-all','cmd'=>'update','settings'=>['json'=>1], 'params'=>['flush_cache'=>'1','id'=>wp_parse_url(home_url(),PHP_URL_HOST),'path'=>'/(.*)']];
fwrite($connection, wp_json_encode($request, JSON_FORCE_OBJECT)."\n");
$result = json_decode(fgets($connection, 32768), true);
fclose($connection);
if (!$result || isset($result['err_code'])) WP_CLI::error('SiteGround cache purge failed.');
WP_CLI::success('SiteGround dynamic cache purged for this site.');
