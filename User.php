<?php
class User {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function findUserByUsername($username) {
        $this->db->query('SELECT * FROM user_id WHERE Username = :username');
        $this->db->bind(':username', $username);
        return $this->db->single();
    }
    
    public function login($username, $password) {
        $row = $this->findUserByUsername($username);
        
        if (!$row) return false;
        
        // Compare passwords directly (no encryption)
        if ($password === $row->Password) {
            return $row;
        }
        
        return false;
    }
    
    public function getUserType($user_id) {
        $this->db->query('SELECT type_name FROM user_types 
                         JOIN user_id ON user_types.type_id = user_id.user_type_id 
                         WHERE User_ID = :user_id');
        $this->db->bind(':user_id', $user_id);
        $row = $this->db->single();
        return $row ? $row->type_name : false;
    }
    
    public function getOfficerDetails($user_id) {
        $this->db->query('SELECT * FROM officer_id WHERE User_ID = :user_id');
        $this->db->bind(':user_id', $user_id);
        return $this->db->single();
    }
}
?>