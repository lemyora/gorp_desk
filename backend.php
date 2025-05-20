<?php
session_start();
header('Content-Type: application/json');

require_once "config.php";

// Ensure session date is set
if (!isset($_SESSION['selected_date'])) {
    echo json_encode(["status" => "error", "message" => "Date not selected"]);
    exit;
}

// Validate GET parameters
if (!isset($_GET['type']) || !isset($_GET['action'])) {
    echo json_encode(["status" => "error", "message" => "Missing type or action"]);
    exit;
}

$type = $_GET['type'];
$action = $_GET['action'];
$date = $_SESSION['selected_date'];

// Main dispatcher
switch ($type) {
    case 'todo':
        handleTodo($action, $conn, $date);
        break;
    case 'expense':
        handleExpense($action, $conn, $date);
        break;
    case 'usda':
        handleUsda($action, $conn, $date);
        break;
    case 'loan':
        handleLoan($action, $conn, $date);
        break;
    case 'journal':
        handleJournal($action, $conn, $date);
        break;
    default:
        echo json_encode(["status" => "error", "message" => "Invalid type"]);
        break;
}

// === HANDLERS === //

function handleTodo($action, $conn, $date) {
    if ($action === 'insert') {
        $task = trim($_POST['task'] ?? '');
        if ($task === '') {
            echo json_encode(["status" => "error", "message" => "Empty task"]);
            return;
        }
        $stmt = $conn->prepare("INSERT INTO todo (task, done, date) VALUES (?, 0, ?)");
        $stmt->bind_param("ss", $task, $date);
        $stmt->execute();
        echo json_encode(["status" => "success"]);
    } elseif ($action === 'view') {
        $stmt = $conn->prepare("SELECT id, task, done FROM todo WHERE date = ?");
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $todo = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($todo);
    }
}

function handleExpense($action, $conn, $date) {
    if ($action === 'insert') {
        $name = trim($_POST['name'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        if ($name === '' || $amount <= 0) {
            echo json_encode(["status" => "error", "message" => "Invalid name or amount"]);
            return;
        }
        $stmt = $conn->prepare("INSERT INTO expenses (name, amount, date) VALUES (?, ?, ?)");
        $stmt->bind_param("sds", $name, $amount, $date);
        $stmt->execute();
        echo json_encode(["status" => "success"]);
    } elseif ($action === 'view') {
        $stmt = $conn->prepare("SELECT id, name, amount FROM expenses WHERE date = ?");
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $expenses = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($expenses);
    }
}

function handleUsda($action, $conn, $date) {
    if ($action === 'insert') {
        $food = trim($_POST['food'] ?? '');
        $calories = floatval($_POST['calories'] ?? 0);
        if ($food === '' || $calories <= 0) {
            echo json_encode(["status" => "error", "message" => "Invalid food or calories"]);
            return;
        }
        $stmt = $conn->prepare("INSERT INTO usda (food, calories, date) VALUES (?, ?, ?)");
        $stmt->bind_param("sds", $food, $calories, $date);
        $stmt->execute();
        echo json_encode(["status" => "success"]);
    } elseif ($action === 'view') {
        $stmt = $conn->prepare("SELECT id, food, calories FROM usda WHERE date = ?");
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $usda = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($usda);
    }
}

function handleLoan($action, $conn, $date) {
    if ($action === 'insert') {
        $title = trim($_POST['title'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $due_date = $_POST['due_date'] ?? '';
        if ($title === '' || $amount <= 0 || $due_date === '') {
            echo json_encode(["status" => "error", "message" => "Invalid loan data"]);
            return;
        }
        $stmt = $conn->prepare("INSERT INTO loans (title, amount, due_date, entry_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdss", $title, $amount, $due_date, $date);
        $stmt->execute();
        echo json_encode(["status" => "success"]);
    } elseif ($action === 'view') {
        $stmt = $conn->prepare("SELECT id, title, amount, due_date FROM loans WHERE entry_date = ?");
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $loans = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($loans);
    }
}

function handleJournal($action, $conn, $date) {
    if ($action === 'insert') {
        $content = trim($_POST['content'] ?? '');
        if ($content === '') {
            echo json_encode(["status" => "error", "message" => "Empty journal entry"]);
            return;
        }
        $stmt = $conn->prepare("INSERT INTO journal (content, date) VALUES (?, ?)");
        $stmt->bind_param("ss", $content, $date);
        $stmt->execute();
        echo json_encode(["status" => "success"]);
    } elseif ($action === 'view') {
        $stmt = $conn->prepare("SELECT id, content FROM journal WHERE date = ?");
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $journal = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($journal);
    }
}
