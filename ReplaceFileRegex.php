<?php

$ds = DIRECTORY_SEPARATOR;
$libreria = dirname(__FILE__) . "{$ds}class{$ds}db-far.php";

$dirExecution = getcwd();
// $document_root = str_replace(getcwd() . DIRECTORY_SEPARATOR, '', $document_root);
require  $libreria;

$limit = ini_get('memory_limit');
echo "Memory Limit: $limit ";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('memory_limit', '7024M');
$limit = ini_get('memory_limit');
echo "Memory Limit New: $limit " . PHP_EOL;

function getFile($argv)
{
  return $argv[1];
}
$config['multiple_replace'] = true;

$file = getFile($argv);
$executions = [
  $params = [
    null,
    "--regex=false",
    "`periodo_ano` char(4) COLLATE utf8_spanish_ci GENERATED ALWAYS AS (year(`tiempo_crea`)) STORED,",
    "`periodo_ano` char(4) COLLATE utf8_spanish_ci,",
    $file,
  ],
  $params = [
    null,
    "--regex=true",
    "(INSERT INTO `transaccion_log` VALUES \(.*\r?\n)",
    "",
    $file,
  ],
  $params = [
    null,
    "--regex=true",
    "(INSERT INTO `fe_apidatatnfactor` VALUES \(1.*)",
    "",
    $file,
  ],
];

$textToReplace = null;
foreach ($executions as $params) {
  list($search, $replace, $file) = getArgs($params);
  echo "Abriendo archivo y remplazando cambios: $file" . PHP_EOL;
  $textToReplace = replace($search, $replace, $file, $options, $textToReplace);
}
echo "Guardando cambios modificados: $file" . PHP_EOL;
file_put_contents($file, $textToReplace);

echo "Archivo modificado exitosamente;" . PHP_EOL;

/* Executar:
php /d/xampp7/htdocs/utils/ReplaceFileRegexPhp/ReplaceFileRegex.php dbc_db.sql

*/

/*
php db-far.php --regex=false "\`periodo_ano\` char(4) COLLATE utf8_spanish_ci GENERATED ALWAYS AS (year(\`tiempo_crea\`)) STORED," "\`periodo_ano\` char(4) COLLATE utf8_spanish_ci," dbc_ferrekarpin_1.sql \
&& php db-far.php --regex=false "(INSERT INTO `fe_apidatatnfactor` VALUES \(1.*)" ""\
&& php db-far.php --regex=false  "" \
*/
