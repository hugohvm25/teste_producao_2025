<?php
declare(strict_types=1);

// Ajuste o path conforme sua estrutura
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/class/Database.php';

require_once __DIR__ . '/class/Models/User.php';
require_once __DIR__ . '/class/Models/Notification.php';
require_once __DIR__ . '/class/Models/Course.php';
require_once __DIR__ . '/class/Models/Prompt.php';
require_once __DIR__ . '/class/Models/Fluxo.php';
require_once __DIR__ . '/class/Models/WhatsAppMessage.php';

require_once __DIR__ . '/class/Repositories/UserRepository.php';
require_once __DIR__ . '/class/Repositories/NotificationRepository.php';
require_once __DIR__ . '/class/Repositories/CourseRepository.php';
require_once __DIR__ . '/class/Repositories/PromptRepository.php';
require_once __DIR__ . '/class/Repositories/FluxoRepository.php';
require_once __DIR__ . '/class/Repositories/WhatsAppMessageRepository.php';

require_once __DIR__ . '/class/Services/FlowService.php';

use App\Database;
use App\Repositories\UserRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\CourseRepository;
use App\Repositories\PromptRepository;
use App\Repositories\FluxoRepository;
use App\Repositories\WhatsAppMessageRepository;
use App\Services\FlowService;

// -------- Parâmetros de entrada (vêm do seu exemplo) --------
$user       = 3; // Rafael
$id_curso   = 3; // "Uso de Adornos e Riscos Associados"
$id_prompt  = 1; // notificação

// -------- Bootstrap de dependências --------
$db = new Database($conn);

$service = new FlowService(
    new UserRepository($db),
    new NotificationRepository($db),
    new CourseRepository($db),
    new PromptRepository($db),
    new FluxoRepository($db),
    new WhatsAppMessageRepository($db)
);

// -------- Carrega tudo em uma tacada só --------
$data = $service->bootstrapFlow($user, $id_curso, $id_prompt);

// Exemplo de leitura segura dos retornos:
$userObj  = $data['user'] ?? null;
$course   = $data['course'] ?? null;
$prompt   = $data['prompt'] ?? null;
$notif    = $data['notification'] ?? null;
$fluxo    = $data['fluxo'] ?? null;
$history  = $data['history'] ?? [];

// Se quiser retornar em JSON para consumir em outra camada:
header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'user' => $userObj ? [
        'id'        => $userObj->id,
        'firstname' => $userObj->firstname,
        'lastname'  => $userObj->lastname,
        'email'     => $userObj->email,
        'phone2'    => $userObj->phone2,
        'city'      => $userObj->city,
    ] : null,
    'course' => $course ? [
        'id'        => $course->id,
        'fullname'  => $course->fullname,
        'shortname' => $course->shortname,
    ] : null,
    'prompt' => $prompt ? [
        'id_prompt'   => $prompt->id_prompt,
        'prompt_type' => $prompt->prompt_type,
        'description' => $prompt->description,
        'prompt_text' => $prompt->prompt_text,
    ] : null,
    'notification' => $notif ? [
        'id'         => $notif->id,
        'useridto'   => $notif->useridto,
        'subject'    => $notif->subject,
        'fullmessage'=> $notif->fullmessage,
        'contexturl' => $notif->contexturl,
    ] : null,
    'fluxo' => $fluxo ? [
        'id_user'   => $fluxo->id_user,
        'id_course' => $fluxo->id_course,
        'id_status' => $fluxo->id_status,
        'pref_type' => $fluxo->pref_type,
    ] : null,
    'history' => array_map(function($m){
        return [
            'sender_phone'   => $m->sender_phone,
            'receiver_phone' => $m->receiver_phone,
            'message_text'   => $m->message_text,
            'media_type'     => $m->media_type,
            'media_url'      => $m->media_url,
            'received_at'    => $m->received_at,
        ];
    }, $history),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
