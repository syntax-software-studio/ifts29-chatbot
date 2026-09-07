<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_aitutor';
$plugin->version   = 2026090601;   // YYYYMMDDXX
// 2022112800 = build de Moodle 4.1.0 (28-nov-2022): mínimo requerido para que
// instale en ambas LTS objetivo (4.1 y 4.5), no solo en la más nueva.
$plugin->requires  = 2022112800;
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = '0.1 (POC)';
