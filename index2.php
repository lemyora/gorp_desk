<?php
session_start();
define('USDA_API_KEY', 'FbwSg34EpJpnwjv4Qh6wiIouTFwqsCiMvfymTjMH'); // <-- Your USDA API key

// Initialize session storage arrays if not set
if (!isset($_SESSION['todos'])) $_SESSION['todos'] = [];
if (!isset($_SESSION['transactions'])) $_SESSION['transactions'] = [];
if (!isset($_SESSION['loanReminders'])) $_SESSION['loanReminders'] = [];
if (!isset($_SESSION['learned'])) $_SESSION['learned'] = '';
if (!isset($_SESSION['mistakes'])) $_SESSION['mistakes'] = '';
if (!isset($_SESSION['improvement'])) $_SESSION['improvement'] = '';
if (!isset($_SESSION['goal'])) $_SESSION['goal'] = '';
if (!isset($_SESSION['skinRoutine'])) $_SESSION['skinRoutine'] = '';
if (!isset($_SESSION['health'])) $_SESSION['health'] = [];
if (!isset($_SESSION['journals'])) $_SESSION['journals'] = []; // <-- Journal section
if (!isset($_SESSION['learned']) || !is_array($_SESSION['learned'])) $_SESSION['learned'] = [];
if (!isset($_SESSION['mistakes']) || !is_array($_SESSION['mistakes'])) $_SESSION['mistakes'] = [];
if (!isset($_SESSION['improvement']) || !is_array($_SESSION['improvement'])) $_SESSION['improvement'] = [];
if (!isset($_SESSION['goal']) || !is_array($_SESSION['goal'])) $_SESSION['goal'] = [];


// --- Date filter logic ---
if (isset($_POST['selected_date'])) {
    $_SESSION['selected_date'] = $_POST['selected_date'];
}
$selected_date = $_SESSION['selected_date'] ?? date('Y-m-d');

// Navigation
$nav = $_GET['nav'] ?? 'todo';

// --- Handle form submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_index'])) {
        $index = (int)$_POST['delete_index'];
        if ($nav === 'todo' && isset($_SESSION['todos'][$index])) {
            unset($_SESSION['todos'][$index]);
            $_SESSION['todos'] = array_values($_SESSION['todos']);
        }
        if ($nav === 'expense' && isset($_SESSION['transactions'][$index])) {
            unset($_SESSION['transactions'][$index]);
            $_SESSION['transactions'] = array_values($_SESSION['transactions']);
        }
    }
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        // To-Do List Actions
        if ($action === 'add_todo') {
            $task = trim($_POST['task'] ?? '');
            $date = $selected_date;
            if ($task !== '' && $date !== '') {
                $_SESSION['todos'][] = ['task' => $task, 'date' => $date, 'done' => false];
            }
        } elseif ($action === 'toggle_done') {
            $index = intval($_POST['index']);
            if (isset($_SESSION['todos'][$index])) {
                $_SESSION['todos'][$index]['done'] = !$_SESSION['todos'][$index]['done'];
            }
        } elseif ($action === 'delete_todo') {
            $index = intval($_POST['index']);
            if (isset($_SESSION['todos'][$index])) {
                array_splice($_SESSION['todos'], $index, 1);
            }
        }
        // Expense Tracker Actions
        elseif ($action === 'add_transaction') {
            $type = $_POST['type'] ?? '';
            $amount = floatval($_POST['amount'] ?? 0);
            $purpose = trim($_POST['purpose'] ?? '');
            $date = $selected_date;
            if (($type === 'income' || $type === 'expense') && $amount > 0 && $date !== '') {
                $_SESSION['transactions'][] = [
                    'type' => $type,
                    'amount' => $amount,
                    'purpose' => $purpose,
                    'date' => $date,
                ];
            }
        }
        // Loan Reminders
        elseif ($action === 'add_loan_reminder') {
            $loanDate = $_POST['loanDate'] ?? '';
            $loanDescription = trim($_POST['loanDescription'] ?? '');
            if ($loanDate !== '' && $loanDescription !== '') {
                $_SESSION['loanReminders'][] = ['date' => $loanDate, 'description' => $loanDescription];
            }
        } elseif ($action === 'delete_loan_reminder') {
            $index = intval($_POST['index']);
            if (isset($_SESSION['loanReminders'][$index])) {
                array_splice($_SESSION['loanReminders'], $index, 1);
            }
        }
        // Save Notes (learned, mistakes, improvement, goal, skinRoutine)
        elseif ($action === 'save_notes') {
            $_SESSION['learned'] = $_POST['learned'] ?? '';
            $_SESSION['mistakes'] = $_POST['mistakes'] ?? '';
            $_SESSION['improvement'] = $_POST['improvement'] ?? '';
            $_SESSION['goal'] = $_POST['goal'] ?? '';
            $_SESSION['skinRoutine'] = $_POST['skinRoutine'] ?? '';
        }
        // Health Tracking (USDA)
        elseif ($action === 'add_health_usda') {
            $food_name = trim($_POST['food_name'] ?? '');
            $fdcId = intval($_POST['fdcId'] ?? 0);
            $grams = floatval($_POST['grams'] ?? 0);
            $today = $selected_date;
            if ($fdcId && $grams > 0) {
                $url = "https://api.nal.usda.gov/fdc/v1/food/$fdcId?api_key=" . USDA_API_KEY;
                $json = @file_get_contents($url);
                if ($json) {
                    $data = json_decode($json, true);
                    $nutrients = [
                        'food' => $food_name,
                        'grams' => $grams,
                        'date' => $today,
                        'Protein' => 0,
                        'Energy' => 0,
                        'Fat' => 0,
                        'Carbohydrate' => 0,
                        'Sugars' => 0,
                        'Fiber' => 0,
                    ];
                    foreach ($data['foodNutrients'] as $n) {
                        $name = strtolower($n['nutrientName']);
                        $value = floatval($n['value'] ?? 0);
                        $perGram = $value / 100.0 * $grams;
                        if (strpos($name, 'protein') !== false) $nutrients['Protein'] = round($perGram, 2);
                        if (strpos($name, 'energy') !== false && strpos($name, 'kj') === false) $nutrients['Energy'] = round($perGram, 2);
                        if (strpos($name, 'fat') !== false && strpos($name, 'saturated') === false) $nutrients['Fat'] = round($perGram, 2);
                        if (strpos($name, 'carbohydrate') !== false) $nutrients['Carbohydrate'] = round($perGram, 2);
                        if (strpos($name, 'sugar') !== false) $nutrients['Sugars'] = round($perGram, 2);
                        if (strpos($name, 'fiber') !== false) $nutrients['Fiber'] = round($perGram, 2);
                    }
                    $_SESSION['health'][] = $nutrients;
                }
            }
        }
        // Clear health data
        elseif ($action === 'clear_health') {
            $_SESSION['health'] = [];
        }
        // Journal Section
        elseif ($action === 'save_journal') {
            $journal_text = trim($_POST['journal_text'] ?? '');
            $learned = trim($_POST['learned'] ?? '');
            $mistakes = trim($_POST['mistakes'] ?? '');
            $improvement = trim($_POST['improvement'] ?? '');
            $goal = trim($_POST['goal'] ?? '');
            if ($journal_text !== '') {
                $_SESSION['journals'][$selected_date] = $journal_text;
                $_SESSION['learned'][$selected_date] = $learned;
                $_SESSION['mistakes'][$selected_date] = $mistakes;
                $_SESSION['improvement'][$selected_date] = $improvement;
                $_SESSION['goal'][$selected_date] = $goal;
            }
        }

    }
    // Date filter form should not redirect
    if (!isset($_POST['selected_date'])) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?nav=$nav");
        exit;
    }
}

// --- Filter data by selected date ---
$filtered_todos = array_filter($_SESSION['todos'], fn($t) => $t['date'] === $selected_date);
$filtered_transactions = array_filter($_SESSION['transactions'], fn($t) => $t['date'] === $selected_date);
$filtered_health = array_filter($_SESSION['health'], fn($h) => $h['date'] === $selected_date);

// Calculate totals for filtered transactions (for the selected date)
$incomeTotal = 0;
$expenseTotal = 0;
foreach ($filtered_transactions as $t) {
    if ($t['type'] === 'income') $incomeTotal += $t['amount'];
    elseif ($t['type'] === 'expense') $expenseTotal += $t['amount'];
}

// --- Calculate running balance up to and including selected date ---
function getBalanceUpToDate($date) {
    $balance = 0;
    $transactions = $_SESSION['transactions'];
    usort($transactions, function($a, $b) {
        if ($a['date'] === $b['date']) return 0;
        return ($a['date'] < $b['date']) ? -1 : 1;
    });
    foreach ($transactions as $t) {
        if ($t['date'] <= $date) {
            if ($t['type'] === 'income') {
                $balance += $t['amount'];
            } elseif ($t['type'] === 'expense') {
                $balance -= $t['amount'];
            }
        }
    }
    return $balance;
}
$balanceAsOfSelectedDate = getBalanceUpToDate($selected_date);
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Disciplina - Daily Life Tracker</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body {
  min-height: 100vh;
  background: linear-gradient(135deg, #181c24 0%, #232b3a 100%);
  color: #e2e8f0;
}
.navbar {
  background: rgba(20, 24, 34, 0.98)!important;
  border-bottom: 1px solid #232b3a;
  box-shadow: 0 2px 16px rgba(0,0,0,0.22);
}
.card {
  background: rgba(24,28,36,0.92);
  border-radius: 16px;
  border: 1px solid #232b3a;
  box-shadow: 0 2px 24px rgba(30,64,175,0.08);
  margin-bottom: 1.5rem;
}
.card-header {
  background: linear-gradient(90deg, #232b3a 0%, #1e293b 100%);
  color: #60a5fa;
  font-weight: bold;
  font-size: 1.1em;
  border-bottom: 1px solid #232b3a;
}
.btn, .form-control, .form-select, textarea {
  border-radius: 8px !important;
}
.form-control, .form-select, textarea {
  background: #232b3a !important;
  color: #e2e8f0 !important;
  border: 1px solid #334155;
}
.form-control:focus, .form-select:focus, textarea:focus {
  border-color: #60a5fa;
  box-shadow: 0 0 0 2px #60a5fa33;
}
.list-group-item {
  background: transparent;
  color: #e2e8f0;
  border: 1px solid #334155;
}
.list-group-item.done {
  text-decoration: line-through;
  color: #64748b !important;
  background: #1e293b !important;
}
.badge {
  background: linear-gradient(90deg,#60a5fa 0%,#818cf8 100%);
  color: #fff;
}
.btn-glass {
  background: rgba(30,41,59,0.7);
  color: #60a5fa;
  border: 1px solid #334155;
  transition: all 0.18s;
}
.btn-glass:hover {
  background: #60a5fa;
  color: #181c24;
}
.suggestions {
  background: #232b3a;
  border: 1px solid #334155;
  border-radius: 8px;
  position: absolute;
  z-index: 10;
  width: 100%;
  max-height: 160px;
  overflow-y: auto;
}
.suggestion-item {
  padding: 8px 12px;
  cursor: pointer;
}
.suggestion-item:hover {
  background: #334155;
}
::-webkit-scrollbar { width: 8px; background: #232b3a; }
::-webkit-scrollbar-thumb { background: #334155; border-radius: 6px; }
@media (max-width: 700px) {
  .container {padding: 8px;}
  h1 {font-size:1.2em;}
}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark shadow-sm mb-4">
  <div class="container">
    <a class="navbar-brand" href="?nav=todo"><i class="bi bi-lightning-charge"></i> <span style="font-weight:bold;">Disciplina</span></a>
    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
      <li class="nav-item"><a class="nav-link<?php if ($nav==='todo') echo ' active'; ?>" href="?nav=todo"><i class="bi bi-list-task"></i> Daily Routine</a></li>
      <li class="nav-item"><a class="nav-link<?php if ($nav==='expense') echo ' active'; ?>" href="?nav=expense"><i class="bi bi-cash-coin"></i> Expense Tracker</a></li>
      <li class="nav-item"><a class="nav-link<?php if ($nav==='health') echo ' active'; ?>" href="?nav=health"><i class="bi bi-heart-pulse"></i> Health & Nutrition</a></li>
      <li class="nav-item"><a class="nav-link<?php if ($nav==='journal') echo ' active'; ?>" href="?nav=journal"><i class="bi bi-journal-richtext"></i> Journal</a></li>
    </ul>
    <form method="post" class="d-flex align-items-center ms-auto" style="gap:8px;">
      <label for="selected_date" class="me-2 mb-0"><i class="bi bi-calendar-date"></i></label>
      <input type="date" class="form-control form-control-sm" id="selected_date" name="selected_date" value="<?php echo htmlspecialchars($selected_date); ?>" style="width: 140px; min-width: 110px;">
      <button class="btn btn-info btn-sm ms-1" type="submit" title="Apply date filter"><i class="bi bi-funnel"></i></button>
    </form>
  </div>
</nav>
<div class="container" style="max-width:950px;">

<?php if ($nav === 'todo'): ?>
  <div class="row">
    <div class="col-lg-7">
      <div class="card shadow">
        <div class="card-header"><i class="bi bi-calendar-check"></i> Add New Task</div>
        <div class="card-body">
          <form method="POST" class="row g-2">
            <input type="hidden" name="action" value="add_todo" />
            <div class="col-md-8">
              <input type="text" name="task" class="form-control" autocomplete="off" placeholder="Task (e.g. Morning Skincare, Pay EMI)" required />
            </div>
            <input type="hidden" name="date" value="<?php echo htmlspecialchars($selected_date); ?>">
            <div class="col-12 text-end">
              <button type="submit" class="btn btn-glass"><i class="bi bi-plus-circle"></i> Add Task</button>
            </div>
          </form>
        </div>
      </div>
      <div class="card shadow">
        <div class="card-header"><i class="bi bi-list-task"></i> To-Do List for <?php echo htmlspecialchars($selected_date); ?></div>
        <ul class="list-group list-group-flush">
          <?php if (count($filtered_todos) > 0): ?>
            <?php foreach ($filtered_todos as $idx => $todo): ?>
              <li class="list-group-item d-flex align-items-center<?php echo $todo['done'] ? ' done' : ''; ?>">
                <span class="flex-grow-1">
                  <strong><?php echo htmlspecialchars($todo['task']); ?></strong>
                  <span class="badge ms-2"><?php echo htmlspecialchars($todo['date']); ?></span>
                </span>
                <form method="POST" class="me-1">
                  <input type="hidden" name="action" value="toggle_done" />
                  <input type="hidden" name="index" value="<?php echo array_search($todo, $_SESSION['todos']); ?>" />
                  <button class="btn btn-glass btn-sm" type="submit" title="Mark as done/undo">
                    <?php echo $todo['done'] ? '<i class="bi bi-arrow-counterclockwise"></i>' : '<i class="bi bi-check2-circle"></i>'; ?>
                  </button>
                </form>
                <form method="POST">
                  <input type="hidden" name="action" value="delete_todo" />
                  <input type="hidden" name="index" value="<?php echo array_search($todo, $_SESSION['todos']); ?>" />
                  <button class="btn btn-glass btn-sm" type="submit" title="Delete"><i class="bi bi-trash"></i></button>
                </form>
              </li>
            <?php endforeach; ?>
          <?php else: ?>
            <li class="list-group-item text-muted">No tasks for this date.</li>
          <?php endif; ?>
        </ul>
      </div>
      </div>
    <div class="col-lg-5">
      <div class="card shadow">
        <div class="card-header"><i class="bi bi-exclamation-triangle"></i> Loan & Bill Reminders</div>
        <div class="card-body">
          <form method="POST" class="row g-2 mb-2">
            <input type="hidden" name="action" value="add_loan_reminder" />
            <div class="col-6">
              <input type="date" name="loanDate" class="form-control" required />
            </div>
            <div class="col-6">
              <input type="text" name="loanDescription" autocomplete="off" class="form-control" placeholder="Loan/bill/EMI" required />
            </div>
            <div class="col-12 text-end">
              <button type="submit" class="btn btn-glass"><i class="bi bi-plus-circle"></i> Add Reminder</button>
            </div>
          </form>
          <?php if (count($_SESSION['loanReminders']) > 0): ?>
            <ul class="list-group">
              <?php foreach ($_SESSION['loanReminders'] as $idx => $loan): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span><strong><?php echo htmlspecialchars($loan['date']); ?></strong> - <?php echo htmlspecialchars($loan['description']); ?></span>
                  <form method="POST" class="mb-0">
                    <input type="hidden" name="action" value="delete_loan_reminder" />
                    <input type="hidden" name="index" value="<?php echo $idx; ?>" />
                    <button class="btn btn-glass btn-sm" type="submit"><i class="bi bi-trash"></i></button>
                  </form>
                </li>
              <?php endforeach; ?>
            </ul>
            
          <?php else: ?>
            <div class="text-muted small">No reminders yet.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
<?php elseif ($nav === 'journal'): ?>
<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card shadow">
      <div class="card-header"><i class="bi bi-journal-text"></i> Daily Notes & Routines</div>
      <div class="card-body">
        <?php if (isset($_SESSION['journals'][$selected_date])): ?>
          <div class="alert alert-info">
            <strong>Your Journal Entry:</strong>
            <div style="white-space: pre-line; margin-top: 8px;"><?php echo nl2br(htmlspecialchars($_SESSION['journals'][$selected_date])); ?></div>
          </div>
          <div>
            <a class="btn btn-link p-0" data-bs-toggle="collapse" href="#detailsCollapse" role="button" aria-expanded="false" aria-controls="detailsCollapse">
              <span id="detailsToggleText">Details &gt;</span>
            </a>
            <div class="collapse mt-3" id="detailsCollapse">
              <div class="mb-2"><span class="fw-bold">Today, you have learned:</span>
                <div style="white-space: pre-line;"><?php echo nl2br(htmlspecialchars($_SESSION['learned'][$selected_date] ?? '')); ?></div>
              </div>
              <div class="mb-2"><span class="fw-bold">The mistakes you made:</span>
                <div style="white-space: pre-line;"><?php echo nl2br(htmlspecialchars($_SESSION['mistakes'][$selected_date] ?? '')); ?></div>
              </div>
              <div class="mb-2"><span class="fw-bold">Improve yourself by:</span>
                <div style="white-space: pre-line;"><?php echo nl2br(htmlspecialchars($_SESSION['improvement'][$selected_date] ?? '')); ?></div>
              </div>
              <div class="mb-2"><span class="fw-bold">Have you achieved your goal?</span>
                <div style="white-space: pre-line;"><?php echo nl2br(htmlspecialchars($_SESSION['goal'][$selected_date] ?? '')); ?></div>
              </div>
            </div>
          </div>
          <script>
            // Optional: Toggle text between "Details >" and "Details v"
            document.addEventListener('DOMContentLoaded', function() {
              var collapse = document.getElementById('detailsCollapse');
              var toggleText = document.getElementById('detailsToggleText');
              collapse.addEventListener('show.bs.collapse', function () {
                toggleText.innerHTML = 'Details &#x25BC;';
              });
              collapse.addEventListener('hide.bs.collapse', function () {
                toggleText.innerHTML = 'Details &gt;';
              });
            });
          </script>
        <?php else: ?>
          <form method="POST">
            <input type="hidden" name="action" value="save_journal" />
            <div class="mb-2">
              <label class="form-label">What you learned today:</label>
              <textarea name="learned" class="form-control" rows="2" placeholder="Write your journal for today..." required>></textarea>
            </div>
            <div class="mb-2">
              <label class="form-label">What mistakes you made today:</label>
              <textarea name="mistakes" class="form-control" rows="2" placeholder="Write your journal for today..." required>></textarea>
            </div>
            <div class="mb-2">
              <label class="form-label">How you improved today:</label>
              <textarea name="improvement" class="form-control" rows="2" placeholder="Write your journal for today..." required>></textarea>
            </div>
            <div class="mb-2">
              <label class="form-label">Your goal for tomorrow:</label>
              <textarea name="goal" class="form-control" rows="2" placeholder="Write your journal for today..." required>></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label">Your journal:</label>
              <textarea name="journal_text" class="form-control" rows="8" placeholder="Write your journal for today..." required></textarea>
            </div>
            <div class="text-end">
              <button type="submit" class="btn btn-glass"><i class="bi bi-save"></i> Save Notes & Routine</button>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php elseif ($nav === 'expense'): ?>
  <div class="row">
    <div class="col-lg-7">
      <div class="card shadow">
        <div class="card-header"><i class="bi bi-cash-coin"></i> Add Transaction</div>
        <div class="card-body">
          <form method="POST" class="row g-2">
            <input type="hidden" name="action" value="add_transaction" />
            <input type="hidden" name="transactionDate" value="<?php echo htmlspecialchars($selected_date); ?>">
            <div class="col-4">
              <select name="type" class="form-select" required>
                <option value="income">Income</option>
                <option value="expense">Expense</option>
              </select>
            </div>
            <div class="col-4">
              <input type="number" step="0.01" name="amount" autocomplete="off" class="form-control" placeholder="Amount" required />
            </div>
            <div class="col-4">
              <input type="text" name="purpose" autocomplete="off" class="form-control" placeholder="Purpose (optional)" />
            </div>
            <div class="col-12 text-end">
              <button type="submit" class="btn btn-glass"><i class="bi bi-plus-circle"></i> Add</button>
            </div>
          </form>
        </div>
      </div>
      <div class="card shadow">
        <div class="card-header"><i class="bi bi-bar-chart"></i> Summary for <?php echo htmlspecialchars($selected_date); ?></div>
        <div class="card-body">
          <div class="row mb-2">
            <div class="col-4"><span class="fw-bold text-info">Total Income:</span><br>₹<?php echo number_format($incomeTotal, 2); ?></div>
            <div class="col-4"><span class="fw-bold text-danger">Total Expenses:</span><br>₹<?php echo number_format($expenseTotal, 2); ?></div>
            <div class="col-4"><span class="fw-bold text-success">Balance:</span><br>₹<?php echo number_format($balanceAsOfSelectedDate, 2); ?></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="card shadow">
        <div class="card-header"><i class="bi bi-clock-history"></i> Transactions for <?php echo htmlspecialchars($selected_date); ?></div>
        <ul class="list-group list-group-flush">
          <?php if (count($filtered_transactions) > 0): ?>
            <?php foreach ($filtered_transactions as $idx => $t): ?>
              <li class="list-group-item<?php echo $t['type'] === 'income' ? ' text-info' : ' text-danger'; ?>">
                <strong><?php echo ucfirst($t['type']); ?></strong> - ₹<?php echo number_format($t['amount'], 2); ?>
                <?php if ($t['purpose'] !== ''): ?>
                  <span class="text-muted small">(<?php echo htmlspecialchars($t['purpose']); ?>)</span>
                <?php endif; ?>
                <span class="badge ms-2"><?php echo htmlspecialchars($t['date']); ?></span>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="delete_index" value="<?php echo array_search($t, $_SESSION['transactions']); ?>">
                  <button class="btn btn-glass btn-sm ms-2" type="submit" title="Delete"><i class="bi bi-trash"></i></button>
                </form>
              </li>
            <?php endforeach; ?>
          <?php else: ?>
            <li class="list-group-item text-muted">No transactions for this date.</li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
<?php elseif ($nav === 'health'): ?>
<div class="row">
  <div class="col-lg-7">
    <div class="card shadow">
      <div class="card-header"><i class="bi bi-heart-pulse"></i> Health & Nutrition Intake</div>
      <div class="card-body">
        <form method="POST" class="row g-3 position-relative" autocomplete="off" id="healthForm">
          <input type="hidden" name="action" value="add_health_usda" />
          <input type="hidden" name="fdcId" id="fdcId" value="" />
          <div class="col-8 position-relative">
            <label class="form-label">Food Name</label>
            <input type="text" name="food_name" id="foodInput" class="form-control" placeholder="Type food name..." required autocomplete="off" />
            <div id="suggestions" class="suggestions d-none"></div>
          </div>
          <div class="col-4">
            <label class="form-label">Amount (grams)</label>
            <input type="number" name="grams" class="form-control" placeholder="e.g. 150" required min="1" />
          </div>
          <div class="col-12 text-end">
            <button type="submit" class="btn btn-info"><i class="bi bi-plus-circle"></i> Add Intake (<?php echo htmlspecialchars($selected_date); ?>)</button>
          </div>
        </form>
      </div>
    </div>
    <div class="card shadow">
      <div class="card-header"><i class="bi bi-graph-up"></i> Intake for <?php echo htmlspecialchars($selected_date); ?></div>
      <div class="card-body">
        <?php if (count($filtered_health)): ?>
        <table class="table table-dark table-striped table-bordered align-middle">
          <thead>
            <tr>
              <th>Date</th>
              <th>Food</th>
              <th>Grams</th>
              <th>Calories</th>
              <th>Protein</th>
              <th>Fat</th>
              <th>Carbs</th>
              <th>Sugar</th>
              <th>Fiber</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($filtered_health as $h): ?>
              <tr>
                <td><?php echo htmlspecialchars($h['date']); ?></td>
                <td><?php echo htmlspecialchars($h['food']); ?></td>
                <td><?php echo htmlspecialchars($h['grams']); ?></td>
                <td><?php echo $h['Energy'] ?? '-'; ?></td>
                <td><?php echo $h['Protein'] ?? '-'; ?>g</td>
                <td><?php echo $h['Fat'] ?? '-'; ?>g</td>
                <td><?php echo $h['Carbohydrate'] ?? '-'; ?>g</td>
                <td><?php echo $h['Sugars'] ?? '-'; ?>g</td>
                <td><?php echo $h['Fiber'] ?? '-'; ?>g</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <form method="post" class="mt-2 text-end">
          <input type="hidden" name="action" value="clear_health" />
          <button class="btn btn-danger btn-sm">Clear All</button>
        </form>
        <?php else: ?>
          <div class="text-muted small">No health data for this date.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<script>
const USDA_API_KEY = "<?php echo USDA_API_KEY; ?>";
const foodInput = document.getElementById('foodInput');
const suggestionsBox = document.getElementById('suggestions');
const fdcIdInput = document.getElementById('fdcId');
let timer = null;

foodInput.addEventListener('input', function() {
  const q = this.value.trim();
  fdcIdInput.value = '';
  if (timer) clearTimeout(timer);
  if (q.length < 2) {
    suggestionsBox.classList.add('d-none');
    return;
  }
  timer = setTimeout(() => {
    fetch(`https://api.nal.usda.gov/fdc/v1/foods/search?api_key=${USDA_API_KEY}&query=${encodeURIComponent(q)}&pageSize=8`)
      .then(res => res.json())
      .then(data => {
        suggestionsBox.innerHTML = '';
        if (data && data.foods && data.foods.length) {
          data.foods.forEach(food => {
            const div = document.createElement('div');
            div.className = 'suggestion-item';
            div.textContent = food.description;
            div.onclick = () => {
              foodInput.value = food.description;
              fdcIdInput.value = food.fdcId;
              suggestionsBox.classList.add('d-none');
            };
            suggestionsBox.appendChild(div);
          });
          suggestionsBox.classList.remove('d-none');
        } else {
          suggestionsBox.classList.add('d-none');
        }
      });
  }, 300);
});
document.addEventListener('click', (e) => {
  if (!foodInput.contains(e.target)) suggestionsBox.classList.add('d-none');
});
</script>
<?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
