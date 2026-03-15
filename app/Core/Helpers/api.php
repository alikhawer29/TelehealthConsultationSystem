<?php
function api_successWithData($message, $data)
{
    return ['status' => true, 'message' => $message, 'detail' => $data];
}

function api_successDataWithoutMessage($data)
{
    return ['status' => true, 'detail' => $data];
}

function api_success($message)
{
    return ['status' => true, 'message' => $message];
}

function api_errorWithData($data, $message)
{
    return ['status' => false, 'message' => $message, 'detail' => $data];
}

function api_error($message)
{
    return ['status' => false, 'message' => $message];
}

function api_validation_errors($errors, $message)
{
    return ['status' => false, 'message' => $message, 'errors' => $errors];
}
