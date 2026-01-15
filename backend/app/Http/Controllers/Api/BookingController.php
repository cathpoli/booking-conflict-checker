<?php

namespace App\Http\Controllers\Api;

use App\Events\BookingCreated;
use App\Events\BookingUpdated;
use App\Events\BookingDeleted;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Repositories\BookingRepository;
use App\Services\BookingService;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService,
        protected BookingRepository $bookingRepository
    ) {}

    /**
     * Get all bookings for the authenticated user
     */
    public function index(Request $request)
    {
        $bookings = $this->bookingRepository->getByUserId($request->user()->id);

        $conflictReport = $this->bookingService->generateConflictReport($bookings);

        return response()->json([
            'bookings' => BookingResource::collection($bookings),
            'conflicts' => $conflictReport,
        ]);
    }

    /**
     * Store a new booking
     */
    public function store(StoreBookingRequest $request)
    {
        $booking = $this->bookingRepository->create([
            'user_id' => $request->user()->id,
            'date' => $request->validated('date'),
            'start_time' => $request->validated('start_time'),
            'end_time' => $request->validated('end_time'),
        ]);

        // Broadcast the event to all connected clients
        broadcast(new BookingCreated($booking))->toOthers();

        return response()->json([
            'message' => 'Booking created successfully',
            'booking' => new BookingResource($booking),
        ], 201);
    }

    /**
     * Update an existing booking
     */
    public function update(UpdateBookingRequest $request, Booking $booking)
    {
        // Ensure user owns this booking
        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->bookingRepository->update($booking, $request->validated());

        // Refresh the booking to get updated data
        $booking->refresh();

        // Broadcast the event to all connected clients
        broadcast(new BookingUpdated($booking))->toOthers();

        return response()->json([
            'message' => 'Booking updated successfully',
            'booking' => new BookingResource($booking),
        ]);
    }

    /**
     * Delete a booking
     */
    public function destroy(Request $request, Booking $booking)
    {
        // Ensure user owns this booking
        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $bookingId = $booking->id;
        $booking->delete();

        // Broadcast the event to all connected clients
        broadcast(new BookingDeleted($bookingId))->toOthers();

        return response()->json([
            'message' => 'Booking deleted successfully',
        ]);
    }

    /**
     * Get conflict report for the authenticated user's bookings
     */
    public function conflictReport(Request $request)
    {
        $bookings = Booking::where('user_id', $request->user()->id)
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        $report = $this->bookingService->generateConflictReport($bookings);

        return response()->json([
            'summary' => [
                'total_bookings' => $bookings->count(),
                'overlapping_count' => count($report['overlapping']),
                'exact_conflicts_count' => count($report['conflicts']),
                'gaps_count' => count($report['gaps']),
            ],
            'overlapping_bookings' => $report['overlapping'],
            'conflicting_bookings' => $report['conflicts'],
            'gaps_between_bookings' => $report['gaps'],
        ]);
    }
}

