<?php

namespace App\Repositories;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Collection;

/**
 * BookingRepository
 * 
 * Handles all database operations related to bookings.
 * This repository encapsulates the data access logic for the Booking model.
 */
class BookingRepository
{
    /**
     * Retrieve all bookings from the database.
     * Optionally ordered by date and time for admin view.
     * 
     * @return Collection Collection of all Booking models
     */
    public function getAll(): Collection
    {
        return Booking::orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();
    }

    /**
     * Retrieve all bookings with user relationship (for admin).
     * 
     * @return Collection Collection of all Booking models with user data
     */
    public function getAllWithUsers(): Collection
    {
        return Booking::with('user')
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();
    }

    /**
     * Retrieve all bookings for a specific user.
     * 
     * @param int $id The user ID to filter bookings by
     * @return Collection Collection of Booking models belonging to the user
     */
    public function getByUserId(int $id): Collection
    {
        return Booking::with('user')
            ->where('user_id', $id)
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();
    }

    /**
     * Create a new booking in the database.
     * 
     * @param array $data Booking data (e.g., user_id, start_time, end_time, resource_id)
     * @return Booking The newly created Booking model instance
     */
    public function create(array $data): Booking
    {
        return Booking::create($data);
    }

    /**
     * Find a booking by ID.
     * 
     * @param int $id The booking ID
     * @return Booking|null The Booking model instance or null if not found
     */
    public function find(int $id): ?Booking
    {
        return Booking::find($id);
    }

    /**
     * Update an existing booking.
     * 
     * @param Booking $booking The booking to update
     * @param array $data Updated booking data
     * @return bool True if update was successful
     */
    public function update(Booking $booking, array $data): bool
    {
        return $booking->update($data);
    }

    /**
     * Delete a booking.
     * 
     * @param Booking $booking The booking to delete
     * @return bool True if deletion was successful
     */
    public function delete(Booking $booking): bool
    {
        return $booking->delete();
    }
}