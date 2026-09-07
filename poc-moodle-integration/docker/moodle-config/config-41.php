<?php
// Reconstruido a mano con los mismos valores que usó admin/cli/install.php
// originalmente. Ahora vive en el host (bind mount) para no volver a
// perderlo si se recrea el contenedor -- ver docker-compose.yml.
unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'pgsql';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'db-41';
$CFG->dbname    = 'moodle';
$CFG->dbuser    = 'moodle';
$CFG->dbpass    = 'moodle';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array(
    'dbpersist' => 0,
    'dbsocket'  => '',
    'dbport'    => '',
);

$CFG->wwwroot  = 'http://localhost:8041';
$CFG->dataroot = '/var/moodledata';
$CFG->admin    = 'admin';

$CFG->directorypermissions = 0777;

require_once(__DIR__ . '/lib/setup.php');
