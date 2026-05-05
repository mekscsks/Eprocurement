<?php
class Admin {
    private $con;

    public function __construct($db_connection) {
        $this->con = $db_connection;
    }

    // Create a new admin
    public function create($name, $username, $email, $password) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'admin';
        $status = 'active';

        $stmt = $this->con->prepare("INSERT INTO accounts (name, username, email, password, role, status) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("ssssss", $name, $username, $email, $hashed_password, $role, $status);
        return $stmt->execute();
    }

    // Fetch all admins
public function getAll() {
    $query = "SELECT * FROM accounts WHERE role='admin' ORDER BY created_at DESC";
    return mysqli_query($this->con, $query);
}

    // Toggle admin status
    public function toggleStatus($id, $action) {
        $status = ($action === 'activate') ? 'active' : 'inactive';
        $stmt = $this->con->prepare("UPDATE accounts SET status=? WHERE account_id=? AND role='admin'");
        $stmt->bind_param("si", $status, $id);
        return $stmt->execute();
    }

    // Fetch single admin by ID
    public function getById($id) {
        $stmt = $this->con->prepare("SELECT * FROM accounts WHERE account_id=? AND role='admin'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Update admin info
    public function update($id, $name, $username, $email, $password = null) {
        if ($password) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->con->prepare("UPDATE accounts SET name=?, username=?, email=?, password=? WHERE account_id=? AND role='admin'");
            $stmt->bind_param("ssssi", $name, $username, $email, $hashed_password, $id);
        } else {
            $stmt = $this->con->prepare("UPDATE accounts SET name=?, username=?, email=? WHERE account_id=? AND role='admin'");
            $stmt->bind_param("sssi", $name, $username, $email, $id);
        }
        return $stmt->execute();
    }
}
?>
