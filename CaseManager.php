<?php
class CaseManager {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function getOfficerCases($officer_id) {
        $this->db->query('SELECT k.*, cs.status_name 
                         FROM kcases_id k
                         JOIN case_statuses cs ON k.status_id = cs.status_id
                         JOIN assignment_id a ON k.Cases_ID = a.Cases_ID
                         WHERE a.Officer_ID = :officer_id
                         ORDER BY k.Created_At DESC');
        $this->db->bind(':officer_id', $officer_id);
        return $this->db->resultSet();
    }
    
    public function getCaseById($case_id, $officer_id) {
        $this->db->query('SELECT k.*, cs.status_name 
                         FROM kcases_id k
                         JOIN case_statuses cs ON k.status_id = cs.status_id
                         JOIN assignment_id a ON k.Cases_ID = a.Cases_ID
                         WHERE k.Cases_ID = :case_id AND a.Officer_ID = :officer_id');
        $this->db->bind(':case_id', $case_id);
        $this->db->bind(':officer_id', $officer_id);
        return $this->db->single();
    }
    
    public function addCase($data) {
        $this->db->query('INSERT INTO kcases_id (Crime_Type, User_ID, status_id) 
                         VALUES (:crime_type, :user_id, 1)');
        $this->db->bind(':crime_type', $data['crime_type']);
        $this->db->bind(':user_id', $data['user_id']);
        
        if($this->db->execute()) {
            $case_id = $this->db->lastInsertId();
            
            $this->db->query('INSERT INTO assignment_id (Officer_ID, Cases_ID, Role) 
                             VALUES (:officer_id, :case_id, "Lead Investigator")');
            $this->db->bind(':officer_id', $data['officer_id']);
            $this->db->bind(':case_id', $case_id);
            
            return $this->db->execute() ? $case_id : false;
        }
        return false;
    }
    
    public function updateCase($data) {
        $this->db->query('UPDATE kcases_id 
                         SET Crime_Type = :crime_type, 
                             status_id = :status_id 
                         WHERE Cases_ID = :case_id');
        $this->db->bind(':crime_type', $data['crime_type']);
        $this->db->bind(':status_id', $data['status_id']);
        $this->db->bind(':case_id', $data['case_id']);
        return $this->db->execute();
    }
    
    public function getCaseStatuses() {
        $this->db->query('SELECT * FROM case_statuses ORDER BY status_id');
        return $this->db->resultSet();
    }
    
    public function getCaseEvidence($case_id) {
        $this->db->query('SELECT * FROM evidence_id WHERE Cases_ID = :case_id');
        $this->db->bind(':case_id', $case_id);
        return $this->db->resultSet();
    }
    
    public function getCaseSuspects($case_id) {
        $this->db->query('SELECT * FROM xsuspect_id WHERE Cases_ID = :case_id');
        $this->db->bind(':case_id', $case_id);
        return $this->db->resultSet();
    }
    
    public function getCaseVictims($case_id) {
        $this->db->query('SELECT * FROM victim WHERE Cases_ID = :case_id');
        $this->db->bind(':case_id', $case_id);
        return $this->db->resultSet();
    }
}
?>