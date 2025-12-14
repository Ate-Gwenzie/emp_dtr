<?php
require_once __DIR__ . '/database.php';

class Analytics {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }
    public function countLateArrivalsLast30Days() {
        $conn = $this->db->getConnection();
        $query = "
            SELECT COUNT(a.id)
            FROM attendance a
            JOIN employee e ON a.employee_id = e.empid
            WHERE a.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            AND TIME(a.time_in_am) > TIME(e.timein_am)
            AND TIME(a.time_in_am) != '00:00:00'";

        $stmt = $conn->prepare($query);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function countEarlyExitsLast30Days() {
        $conn = $this->db->getConnection();
        $query = "
            SELECT COUNT(a.id)
            FROM attendance a
            JOIN employee e ON a.employee_id = e.empid
            WHERE a.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            AND TIME_TO_SEC(TIME(a.time_out_pm)) < (TIME_TO_SEC(TIME(e.timeout_pm)) - 300)
            AND TIME(a.time_out_pm) != '00:00:00'";

        $stmt = $conn->prepare($query);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
    
    public function getHourlyActivityData() {
        $conn = $this->db->getConnection();
        
        $query_aggregate = "
            SELECT
                HOUR(time_field) as activity_hour,
                COUNT(*) as activity_count  /* FIX APPLIED HERE */
            FROM (
                SELECT time_in_am as time_field FROM attendance WHERE time_in_am != '00:00:00' AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                UNION ALL
                SELECT time_out_am FROM attendance WHERE time_out_am != '00:00:00' AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                UNION ALL
                SELECT time_in_pm FROM attendance WHERE time_in_pm != '00:00:00' AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                UNION ALL
                SELECT time_out_pm FROM attendance WHERE time_out_pm != '00:00:00' AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ) as combined_times
            GROUP BY activity_hour
            ORDER BY activity_hour ASC";

        $stmt = $conn->prepare($query_aggregate);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $hourly_data = array_fill(0, 24, 0);
        foreach ($results as $row) {
            $hour = (int) $row['activity_hour'];
            if ($hour >= 0 && $hour < 24) {
                $hourly_data[$hour] = (int) $row['activity_count'];
            }
        }
        
        return $hourly_data;
    }
}
