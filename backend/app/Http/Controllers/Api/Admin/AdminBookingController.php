<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Repositories\BookingRepository;
use App\Services\BookingService;
use Illuminate\Http\Request;

class AdminBookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService,
        protected BookingRepository $bookingRepository
    ) {}

    /**
     * Get all bookings from all users (Admin only)
     */
    public function index()
    {
        $bookings = $this->bookingRepository->getAllWithUsers();

        $conflictReport = $this->bookingService->generateConflictReport($bookings);

        return response()->json([
            'bookings' => BookingResource::collection($bookings),
            'conflicts' => $conflictReport,
        ]);
    }

    /**
     * Get conflict report for all bookings (Admin only)
     */
    public function conflictReport()
    {
        $bookings = $this->bookingRepository->getAll();
        $report = $this->bookingService->generateConflictReport($bookings);

        // Add summary counts
        $summary = [
            'total_bookings' => $bookings->count(),
            'overlapping_count' => count($report['overlapping']),
            'exact_conflicts_count' => count($report['conflicts']),
            'gaps_count' => count($report['gaps']),
        ];

        return response()->json([
            'summary' => $summary,
            'overlapping' => $report['overlapping'],
            'conflicts' => $report['conflicts'],
            'gaps' => $report['gaps'],
        ]);
    }
}
