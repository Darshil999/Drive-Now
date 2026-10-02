<?php
// Admin panel bootstrap: shares DB connection, session and helpers with the site.
require_once __DIR__ . '/../../includes/bootstrap.php';

// Status messages shown by individual admin pages.
$msg = null;
$error = null;

/**
 * Renders a state-changing admin action (confirm, cancel, delete...) as a
 * small POST form with the CSRF token, instead of a GET link.
 * $field is the POST key the page handler looks for; its value is the record id.
 */
function action_button($field, $id, $label, $confirmText, $class = 'btn btn-xs btn-default')
{
    return '<form method="post" class="inline-action" onsubmit="return confirm(' . e(json_encode($confirmText)) . ');">'
        . csrf_field()
        . '<input type="hidden" name="' . e($field) . '" value="' . (int) $id . '">'
        . '<button type="submit" class="' . e($class) . '">' . $label . '</button>'
        . '</form>';
}

function booking_status_badge($status)
{
    [$label, $class] = booking_status_label($status);
    return '<span class="label label-' . $class . '">' . $label . '</span>';
}

/** POSTed record id for an action button, or null when that action was not submitted. */
function posted_action_id($field)
{
    return isset($_POST[$field]) ? (int) $_POST[$field] : null;
}
