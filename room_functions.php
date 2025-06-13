<?php
// Set Malaysia timezone at the beginning
date_default_timezone_set('Asia/Kuala_Lumpur');

// Room reservation related functions

/**
 * Fetches all discussion rooms from the database
 * 
 * @param mysqli $conn Database connection
 * @param bool $includeHidden Whether to include hidden/inactive rooms (default: false)
 * @return array Array of rooms with their details
 */
function getRooms($conn, $includeHidden = false) {
    $rooms = [];
    
    $sql = "SELECT * FROM rooms";
    
    // Only include active rooms unless specifically requested
    if (!$includeHidden) {
        $sql .= " WHERE is_active = 1";
    }
    
    $sql .= " ORDER BY room_id";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $rooms[] = $row;
        }
    }
    
    return $rooms;
}

/**
 * Checks if a room is available for reservation on a specific date
 * 
 * @param mysqli $conn Database connection
 * @param int $roomId The room ID to check
 * @param string $date The date to check in YYYY-MM-DD format
 * @return bool True if available, false if not
 */
function isRoomAvailable($conn, $roomId, $date) {
    // Validate inputs
    if (!$roomId || !$date) {
        return false;
    }
    
    // Check existing reservations for this room and date
    $sql = "SELECT COUNT(*) as reservation_count 
           FROM reservation 
           WHERE room_id = ? AND date = ? AND status != 'cancelled'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $roomId, $date);
    
    if (!$stmt->execute()) {
        error_log("Database error checking room availability: " . $conn->error);
        return false;
    }
    
    $result = $stmt->get_result();
    
    if ($result && $row = $result->fetch_assoc()) {
        // If there are any reservations, the room is not available
        return ($row['reservation_count'] == 0);
    }
    
    return true;
}

/**
 * Creates a new room reservation
 * 
 * @param mysqli $conn Database connection
 * @param int $userId The user ID making the reservation
 * @param int $roomId The room ID being reserved
 * @param string $date The reservation date in YYYY-MM-DD format
 * @param int $slotId The timeslot ID selected (optional)
 * @return bool|string True on success, error message on failure
 */
function createReservation($conn, $userId, $roomId, $date, $slotId = null) {
    // Check if the room is available on this date and timeslot
    if ($slotId) {
        if (!isTimeslotAvailable($conn, $roomId, $date, $slotId)) {
            return "This room is not available at the selected timeslot. Please select another timeslot or room.";
        }
    } else {
        if (!isRoomAvailable($conn, $roomId, $date)) {
            return "This room is not available on the selected date. Please select another date or room.";
        }
    }
    
    // Create the reservation
    $sql = "INSERT INTO reservation (id, room_id, date, slot_id, status, created_at) 
            VALUES (?, ?, ?, ?, 'confirmed', NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iisi', $userId, $roomId, $date, $slotId);
    
    if ($stmt->execute()) {
        return true;
    } else {
        return "Database error: " . $conn->error;
    }
}

/**
 * Gets all reservations for a specific user
 * 
 * @param mysqli $conn Database connection
 * @param int $userId The user ID
 * @param int $limit Optional limit on number of results (default: 5)
 * @return array Array of reservations with room details
 */
function getUserReservations($conn, $userId, $limit = 5) {
    $reservations = [];
    $sql = "SELECT r.reservation_id, r.room_id, r.date, r.slot_id,
                   r.status, r.created_at, rm.room_name, rm.capacity, rm.features,
                   ts.start_time, ts.end_time, ts.display_text
            FROM reservation r
            JOIN rooms rm ON r.room_id = rm.room_id
            LEFT JOIN timeslots ts ON r.slot_id = ts.slot_id
            WHERE r.id = ?
            ORDER BY r.created_at DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $userId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $reservations[] = $row;
        }
    }
    
    return $reservations;
}

/**
 * Cancels a reservation
 * 
 * @param mysqli $conn Database connection
 * @param int $reservationId The reservation ID to cancel
 * @param int $userId The user ID (for security check)
 * @return bool|string True on success, error message on failure
 */
function cancelReservation($conn, $reservationId, $userId) {
    // First check if the reservation belongs to this user
    $sql = "SELECT id FROM reservation WHERE reservation_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $reservationId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return "Reservation not found.";
    }
    
    $row = $result->fetch_assoc();
    
    if ($row['id'] != $userId) {
        return "You are not authorized to cancel this reservation.";
    }
    
    // Update the reservation status
    $sql = "UPDATE reservation SET status = 'cancelled' WHERE reservation_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $reservationId);
    
    if ($stmt->execute()) {
        return true;
    } else {
        return "Database error: " . $conn->error;
    }
}

/**
 * Gets all available timeslots from the database
 * 
 * @param mysqli $conn Database connection
 * @return array Array of timeslots
 */
function getTimeslots($conn) {
    $timeslots = [];
    
    $sql = "SELECT * FROM timeslots WHERE is_active = 1 ORDER BY start_time";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $timeslots[] = $row;
        }
    }
    
    return $timeslots;
}

/**
 * Checks if a timeslot is available for booking, taking into account current time
 * 
 * @param mysqli $conn Database connection
 * @param int $roomId The room ID to check
 * @param string $date The date to check in YYYY-MM-DD format
 * @param int $slotId The timeslot ID to check
 * @return bool True if available, false if not
 */
function isTimeslotAvailable($conn, $roomId, $date, $slotId) {
    // Validate inputs
    if (!$roomId || !$date || !$slotId) {
        return false;
    }
    
    // Check if the date is today
    $today = date('Y-m-d');
    $now = new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur'));
    
    // Get the timeslot details to check the start time
    $sql = "SELECT start_time, end_time FROM timeslots WHERE slot_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $slotId);
    $stmt->execute();
    $result = $stmt->get_result();
    $timeslot = $result->fetch_assoc();
    
    // If the date is today, check if the start time plus one hour has already passed
    if ($date === $today && $timeslot) {
        $startTime = new DateTime($date . ' ' . $timeslot['start_time'], new DateTimeZone('Asia/Kuala_Lumpur'));
        $oneHourAfterStart = clone $startTime;
        $oneHourAfterStart->modify('+60 minutes');
        
        // If current time is after one hour past the start time, this slot is no longer available
        if ($now > $oneHourAfterStart) {
            return false;
        }
    }
    
    // Check existing reservations for this room, date and timeslot
    $sql = "SELECT COUNT(*) as reservation_count 
           FROM reservation 
           WHERE room_id = ? AND date = ? AND slot_id = ? AND status != 'cancelled'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('isi', $roomId, $date, $slotId);
    
    if (!$stmt->execute()) {
        error_log("Database error checking timeslot availability: " . $conn->error);
        return false;
    }
    
    $result = $stmt->get_result();
    
    if ($result && $row = $result->fetch_assoc()) {
        // If there are any reservations, the timeslot is not available
        return ($row['reservation_count'] == 0);
    }
    
    return true;
}

/**
 * Gets available timeslots for a specific room and date,
 * filtering out timeslots that started more than an hour ago if the date is today
 * 
 * @param mysqli $conn Database connection
 * @param int $roomId The room ID to check
 * @param string $date The date to check in YYYY-MM-DD format
 * @return array Array of available timeslots
 */
function getAvailableTimeslots($conn, $roomId, $date) {
    $availableTimeslots = [];
    
    // Get all active timeslots
    $sql = "SELECT * FROM timeslots WHERE is_active = 1 ORDER BY start_time";
    $result = $conn->query($sql);
    
    // Check if date is today - using Malaysia timezone
    $today = date('Y-m-d');
    $now = new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur'));
    $isToday = ($date === $today);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // For today, filter out timeslots that started more than an hour ago
            if ($isToday) {
                $startTime = new DateTime($date . ' ' . $row['start_time'], new DateTimeZone('Asia/Kuala_Lumpur'));
                $oneHourAfterStart = clone $startTime;
                $oneHourAfterStart->modify('+60 minutes');
                
                if ($now > $oneHourAfterStart) {
                    // Skip this timeslot as it started more than an hour ago
                    continue;
                }
            }
            
            // Check if this timeslot is available (no existing reservations)
            if (isTimeslotAvailable($conn, $roomId, $date, $row['slot_id'])) {
                $availableTimeslots[] = $row;
            }
        }
    }
    
    return $availableTimeslots;
}

/**
 * Updates reservation statuses based on current time
 * Changes confirmed reservations to completed if their end time has passed
 * 
 * @param mysqli $conn Database connection
 * @return int Number of reservations updated
 */
function updateReservationStatuses($conn) {
    // Get current date and time in Malaysia timezone
    $now = new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur'));
    $currentDate = $now->format('Y-m-d');
    $currentTime = $now->format('H:i:s');
    
    // Update reservations whose end time has passed to "completed"
    $sql = "UPDATE reservation r
            JOIN timeslots ts ON r.slot_id = ts.slot_id
            SET r.status = 'completed'
            WHERE r.status = 'confirmed'
            AND (r.date < ? OR (r.date = ? AND ts.end_time < ?))";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $currentDate, $currentDate, $currentTime);
    $stmt->execute();
    
    return $stmt->affected_rows;
}