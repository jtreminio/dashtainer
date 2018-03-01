<?php

namespace Dashtainer\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

class AjaxResponse extends JsonResponse
{
    const AJAX_ERROR         = 'error';
    const AJAX_MODAL         = 'modal';
    const AJAX_MODAL_CLOSE   = 'modal_close';
    const AJAX_MODAL_CONTENT = 'modal_content';
    const AJAX_MODAL_REMOTE  = 'modal_remote';
    const AJAX_REDIRECT      = 'redirect';
    const AJAX_SUCCESS       = 'success';
}
