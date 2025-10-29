<?php
require 'config.php';
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

switch($action) {

    // --- FETCH MEMBERS ---
    case "fetchMembers":
        $table = $_GET['table'] ?? 'zsmMEMBERS';
        if(!in_array($table, ['zsmMEMBERS','registeredVOTERS','voterSTATUS'])) {
            echo json_encode(['error'=>'Invalid table']); exit;
        }
        $sql = "SELECT * FROM $table"; 
        $stmt = $conn->query($sql);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    // --- FETCH TABLE / VOTER STATUS ---
    case "fetchTable":
    case "fetch_voter_status":
        $table = $_GET['table'] ?? 'registeredVOTERS';
        if($table === 'registeredVOTERS') {
            $stmt = $conn->query("SELECT voterID, RegistrationDate, Firstname, Surname, City, AcademicYear FROM registeredVOTERS");
        } elseif($table === 'voterSTATUS') {
            $sql = "SELECT r.voterID, r.Firstname, r.Surname, r.City, r.AcademicYear, s.Status, s.Reason
                    FROM registeredVOTERS r
                    LEFT JOIN voterSTATUS s ON r.voterID = s.voterID";
            $stmt = $conn->query($sql);
        } else {
            echo json_encode(['error'=>'Invalid table']); exit;
        }
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    // --- UPDATE SINGLE STATUS ---
    case 'updateStatus':
        if($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'Invalid request']); exit; }
        $voterID = $_POST['voterID'] ?? null;
        $status = $_POST['status'] ?? null;
        $reason = $_POST['reason'] ?? '';
        if(!$voterID || !$status){ echo json_encode(['error'=>'Missing params']); exit; }

        $stmt = $conn->prepare("SELECT * FROM voterSTATUS WHERE voterID=?");
        $stmt->execute([$voterID]);
        if($stmt->rowCount() > 0){
            $stmt = $conn->prepare("UPDATE voterSTATUS SET Status=?, Reason=? WHERE voterID=?");
            $stmt->execute([$status,$reason,$voterID]);
        } else {
            $stmt = $conn->prepare("INSERT INTO voterSTATUS (voterID, Status, Reason) VALUES (?,?,?)");
            $stmt->execute([$voterID,$status,$reason]);
        }
        echo json_encode(['success'=>true]);
        break;

    // --- BULK UPDATE ---
    case 'bulkUpdate':
        if($_SERVER['REQUEST_METHOD'] !== 'POST'){ echo json_encode(['error'=>'Invalid request']); exit; }
        $ids = $_POST['ids'] ?? [];
        $status = $_POST['status'] ?? null;
        $reason = $_POST['reason'] ?? '';
        if(!$ids || !$status){ echo json_encode(['error'=>'Missing params']); exit; }

        $stmt = $conn->prepare("INSERT INTO voterSTATUS (voterID, Status, Reason) VALUES (?,?,?)
                                ON DUPLICATE KEY UPDATE Status=VALUES(Status), Reason=VALUES(Reason)");
        foreach($ids as $voterID){
            $stmt->execute([$voterID, $status, $reason]);
        }
        echo json_encode(['success'=>true]);
        break;

    // --- VERIFY VOTER ---
    case 'verifyVoter':
        if($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'Invalid request']); exit; }
        $voterID = $_POST['voterID'] ?? null;
        if(!$voterID){ echo json_encode(['error'=>'Missing voterID']); exit; }

        $stmt = $conn->prepare("SELECT * FROM registeredVOTERS WHERE voterID=?");
        $stmt->execute([$voterID]);
        $voter = $stmt->fetch(PDO::FETCH_ASSOC);
        if(!$voter){
            echo json_encode(['voterID'=>$voterID,'status'=>'Ineligible','reason'=>'Voter not found']);
            exit;
        }

        $stmt = $conn->prepare("SELECT * FROM zsmMEMBERS WHERE (Firstname=? OR Email=?) AND City=?");
        $stmt->execute([$voter['Firstname'],$voter['Email'],$voter['City']]);
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $matched = 0; $totalCriteria = 4; $reason = '';
        foreach($members as $member){
            $matches = 0;
            if($member['Firstname'] === $voter['Firstname']) $matches++;
            if($member['Surname'] === $voter['Surname']) $matches++;
            if($member['City'] === $voter['City']) $matches++;
            if($member['Email'] === $voter['Email']) $matches++;

            if($matches > $matched) $matched = $matches;

            if($member['EDU_Status'] === 'Graduate' || $member['Membership'] === 'None'){
                $matched = 0; $reason = 'Graduate or membership inactive'; break;
            }
        }

        if($matched / $totalCriteria >= 0.75){ $status='Eligible'; }
        elseif($matched >=2){ $status='Pending'; $reason='Needs manual review'; }
        else{ $status='Ineligible'; $reason='Student not found or insufficient match'; }

        $stmt = $conn->prepare("SELECT * FROM voterSTATUS WHERE voterID=?");
        $stmt->execute([$voterID]);
        if($stmt->rowCount() > 0){
            $stmt = $conn->prepare("UPDATE voterSTATUS SET Status=?, Reason=? WHERE voterID=?");
            $stmt->execute([$status,$reason,$voterID]);
        } else {
            $stmt = $conn->prepare("INSERT INTO voterSTATUS (voterID, Status, Reason) VALUES (?,?,?)");
            $stmt->execute([$voterID,$status,$reason]);
        }

        if($status==='Pending'){
            $stmt = $conn->prepare("SELECT * FROM pendingVERIFICATION WHERE voterID=?");
            $stmt->execute([$voterID]);
            if($stmt->rowCount() === 0){
                $stmt = $conn->prepare("INSERT INTO pendingVERIFICATION 
                    (voterID, Firstname, Surname, Email, City, AcademicYear, Status, Reason) 
                    VALUES (?,?,?,?,?,?,?,?)");
                $stmt->execute([
                    $voterID,
                    $voter['Firstname'],
                    $voter['Surname'],
                    $voter['Email'],
                    $voter['City'],
                    $voter['AcademicYear'],
                    'Pending',
                    $reason
                ]);
            }
        }

        echo json_encode(['voterID'=>$voterID,'status'=>$status,'reason'=>$reason]);
        break;

    // --- FETCH PENDING ---
    case 'fetchPending':
        $stmt = $conn->query("SELECT * FROM pendingVERIFICATION ORDER BY pvID ASC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    // --- FINALIZE PENDING ---
    case 'finalizePending':
        if($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'Invalid request']); exit; }
        $voterID = $_POST['voterID'] ?? null;
        $status = $_POST['status'] ?? null;
        $reason = $_POST['reason'] ?? '';
        if(!$voterID || !$status){ echo json_encode(['error'=>'Missing params']); exit; }

        $stmt = $conn->prepare("UPDATE voterSTATUS SET Status=?, Reason=? WHERE voterID=?");
        $stmt->execute([$status,$reason,$voterID]);

        $stmt = $conn->prepare("DELETE FROM pendingVERIFICATION WHERE voterID=?");
        $stmt->execute([$voterID]);

        echo json_encode(['voterID'=>$voterID,'status'=>$status]);
        break;

    // --- UPLOAD CSV ---
case 'uploadCSV':
    if(!isset($_FILES['csvFile'])){ echo json_encode(['message'=>'No file uploaded']); exit; }
    $file = $_FILES['csvFile']['tmp_name'];
    if(!file_exists($file)){ echo json_encode(['message'=>'Temporary file not found']); exit; }

    $handle = fopen($file, 'r');
    if(!$handle){ echo json_encode(['message'=>'Failed to open file']); exit; }

    $header = fgetcsv($handle);
    $count = 0; 
    $errors = [];

    while(($row = fgetcsv($handle)) !== false){
        if(count($row) < 6){ 
            $errors[] = 'Skipped row: insufficient columns'; 
            continue; 
        }

        $RegistrationDate = trim($row[1]) ?: date('Y-m-d');
        $Firstname = trim($row[2]);
        $Surname = trim($row[3]);
        $Email = trim($row[4]);
        $City = trim($row[5]);
        $AcademicYear = trim($row[6]) ?? date('Y');

        try{
            // Insert into registeredVOTERS
            $stmt = $conn->prepare("INSERT INTO registeredVOTERS 
                (RegistrationDate, Firstname, Surname, Email, City, AcademicYear) 
                VALUES (?,?,?,?,?,?)");
            $stmt->execute([$RegistrationDate, $Firstname, $Surname, $Email, $City, $AcademicYear]);
            $voterID = $conn->lastInsertId(); // Get inserted voter ID
            $count++;

            // --- AUTOMATIC VERIFICATION ---
            $stmtMember = $conn->prepare("SELECT * FROM zsmMEMBERS WHERE (Firstname=? OR Email=?) AND City=?");
            $stmtMember->execute([$Firstname, $Email, $City]);
            $members = $stmtMember->fetchAll(PDO::FETCH_ASSOC);

            $bestMatch = 0;
            $reason = '';

            foreach($members as $member){
                $matches = 0;
                if($member['Firstname'] === $Firstname) $matches++;
                if($member['Surname'] === $Surname) $matches++;
                if($member['City'] === $City) $matches++;
                if($member['Email'] === $Email) $matches++;

                if($matches > $bestMatch) $bestMatch = $matches;

                if($member['EDU_Status'] === 'Graduate' || $member['Membership'] === 'None'){
                    $bestMatch = 2; 
                    $reason = 'Graduate or membership inactive';
                    break;
                }
            }

            if($bestMatch / 4 >= 0.75){
                $status = 'Eligible';
            } elseif($bestMatch >= 2) {
                $status = 'Pending';
                $reason = $reason ?: 'Needs manual review';
            } else {
                $status = 'Ineligible';
                $reason = $reason ?: 'Student not found or insufficient match';
            }

            // Insert/Update voterSTATUS
            $stmtStatus = $conn->prepare("SELECT * FROM voterSTATUS WHERE voterID=?");
            $stmtStatus->execute([$voterID]);
            if($stmtStatus->rowCount() > 0){
                $stmtStatus = $conn->prepare("UPDATE voterSTATUS SET Status=?, Reason=? WHERE voterID=?");
                $stmtStatus->execute([$status, $reason, $voterID]);
            } else {
                $stmtStatus = $conn->prepare("INSERT INTO voterSTATUS (voterID, Status, Reason) VALUES (?,?,?)");
                $stmtStatus->execute([$voterID, $status, $reason]);
            }

        } catch(PDOException $e){
            $errors[] = "Row failed: ".implode(',', $row)." | Error: ".$e->getMessage();
        }
    }

    fclose($handle);

    $message = "CSV uploaded: $count records.";
    if(!empty($errors)) $message .= " Errors: ".implode(' | ', $errors);
    echo json_encode(['message'=>$message]);
    break;


    default:
        echo json_encode(['error'=>'Unknown action']);
        break;
}
?>
