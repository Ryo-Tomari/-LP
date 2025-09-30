<?php
// 日本のタイムゾーンに設定
date_default_timezone_set('Asia/Tokyo');

// ===== 設定項目 =====
// 1. 管理者（あなた）が応募通知を受け取るメールアドレス
$admin_email_to = 'kansai-job@kyoei-c.jp'; 

// 2. ユーザーに送信する受付メールの差出人アドレス
$user_email_from = 'kansai-job@kyoei-c.jp'; 

// 3. 【重要】Google Apps ScriptのウェブアプリURL
$gas_webhook_url = 'https://script.google.com/macros/s/AKfycbw9-QH9KFrhGW1ZzM81Bx2jk4QTwbXDu7Ppw1_9IVY31BdtAIifYI9Dky6VbneS2T8hfw/exec';
// ===================

// CORSヘッダーを追加
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// フォームから送信されたデータを取得
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';
$birthday = isset($_POST['birthday']) ? trim($_POST['birthday']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$tel = isset($_POST['tel']) ? trim($_POST['tel']) : '';
$location = isset($_POST['location']) ? trim($_POST['location']) : '';
$timestamp = date("Y-m-d H:i:s");

// 広告パラメータを取得
$gclid = isset($_POST['gclid']) ? trim($_POST['gclid']) : '';
$yclid = isset($_POST['yclid']) ? trim($_POST['yclid']) : '';
$campaign = isset($_POST['campaign']) ? trim($_POST['campaign']) : '';
$adgroup = isset($_POST['adgroup']) ? trim($_POST['adgroup']) : '';
$keyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : '';
$checkbox = isset($_POST['checkbox']) ? trim($_POST['checkbox']) : '';

// --- 1. 管理者への通知メール ---
$admin_subject = "yahoo広告新規応募がありました";
$admin_body = "Yahoo広告の応募フォームから新規応募がありました。\n";
$admin_body .= "---------------------------------\n";
$admin_body .= "応募日時: " . $timestamp . "\n";
$admin_body .= "名前: " . $name . "\n";
$admin_body .= "性別: " . $gender . "\n";
$admin_body .= "生年月日: " . $birthday . "\n";
$admin_body .= "メールアドレス: " . $email . "\n";
$admin_body .= "電話番号: " . $tel . "\n";
$admin_body .= "現在地: " . $location . "\n";
$admin_body .= "---------------------------------\n";
$admin_body .= "■広告パラメータ\n";
$admin_body .= "GCLID: " . $gclid . "\n";
$admin_body .= "YCLID: " . $yclid . "\n";
$admin_body .= "キャンペーン: " . $campaign . "\n";
$admin_body .= "広告グループ: " . $adgroup . "\n";
$admin_body .= "検索KW: " . $keyword . "\n";
$admin_body .= "---------------------------------\n";
$admin_headers = "From: " . $user_email_from;

// --- 2. ユーザーへの自動返信メール ---
$user_subject = "【寮ナビ】ご応募ありがとうございます";
$user_body = $name . " 様\n\n";
$user_body .= "この度は「寮ナビ」にご応募いただき、誠にありがとうございます。\n";
$user_body .= "担当者より折り返しご連絡いたしますので、今しばらくお待ちくださいませ。\n\n";
$user_body .= "---------------------------------\n";
$user_headers = "From: " . $user_email_from;

// メール送信処理
$admin_mail_success = mb_send_mail($admin_email_to, $admin_subject, $admin_body, $admin_headers);
$user_mail_success = mb_send_mail($email, $user_subject, $user_body, $user_headers);

// --- 3. Googleスプレッドシートへのデータ転送 ---
$post_data = [
    'name' => $name, 'gender' => $gender, 'birthday' => $birthday,
    'email' => $email, 'tel' => $tel, 'location' => $location,
    'gclid' => $gclid, 'yclid' => $yclid, 'campaign' => $campaign,
    'adgroup' => $adgroup, 'keyword' => $keyword, 'checkbox' => $checkbox
];
$ch = curl_init($gas_webhook_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_exec($ch);
curl_close($ch);

if ($admin_mail_success && $user_mail_success) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'メールの送信に失敗しました。']);
}
?>