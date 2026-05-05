<?php 

include __DIR__ . '/../../config/localdb.php';

// Generic function to run queries
function query($con, $sql, $params = []) {
    $stmt = $con->prepare($sql);
    if ($params) {
        $types = str_repeat('s', count($params)); // assuming all strings for simplicity
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result();
}

function getAllProcurements(mysqli $con) {
    $query = "SELECT * FROM procurements WHERE deleted_at IS NULL ORDER BY id DESC";
    $result = $con->query($query);

    $procurements = [];
    if($result){
        while($row = $result->fetch_assoc()){
            $procurements[] = $row;
        }
    }
    return $procurements;
}





?>