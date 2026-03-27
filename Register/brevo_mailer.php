<?php
declare(strict_types=1);

function brevo_send_email(array $config, string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody): array
{
    $payload = [
        "sender" => [
            "name" => $config["from_name"],
            "email" => $config["from_email"]
        ],
        "to" => [
            ["email" => $toEmail, "name" => $toName]
        ],
        "subject" => $subject,
        "htmlContent" => $htmlBody,
        "textContent" => $textBody
    ];

    $ch = curl_init("https://api.brevo.com/v3/smtp/email");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "accept: application/json",
            "content-type: application/json",
            "api-key: " . $config["api_key"]
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) {
        return ["ok" => false, "error" => $err, "status" => $status];
    }

    if ($status < 200 || $status >= 300) {
        return ["ok" => false, "error" => $response ?: "API error", "status" => $status];
    }

    return ["ok" => true, "status" => $status];
}
