<?php
// 日本のタイムゾーンに設定
date_default_timezone_set('Asia/Tokyo');

// CORSヘッダーを追加
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

try {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        exit;
    }

    // ===== Google Apps Script連携URL =====
    $gas_webhook_url = 'https://script.google.com/macros/s/AKfycbymK92-sFpH4OuPqRIovgHqBceTqUjXzQKiHj6HYjFgi-6oTFJNkiWdJRuLNn7VexsAxQ/exec';

    // ===== フォームデータ取得 =====
    $name = trim($_POST['name'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $birthday = trim($_POST['birthday'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $tel = trim($_POST['tel'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $timestamp = date("Y-m-d H:i:s");

    $gclid = trim($_POST['gclid'] ?? '');
    $yclid = trim($_POST['yclid'] ?? '');
    $campaign = trim($_POST['campaign'] ?? '');
    $adgroup = trim($_POST['adgroup'] ?? '');
    $keyword = trim($_POST['keyword'] ?? '');
    $checkbox = trim($_POST['checkbox'] ?? '');

    // ===== 自動振り分けロジック（大阪45%、名古屋42%、福岡13%） =====
    $rand = mt_rand(1, 100);
    if ($rand <= 45) {
        $area = 'osaka';
        $admin_email_to = 'kansai-job@kyoei-c.jp,r-tomari@kyoeicenter.com';
        $user_email_from = 'kansai-job@kyoei-c.jp';
        $redirect_url = 'thanks_osaka.html';
    } elseif ($rand <= 87) { // 45 + 42 = 87
        $area = 'nagoya';
        $admin_email_to = 'nagoya-job@kyoei-c.jp,r-tomari@kyoeicenter.com';
        $user_email_from = 'nagoya-job@kyoei-c.jp';
        $redirect_url = 'thanks_nagoya.html';
    } else {
        $area = 'fukuoka';
        $admin_email_to = 'fukuoka-job@kyoei-c.jp,r-tomari@kyoeicenter.com';
        $user_email_from = 'fukuoka-job@kyoei-c.jp';
        $redirect_url = 'thanks_fukuoka.html';
    }

    // ===== 管理者への通知メール =====
    $admin_subject = "Google広告新規応募がありました";
    $admin_body = "Google広告の応募フォームから新規応募がありました。\n";
    $admin_body .= "■振り分けエリア: " . strtoupper($area) . "\n";
    $admin_body .= "---------------------------------\n";
    $admin_body .= "応募日時: $timestamp\n名前: $name\n性別: $gender\n生年月日: $birthday\n";
    $admin_body .= "メールアドレス: $email\n電話番号: $tel\n現在地: $location\n";
    $admin_headers = "From: $user_email_from";

    // ===== 応募者への自動返信メール =====
    $user_subject = "【即入寮ナビ】ご応募ありがとうございます";
    $user_body = "$name 様\n\nこの度は「即入寮ナビ」にご応募いただき、誠にありがとうございます。\n";
    $user_body .= "担当者より折り返しご連絡いたしますので、今しばらくお待ちくださいませ。\n\n---------------------------------\n";
    $user_headers = "From: $user_email_from";

    // ===== メール送信 =====
    mb_language("Japanese");
    mb_internal_encoding("UTF-8");
    $admin_mail_success = mb_send_mail($admin_email_to, $admin_subject, $admin_body, $admin_headers);
    $user_mail_success = mb_send_mail($email, $user_subject, $user_body, $user_headers);

    // ===== Google スプレッドシートへ送信 =====
    $post_data = [
        'name' => $name,
        'gender' => $gender,
        'birthday' => $birthday,
        'email' => $email,
        'tel' => $tel,
        'location' => $location,
        'gclid' => $gclid,
        'yclid' => $yclid,
        'campaign' => $campaign,
        'adgroup' => $adgroup,
        'keyword' => $keyword,
        'checkbox' => $checkbox,
        'area' => $area,
        'timestamp' => $timestamp
    ];

    $ch = curl_init($gas_webhook_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_exec($ch);
    curl_close($ch);

    // ===== 結果を返す =====
    if ($admin_mail_success && $user_mail_success) {
        echo json_encode(['success' => true, 'redirect' => $redirect_url]);
    } else {
        echo json_encode(['success' => false, 'message' => 'メールの送信に失敗しました。']);
    }

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'サーバーエラーが発生しました。',
        'error' => $e->getMessage()
    ]);
}
?>