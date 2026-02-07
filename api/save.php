<?php
$data = json_decode(file_get_contents('php://input'), true);

file_put_contents(
  __DIR__.'/../storage/answers.json',
  json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);
