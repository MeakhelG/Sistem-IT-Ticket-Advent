<?php
namespace App\Controllers;

use App\Services\TicketService;
use App\Services\ReplyService;
use App\Models\Category;
use App\Models\Ticket;
use Exception;

class TicketController extends BaseController {
    private TicketService $ticketService;
    private ReplyService $replyService;
    private Ticket $ticketModel;
    private Category $categoryModel;

    public function __construct() {
        parent::__construct();
        $this->ticketService = new TicketService();
        $this->replyService = new ReplyService();
        $this->ticketModel = new Ticket();
        $this->categoryModel = new Category();
    }

    /**
     * Halaman Kotak Tiket Masuk (Mailbox Style)
     */
    public function index(): void {
        $currentUser = $this->getCurrentUser();
        $page = max(1, (int)($_GET['p'] ?? 1));

        $filters = [
            'filter'   => $_GET['filter'] ?? 'all',
            'category' => $_GET['category'] ?? '',
            'priority' => $_GET['priority'] ?? '',
            'status'   => $_GET['status'] ?? '',
            'q'        => $_GET['q'] ?? ''
        ];

        $ticketData = $this->ticketService->getFilteredTickets($filters, $currentUser, $page, 15);
        $counts = $this->ticketService->getBadgeCounts($currentUser['id'], $currentUser['role']);
        $categories = $this->categoryModel->all();

        $this->render('tickets/index', [
            'tickets'      => $ticketData['data'],
            'pagination'   => $ticketData,
            'counts'       => $counts,
            'filters'      => $filters,
            'categories'   => $categories,
            'currentUser'  => $currentUser,
            'pageTitle'    => 'Kotak Tiket Masuk'
        ]);
    }

    /**
     * Tampilan Formulir Pembuatan Tiket
     */
    public function create(): void {
        $currentUser = $this->getCurrentUser();
        $categories = $this->categoryModel->all();

        $this->render('tickets/create', [
            'categories'  => $categories,
            'currentUser' => $currentUser,
            'pageTitle'   => 'Buat Tiket Kendala Baru'
        ]);
    }

    /**
     * Detail Tiket, Percakapan, dan Audit Trail
     */
    public function show(): void {
        $currentUser = $this->getCurrentUser();
        $ticketId = (int)($_GET['id'] ?? 0);

        if ($ticketId <= 0) {
            $this->setFlash('danger', 'ID Tiket tidak valid.');
            $this->redirect('index.php?page=tickets');
        }

        $ticket = $this->ticketModel->findById($ticketId);

        if (!$ticket) {
            $this->setFlash('danger', 'Tiket tidak ditemukan.');
            $this->redirect('index.php?page=tickets');
        }

        // Staf hanya boleh melihat tiket miliknya sendiri
        if ($currentUser['role'] === 'staff' && (int)$ticket['user_id'] !== (int)$currentUser['id']) {
            $this->setFlash('danger', 'Anda tidak memiliki hak akses untuk membuka tiket ini.');
            $this->redirect('index.php?page=tickets');
        }

        $replies = $this->replyService->getReplies($ticketId, $currentUser);

        // Ambil riwayat audit log
        $db = \App\Config\Database::getConnection();
        $logStmt = $db->prepare("
            SELECT l.*, u.name as user_name 
            FROM ticket_logs l 
            JOIN users u ON l.user_id = u.id 
            WHERE l.ticket_id = ? 
            ORDER BY l.created_at DESC
        ");
        $logStmt->execute([$ticketId]);
        $logs = $logStmt->fetchAll();

        $this->render('tickets/show', [
            'ticket'      => $ticket,
            'replies'     => $replies,
            'logs'        => $logs,
            'currentUser' => $currentUser,
            'pageTitle'   => 'Tiket ' . $ticket['ticket_code'] . ' - ' . $ticket['title']
        ]);
    }

    /**
     * Simpan Tiket Baru (POST)
     */
    public function store(): void {
        $currentUser = $this->getCurrentUser();

        try {
            $ticketId = $this->ticketService->createTicket($_POST, $_FILES['attachment'] ?? null, $currentUser);
            $this->setFlash('success', 'Tiket baru berhasil diajukan! Tim IT akan segera menangani laporan Anda.');
            $this->redirect("index.php?page=show_ticket&id={$ticketId}");
        } catch (Exception $e) {
            $this->setFlash('danger', $e->getMessage());
            $this->redirect('index.php?page=create_ticket');
        }
    }

    /**
     * Ubah Status Tiket & Catat Solusi (POST, Tim IT)
     */
    public function updateStatus(): void {
        $currentUser = $this->getCurrentUser();
        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $status = $_POST['status'] ?? 'open';
        $solution = trim($_POST['solution'] ?? '');

        try {
            $this->ticketService->updateStatus($ticketId, $status, $solution, $currentUser);
            $this->setFlash('success', 'Status tiket dan solusi berhasil diperbarui.');
        } catch (Exception $e) {
            $this->setFlash('danger', $e->getMessage());
        }

        $this->redirect("index.php?page=show_ticket&id={$ticketId}");
    }

    /**
     * Berikan Rating & Ulasan Kepuasan (POST, Pelapor)
     */
    public function rate(): void {
        $currentUser = $this->getCurrentUser();
        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $rating = (int)($_POST['rating'] ?? 5);
        $feedback = trim($_POST['rating_feedback'] ?? '');

        try {
            $this->ticketService->rateTicket($ticketId, $rating, $feedback, $currentUser);
            $this->setFlash('success', 'Terima kasih atas penilaian kepuasan yang Anda berikan!');
        } catch (Exception $e) {
            $this->setFlash('danger', $e->getMessage());
        }

        $this->redirect("index.php?page=show_ticket&id={$ticketId}");
    }

    /**
     * Cetak Lembar Kerja Tiket
     */
    public function print(): void {
        $ticketId = (int)($_GET['id'] ?? 0);
        $ticket = $this->ticketModel->findById($ticketId);

        if (!$ticket) {
            $this->setFlash('danger', 'Tiket tidak ditemukan.');
            $this->redirect('index.php?page=tickets');
        }

        $this->render('tickets/print', [
            'ticket' => $ticket
        ]);
    }
}
