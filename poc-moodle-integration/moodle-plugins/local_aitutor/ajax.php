<?php
// Endpoint AJAX del widget. Valida sesión + sesskey de Moodle (evita CSRF y
// que cualquiera fuera de una sesión logueada le pegue a esto), arma el
// contexto (usuario/curso) del lado del servidor, y responde.
//
// Para la POC: respuesta fija, sin llamar a ningún backend externo todavía.
// El día que exista el tutor real, acá va la llamada server-to-server
// (con su propia API key) en vez del texto hardcodeado.

define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

require_login(null, false);
require_sesskey();

$courseid = optional_param('courseid', SITEID, PARAM_INT);
$message  = optional_param('message', '', PARAM_TEXT);

$course = get_course($courseid);
require_login($course, false);

// Defensa real: aunque alguien arme el POST a mano con otro courseid, si el
// curso no está en la lista de habilitados no le contestamos nada útil.
if (!local_aitutor_is_course_enabled($course->id)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'El Tutor IA no está habilitado en este curso.']);
    exit;
}

global $USER;

$reply = sprintf(
    'Hola %s 👋 Soy el Tutor IA (placeholder de la POC). '
    . 'Recibí tu mensaje ("%s") en el curso "%s". '
    . 'Todavía no pienso de verdad -- esto solo confirma que el circuito '
    . 'Moodle → widget → backend funciona.',
    fullname($USER),
    s($message),
    format_string($course->fullname)
);

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'reply'   => $reply,
    'context' => [
        'userid'     => (int) $USER->id,
        'courseid'   => (int) $course->id,
        'coursename' => $course->fullname,
    ],
]);
