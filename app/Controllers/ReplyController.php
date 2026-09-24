<?php
namespace App\Controllers;

use App\Services\ReplyService;
use Exception;

class ReplyController extends BaseController {
    private ReplyService $replyService;

    public function __construct() {
        parent::__construct();
        $this->replyService = new ReplyService();
    }

    /**
     * Kirim Balasan Percakapan / Catatan Internal IT (Mendukung AJAX & Regular POST)
     */
    public function store(): void {
        $currentUser = $this->getCurrentUser();
        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        $isInternal = isset($_POST['is_internal']) && $_POST['is_internal'] == '1';

        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                  || isset($_POST['ajax']) 
                  || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        if ($ticketId <= 0 || empty($message)) {
            if ($isAjax) {
                http_response_code(400);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'message' => 'Isi pesan balasan tidak boleh kosong.']);
                exit;
            }
            $this->setFlash('danger', 'Isi pesan balasan tidak boleh kosong.');
            $this->redirect("index.php?page=show_ticket&id={$ticketId}");
        }

        try {
            $replyId = $this->replyService->createReply($ticketId, $message, $isInternal, $_FILES['attachment'] ?? null, $currentUser);

            if ($isAjax) {
                $db = \App\Config\Database::getConnection();
                $stmt = $db->prepare("SELECT * FROM ticket_replies WHERE id = ?");
                $stmt->execute([$replyId]);
                $reply = $stmt->fetch();

                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status' => 'success',
                    'message' => $isInternal ? 'Catatan internal IT berhasil disimpan.' : 'Balasan berhasil dikirim.',
                    'reply' => [
                        'id' => $reply['id'],
                        'sender_name' => $reply['sender_name'] ?: $currentUser['name'],
                        'user_role' => $currentUser['role'],
                        'message' => $reply['message'],
                        'attachment' => $reply['attachment'] ?: null,
                        'is_internal' => (int)$reply['is_internal'],
                        'time_ago' => function_exists('timeAgo') ? timeAgo($reply['created_at']) : 'Baru saja',
                        'formatted_date' => function_exists('formatDateIndo') ? formatDateIndo($reply['created_at']) : date('d M Y, H:i', strtotime($reply['created_at'])),
                        'created_at' => $reply['created_at']
                    ]
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $this->setFlash('success', $isInternal ? 'Catatan internal IT berhasil disimpan.' : 'Balasan berhasil dikirim.');
        } catch (Exception $e) {
            if ($isAjax) {
                http_response_code(400);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                exit;
            }
            $this->setFlash('danger', $e->getMessage());
        }

        $this->redirect("index.php?page=show_ticket&id={$ticketId}");
    }
}
