<?php
defined('MOODLE_INTERNAL') || die();

/**
 * ¿Este curso tiene el widget habilitado? Compara contra
 * local_aitutor/enabledcourses (lista de shortnames, configurable en
 * Administración del sitio > Plugins locales > AI Tutor). Por defecto
 * (config vacía) NO se muestra en ningún curso -- opt-in explícito, nunca
 * opt-out, para no exponer el tutor por accidente en un curso nuevo.
 *
 * Se usa tanto para decidir si se inyecta la burbuja (lib.php) como para
 * autorizar la llamada AJAX (ajax.php) -- el chequeo del lado servidor es
 * el que realmente importa, el del frontend es solo para no mostrarla.
 */
function local_aitutor_is_course_enabled(int $courseid): bool {
    if ($courseid == SITEID) {
        return false; // Nunca en la portada del sitio, solo dentro de cursos.
    }

    $raw = trim((string) get_config('local_aitutor', 'enabledcourses'));
    if ($raw === '') {
        return false;
    }

    $enabled = array_filter(array_map('trim', explode(',', $raw)));
    if (!$enabled) {
        return false;
    }

    $course = get_course($courseid);
    return in_array($course->shortname, $enabled, true);
}

/**
 * Callback clásico de Moodle: cualquier plugin con una función
 * <frankenstyle>_before_footer() en su lib.php se ejecuta antes del </body>
 * y lo que devuelve se inyecta en el HTML de la página. Funciona igual en
 * Moodle 4.1 y 4.5 (Moodle 4.4 sumó la Hooks API en paralelo, pero mantiene
 * este mecanismo por compatibilidad).
 */
function local_aitutor_before_footer() {
    global $PAGE, $USER;

    // Ni antes de loguearse ni como invitado -- para la POC alcanza con
    // usuarios reales autenticados.
    if (!isloggedin() || isguestuser()) {
        return '';
    }

    $courseid = isset($PAGE->course->id) ? (int) $PAGE->course->id : SITEID;

    if (!local_aitutor_is_course_enabled($courseid)) {
        return '';
    }

    $sesskey  = sesskey();
    $ajaxurl  = (new moodle_url('/local/aitutor/ajax.php'))->out(false);
    $username = s(fullname($USER));

    // JS/CSS inline y vanilla a propósito: para esta POC de integración
    // interesa validar el circuito (contexto + sesskey + AJAX), no armar
    // un frontend prolijo. Se puede migrar a AMD/Mustache más adelante.
    return <<<HTML
<div id="aitutor-widget" style="position:fixed;bottom:20px;right:20px;z-index:100000;font-family:Arial,sans-serif;">
  <button id="aitutor-bubble-btn" style="width:56px;height:56px;border-radius:50%;border:none;background:#0f6cbf;color:#fff;font-size:24px;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.3);">
    🤖
  </button>
  <div id="aitutor-panel" style="display:none;position:absolute;bottom:66px;right:0;width:320px;max-height:420px;background:#fff;border:1px solid #ccc;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,.25);display:none;flex-direction:column;overflow:hidden;">
    <div style="background:#0f6cbf;color:#fff;padding:10px 12px;font-weight:bold;">
      Tutor IA (POC) &mdash; {$username}
    </div>
    <div id="aitutor-log" style="flex:1;padding:10px;overflow-y:auto;font-size:13px;background:#f7f7f7;min-height:220px;max-height:220px;"></div>
    <form id="aitutor-form" style="display:flex;border-top:1px solid #ddd;">
      <input id="aitutor-input" type="text" placeholder="Escribí tu consulta..." autocomplete="off"
             style="flex:1;border:none;padding:8px;font-size:13px;outline:none;">
      <button type="submit" style="border:none;background:#0f6cbf;color:#fff;padding:0 14px;cursor:pointer;">Enviar</button>
    </form>
  </div>
</div>
<script>
(function () {
  var COURSEID = {$courseid};
  var SESSKEY  = "{$sesskey}";
  var AJAX_URL = "{$ajaxurl}";

  var btn   = document.getElementById('aitutor-bubble-btn');
  var panel = document.getElementById('aitutor-panel');
  var log   = document.getElementById('aitutor-log');
  var form  = document.getElementById('aitutor-form');
  var input = document.getElementById('aitutor-input');

  btn.addEventListener('click', function () {
    panel.style.display = (panel.style.display === 'flex') ? 'none' : 'flex';
  });

  function addMessage(text, who) {
    var p = document.createElement('div');
    p.style.margin = '0 0 8px 0';
    p.style.textAlign = (who === 'me') ? 'right' : 'left';
    p.innerHTML = '<span style="display:inline-block;padding:6px 10px;border-radius:12px;background:' +
      (who === 'me' ? '#0f6cbf;color:#fff' : '#e4e4e4;color:#000') + ';max-width:85%;">' +
      text.replace(/</g, '&lt;') + '</span>';
    log.appendChild(p);
    log.scrollTop = log.scrollHeight;
  }

  form.addEventListener('submit', function (ev) {
    ev.preventDefault();
    var msg = input.value.trim();
    if (!msg) { return; }
    addMessage(msg, 'me');
    input.value = '';

    var body = new URLSearchParams();
    body.set('sesskey', SESSKEY);
    body.set('courseid', COURSEID);
    body.set('message', msg);

    fetch(AJAX_URL, {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: body.toString(),
      credentials: 'same-origin'
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      addMessage(data.reply || '(sin respuesta)', 'bot');
    })
    .catch(function (err) {
      addMessage('Error de conexión con el tutor: ' + err, 'bot');
    });
  });
})();
</script>
HTML;
}
