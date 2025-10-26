<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\Course;
use App\Models\Prompt;
use App\Models\Notification;
use App\Models\Fluxo;
use App\Models\WhatsAppMessage;
use App\Repositories\UserRepository;
use App\Repositories\CourseRepository;
use App\Repositories\PromptRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\FluxoRepository;
use App\Repositories\WhatsAppMessageRepository;

class FlowService
{
    private UserRepository $users;
    private NotificationRepository $notifications;
    private CourseRepository $courses;
    private PromptRepository $prompts;
    private FluxoRepository $fluxos;
    private WhatsAppMessageRepository $messages;

    public function __construct(
        UserRepository $users,
        NotificationRepository $notifications,
        CourseRepository $courses,
        PromptRepository $prompts,
        FluxoRepository $fluxos,
        WhatsAppMessageRepository $messages
    ) {
        $this->users = $users;
        $this->notifications = $notifications;
        $this->courses = $courses;
        $this->prompts = $prompts;
        $this->fluxos = $fluxos;
        $this->messages = $messages;
    }

    /**
     * Carrega todos os dados necessários para iniciar/continuar o fluxo.
     *
     * @param int $userId
     * @param int $courseId
     * @param int $promptId
     * @return array{
     *   user?:User, course?:Course, prompt?:Prompt,
     *   notification?:Notification, fluxo?:Fluxo, history:WhatsAppMessage[]
     * }
     */
    public function bootstrapFlow(int $userId, int $courseId, int $promptId): array
    {
        $data = [
            'user' => null,
            'course' => null,
            'prompt' => null,
            'notification' => null,
            'fluxo' => null,
            'history' => [],
        ];

        // Usuário
        $user = $this->users->findById($userId);
        if ($user instanceof User) {
            $data['user'] = $user;

            // Histórico WhatsApp (se tiver phone2)
            if (!empty($user->phone2)) {
                $data['history'] = $this->messages->findHistoryByPhone($user->phone2, 200);
            }
        }

        // Notificação
        $notification = $this->notifications->findFirstForUser($userId);
        if ($notification instanceof Notification) {
            $data['notification'] = $notification;
        }

        // Curso
        $course = $this->courses->findById($courseId);
        if ($course instanceof Course) {
            $data['course'] = $course;
        }

        // Prompt
        $prompt = $this->prompts->findById($promptId);
        if ($prompt instanceof Prompt) {
            $data['prompt'] = $prompt;
        }

        // Fluxo (andamento)
        $fluxo = $this->fluxos->findByUserAndCourse($userId, $courseId);
        if ($fluxo instanceof Fluxo) {
            $data['fluxo'] = $fluxo;
        }

        return $data;
    }
}
