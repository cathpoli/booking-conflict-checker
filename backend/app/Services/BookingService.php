<?php

namespace App\Services;

use App\Repositories\BookingRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * BookingService
 * 
 * Handles business logic for bookings, including conflict detection,
 * overlap analysis, and scheduling gap calculations.
 */
class BookingService
{
    /**
     * Constructor with dependency injection.
     * 
     * @param BookingRepository $repository Repository for booking data access
     */
    public function __construct(protected BookingRepository $repository) {}

    /**
     * Generate a comprehensive conflict report for a set of bookings.
     * 
     * This method analyzes bookings and returns a detailed report containing:
     * - Overlapping bookings (partial time conflicts)
     * - Exact conflicts (duplicate bookings)
     * - Gaps between consecutive bookings
     * 
     * @param Collection|array $bookings Collection of Booking models to analyze
     * @return array Associative array with 'overlapping', 'conflicts', and 'gaps' keys
     */
    public function generateConflictReport($bookings): array
    {
        return [
            'overlapping' => $this->findOverlaps($bookings),
            'conflicts' => $this->findExactConflicts($bookings),
            'gaps' => $this->calculateGaps($bookings),
        ];
    }
    
    /**
     * Find overlapping bookings where time ranges intersect.
     * 
     * Logic: Two bookings overlap if:
     * - End time of booking A > Start time of booking B, AND
     * - Start time of booking A < End time of booking B
     * 
     * This detects partial overlaps where bookings share some time period.
     * 
     * @param Collection|array $bookings Collection of bookings to check
     * @return array Array of overlapping booking pairs
     */
    private function findOverlaps($bookings): array
    {
        $overlaps = [];
        $bookingsArray = is_array($bookings) ? $bookings : $bookings->all();
        
        for ($i = 0; $i < count($bookingsArray); $i++) {
            for ($j = $i + 1; $j < count($bookingsArray); $j++) {
                $booking1 = $bookingsArray[$i];
                $booking2 = $bookingsArray[$j];
                
                // Check if they're on the same date
                if ($booking1->date !== $booking2->date) {
                    continue;
                }
                
                // Check for time overlap (not exact match)
                $start1 = $booking1->start_time;
                $end1 = $booking1->end_time;
                $start2 = $booking2->start_time;
                $end2 = $booking2->end_time;
                
                // Overlapping condition: (start1 < end2) AND (end1 > start2)
                // Exclude exact matches (handled in findExactConflicts)
                if ($start1 < $end2 && $end1 > $start2 && !($start1 === $start2 && $end1 === $end2)) {
                    $overlaps[] = [
                        'booking1' => [
                            'id' => $booking1->id,
                            'user_id' => $booking1->user_id,
                            'user_name' => $booking1->user->name ?? 'Unknown',
                            'date' => $booking1->date,
                            'start_time' => $booking1->start_time,
                            'end_time' => $booking1->end_time,
                        ],
                        'booking2' => [
                            'id' => $booking2->id,
                            'user_id' => $booking2->user_id,
                            'user_name' => $booking2->user->name ?? 'Unknown',
                            'date' => $booking2->date,
                            'start_time' => $booking2->start_time,
                            'end_time' => $booking2->end_time,
                        ],
                        'overlap_start' => max($start1, $start2),
                        'overlap_end' => min($end1, $end2),
                    ];
                }
            }
        }
        
        return $overlaps;
    }

    /**
     * Find exact conflicts (duplicate bookings with identical time slots).
     * 
     * This identifies bookings that have the exact same start and end times,
     * which represents a scheduling conflict requiring resolution.
     * 
     * @param Collection|array $bookings Collection of bookings to check
     * @return array Array of conflicting booking groups
     */
    private function findExactConflicts($bookings): array
    {
        $conflicts = [];
        $bookingsArray = is_array($bookings) ? $bookings : $bookings->all();
        
        for ($i = 0; $i < count($bookingsArray); $i++) {
            for ($j = $i + 1; $j < count($bookingsArray); $j++) {
                $booking1 = $bookingsArray[$i];
                $booking2 = $bookingsArray[$j];
                
                // Check if they have identical date and time
                if ($booking1->date === $booking2->date &&
                    $booking1->start_time === $booking2->start_time &&
                    $booking1->end_time === $booking2->end_time) {
                    
                    $conflicts[] = [
                        'date' => $booking1->date,
                        'start_time' => $booking1->start_time,
                        'end_time' => $booking1->end_time,
                        'bookings' => [
                            [
                                'id' => $booking1->id,
                                'user_id' => $booking1->user_id,
                                'user_name' => $booking1->user->name ?? 'Unknown',
                                'date' => $booking1->date,
                                'start_time' => $booking1->start_time,
                                'end_time' => $booking1->end_time,
                            ],
                            [
                                'id' => $booking2->id,
                                'user_id' => $booking2->user_id,
                                'user_name' => $booking2->user->name ?? 'Unknown',
                                'date' => $booking2->date,
                                'start_time' => $booking2->start_time,
                                'end_time' => $booking2->end_time,
                            ],
                        ],
                    ];
                }
            }
        }
        
        return $conflicts;
    }

    /**
     * Calculate time gaps between consecutive bookings.
     * 
     * This method identifies free time periods between scheduled bookings,
     * useful for finding available time slots or optimizing schedules.
     * 
     * @param Collection|array $bookings Collection of bookings (should be sorted by time)
     * @return array Array of gap information with start, end, and duration
     */
    private function calculateGaps($bookings): array
    {
        $gaps = [];
        $bookingsArray = is_array($bookings) ? $bookings : $bookings->toArray();
        
        // Group bookings by date
        $byDate = [];
        foreach ($bookingsArray as $booking) {
            $date = $booking['date'];
            if (!isset($byDate[$date])) {
                $byDate[$date] = [];
            }
            $byDate[$date][] = $booking;
        }
        
        // Calculate gaps for each date
        foreach ($byDate as $date => $dateBookings) {
            // Sort by start time
            usort($dateBookings, function($a, $b) {
                return strcmp($a['start_time'], $b['start_time']);
            });
            
            // Find gaps between consecutive bookings
            for ($i = 0; $i < count($dateBookings) - 1; $i++) {
                $currentEnd = $dateBookings[$i]['end_time'];
                $nextStart = $dateBookings[$i + 1]['start_time'];
                
                // If there's a gap
                if ($currentEnd < $nextStart) {
                    $gaps[] = [
                        'date' => $date,
                        'gap_start' => $currentEnd,
                        'gap_end' => $nextStart,
                        'duration_minutes' => $this->calculateMinutesBetween($currentEnd, $nextStart),
                        'before_booking_id' => $dateBookings[$i]['id'],
                        'after_booking_id' => $dateBookings[$i + 1]['id'],
                    ];
                }
            }
        }
        
        return $gaps;
    }
    
    /**
     * Calculate the number of minutes between two time strings.
     * 
     * @param string $time1 Start time (HH:MM:SS or HH:MM)
     * @param string $time2 End time (HH:MM:SS or HH:MM)
     * @return int Number of minutes between the times
     */
    private function calculateMinutesBetween(string $time1, string $time2): int
    {
        $start = \DateTime::createFromFormat('H:i:s', $time1) ?: \DateTime::createFromFormat('H:i', $time1);
        $end = \DateTime::createFromFormat('H:i:s', $time2) ?: \DateTime::createFromFormat('H:i', $time2);
        
        if (!$start || !$end) {
            return 0;
        }
        
        $diff = $start->diff($end);
        return ($diff->h * 60) + $diff->i;
    }
}