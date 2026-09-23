<?php


return [
    'credentials' => [
        'type' => 'service_account',
        'project_id' => env('FIREBASE_PROJECT_ID', "grinta-2ac3f"),
        'private_key_id' => env('FIREBASE_PRIVATE_KEY_ID', "1531e26e1c2ddd4582e06ea0ec2423b2646ae546"),
        'private_key' => str_replace('\\n', "\n", env('FIREBASE_PRIVATE_KEY', "-----BEGIN PRIVATE KEY-----\nMIIEvwIBADANBgkqhkiG9w0BAQEFAASCBKkwggSlAgEAAoIBAQC9pbFO+lVwHsmE\nJVZAKveOfVSl8XgaxXTMuVMJtbIxiezQXRj/dRCIet2sfmzg4H2EAtjGhld3Nmvn\nipCrKMkEOrsMzjqI05dF5hKOjOev1rnU1C4CmzdKnqM7EjdnEt+3TQRD0WWqRU8x\nVSWse5E2wV35p5WC2fVC7KoO+dTP/g2L4juE09WGzm8wgDAIkPb5twFSzXejO67+\noWUW4UXD6tryH64GxMwDKNzxuAfOqnyTBceifUEWWUaiCSBAnISYeEhYMRlMfXgr\nkHqXDvASzO/yyrPierqUJQS36envmtr3yzbE4o8Sg6rIZEalHzx4xvOq4pEsrHsj\nkR0JKnq9AgMBAAECggEAD0RzjcQhdLFFz9mho/DRlSsJ0YIrBRy4VnLk+HckLVDJ\nO80C4i3ucs5RYj2bTk9ES4hfIzxVdkdvUGibVOwnoquHu3Quyi0elIX9IHS/gixr\nMXMTcSpFvUAgK/U/eJDQadVNbphA8wA5a+NK8SPRgPL4Oc0AAFTDpeuflS0PsN7D\nPCh4W/v5U2EFdtzPEitFmzcQY2DeWWiabSlzlIwgPs8SWK5zc+CggWsahUlpKGdF\nxfVMQiEk4N6qm7ipQ1OGhHYLzs82U8OmiQvt81Lo7hnSn2kh2vO4JKBAZTOgBWkw\nnZhCpiC36ep06yAgIZkFIXj/6GYczlFooxq4YydnkQKBgQD6WM7nRiWBN55Pa1JY\nh0FdiLpjp8ItcbjkX1eCIF5LSsMpQqW1SPXGs++YgzU2tfmnZaoZJ5Sw2Lmu80TJ\nLhO3zD2Ty4vJ9Ln4zroMxxCuZSS3C5XGlnF4muY6OYQpRzpOc0NdcygTCzeBmGtv\ngZ5AQy3JzT7SuUqnPiiQ0UILQwKBgQDB7f67Fq7ZGIkrOhpW4BsDSrgfZLlNMDQA\nGrvHf/Bnmf0iEs9c+JztiBwoRSyGF0+vE5b25vJQ3u5qu5yy4DpMs42RLyTFAkUJ\nIVPbge2/NAaDX1uuzA6KvUDgWuDwKJwI/G7+d8zS2fXZV1In/TaEh/tN5eMm2SuI\nwoEG+/IB/wKBgQDs3comGMCzmiQ3kJvBF3hJP2zbjYaz6L2/llX90RJmur4c8+pF\ntFqXPvibnMlkkpk8QXzHgCO3j9dgKNwUXXxakuxQQm5pDxxGxZJTNKYb26b5agS0\nePOz4RukS3c4dyQ7xeMfMC3iluJVxBkiz8kjlGxmW8PtLVPtGWrYWqXacQKBgQCD\nngIXedKbVvKpmtqLgbfzqP+DlvnIcGGqfHpbcPJ5beKGAJwp7jWbZJgvoJsSOroD\nCdmYoq9swZCwcbptEI+gxO/czFE5QEwYYT0nqmvwK+ALw2lXHfj0onNokWU+uWEF\nHEY8gJRLulAH/SKuL0WT9zJQ8SgUkqngPJoTYmUigwKBgQCg8bYEQwhqtYfD3bt1\n+QoRrkcI9AvhEgMA4qD3O1VgLiT0X+/NYD7nt8jp3FWlNc/LKakHsCfU6sHFyiU+\nVxJTmvmmBA4kYXeSqkNjjqEJfJa91g4T9IlN/1ZgHDVLYc/XQuBHqsZLAAb2Gjuu\njRk4lWjdJoiOVNUCQqb5dKUMgg==\n-----END PRIVATE KEY-----\n")),
        'client_email' => env('FIREBASE_CLIENT_EMAIL', "firebase-adminsdk-146kr@grinta-2ac3f.iam.gserviceaccount.com"),
        'client_id' => env('FIREBASE_CLIENT_ID', "107434020909823112642"),
        'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
        'token_uri' => 'https://oauth2.googleapis.com/token',
        'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
        'client_x509_cert_url' => 'https://www.googleapis.com/robot/v1/metadata/x509/firebase-adminsdk-146kr%40grinta-2ac3f.iam.gserviceaccount.com',
        'universe_domain' => 'googleapis.com',
    ],
];
