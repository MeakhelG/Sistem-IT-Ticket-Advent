<?php
namespace App\Controllers;

use App\Services\TicketService;

class ApiController extends BaseController {
    private TicketService $ticketService;

    public function __construct() {
        parent::__construct();
        $this->ticketService = new TicketService();
    }

    /**
     * JSON Endpoint: Pencarian Real-Time Tiket
     */
    public function search(): void {
        header('Content-Type: application/json; charset=utf-8');
        $currentUser = $this->getCurrentUser();

        $filters = [
            'q'        => $_GET['q'] ?? '',
            'status'   => $_GET['status'] ?? '',
            'priority' => $_GET['priority'] ?? '',
            'category' => $_GET['category'] ?? '',
            'filter'   => $_GET['filter'] ?? 'all'
        ];

        $page = max(1, (int)($_GET['p'] ?? 1));
        $result = $this->ticketService->getFilteredTickets($filters, $currentUser, $page, 15);

        echo json_encode([
            'status' => 'success',
            'data'   => $result['data'],
            'total'  => $result['total'],
            'page'   => $result['current_page']
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * JSON Endpoint: Polling Angka Badge Real-Time
     */
    public function badgeCounts(): void {
        header('Content-Type: application/json; charset=utf-8');
        $currentUser = $this->getCurrentUser();
        $counts = $this->ticketService->getBadgeCounts($currentUser['id'], $currentUser['role']);

        echo json_encode([
            'status' => 'success',
            'counts' => $counts
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
